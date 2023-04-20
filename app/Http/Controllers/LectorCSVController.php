<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\ImportacionEstadoJugador;
use App\ImportacionEstadoJuego;
use App\Producido;
use App\ProducidoJugadores;
use App\ProducidoPoker;
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

    return ['id_producido' => $producido->id_producido,
    'fecha' => $producido->fecha,
    'plataforma' => $producido->plataforma->nombre,
    'tipo_moneda' => $producido->tipo_moneda->descripcion,
    'cantidad_registros' => $producido->detalles()->count(),
    'juegos_multiples_reportes' => $duplicados];
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

    return ['id_producido_jugadores' => $producido->id_producido_jugadores,
    'fecha' => $producido->fecha,
    'plataforma' => $producido->plataforma->nombre,
    'tipo_moneda' => $producido->tipo_moneda->descripcion,
    'cantidad_registros' => $producido->detalles()->count(),
    'jugadores_multiples_reportes' => $duplicados];
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

    return ['id_producido_poker' => $producido->id_producido_poker,
    'fecha' => $producido->fecha,
    'plataforma' => $producido->plataforma->nombre,
    'tipo_moneda' => $producido->tipo_moneda->descripcion,
    'cantidad_registros' => $producido->detalles()->count(),
    'juegos_multiples_reportes' => $duplicados];
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
      observacion
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
    ''               as observacion
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
             SUM(b.ajuste)    as ajuste   , SUM(b.puntos_club_jugadores) as puntos_club_jugadores
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

    return [ 'id_beneficio_mensual' => $benMensual->id_beneficio_mensual, 'fecha' => $benMensual->fecha, 
    'bruto' => $benMensual->beneficio, 'dias' => $benMensual->beneficios()->count()]; 
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

    return [ 'id_beneficio_mensual_poker' => $benMensual->id_beneficio_mensual_poker, 'fecha' => $benMensual->fecha, 
    'bruto' => $benMensual->utilidad, 'dias' => $benMensual->beneficios()->count()]; 
  }

  private function importarJugadoresTemporal($id_importacion_estado_jugador,$archivo){
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE jugadores_temporal
    FIELDS TERMINATED BY ';'
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    (@codigo,@localidad,@provincia,@fecha_alta,@estado,@fecha_autoexclusion,@fecha_nacimiento,@fecha_ultimo_movimiento,@sexo)
    SET id_importacion_estado_jugador = %d,
                      codigo = @codigo,
                   localidad = @localidad,
                   provincia = @provincia,
                  fecha_alta = @fecha_alta,
                      estado = @estado,
         fecha_autoexclusion = IF(@fecha_autoexclusion = '',NULL,@fecha_autoexclusion),
            fecha_nacimiento = @fecha_nacimiento,
     fecha_ultimo_movimiento = @fecha_ultimo_movimiento,
                        sexo = @sexo",
      $archivo->getRealPath(),$id_importacion_estado_jugador
    );
    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $pdo->exec($query);
  }

  public function importarJugadores($archivo,$md5,$fecha,$id_plataforma){
    $importacion = new ImportacionEstadoJugador;
    $importacion->id_plataforma = $id_plataforma;
    $importacion->fecha_importacion = $fecha;
    $importacion->md5 = $md5;
    $importacion->save();
    $this->importarJugadoresTemporal($importacion->id_importacion_estado_jugador,$archivo);
    
    //Me fijo si puedo mover para atras una importacion que este inmediatamente adelante
    $err = DB::statement("UPDATE jugador j
    JOIN jugadores_temporal jt ON (
      jt.id_importacion_estado_jugador = ?
      AND j.codigo                  = jt.codigo
      AND j.localidad               = jt.localidad
      AND j.provincia               = jt.provincia
      AND j.fecha_alta              = jt.fecha_alta
      AND j.estado                  = jt.estado
      AND j.fecha_nacimiento        = jt.fecha_nacimiento
      AND j.fecha_ultimo_movimiento = jt.fecha_ultimo_movimiento
      AND j.sexo                    = jt.sexo 
      AND IFNULL(j.fecha_autoexclusion,'') = IFNULL(jt.fecha_autoexclusion,'')
    )
    JOIN (
      SELECT j2.id_plataforma,j2.codigo,MIN(j2.fecha_importacion) as fecha_importacion
	    FROM jugador j2 FORCE INDEX(unq_jugador_importacion)
	    WHERE j2.id_plataforma = ? AND j2.fecha_importacion > ?
      GROUP BY j2.id_plataforma,j2.codigo
   	) prox_jug ON (
          j.id_plataforma = prox_jug.id_plataforma
      AND j.codigo = prox_jug.codigo
      AND j.fecha_importacion = prox_jug.fecha_importacion
    )
    SET j.fecha_importacion = ?",
      [$importacion->id_importacion_estado_jugador,$id_plataforma,$fecha,$fecha]
    );
    if(!$err){
      throw new \Exception('Error 1 al importar datos del jugador');
    }
    
    //Inserto datos si es distinto a la importacion anterior
    $err = DB::statement("INSERT INTO jugador (id_plataforma,fecha_importacion,localidad,provincia,fecha_alta,codigo,estado,fecha_autoexclusion,fecha_nacimiento,fecha_ultimo_movimiento,sexo)
    SELECT ?,?,
      jt.localidad,jt.provincia,jt.fecha_alta,jt.codigo,jt.estado,jt.fecha_autoexclusion,jt.fecha_nacimiento,jt.fecha_ultimo_movimiento,jt.sexo
    FROM jugadores_temporal jt
    LEFT JOIN (
      SELECT j2.id_plataforma,j2.codigo,MAX(j2.fecha_importacion) as fecha_importacion
	    FROM jugador j2 FORCE INDEX(unq_jugador_importacion)
	    WHERE j2.id_plataforma = ? AND j2.fecha_importacion <= ?
      GROUP BY j2.id_plataforma,j2.codigo
   	) ult_jug ON (jt.codigo = ult_jug.codigo)
    LEFT JOIN jugador j2 ON (
          j2.id_plataforma     = ult_jug.id_plataforma 
      AND j2.fecha_importacion = ult_jug.fecha_importacion
      AND j2.codigo            = ult_jug.codigo
    )
    WHERE jt.id_importacion_estado_jugador = ? AND (
         j2.id_jugador IS NULL 
      OR j2.localidad               <> jt.localidad
      OR j2.provincia               <> jt.provincia
      OR j2.fecha_alta              <> jt.fecha_alta
      OR j2.codigo                  <> jt.codigo
      OR j2.estado                  <> jt.estado
      OR j2.fecha_autoexclusion     <> jt.fecha_autoexclusion
      OR j2.fecha_nacimiento        <> jt.fecha_nacimiento
      OR j2.fecha_ultimo_movimiento <> jt.fecha_ultimo_movimiento
      OR j2.sexo                    <> jt.sexo
    )",[$id_plataforma,$fecha,$id_plataforma,$fecha,$importacion->id_importacion_estado_jugador]);
    if(!$err){
      throw new \Exception('Error 2 al importar datos del jugador');
    }
    
    $err = DB::statement(
      "DELETE FROM jugadores_temporal WHERE id_importacion_estado_jugador = ?",
      [$importacion->id_importacion_estado_jugador]
    );
    if(!$err){
      throw new \Exception('Error 3 al importar datos del jugador');
    }

    return $importacion;
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
    return 1;
  }
  
  public function migrarJugadores(){
    return DB::transaction(function(){
      $plats = DB::table('plataforma')->select('id_plataforma')->distinct()
      ->get()->pluck('id_plataforma');
      foreach($plats as $idp){
        dump($idp);
        $importaciones = ImportacionEstadoJugador::where([
          ['id_plataforma','=',$idp],
        ])->orderBy('fecha_importacion','asc')->get();
        
        foreach($importaciones as $imp){
          dump($imp);
          $err = DB::statement("INSERT INTO jugador (id_plataforma,fecha_importacion,localidad,provincia,fecha_alta,codigo,estado,fecha_autoexclusion,fecha_nacimiento,fecha_ultimo_movimiento,sexo)
          SELECT ?,?,
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
            throw new \Exception('Error 2 al importar datos del jugador');
          }
        }
      }
      return 0;
    });
  }
}
