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
    $query = sprintf(" INSERT INTO detalle_producido 
    (id_producido,
    cod_juego,
    categoria,
    jugadores,
    TotalWagerCash,
    TotalWagerBonus,
    TotalWager,
    GrossRevenueCash,
    GrossRevenueBonus,
    GrossRevenue,
    valor)
    SELECT 
    id_producido,
    GameId as cod_juego,
    GameCategory as categoria,
    Players as jugadores,
    TotalWagerCash,
    TotalWagerBonus,
    TotalWager,
    GrossRevenueCash,
    GrossRevenueBonus,
    GrossRevenue,
    ((TotalWagerCash + TotalWagerBonus) - (GrossRevenueCash + GrossRevenueBonus)) as valor
    FROM producido_temporal
    WHERE producido_temporal.id_producido = '%d'
    ",$producido->id_producido);

    $pdo->exec($query);

    $query = sprintf(" DELETE FROM producido_temporal
                       WHERE id_producido = '%d'
                       ",$producido->id_producido);

    $pdo->exec($query);

    DB::connection()->enableQueryLog();

    $duplicados = DB::table('detalle_producido')->select('cod_juego',DB::raw('COUNT(distinct id_detalle_producido) as veces'))
    ->where('id_producido','=',$producido->id_producido)
    ->groupBy('cod_juego')
    ->havingRaw('COUNT(distinct id_detalle_producido) > 1')->get()->count();

    /*$inhabilitados_reportando = 999;//@TODO Agregar estado a plataforma_tiene_juego
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
    $benMensual->bruto = 0;
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
    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE beneficio_temporal
                      FIELDS TERMINATED BY ','
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES TERMINATED BY '\\r\\n'
                      IGNORE 1 LINES
                      (@Total,@DateReport,@Currency,@TotalRegistrations,@Verified,@TotalVerified,@Players,@TotalDeposits,@TotalWithdrawals,@TotalBonus,@TotalManualAdjustments,@TotalVPoints,@TotalWager,@TotalOut,@GrossRevenue,@lastupdated)
                       SET id_beneficio_mensual = '%d',
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
                       lastupdated = @lastupdated
                      ",$path,$benMensual->id_beneficio_mensual);

    $pdo->exec($query);

    $query = sprintf(" INSERT INTO beneficio 
    (
      id_beneficio_mensual,
      fecha,
      jugadores,
      TotalWager,
      TotalOut,
      GrossRevenue
    )
    SELECT
    id_beneficio_mensual, 
    DateReport as fecha,
    Players as jugadores,
    TotalWager,
    TotalOut,
    GrossRevenue
    FROM beneficio_temporal
    WHERE beneficio_temporal.id_beneficio_mensual = '%d' AND beneficio_temporal.Total = ''
    ",$benMensual->id_beneficio_mensual);//La ultima comparacion en el WHERE es para ignorar la linea final con el total

    $pdo->exec($query);

    $query = sprintf("DELETE FROM beneficio_temporal
    WHERE id_beneficio_mensual = '%d'
    ",$benMensual->id_beneficio_mensual);

    $pdo->exec($query);

    //Lo updateo por SQL porque son DECIMAL y no se si hay error de casteo si lo hago en PHP (pasa a float?)
    //@TODO: Revisar si esta bien calculado el bruto
    $query = sprintf("UPDATE beneficio_mensual bm
    SET bm.bruto = IFNULL((
      SELECT SUM(b.TotalWager - b.TotalOut)
      FROM beneficio b
      WHERE b.id_beneficio_mensual = bm.id_beneficio_mensual
      GROUP BY b.id_beneficio_mensual
    ),0)
    WHERE bm.id_beneficio_mensual = '%d'",$benMensual->id_beneficio_mensual);
    $pdo->exec($query);
    //Actualizo la entidad
    $benMensual = BeneficioMensual::find($benMensual->id_beneficio_mensual);

    DB::connection()->enableQueryLog();

    $pdo = null;

    return [ 'id_beneficio_mensual' => $benMensual->id_beneficio_mensual, 'fecha' => $benMensual->fecha, 
    'bruto' => $benMensual->bruto, 'dias' => $benMensual->beneficios()->count()]; 
  }
}
