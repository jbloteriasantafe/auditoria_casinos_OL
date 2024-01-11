<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\ImportacionEstadoJugador;
use App\ImportacionEstadoJuego;
use App\Producido;
use App\ProducidoJugadores;
use App\ProducidoPoker;
use App\Beneficio;
use App\BeneficioMensual;
use App\BeneficioMensualPoker;
use App\Http\Controllers\ProducidoController;
use App\Http\Controllers\BeneficioMensualController;

class LectorCSVController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)){
      self::$instance = new LectorCSVController();
    }
    return self::$instance;
  }

  public function importarProducido($archivoCSV,$fecha,$plataforma,$moneda){
    $producidos_viejos = DB::table('producido')->where([
      ['id_plataforma','=',$plataforma],['fecha','=',$fecha],['id_tipo_moneda','=',$moneda]]
    )->get();

    $prodCont = ProducidoController::getInstancia();
    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $prodCont->eliminarProducido($prod->id_producido);
      }
    }

    $producido = new Producido;
    $producido->id_plataforma = $plataforma;
    $producido->fecha = $fecha;
    $producido->id_tipo_moneda = $moneda;
    $producido->apuesta_efectivo   = 0;$producido->apuesta_bono   = 0;$producido->apuesta   = 0;
    $producido->premio_efectivo    = 0;$producido->premio_bono    = 0;$producido->premio    = 0;
    $producido->beneficio_efectivo = 0;$producido->beneficio_bono = 0;$producido->beneficio = 0;
    $producido->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $producido->diferencia_montos  = 0;
    $producido->save();
    
    {//Limpio el ajuste de auditoria que habia en el beneficio
      $aaaammyy = explode('-',$fecha);
      $bms = BeneficioMensual::whereYear('fecha','=',$fecha[0])
      ->whereMonth('fecha','=',$fecha[1])
      ->where('id_tipo_moneda','=',$moneda)
      ->where('id_plataforma','=',$plataforma)
      ->get();
      foreach($bms as $bm){
        $bens = Beneficio::where([
          ['id_beneficio_mensual','=',$bm->id_beneficio_mensual],['fecha','=',$fecha]
        ])->get();
        foreach($bens as $b) {
          $bm->ajuste_auditoria -= $b->ajuste_auditoria;
          $bm->validado = 0;
          $bm->save();
          $b->ajuste_auditoria = 0;
          $b->save();
        }
      }
    }

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $path = $archivoCSV->getRealPath();

    //No se puede usar sentencia preparada LOAD DATA por lo que busque
    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE producido_temporal
                      FIELDS TERMINATED BY ','
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\r\\n'
                      IGNORE 1 LINES
                      (@DateReport,@GameCode,@GameCategory,@Players,@TotalWagerCash,@TotalWagerBonus,@TotalWager,@GrossRevenueCash,@GrossRevenueBonus,@GrossRevenue)
                       SET id_producido = '%d',
                       DateReport = @DateReport,
                       GameCode = @GameCode,
                       GameCategory = @GameCategory,
                       Players = @Players,
                       TotalWagerCash = REPLACE(@TotalWagerCash,',','.'),
                       TotalWagerBonus = REPLACE(@TotalWagerBonus,',','.'),
                       TotalWager = REPLACE(@TotalWager,',','.'),
                       GrossRevenueCash = REPLACE(@GrossRevenueCash,',','.'),
                       GrossRevenueBonus = REPLACE(@GrossRevenueBonus,',','.'),
                       GrossRevenue = REPLACE(@GrossRevenue,',','.')
                      ",$path,$producido->id_producido);

    $pdo->exec($query);

    $query = $pdo->prepare("INSERT INTO detalle_producido 
    (id_producido,
    cod_juego,
    categoria,
    jugadores,
    apuesta_efectivo  , apuesta_bono  , apuesta,
    premio_efectivo   , premio_bono   , premio,
    beneficio_efectivo, beneficio_bono, beneficio,
    diferencia_montos)
    SELECT 
    id_producido,
    GameCode as cod_juego,
    GameCategory as categoria,
    Players as jugadores,
    TotalWagerCash                         as apuesta_efectivo,
    TotalWagerBonus                        as apuesta_bono,
    (TotalWagerCash   + TotalWagerBonus)   as apuesta,
    (TotalWagerCash   - GrossRevenueCash)  as premio_efectivo,
    (TotalWagerBonus  - GrossRevenueBonus) as premio_bono,
    ((TotalWagerCash  + TotalWagerBonus) - (GrossRevenueCash + GrossRevenueBonus)) as premio,
    GrossRevenueCash                       as beneficio_efectivo,
    GrossRevenueBonus                      as beneficio_bono,
    (GrossRevenueCash + GrossRevenueBonus) as beneficio,
    0                                      as diferencia_montos
    FROM producido_temporal
    WHERE producido_temporal.id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    $query = $pdo->prepare("DELETE FROM producido_temporal WHERE id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    //Precalculo si tiene errores en los montos porque demoraba en la pantalla de Producidos
    $query = $pdo->prepare("UPDATE detalle_producido dp
    SET diferencia_montos = (   
                                ((apuesta_bono     + apuesta_efectivo  ) <> apuesta)
                            OR ((premio_bono      + premio_efectivo   ) <> premio)
                            OR ((beneficio_bono   + beneficio_efectivo) <> beneficio)
                            OR ((apuesta_efectivo - premio_efectivo   ) <> beneficio_efectivo)
                            OR ((apuesta_bono     - premio_bono       ) <> beneficio_bono)
    )
    WHERE id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    $query = $pdo->prepare("UPDATE producido p
    SET p.diferencia_montos = (
      SELECT BIT_OR(dp.diferencia_montos)
      FROM detalle_producido dp
      WHERE dp.id_producido = p.id_producido
    )
    WHERE id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    $query = $pdo->prepare("UPDATE 
    producido p,
    (
      SELECT 
      SUM(dp.apuesta_efectivo)   as apuesta_efectivo  , SUM(dp.apuesta_bono)   as apuesta_bono  , SUM(dp.apuesta)   as apuesta,
      SUM(dp.premio_efectivo)    as premio_efectivo   , SUM(dp.premio_bono)    as premio_bono   , SUM(dp.premio)    as premio,
      SUM(dp.beneficio_efectivo) as beneficio_efectivo, SUM(dp.beneficio_bono) as beneficio_bono, SUM(dp.beneficio) as beneficio
      FROM detalle_producido dp
      WHERE dp.id_producido = :id_producido1
      GROUP BY dp.id_producido
    ) total
    SET 
    p.apuesta_efectivo   = IFNULL(total.apuesta_efectivo,0)  , p.apuesta_bono   = IFNULL(total.apuesta_bono,0)  , p.apuesta   = IFNULL(total.apuesta,0),
    p.premio_efectivo    = IFNULL(total.premio_efectivo,0)   , p.premio_bono    = IFNULL(total.premio_bono,0)   , p.premio    = IFNULL(total.premio,0),
    p.beneficio_efectivo = IFNULL(total.beneficio_efectivo,0), p.beneficio_bono = IFNULL(total.beneficio_bono,0), p.beneficio = IFNULL(total.beneficio,0)
    WHERE p.id_producido = :id_producido2");

    $query->execute([":id_producido1" => $producido->id_producido,":id_producido2" => $producido->id_producido]);

    DB::connection()->enableQueryLog();

    $duplicados = DB::table('detalle_producido')->select('cod_juego',DB::raw('COUNT(distinct id_detalle_producido) as veces'))
    ->where('id_producido','=',$producido->id_producido)
    ->groupBy('cod_juego')
    ->havingRaw('COUNT(distinct id_detalle_producido) > 1')->get()->count();

    return [
      'cantidad_registros' => $producido->detalles()->count(),
      'juegos_multiples_reportes' => $duplicados
    ];
  }

  public function importarProducidoJugadores($archivoCSV,$fecha,$plataforma,$moneda){
    $producidos_viejos = DB::table('producido_jugadores')->where([
      ['id_plataforma','=',$plataforma],['fecha','=',$fecha],['id_tipo_moneda','=',$moneda]]
    )->get();
    
    $prodCont = ProducidoController::getInstancia();
    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $prodCont->eliminarProducidoJugadores($prod->id_producido_jugadores);
      }
    }
    
    $producido = new ProducidoJugadores;
    $producido->id_plataforma = $plataforma;
    $producido->fecha = $fecha;
    $producido->id_tipo_moneda = $moneda;
    $producido->apuesta_efectivo   = 0;$producido->apuesta_bono   = 0;$producido->apuesta   = 0;
    $producido->premio_efectivo    = 0;$producido->premio_bono    = 0;$producido->premio    = 0;
    $producido->beneficio_efectivo = 0;$producido->beneficio_bono = 0;$producido->beneficio = 0;
    $producido->diferencia_montos  = 0;
    $producido->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $producido->save();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();

    $path = $archivoCSV->getRealPath();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE producido_jugadores_temporal
                      FIELDS TERMINATED BY ','
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\r\\n'
                      IGNORE 1 LINES
                      (@DateReport,@PlayerID,@Games,@TotalWagerCash,@TotalWagerBonus,@TotalWager,@GrossRevenueCash,@GrossRevenueBonus,@GrossRevenue)
                       SET id_producido_jugadores = '%d',
                       DateReport = @DateReport,
                       PlayerID = @PlayerID,
                       Games = @Games,
                       TotalWagerCash = REPLACE(@TotalWagerCash,',','.'),
                       TotalWagerBonus = REPLACE(@TotalWagerBonus,',','.'),
                       TotalWager = REPLACE(@TotalWager,',','.'),
                       GrossRevenueCash = REPLACE(@GrossRevenueCash,',','.'),
                       GrossRevenueBonus = REPLACE(@GrossRevenueBonus,',','.'),
                       GrossRevenue = REPLACE(@GrossRevenue,',','.')
                      ",$path,$producido->id_producido_jugadores);

    $pdo->exec($query);

    $query = $pdo->prepare("INSERT INTO detalle_producido_jugadores
    (id_producido_jugadores,
    jugador,
    juegos,
    apuesta_efectivo  , apuesta_bono  , apuesta,
    premio_efectivo   , premio_bono   , premio,
    beneficio_efectivo, beneficio_bono, beneficio,
    diferencia_montos)
    SELECT 
    id_producido_jugadores,
    PlayerID as jugador,
    Games as juegos,
    TotalWagerCash                         as apuesta_efectivo,
    TotalWagerBonus                        as apuesta_bono,
    (TotalWagerCash   + TotalWagerBonus)   as apuesta,
    (TotalWagerCash   - GrossRevenueCash)  as premio_efectivo,
    (TotalWagerBonus  - GrossRevenueBonus) as premio_bono,
    ((TotalWagerCash  + TotalWagerBonus) - (GrossRevenueCash + GrossRevenueBonus)) as premio,
    GrossRevenueCash                       as beneficio_efectivo,
    GrossRevenueBonus                      as beneficio_bono,
    (GrossRevenueCash + GrossRevenueBonus) as beneficio,
    0                                      as diferencia_montos
    FROM producido_jugadores_temporal
    WHERE producido_jugadores_temporal.id_producido_jugadores = :id_producido_jugadores");
    $query->execute([":id_producido_jugadores" => $producido->id_producido_jugadores]);

    $query = $pdo->prepare("DELETE FROM producido_jugadores_temporal WHERE id_producido_jugadores = :id_producido_jugadores");
    $query->execute([":id_producido_jugadores" => $producido->id_producido_jugadores]);

    //Precalculo si tiene errores en los montos porque demoraba en la pantalla de Producidos
    $query = $pdo->prepare("UPDATE detalle_producido_jugadores dp
    SET diferencia_montos = (   
                                ((apuesta_bono     + apuesta_efectivo  ) <> apuesta)
                            OR ((premio_bono      + premio_efectivo   ) <> premio)
                            OR ((beneficio_bono   + beneficio_efectivo) <> beneficio)
                            OR ((apuesta_efectivo - premio_efectivo   ) <> beneficio_efectivo)
                            OR ((apuesta_bono     - premio_bono       ) <> beneficio_bono)
    )
    WHERE id_producido_jugadores = :id_producido_jugadores");

    $query->execute([":id_producido_jugadores" => $producido->id_producido_jugadores]);
    $query = $pdo->prepare("UPDATE producido_jugadores pj
    SET pj.diferencia_montos = (
      SELECT BIT_OR(dpj.diferencia_montos)
      FROM detalle_producido_jugadores dpj
      WHERE dpj.id_producido_jugadores = pj.id_producido_jugadores
    )
    WHERE id_producido_jugadores = :id_producido_jugadores");
    $query->execute([":id_producido_jugadores" => $producido->id_producido_jugadores]);


    $query = $pdo->prepare("UPDATE 
    producido_jugadores p,
    (
      SELECT 
      SUM(dp.apuesta_efectivo)   as apuesta_efectivo  , SUM(dp.apuesta_bono)   as apuesta_bono  , SUM(dp.apuesta)   as apuesta,
      SUM(dp.premio_efectivo)    as premio_efectivo   , SUM(dp.premio_bono)    as premio_bono   , SUM(dp.premio)    as premio,
      SUM(dp.beneficio_efectivo) as beneficio_efectivo, SUM(dp.beneficio_bono) as beneficio_bono, SUM(dp.beneficio) as beneficio
      FROM detalle_producido_jugadores dp
      WHERE dp.id_producido_jugadores = :id_producido_jugadores1
      GROUP BY dp.id_producido_jugadores
    ) total
    SET 
    p.apuesta_efectivo   = IFNULL(total.apuesta_efectivo,0)  , p.apuesta_bono   = IFNULL(total.apuesta_bono,0)  , p.apuesta   = IFNULL(total.apuesta,0),
    p.premio_efectivo    = IFNULL(total.premio_efectivo,0)   , p.premio_bono    = IFNULL(total.premio_bono,0)   , p.premio    = IFNULL(total.premio,0),
    p.beneficio_efectivo = IFNULL(total.beneficio_efectivo,0), p.beneficio_bono = IFNULL(total.beneficio_bono,0), p.beneficio = IFNULL(total.beneficio,0)
    WHERE p.id_producido_jugadores = :id_producido_jugadores2");

    $query->execute([":id_producido_jugadores1" => $producido->id_producido_jugadores,":id_producido_jugadores2" => $producido->id_producido_jugadores]);

    DB::connection()->enableQueryLog();

    $duplicados = DB::table('detalle_producido_jugadores')->select('jugador',DB::raw('COUNT(distinct id_detalle_producido_jugadores) as veces'))
    ->where('id_producido_jugadores','=',$producido->id_producido_jugadores)
    ->groupBy('jugador')
    ->havingRaw('COUNT(distinct id_detalle_producido_jugadores) > 1')->get()->count();

    return [
      'cantidad_registros' => $producido->detalles()->count(),
      'jugadores_multiples_reportes' => $duplicados
    ];
  }

  public function importarProducidoPoker($archivoCSV,$fecha,$plataforma,$moneda){
    $producidos_viejos = DB::table('producido_poker')->where([
      ['id_plataforma','=',$plataforma],['fecha','=',$fecha],['id_tipo_moneda','=',$moneda]]
    )->get();
    $prodCont = ProducidoController::getInstancia();
    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $prodCont->eliminarProducidoPoker($prod->id_producido_poker);
      }
    }

    $producido = new ProducidoPoker;
    $producido->id_plataforma = $plataforma;
    $producido->fecha = $fecha;
    $producido->id_tipo_moneda = $moneda;
    $producido->jugadores = 0;
    $producido->droop     = 0;
    $producido->utilidad  = 0;
    $producido->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $producido->save();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();

    $path = $archivoCSV->getRealPath();

    //No se puede usar sentencia preparada LOAD DATA por lo que busque
    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE producido_poker_temporal
                      FIELDS TERMINATED BY ','
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\r\\n'
                      IGNORE 1 LINES
                      (@DateReport,@GameCode,@GameCategory,@Players,@TotalWagerCash,@TotalWagerBonus,@TotalWager,@GrossRevenueCash,@GrossRevenueBonus,@GrossRevenue)
                       SET id_producido_poker = '%d',
                       DateReport = @DateReport,
                       GameCode = @GameCode,
                       GameCategory = @GameCategory,
                       Players = @Players,
                       TotalWagerCash = REPLACE(@TotalWagerCash,',','.'),
                       TotalWagerBonus = REPLACE(@TotalWagerBonus,',','.'),
                       TotalWager = REPLACE(@TotalWager,',','.'),
                       GrossRevenueCash = REPLACE(@GrossRevenueCash,',','.'),
                       GrossRevenueBonus = REPLACE(@GrossRevenueBonus,',','.'),
                       GrossRevenue = REPLACE(@GrossRevenue,',','.')
                      ",$path,$producido->id_producido_poker);

    $pdo->exec($query);

    $query = $pdo->prepare("INSERT INTO detalle_producido_poker 
    (id_producido_poker, cod_juego, categoria, jugadores, droop, utilidad)
    SELECT id_producido_poker,GameCode as cod_juego,GameCategory as categoria,Players as jugadores,TotalWager  as droop,GrossRevenue as utilidad
    FROM producido_poker_temporal
    WHERE producido_poker_temporal.id_producido_poker = :id_producido_poker");
    $query->execute([":id_producido_poker" => $producido->id_producido_poker]);

    $query = $pdo->prepare("DELETE FROM producido_poker_temporal WHERE id_producido_poker = :id_producido_poker");
    $query->execute([":id_producido_poker" => $producido->id_producido_poker]);

    $query = $pdo->prepare("UPDATE 
    producido_poker p,
    (
      SELECT SUM(dp.jugadores) as jugadores,SUM(dp.droop) as droop, SUM(dp.utilidad) as utilidad
      FROM detalle_producido_poker dp
      WHERE dp.id_producido_poker = :id_producido_poker1
      GROUP BY dp.id_producido_poker
    ) total
    SET p.jugadores = IFNULL(total.jugadores,0), p.droop = IFNULL(total.droop,0), p.utilidad = IFNULL(total.utilidad,0)
    WHERE p.id_producido_poker = :id_producido_poker2");

    $query->execute([":id_producido_poker1" => $producido->id_producido_poker,":id_producido_poker2" => $producido->id_producido_poker]);

    DB::connection()->enableQueryLog();

    $duplicados = DB::table('detalle_producido_poker')->select('cod_juego',DB::raw('COUNT(distinct id_detalle_producido_poker) as veces'))
    ->where('id_producido_poker','=',$producido->id_producido_poker)
    ->groupBy('cod_juego')
    ->havingRaw('COUNT(distinct id_detalle_producido_poker) > 1')->get()->count();

    return [
      'cantidad_registros' => $producido->detalles()->count(),
      'juegos_multiples_reportes' => $duplicados
    ];
  }

  public function importarBeneficio($archivoCSV,$fecha,$plataforma,$moneda){
    $fecha_aux = explode("-",$fecha);
    $ben_viejos = DB::table('beneficio_mensual')->where([
      ['id_plataforma','=',$plataforma],['id_tipo_moneda','=',$moneda]
    ])->whereRaw('YEAR(fecha) = ? and MONTH(fecha) = ?',[$fecha_aux[0],$fecha_aux[1]])->get();
    $benCont = BeneficioMensualController::getInstancia();
    if($ben_viejos != null){
      foreach($ben_viejos as $b){
        $benCont->eliminarBeneficioMensual($b->id_beneficio_mensual);
      }
    }

    //Hay un "analogo conceptual" Producido <-> BeneficioMensual, DetalleProducido <-> Beneficio
    $benMensual = new BeneficioMensual;
    $benMensual->id_plataforma = $plataforma;
    $benMensual->id_tipo_moneda = $moneda;
    $benMensual->fecha = $fecha_aux[0] . '-' . $fecha_aux[1] . '-01';
    $benMensual->depositos = 0;$benMensual->retiros   = 0;
    $benMensual->apuesta   = 0;$benMensual->premio    = 0;$benMensual->beneficio = 0;
    $benMensual->ajuste = 0;
    $benMensual->ajuste_auditoria = 0;
    $benMensual->puntos_club_jugadores = 0;
    $benMensual->validado = false;
    $benMensual->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $benMensual->save();
    
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $path = $archivoCSV->getRealPath();
    //DateReport es un quilombo porque no puedo usar REGEXP_REPLACE en el servidor de prueba porque es mysql 5.7

    //No se puede usar sentencia preparada LOAD DATA por lo que busque
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE beneficio_temporal
    FIELDS TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    (@Total,@DateReport,@Currency,@TotalRegistrations,@Verified,@TotalVerified,@Players,@TotalDeposits,@TotalWithdrawals,@TotalBonus,@TotalManualAdjustments,@TotalVPoints,@TotalWager,@TotalOut,@GrossRevenue,@lastupdated)
     SET id_beneficio_mensual = %d,
     Total                  = @Total,
     DateReport             = CONCAT(
      SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',-1),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',2),'/',-1),2,'00'),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',1),2,'00')
     ),
     Currency               = @Currency,
     TotalRegistrations     = @TotalRegistrations,
     Verified               = @Verified,
     TotalVerified          = @TotalVerified,
     Players                = @Players,
     TotalDeposits          = IFNULL(REPLACE(@TotalDeposits,',','.'),0.00),
     TotalWithdrawals       = IFNULL(REPLACE(@TotalWithdrawals,',','.'),0.00),
     TotalBonus             = REPLACE(@TotalBonus,',','.'),
     TotalManualAdjustments = REPLACE(@TotalManualAdjustments,',','.'),
     TotalVPoints           = REPLACE(@TotalVPoints,',','.'),
     TotalWager             = REPLACE(@TotalWager,',','.'),
     TotalOut               = REPLACE(@TotalOut,',','.'),
     GrossRevenue           = REPLACE(@GrossRevenue,',','.'),
     lastupdated            = @lastupdated",$path,$benMensual->id_beneficio_mensual);
    $pdo->exec($query);

    //La ultima comparacion en el WHERE es para ignorar la ultima linea
    $query = $pdo->prepare("INSERT INTO beneficio 
    (
      id_beneficio_mensual,
      fecha,
      jugadores,
      depositos,
      retiros,
      apuesta,
      premio,
      beneficio,
      ajuste,
      puntos_club_jugadores,
      observacion,
      ajuste_auditoria
    )
    SELECT
    id_beneficio_mensual, 
    DateReport       as fecha,
    Players          as jugadores,
    TotalDeposits    as depositos,
    TotalWithdrawals as retiros,
    TotalWager       as apuesta,
    TotalOut         as premio,
    GrossRevenue     as beneficio,
    TotalManualAdjustments as ajuste,
    TotalVPoints     as puntos_club_jugadores,
    ''               as observacion,
    0                as ajuste_auditoria
    FROM beneficio_temporal
    WHERE beneficio_temporal.id_beneficio_mensual = :id_beneficio_mensual AND beneficio_temporal.Total = ''");
    $query->execute([":id_beneficio_mensual" => $benMensual->id_beneficio_mensual]);

    $query = $pdo->prepare("DELETE FROM beneficio_temporal WHERE id_beneficio_mensual = :id_beneficio_mensual");
    $query->execute([":id_beneficio_mensual" => $benMensual->id_beneficio_mensual]);

    //Lo updateo por SQL porque son DECIMAL y no se si hay error de casteo si lo hago en PHP (pasa a float?)
    $query = $pdo->prepare("UPDATE beneficio_mensual bm,
    (
      SELECT SUM(b.depositos) as depositos, SUM(b.retiros)   as retiros,
             SUM(b.apuesta)   as apuesta  , SUM(b.premio)    as premio   , SUM(b.beneficio) as beneficio,
             SUM(b.ajuste)    as ajuste   , SUM(b.puntos_club_jugadores) as puntos_club_jugadores,
             0 as ajuste_auditoria
      FROM beneficio b
      WHERE b.id_beneficio_mensual = :id_beneficio_mensual1
      GROUP BY b.id_beneficio_mensual
    ) total
    SET bm.depositos = IFNULL(total.depositos,0),bm.retiros = IFNULL(total.retiros,0),
        bm.apuesta = IFNULL(total.apuesta,0),bm.premio = IFNULL(total.premio,0),
        bm.beneficio = IFNULL(total.beneficio,0),bm.ajuste  = IFNULL(total.ajuste,0),
        bm.puntos_club_jugadores = IFNULL(total.puntos_club_jugadores,0)
    WHERE bm.id_beneficio_mensual = :id_beneficio_mensual2");
    $query->execute([":id_beneficio_mensual1" => $benMensual->id_beneficio_mensual,":id_beneficio_mensual2" => $benMensual->id_beneficio_mensual]);

    //Actualizo la entidad
    $benMensual = BeneficioMensual::find($benMensual->id_beneficio_mensual);

    DB::connection()->enableQueryLog();

    $pdo = null;

    return [
      'bruto' => $benMensual->beneficio,
      'dias' => $benMensual->beneficios()->count()
    ]; 
  }

  public function importarBeneficioPoker($archivoCSV,$fecha,$plataforma,$moneda){
    $fecha_aux = explode("-",$fecha);
    $ben_viejos = DB::table('beneficio_mensual_poker')->where([
      ['id_plataforma','=',$plataforma],['id_tipo_moneda','=',$moneda]
    ])->whereRaw('YEAR(fecha) = ? and MONTH(fecha) = ?',[$fecha_aux[0],$fecha_aux[1]])->get();
    $benCont = BeneficioMensualController::getInstancia();
    if($ben_viejos != null){
      foreach($ben_viejos as $b){
        $benCont->eliminarBeneficioMensualPoker($b->id_beneficio_mensual_poker);
      }
    }

    $benMensual = new BeneficioMensualPoker;
    $benMensual->id_plataforma = $plataforma;
    $benMensual->id_tipo_moneda = $moneda;
    $benMensual->fecha = $fecha_aux[0] . '-' . $fecha_aux[1] . '-01';
    $benMensual->validado    = false;
    $benMensual->jugadores   = 0;
    $benMensual->mesas       = 0;
    $benMensual->buy         = 0;
    $benMensual->rebuy       = 0;
    $benMensual->total_buy   = 0;
    $benMensual->total_bonus = 0;
    $benMensual->cash_out    = 0;
    $benMensual->otros_pagos = 0;
    $benMensual->utilidad    = 0;
    $benMensual->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $benMensual->save();
    
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $path = $archivoCSV->getRealPath();
    //DateReport es un quilombo porque no puedo usar REGEXP_REPLACE en el servidor de prueba porque es mysql 5.7

    //No se puede usar sentencia preparada LOAD DATA por lo que busque
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE beneficio_poker_temporal
    FIELDS TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    (@Total,@DateReport,@Currency,@TotalPlayers,@TotalBuy,@ReBuy,@TotalBonus,@Rake,@DateTimeUpdated,@TotalCashout,@TotalTable,@Buy,@OtherPayments)
     SET id_beneficio_mensual_poker = %d,
     Total                  = @Total,
     DateReport             = CONCAT(
      SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',-1),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',2),'/',-1),2,'00'),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',1),2,'00')
     ),
     Currency        = @Currency,
     TotalPlayers    = @TotalPlayers,
     TotalBuy        = @TotalBuy,
     ReBuy           = @ReBuy,
     TotalBonus      = @TotalBonus,
     Rake            = @Rake,
     DateTimeUpdated = @DateTimeUpdated,
     TotalCashout    = @TotalCashout,
     TotalTable      = @TotalTable,
     Buy             = @Buy,
     OtherPayments   = @OtherPayments",$path,$benMensual->id_beneficio_mensual_poker);
    $pdo->exec($query);

    //La ultima comparacion en el WHERE es para ignorar la ultima linea
    $query = $pdo->prepare("INSERT INTO beneficio_poker 
    (
      id_beneficio_mensual_poker,
      fecha,
      jugadores,
      mesas,
      buy,
      rebuy,
      total_buy,
      cash_out,
      otros_pagos,
      total_bonus,
      utilidad,
      observacion
    )
    SELECT
    id_beneficio_mensual_poker, 
    DateReport    as fecha,
    TotalPlayers  as jugadores,
    TotalTable    as mesas,
    Buy           as buy,
    ReBuy         as rebuy,
    TotalBuy      as total_buy,
    TotalCashout  as cash_out,
    OtherPayments as otros_pagos,
    TotalBonus    as total_bonus,
    Rake          as utilidad,
    ''            as observacion
    FROM beneficio_poker_temporal
    WHERE beneficio_poker_temporal.id_beneficio_mensual_poker = :id_beneficio_mensual_poker AND beneficio_poker_temporal.Total = ''");
    $query->execute([":id_beneficio_mensual_poker" => $benMensual->id_beneficio_mensual_poker]);

    $query = $pdo->prepare("DELETE FROM beneficio_poker_temporal WHERE id_beneficio_mensual_poker = :id_beneficio_mensual_poker");
    $query->execute([":id_beneficio_mensual_poker" => $benMensual->id_beneficio_mensual_poker]);

    //Lo updateo por SQL porque son DECIMAL y no se si hay error de casteo si lo hago en PHP (pasa a float?)
    $query = $pdo->prepare("UPDATE beneficio_mensual_poker bm,
    (
      SELECT SUM(b.jugadores) as jugadores, SUM(b.mesas) as mesas,
             SUM(b.buy) as buy, SUM(b.rebuy) as rebuy, SUM(b.total_buy) as total_buy,
             SUM(b.cash_out) as cash_out, SUM(b.otros_pagos) as otros_pagos, SUM(b.total_bonus) as total_bonus, SUM(b.utilidad) as utilidad
      FROM beneficio_poker b
      WHERE b.id_beneficio_mensual_poker = :id_beneficio_mensual_poker1
      GROUP BY b.id_beneficio_mensual_poker
    ) total
    SET bm.jugadores = IFNULL(total.jugadores,0),bm.mesas = IFNULL(total.mesas,0),
        bm.buy = IFNULL(total.buy,0),bm.rebuy = IFNULL(total.rebuy,0),
        bm.total_buy = IFNULL(total.total_buy,0),bm.cash_out  = IFNULL(total.cash_out,0),
        bm.otros_pagos = IFNULL(total.otros_pagos,0),bm.total_bonus = IFNULL(total.total_bonus,0), bm.utilidad = IFNULL(total.utilidad,0)
    WHERE bm.id_beneficio_mensual_poker = :id_beneficio_mensual_poker2");
    $query->execute([":id_beneficio_mensual_poker1" => $benMensual->id_beneficio_mensual_poker,":id_beneficio_mensual_poker2" => $benMensual->id_beneficio_mensual_poker]);

    //Actualizo la entidad
    $benMensual = BeneficioMensualPoker::find($benMensual->id_beneficio_mensual_poker);

    DB::connection()->enableQueryLog();

    $pdo = null;

    return [
      'bruto' => $benMensual->utilidad,
      'dias' => $benMensual->beneficios()->count()
    ]; 
  }
  
  private function attrsJugadorArchivo(){//Importante el orden porque se usa para leer el archivo
    return ['codigo','localidad','provincia','fecha_alta','estado','fecha_autoexclusion','fecha_nacimiento','fecha_ultimo_movimiento','sexo'];
  }
  private function attrsJugadorArchivoNullables(){
    return [   false,      false,      false,       false,   false,                 true,             false,                    false, false];
  }

  private function importarJugadoresTemporal($id_importacion_estado_jugador,$archivo){
    $LIST_ATTRS = '@'.implode(',@',$this->attrsJugadorArchivo());
    $SET_ATTRS = array_map(function($a,$is_null){
      $right = '@'.$a;
      if($is_null) $right="NULLIF($right,'')";
      return "$a = $right";
    },$this->attrsJugadorArchivo(),$this->attrsJugadorArchivoNullables());
    $SET_ATTRS = implode(',',$SET_ATTRS);
    
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE jugadores_temporal
    FIELDS TERMINATED BY ';'
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    ($LIST_ATTRS)
    SET id_importacion_estado_jugador = %d,
    $SET_ATTRS",
      $archivo->getRealPath(),$id_importacion_estado_jugador
    );
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $pdo->exec($query);
  }
  
  private function query_jugAnteriores($id_plataforma,$fecha_importacion){
    $stamp = __LINE__.intval(microtime(true)*1000);
    $k_id_plataforma = $stamp.'_id_plataforma';
    $k_fecha_importacion = $stamp.'_fecha_importacion';
    return [
      'sql' => "(
        SELECT j2.id_plataforma,j2.codigo,MAX(j2.fecha_importacion) as fecha_importacion
        FROM jugador j2
        WHERE j2.id_plataforma = :$k_id_plataforma AND j2.fecha_importacion < :$k_fecha_importacion
        AND   (j2.valido_hasta IS NULL OR j2.valido_hasta >= :${k_fecha_importacion}2)
        GROUP BY j2.id_plataforma,j2.codigo
      )",
      'params' => [
        $k_id_plataforma => $id_plataforma,
        $k_fecha_importacion => $fecha_importacion,
        "${k_fecha_importacion}2" => $fecha_importacion,
      ]
    ];
  }
  //Tambien usado al borrar
  public function query_jugProximos($id_plataforma,$fecha_importacion){
    $stamp = __LINE__.intval(microtime(true)*1000);
    $k_id_plataforma = $stamp.'_id_plataforma';
    $k_fecha_importacion = $stamp.'_fecha_importacion';
    return [
      'sql' => "(
        SELECT j2.id_plataforma,j2.codigo,MIN(j2.fecha_importacion) as fecha_importacion
        FROM jugador j2
        WHERE j2.id_plataforma = :$k_id_plataforma AND j2.fecha_importacion > :$k_fecha_importacion
        GROUP BY j2.id_plataforma,j2.codigo
      )",
      'params' => [
        $k_id_plataforma => $id_plataforma,
        $k_fecha_importacion => $fecha_importacion,
      ]
    ];
  }

  public function importarJugadores($archivo,$md5,$fecha,$id_plataforma){
    $importacion = new ImportacionEstadoJugador;
    $importacion->id_plataforma = $id_plataforma;
    $importacion->fecha_importacion = $fecha;
    $importacion->md5 = $md5;
    $importacion->save();
    $this->importarJugadoresTemporal($importacion->id_importacion_estado_jugador,$archivo);
    
    $attrs_jug = $this->attrsJugadorArchivo();
    $attrs_jug_null = $this->attrsJugadorArchivoNullables();
    
    $prefix_attrs = function($prefix = '') use ($attrs_jug){
      $prefixed = array_map(function($a) use ($prefix){return "$prefix$a";},$attrs_jug);
      return implode(',',$prefixed);
    };
    
    $comp_attrs = function($prefix1,$comp_operator,$prefix2,$log_operator) use ($attrs_jug,$attrs_jug_null){
      $map_f_prefix = '';
      $map_f = function($a,$is_null) use (&$map_f_prefix){
          $ret = "$map_f_prefix$a";
          if($is_null){
            $ret = "IFNULL($ret,'')";
          }
          return $ret;
      };
      $map_f_prefix = $prefix1;
      $prefixed1 = array_map($map_f,$attrs_jug,$attrs_jug_null);
      $map_f_prefix = $prefix2;
      $prefixed2 = array_map($map_f,$attrs_jug,$attrs_jug_null);
      
      $pairs = array_map(
        function($a1,$a2) use ($comp_operator){
          return "(($a1) $comp_operator ($a2))";
        },
        $prefixed1,$prefixed2
      );
      
      return '('.implode($log_operator,$pairs).')';
    };
    
    $query_ant  = $this->query_jugAnteriores($id_plataforma,$fecha);
    $query_prox = $this->query_jugProximos($id_plataforma,$fecha);
    $prox_imp   = ImportacionEstadoJugador::where([
      ['id_plataforma','=',$importacion->id_plataforma],
      ['fecha_importacion','>',$importacion->fecha_importacion]
    ])->orderBy('fecha_importacion','asc')->first();
    $prox_imp   = is_null($prox_imp)? null : $prox_imp->fecha_importacion;
    
    /*
     Manejo los jugadores "borrados" (estaban validos y desaparecieron)
     Para J en Janteriores
      Si no existe J en Jnuevos
        Inserto uno igual con fecha_importacion = prox_imp y valido_hasta = igual_que_Jant
          Siempre que la proxima importacion sea abarcada por el valido_hasta
          y no sea la misma del proximo jugador
        Seteo valido_hasta al anterior en 1 dia antes de la importacion
    */
    //Jant───────►Jprox => Jant───▻  Jins─►Jprox
    //       ↑*desaparece*         
    //Jant─────▻  Jprox => Jant───▻  Jins─►Jprox
    //       ↑*desaparece*    
    //Jant►Jprox => Jant Jprox
    //    ↑*desaparece*    
    
    $pdo = DB::connection('mysql')->getPdo();
    $pdo->prepare("INSERT INTO jugador (id_plataforma,fecha_importacion,valido_hasta,".$prefix_attrs('').")
    SELECT j_ant.id_plataforma,:prox_imp_fecha_importacion1,j_ant.valido_hasta,".$prefix_attrs('j_ant.')."
    FROM jugador j_ant
    JOIN ${query_ant['sql']} es_ant ON (
          j_ant.id_plataforma = es_ant.id_plataforma
      AND j_ant.codigo = es_ant.codigo
      AND j_ant.fecha_importacion = es_ant.fecha_importacion
    )
    LEFT JOIN jugadores_temporal jt ON (
      jt.id_importacion_estado_jugador = :id_importacion_estado_jugador
      AND j_ant.codigo = jt.codigo
    )
    LEFT JOIN ${query_prox['sql']} j_prox ON (
          j_ant.id_plataforma = j_prox.id_plataforma
      AND j_ant.codigo = j_prox.codigo
    )
    WHERE jt.id_importacion_estado_jugador IS NULL
    AND (j_ant.valido_hasta IS NULL OR j_ant.valido_hasta >= :prox_imp_fecha_importacion2)
    AND (j_prox.fecha_importacion IS NULL OR j_prox.fecha_importacion > :prox_imp_fecha_importacion3)
    AND :prox_imp_fecha_importacion4 IS NOT NULL")->execute(array_merge([
      'prox_imp_fecha_importacion1' => $prox_imp,
      'prox_imp_fecha_importacion2' => $prox_imp,
      'prox_imp_fecha_importacion3' => $prox_imp,
      'prox_imp_fecha_importacion4' => $prox_imp,
      'id_importacion_estado_jugador' => $importacion->id_importacion_estado_jugador,
    ],$query_ant['params'],$query_prox['params']));
    
    //Seteo valido_hasta a 1 dia antes
    $pdo->prepare("UPDATE jugador j_ant
    JOIN ${query_ant['sql']} es_ant ON (
          j_ant.id_plataforma = es_ant.id_plataforma
      AND j_ant.codigo = es_ant.codigo
      AND j_ant.fecha_importacion = es_ant.fecha_importacion
    )
    LEFT JOIN jugadores_temporal jt ON (
      jt.id_importacion_estado_jugador = :id_importacion_estado_jugador
      AND j_ant.codigo = jt.codigo
    )
    SET j_ant.valido_hasta = DATE_SUB(:fecha_importacion,INTERVAL 1 DAY)
    WHERE jt.id_importacion_estado_jugador IS NULL")->execute(array_merge([
      'fecha_importacion' => $fecha,
      'id_importacion_estado_jugador' => $importacion->id_importacion_estado_jugador,
    ],$query_ant['params']));
    
    /*
      Ahora manejo las inserciones
      Si es igual al proximo lo muevo nomas
      Semanticamente igual que hacer una inserción
      Luego se arregla con el ultimo update el hecho de que quede en el medio
     Jant───────►Jprox => Jant───────▻     
                    ↑Jins                Jprox────...  
     */
    $pdo->prepare("UPDATE jugador j_prox
    JOIN jugadores_temporal jt ON (
      jt.id_importacion_estado_jugador = :id_importacion_estado_jugador
      AND ".$comp_attrs('jt.','=','j_prox.',' AND ')."
    )
    JOIN ${query_prox['sql']} es_prox ON (
          j_prox.id_plataforma = es_prox.id_plataforma
      AND j_prox.codigo = es_prox.codigo
      AND j_prox.fecha_importacion = es_prox.fecha_importacion
    )
    SET j_prox.fecha_importacion = :fecha_importacion")->execute(array_merge([
      'id_importacion_estado_jugador' => $importacion->id_importacion_estado_jugador,
      'fecha_importacion' => $fecha
    ],$query_prox['params']));
    
    //Inserto datos si es distinto a la importacion valida anterior
    //Si el jugador no tiene importacion anterior, es valido_hasta hasta el mas cercano
    //entre la fecha de importacion proxima y el jugador importado proximo
    //(porque puede que haya una importacion anterior sin el jugador)
    //Casos 
    //Jant───────►Jprox => Jant───────►Jprox  | seteo igual que el valido_hasta de Jant
    //       ↑Jins              Jins──►       |
    //---------------------------------------------------------------------------------------
    //Jant─────▻  Jprox => Jant─────▻  Jprox  | seteo igual que el valido_hasta de Jant
    //       ↑Jins             Jins─▻         |
    //---------------------------------------------------------------------------------------
    //    [nada]  JProx => Jins───────►Jprox  | seteo el valido_hasta en Jprox-1
    //       ↑Jins                            |
    //---------------------------------------------------------------------------------------
    //[nada] Imp JProx  => Jins─▻Imp   Jprox  | seteo el valido_hasta en la importacion sin el jugador-1
    //     ↑Jins                              |
    //---------------------------------------------------------------------------------------
    //Jant [nada]       => Jant  Jins  [nada] | seteo el valido_hasta en NULL
    //       ↑Jins                            |
    //---------------------------------------------------------------------------------------
    //Jant─────▻ [nada] => Jant──────▻ [nada] | seteo el valido_hasta igual que Jant
    //     ↑Jins               Jins──▻        |
    //---------------------------------------------------------------------------------------
    //Jant─────▻ [nada] => Jant──────▻ Jins   | seteo el valido_hasta en NULL
    //          ↑Jins                         |
    //---------------------------------------------------------------------------------------
    $pdo->prepare("INSERT INTO jugador (id_plataforma,fecha_importacion,valido_hasta,".$prefix_attrs('').")
    SELECT :id_plataforma,:fecha_importacion,
      IF(
        j_ant.id_jugador IS NOT NULL,
        j_ant.valido_hasta,
        DATE_SUB(
          LEAST(COALESCE(j_prox.fecha_importacion,:prox_imp_fecha_importacion1),COALESCE(:prox_imp_fecha_importacion2,j_prox.fecha_importacion)),
          INTERVAL 1 DAY
        )
      ),
      ".$prefix_attrs('jt.')."
    FROM jugadores_temporal jt
    LEFT JOIN ${query_ant['sql']} j_ant_aux ON (jt.codigo = j_ant_aux.codigo)
    LEFT JOIN jugador j_ant ON (
          j_ant.id_plataforma     = j_ant_aux.id_plataforma 
      AND j_ant.fecha_importacion = j_ant_aux.fecha_importacion
      AND j_ant.codigo            = j_ant_aux.codigo
    )
    LEFT JOIN ${query_prox['sql']} j_prox ON (jt.codigo = j_prox.codigo)
    WHERE jt.id_importacion_estado_jugador = :id_importacion_estado_jugador
    AND (
         j_ant.id_jugador IS NULL 
      OR ".$comp_attrs('jt.','<>','j_ant.',' OR ')."
    )")->execute(array_merge([
      'id_plataforma' => $id_plataforma,
      'fecha_importacion' => $fecha,
      'prox_imp_fecha_importacion1' => $prox_imp,
      'prox_imp_fecha_importacion2' => $prox_imp,
      'id_importacion_estado_jugador' => $importacion->id_importacion_estado_jugador,
    ],$query_ant['params'],$query_prox['params']));
  
    //Updateo el fin de validez para los anteriores que quedaron el medio
    //Casos
    //Jant ────────►Jprox  => Jant─►Jins──►Jprox 
    //      Jins───►
    //Jant ──────▻  Jprox  => Jant─►Jins─▻ Jprox
    //      Jins─▻
    //Jant  Jins  [nada]   => Jans─►Jins [nada]
    //Jant───────▻         => Jant─►Jprox────...
    //      Jprox────...  
    $pdo->prepare("UPDATE jugador j_ant
    JOIN ${query_ant['sql']} es_ant 
      ON (j_ant.id_plataforma = es_ant.id_plataforma 
      AND j_ant.codigo = es_ant.codigo
      AND j_ant.fecha_importacion = es_ant.fecha_importacion)
    JOIN jugador j_insertado
      ON (j_insertado.id_plataforma = j_ant.id_plataforma
      AND j_insertado.codigo        = j_ant.codigo
      AND j_insertado.fecha_importacion = :fecha_importacion)
    SET j_ant.valido_hasta = DATE_SUB(j_insertado.fecha_importacion,INTERVAL 1 DAY)")->execute(array_merge([
      'fecha_importacion' => $fecha,
    ],$query_ant['params']));
    
    $pdo->prepare(
      'DELETE FROM jugadores_temporal WHERE id_importacion_estado_jugador = :id_importacion_estado_jugador'
    )->execute([
      'id_importacion_estado_jugador' => $importacion->id_importacion_estado_jugador,
    ]);
    
    return [
      'jugadores_importados' => DB::table('jugador')
      ->where('fecha_importacion','<=',$fecha)
      ->where(function($q) use ($fecha){
        return $q->where('valido_hasta','>=',$fecha)
        ->orWhereNull('valido_hasta');
      })
      ->count()
    ];
  }

  private function importarEstadosJuegosTemporal($id_importacion_estado_juego,$archivo){
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE juego_importado_temporal
    CHARACTER SET UTF8
    FIELDS TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    (@GameCode,@GameName,@IsPublised,@GameCategory,@Technology)
    SET id_importacion_estado_juego = %d,
                        codigo = @GameCode,
                        nombre = @GameName,
                        estado = @IsPublised,
                     categoria = @GameCategory,
                    tecnologia = @Technology",
      $archivo->getRealPath(),$id_importacion_estado_juego
    );
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $pdo->exec($query);
  }

  public function importarEstadosJuegos($archivo,$md5,$fecha,$id_plataforma){
    $importacion = new ImportacionEstadoJuego;
    $importacion->id_plataforma = $id_plataforma;
    $importacion->fecha_importacion = $fecha;
    $importacion->md5 = $md5;
    $importacion->save();
    $this->importarEstadosJuegosTemporal($importacion->id_importacion_estado_juego,$archivo);
    //Inserto datos si no estan repetidos
    $err = DB::statement("INSERT INTO datos_juego_importado (codigo,nombre,categoria,tecnologia)
    SELECT jt.codigo,jt.nombre,jt.categoria,jt.tecnologia
    FROM juego_importado_temporal jt
    WHERE jt.id_importacion_estado_juego = ? AND NOT EXISTS (
      SELECT dj.id_datos_juego_importado
      FROM datos_juego_importado dj FORCE INDEX (idx_datosjuego_codigo)
      WHERE dj.codigo   = jt.codigo 
      AND dj.nombre     = jt.nombre 
      AND dj.categoria  = jt.categoria
      AND dj.tecnologia = jt.tecnologia
    )",[$importacion->id_importacion_estado_juego]);
    if(!$err){
      throw new \Exception('Error al importar datos del juego');
    }

    //Pongo "ultimo estado" en 0 todos los estados con fecha de importacion menor a la que se inserta
    //Hago un JOIN con juegos temporal para solo afectar los juegos que se van a insertar
    $err = DB::statement("UPDATE estado_juego_importado ej
    JOIN datos_juego_importado dj on dj.id_datos_juego_importado = ej.id_datos_juego_importado
    JOIN importacion_estado_juego iej on iej.id_importacion_estado_juego = ej.id_importacion_estado_juego
    JOIN juego_importado_temporal jt on jt.codigo = dj.codigo AND jt.id_importacion_estado_juego = ?
    SET ej.es_ultimo_estado_del_juego = 0
    WHERE ej.es_ultimo_estado_del_juego = 1 AND iej.fecha_importacion < ? AND iej.id_plataforma = ?",
      [$importacion->id_importacion_estado_juego,$fecha,$id_plataforma]
    );
    if(!$err){
      throw new \Exception('Error al resetear los "ultimos estados" de los juegos');
    }

    $err = DB::statement("INSERT INTO estado_juego_importado (id_importacion_estado_juego,id_datos_juego_importado,estado,es_ultimo_estado_del_juego)
    SELECT jt.id_importacion_estado_juego,dj.id_datos_juego_importado,
    IFNULL(
      (SELECT estado_juego.nombre
       FROM estado_juego
       WHERE estado_juego.conversiones LIKE CONCAT('%|',jt.estado,'|%')
       LIMIT 1),
      jt.estado) as estado,
    (NOT EXISTS (
      SELECT ej_max.id_estado_juego_importado
      FROM datos_juego_importado dj_max
      JOIN estado_juego_importado ej_max on ej_max.id_datos_juego_importado = dj_max.id_datos_juego_importado
      JOIN importacion_estado_juego iej_max on iej_max.id_importacion_estado_juego = ej_max.id_importacion_estado_juego
      WHERE dj_max.codigo = jt.codigo AND iej_max.id_plataforma = ? AND iej_max.fecha_importacion > ?
    )) as es_ultimo_estado_del_juego
    FROM juego_importado_temporal jt
    JOIN datos_juego_importado dj FORCE INDEX (idx_datosjuego_codigo) ON (
          dj.codigo = jt.codigo 
      AND dj.nombre = jt.nombre 
      AND dj.categoria = jt.categoria 
      AND dj.tecnologia = jt.tecnologia
    )
    WHERE jt.id_importacion_estado_juego = ?",[$id_plataforma,$fecha,$importacion->id_importacion_estado_juego]);
    if(!$err){
      throw new \Exception('Error al importar estados del juego');
    }

    DB::table('juego_importado_temporal')->where('id_importacion_estado_juego','=',$importacion->id_importacion_estado_juego)->delete();
    
    //Este comando sirve para poner correctamente todos los "estados ultimos" por si alguna razon fallara o se editara la BD manualmente
    //Lo dejo de referencia/emergencia, era muy lento para usarse en producción ya que reocrre todos los estados de la BD
    /*$err = DB::statement("UPDATE estado_juego_importado ej
    JOIN importacion_estado_juego iej ON iej.id_importacion_estado_juego = ej.id_importacion_estado_juego
    JOIN datos_juego_importado dj ON dj.id_datos_juego_importado = ej.id_datos_juego_importado
    JOIN (
      SELECT dj_max.codigo,iej_max.id_plataforma,MAX(iej_max.fecha_importacion) as fecha_importacion
      FROM datos_juego_importado dj_max
      JOIN estado_juego_importado ej_max on ej_max.id_datos_juego_importado = dj_max.id_datos_juego_importado
      JOIN importacion_estado_juego iej_max on iej_max.id_importacion_estado_juego = ej_max.id_importacion_estado_juego
      GROUP BY dj_max.codigo,iej_max.id_plataforma
    ) max_fecha ON (max_fecha.codigo = dj.codigo AND max_fecha.id_plataforma = iej.id_plataforma)
    SET ej.es_ultimo_estado_del_juego = (iej.fecha_importacion = max_fecha.fecha_importacion)");

    if(!$err){
      throw new \Exception('Error al actualizar los estados de los juegos');
    }*/
    return [
      'juegos_importados' => DB::table('estado_juego_importado as ej')
      ->where('ej.id_importacion_estado_juego','=',$importacion->id_importacion_estado_juego)
      ->count()
    ];
  }
  
  //Lo dejo por si en algun momento se cambia estado_juego_importado a una estructura similar
  /*public function migrarJugadores(){
    return DB::transaction(function(){
      $plats = DB::table('plataforma')->select('id_plataforma')->distinct()
      ->get()->pluck('id_plataforma');
      foreach($plats as $idp){
        dump($idp);
        $importaciones = ImportacionEstadoJugador::where([
          ['id_plataforma','=',$idp],
        ])->orderBy('fecha_importacion','asc')->get();
        
        foreach($importaciones as $imp){
          $err = DB::statement("INSERT INTO jugador (id_plataforma,fecha_importacion,valido_hasta,localidad,provincia,fecha_alta,codigo,estado,fecha_autoexclusion,fecha_nacimiento,fecha_ultimo_movimiento,sexo)
          SELECT ?,?,NULL,
            dj.localidad,dj.provincia,dj.fecha_alta,dj.codigo,ej.estado,ej.fecha_autoexclusion,dj.fecha_nacimiento,ej.fecha_ultimo_movimiento,dj.sexo
          FROM estado_jugador ej
          JOIN datos_jugador dj ON (dj.id_datos_jugador = ej.id_datos_jugador)
          LEFT JOIN (
            SELECT j2.id_plataforma,j2.codigo,MAX(j2.fecha_importacion) as fecha_importacion
            FROM jugador j2 FORCE INDEX(unq_jugador_importacion)
            WHERE j2.id_plataforma = ? AND j2.fecha_importacion <= ?
            GROUP BY j2.id_plataforma,j2.codigo
          ) ult_jug ON (dj.codigo = ult_jug.codigo)
          LEFT JOIN jugador j2 ON (
                j2.id_plataforma     = ult_jug.id_plataforma
            AND j2.fecha_importacion = ult_jug.fecha_importacion
            AND j2.codigo            = ult_jug.codigo
          )
          WHERE ej.id_importacion_estado_jugador = ? AND (
               j2.id_jugador IS NULL 
            OR j2.localidad               <> dj.localidad
            OR j2.provincia               <> dj.provincia
            OR j2.fecha_alta              <> dj.fecha_alta
            OR j2.codigo                  <> dj.codigo
            OR j2.estado                  <> ej.estado
            OR j2.fecha_autoexclusion     <> ej.fecha_autoexclusion
            OR j2.fecha_nacimiento        <> dj.fecha_nacimiento
            OR j2.fecha_ultimo_movimiento <> ej.fecha_ultimo_movimiento
            OR j2.sexo                    <> dj.sexo
          )",[
            $idp,$imp->fecha_importacion,
            $idp,$imp->fecha_importacion,
            $imp->id_importacion_estado_jugador
          ]);
          if(!$err){
            throw new \Exception('Error 1 al importar datos del jugador');
          }
          
          //Updateo fin de validez para los insertados anteriormente 
          //a los que se le inserto uno nuevo
          $err = DB::statement("UPDATE jugador j_anterior
          JOIN jugador j_insertado
            ON (j_insertado.id_plataforma = j_anterior.id_plataforma
            AND j_insertado.codigo = j_anterior.codigo
            AND j_insertado.fecha_importacion > j_anterior.fecha_importacion
            AND j_insertado.valido_hasta IS NULL)
          SET j_anterior.valido_hasta = DATE_SUB(j_insertado.fecha_importacion,INTERVAL 1 DAY)
          WHERE j_anterior.id_plataforma = ? 
          AND j_anterior.fecha_importacion < ?
          AND j_anterior.valido_hasta IS NULL",
            [$idp,$imp->fecha_importacion]
          );
          if(!$err){
            throw new \Exception('Error 2 al importar datos del jugador');
          }
        }
      }
      return 0;
    });
  }*/
}
