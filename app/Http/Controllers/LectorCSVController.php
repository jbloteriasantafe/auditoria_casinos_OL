<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use PDO;
use Validator;
use App\DatosJugador;
use App\EstadoJugador;
use App\ImportacionEstadoJugador;
use App\Plataforma;
use App\Producido;
use App\ProducidoJugadores;
use App\Beneficio;
use App\BeneficioMensual;
use App\DetalleProducido;
use App\TipoMoneda;
use App\Http\Controllers\ProducidoController;
use App\Http\Controllers\BeneficioMensualController;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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

    $producidos_viejos = DB::table('producido')->where([
      ['id_producido','<>',$producido->id_producido],['id_plataforma','=',$producido->id_plataforma],['fecha','=',$producido->fecha]]
    )->get();


    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $prodCont = ProducidoController::getInstancia();
    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $prodCont->eliminarProducido($prod->id_producido);
      }
    }

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

    $producidos_viejos = DB::table('producido_jugadores')->where([
      ['id_producido_jugadores','<>',$producido->id_producido_jugadores],['id_plataforma','=',$producido->id_plataforma],['fecha','=',$producido->fecha]]
    )->get();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $prodCont = ProducidoController::getInstancia();
    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $prodCont->eliminarProducidoJugadores($prod->id_producido_jugadores);
      }
    }

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

  public function importarBeneficio($archivoCSV,$fecha,$plataforma,$moneda){
    //Hay un "analogo conceptual" Producido <-> BeneficioMensual, DetalleProducido <-> Beneficio
    $benMensual = new BeneficioMensual;
    $benMensual->id_plataforma = $plataforma;
    $benMensual->id_tipo_moneda = $moneda;
    $fecha_aux = explode("-",$fecha);
    $benMensual->fecha = $fecha_aux[0] . '-' . $fecha_aux[1] . '-01';
    $benMensual->depositos = 0;$benMensual->retiros   = 0;
    $benMensual->apuesta   = 0;$benMensual->premio    = 0;$benMensual->beneficio = 0;
    $benMensual->ajuste = 0;
    $benMensual->puntos_club_jugadores = 0;
    $benMensual->validado = false;
    $benMensual->md5 = DB::select(DB::raw('SELECT md5(?) as hash'),[file_get_contents($archivoCSV)])[0]->hash;
    $benMensual->save();
    
    //Verifico si ya existen con las mismas caracteristicas, differente ID y los borro
    $ben_viejos = DB::table('beneficio_mensual')->where([
      ['id_beneficio_mensual','<>',$benMensual->id_beneficio_mensual],['id_plataforma','=',$benMensual->id_plataforma],
      ['id_tipo_moneda','=',$benMensual->id_tipo_moneda]
    ])->whereRaw('YEAR(fecha) = ? and MONTH(fecha) = ?',[$fecha_aux[0],$fecha_aux[1]])->get();

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    
    $benCont = BeneficioMensualController::getInstancia();
    if($ben_viejos != null){
      foreach($ben_viejos as $b){
        $benCont->eliminarBeneficioMensual($b->id_beneficio_mensual);
      }
    }

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
    //Inserto datos si no estan repetidos
    $err = DB::statement("INSERT INTO datos_jugador (codigo,fecha_alta,fecha_nacimiento,sexo,localidad,provincia)
    SELECT jt.codigo,jt.fecha_alta,jt.fecha_nacimiento,jt.sexo,jt.localidad,jt.provincia
    FROM jugadores_temporal jt
    WHERE jt.id_importacion_estado_jugador = ? AND NOT EXISTS (
      SELECT dj.id_datos_jugador
      FROM datos_jugador dj FORCE INDEX (idx_datosjugador_codigo)
      WHERE dj.codigo         = jt.codigo 
      AND dj.fecha_alta       = jt.fecha_alta 
      AND dj.fecha_nacimiento = jt.fecha_nacimiento 
      AND dj.sexo             = jt.sexo
      AND dj.localidad        = jt.localidad
      AND dj.provincia        = jt.provincia
    )",[$importacion->id_importacion_estado_jugador]);
    if(!$err){
      throw new \Exception('Error al importar datos del jugador');
    }
    
    $err = DB::statement("INSERT INTO estado_jugador (id_importacion_estado_jugador,id_datos_jugador,estado,fecha_autoexclusion,fecha_ultimo_movimiento)
    SELECT jt.id_importacion_estado_jugador,dj.id_datos_jugador,jt.estado,jt.fecha_autoexclusion,jt.fecha_ultimo_movimiento
    FROM jugadores_temporal jt
    JOIN datos_jugador dj FORCE INDEX (idx_datosjugador_codigo) ON (
          dj.codigo = jt.codigo 
      AND dj.fecha_alta = jt.fecha_alta 
      AND dj.fecha_nacimiento = jt.fecha_nacimiento 
      AND dj.sexo = jt.sexo
      AND dj.localidad = jt.localidad
      AND dj.provincia = jt.provincia
    )
    WHERE jt.id_importacion_estado_jugador = ?",[$importacion->id_importacion_estado_jugador]);
    if(!$err){
      throw new \Exception('Error al importar estados del jugador');
    }

    DB::table('jugadores_temporal')->where('id_importacion_estado_jugador','=',$importacion->id_importacion_estado_jugador)->delete();

    //Util para la busqueda en la pantalla principal
    DB::statement("UPDATE importacion_estado_jugador SET es_ultima_importacion = 0");
    foreach(Plataforma::all() as $p){
      $ultimo = ImportacionEstadoJugador::where('id_plataforma','=',$p->id_plataforma)->orderBy('fecha_importacion','desc')->take(1)->get()->first();
      if(!is_null($ultimo)){
        $ultimo->es_ultima_importacion = 1;
        $ultimo->save();
      }
    }
    return 1;
  }
}
