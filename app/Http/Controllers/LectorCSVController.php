<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use PDO;
use Validator;
use App\Maquina;
use App\Sector;
use App\Isla;
use App\ContadorHorario;
use App\Producido;
use App\Beneficio;
use App\BeneficioMensual;
use App\DetalleContadorHorario;
use App\DetalleProducido;
use App\TipoMoneda;
use App\Http\Controllers\ContadorController;
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
    $producido->jugadores = 0;
    $producido->ingreso = 0;
    $producido->premio = 0;
    $producido->valor = 0;
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

    //No se puede usar parametro preparado LOAD DATA por lo que busque
    //A totalwager y gross revenue le saco el $, le saco el punto de los miles y le cambio la coma decimal por un punto
    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE producido_temporal
                      FIELDS TERMINATED BY ','
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\r\\n'
                      IGNORE 1 LINES
                      (@DateReport,@GameId,@GameCategory,@Players,@TotalWagerCash,@TotalWagerBonus,@TotalWager,@GrossRevenueCash,@GrossRevenueBonus,@GrossRevenue)
                       SET id_producido = '%d',
                       DateReport = @DateReport,
                       GameId = @GameId,
                       GameCategory = @GameCategory,
                       Players = @Players,
                       TotalWagerCash = REPLACE(@TotalWagerCash,',','.'),
                       TotalWagerBonus = REPLACE(@TotalWagerBonus,',','.'),
                       TotalWager = REPLACE(REPLACE(REPLACE(REPLACE(@TotalWager,'$',''),' ',''),'.',''),',','.'),
                       GrossRevenueCash = REPLACE(@GrossRevenueCash,',','.'),
                       GrossRevenueBonus = REPLACE(@GrossRevenueBonus,',','.'),
                       GrossRevenue = REPLACE(REPLACE(REPLACE(REPLACE(@GrossRevenue,'$',''),' ',''),'.',''),',','.')
                      ",$path,$producido->id_producido);

    $pdo->exec($query);

    $query = $pdo->prepare("INSERT INTO detalle_producido 
    (id_producido,
    cod_juego,
    categoria,
    jugadores,
    ingreso,
    premio,
    valor)
    SELECT 
    id_producido,
    GameId as cod_juego,
    GameCategory as categoria,
    Players as jugadores,
    (TotalWagerCash + TotalWagerBonus) as ingreso,
    ((TotalWagerCash + TotalWagerBonus) - (GrossRevenueCash + GrossRevenueBonus)) as premio,
    (GrossRevenueCash + GrossRevenueBonus) as valor
    FROM producido_temporal
    WHERE producido_temporal.id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    $query = $pdo->prepare("DELETE FROM producido_temporal WHERE id_producido = :id_producido");
    $query->execute([":id_producido" => $producido->id_producido]);

    $query = $pdo->prepare("UPDATE 
    producido p,
    (
      SELECT SUM(dp.jugadores) as jugadores,SUM(dp.ingreso) as ingreso,SUM(dp.premio) as premio,SUM(dp.valor) as valor
      FROM detalle_producido dp
      WHERE dp.id_producido = :id_producido1
      GROUP BY dp.id_producido
    ) total
    SET p.jugadores = IFNULL(total.jugadores,0), p.ingreso = IFNULL(total.ingreso,0),p.premio = IFNULL(total.premio,0),p.valor = IFNULL(total.valor,0)
    WHERE p.id_producido = :id_producido2");

    $query->execute([":id_producido1" => $producido->id_producido,":id_producido2" => $producido->id_producido]);

    DB::connection()->enableQueryLog();

    $duplicados = DB::table('detalle_producido')->select('cod_juego',DB::raw('COUNT(distinct id_detalle_producido) as veces'))
    ->where('id_producido','=',$producido->id_producido)
    ->groupBy('cod_juego')
    ->havingRaw('COUNT(distinct id_detalle_producido) > 1')->get()->count();

    /*$inhabilitados_reportando = 999;
    $habilitados_sin_reportar = 999;
    $juego_faltante_en_bd = 999;
    $juego_en_bd_sin_asignar_plataforma = 999;
    $mal_categoria = 999;*/
    return ['id_producido' => $producido->id_producido,
    'fecha' => $producido->fecha,
    'plataforma' => $producido->plataforma->nombre,
    'tipo_moneda' => $producido->tipo_moneda->descripcion,
    'cantidad_registros' => $producido->detalles()->count(),
    'juegos_multiples_reportes' => $duplicados];
  }

  public function importarBeneficio($archivoCSV,$fecha,$plataforma,$moneda){
    //Hay un "analogo conceptual" Producido <-> BeneficioMensual, DetalleProducido <-> Beneficio
    $benMensual = new BeneficioMensual;
    $benMensual->id_plataforma = $plataforma;
    $benMensual->id_tipo_moneda = $moneda;
    $fecha_aux = explode("-",$fecha);
    $benMensual->fecha = $fecha_aux[0] . '-' . $fecha_aux[1] . '-01';
    $benMensual->jugadores = 0;
    $benMensual->ingreso = 0;
    $benMensual->premio = 0;
    $benMensual->valor = 0;
    $benMensual->ajuste = 0;
    $benMensual->validado = false;
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

    //No se puede usar parametro preparado LOAD DATA por lo que busque
    $query = sprintf("LOAD DATA local INFILE '%s'
    INTO TABLE beneficio_temporal
    FIELDS TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '\"'
    ESCAPED BY '\"'
    LINES TERMINATED BY '\\r\\n'
    IGNORE 1 LINES
    (@Total,@DateReport,@Currency,@TotalRegistrations,@Verified,@TotalVerified,@Players,@TotalDeposits,@TotalWithdrawals,@TotalBonus,@TotalManualAdjustments,@TotalVPoints,@TotalWager,@TotalOut,@GrossRevenue,@lastupdated)
     SET id_beneficio_mensual = %d,
     Total = @Total,
     DateReport = CONCAT(
      SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',-1),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',2),'/',-1),2,'00'),'-',
      LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(@DateReport, ' ', 1),'/',1),2,'00')
     ),
     Currency = @Currency,
     TotalRegistrations = @TotalRegistrations,
     Verified = @Verified,
     TotalVerified = @TotalVerified,
     Players = @Players,
     TotalDeposits          = REPLACE(REPLACE(REPLACE(REPLACE(@TotalDeposits,'$',''),' ',''),'.',''),',','.'),
     TotalWithdrawals       = REPLACE(REPLACE(REPLACE(REPLACE(@TotalWithdrawals,'$',''),' ',''),'.',''),',','.'),
     TotalBonus             = REPLACE(@TotalBonus,',','.'),
     TotalManualAdjustments = REPLACE(@TotalManualAdjustments,',','.'),
     TotalVPoints           = REPLACE(@TotalVPoints,',','.'),
     TotalWager             = REPLACE(REPLACE(REPLACE(REPLACE(@TotalWager,'$',''),' ',''),'.',''),',','.'),
     TotalOut               = REPLACE(@TotalOut,',','.'),
     GrossRevenue           = REPLACE(REPLACE(REPLACE(REPLACE(@GrossRevenue,'$',''),' ',''),'.',''),',','.'),
     lastupdated = @lastupdated",$path,$benMensual->id_beneficio_mensual);
    $pdo->exec($query);

    //La ultima comparacion en el WHERE es para ignorar la ultima linea
    $query = $pdo->prepare("INSERT INTO beneficio 
    (
      id_beneficio_mensual,
      fecha,
      jugadores,
      ingreso,
      premio,
      valor,
      ajuste,
      observacion
    )
    SELECT
    id_beneficio_mensual, 
    DateReport as fecha,
    Players as jugadores,
    TotalWager as ingreso,
    TotalOut as premio,
    GrossRevenue as valor,
    0 as ajuste,
    '' as observacion
    FROM beneficio_temporal
    WHERE beneficio_temporal.id_beneficio_mensual = :id_beneficio_mensual AND beneficio_temporal.Total = ''");
    $query->execute([":id_beneficio_mensual" => $benMensual->id_beneficio_mensual]);

    $query = $pdo->prepare("DELETE FROM beneficio_temporal WHERE id_beneficio_mensual = :id_beneficio_mensual");
    $query->execute([":id_beneficio_mensual" => $benMensual->id_beneficio_mensual]);

    //Lo updateo por SQL porque son DECIMAL y no se si hay error de casteo si lo hago en PHP (pasa a float?)
    $query = $pdo->prepare("UPDATE beneficio_mensual bm,
    (
      SELECT SUM(b.jugadores) as jugadores,SUM(b.ingreso) as ingreso,SUM(b.premio) as premio,SUM(b.valor) as valor
      FROM beneficio b
      WHERE b.id_beneficio_mensual = :id_beneficio_mensual1
      GROUP BY b.id_beneficio_mensual
    ) total
    SET bm.jugadores = IFNULL(total.jugadores,0),bm.ingreso = IFNULL(total.ingreso,0),bm.premio = IFNULL(total.premio,0),bm.valor = IFNULL(total.valor,0)
    WHERE bm.id_beneficio_mensual = :id_beneficio_mensual2");
    $query->execute([":id_beneficio_mensual1" => $benMensual->id_beneficio_mensual,":id_beneficio_mensual2" => $benMensual->id_beneficio_mensual]);

    //Actualizo la entidad
    $benMensual = BeneficioMensual::find($benMensual->id_beneficio_mensual);

    DB::connection()->enableQueryLog();

    $pdo = null;

    return [ 'id_beneficio_mensual' => $benMensual->id_beneficio_mensual, 'fecha' => $benMensual->fecha, 
    'bruto' => $benMensual->bruto, 'dias' => $benMensual->beneficios()->count()]; 
  }
}
