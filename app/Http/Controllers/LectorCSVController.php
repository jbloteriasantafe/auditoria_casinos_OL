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
use App\DetalleContadorHorario;
use App\DetalleProducido;
use App\TipoMoneda;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\ProducidoController;
use App\Http\Controllers\BeneficioController;

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

  // importarProducido crea nuevo producido
  // inserta en una tabla temporal , formateando a valores validos
  // luego toma esta tabla , hace un join con maquinas para tomar solo las mtm del maestro validas
  // y va generando los detalles producidos
  // es posbile que en el archivo no envien juegos (diversos motivos) en ese caso se fuerza a que tenga
  // producido 0 y se genera un log en el archivo de producido
  public function importarProducido($archivoCSV,$fecha,$plataforma){
    $producido = new Producido;
    $producido->id_plataforma = $plataforma;
    $producido->fecha = $fecha;
    $producido->save();

    $producidos_viejos = DB::table('producido')->where([
      ['id_producido','<>',$producido->id_producido],['id_plataforma','=',$producido->id_plataforma],['fecha','=',$producido->fecha]]
    )->get();


    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();

    if($producidos_viejos != null){
      foreach($producidos_viejos as $prod){
        $query = sprintf(" DELETE FROM detalle_producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);

        $query = sprintf(" DELETE FROM producido
                           WHERE id_producido = '%d'
                           ",$prod->id_producido);
        $pdo->exec($query);
      }
    }


    $path = $archivoCSV->getRealPath();

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
                       TotalWager = REPLACE(@TotalWager,',','.'),
                       GrossRevenueCash = REPLACE(@GrossRevenueCash,',','.'),
                       GrossRevenueBonus = REPLACE(@GrossRevenueBonus,',','.'),
                       GrossRevenue = REPLACE(@GrossRevenue,',','.')
                      ",$path,$producido->id_producido);

    $pdo->exec($query);

    $query = sprintf(" INSERT INTO detalle_producido (valor,cod_juego,id_producido)
    SELECT ((TotalWagerCash + TotalWagerBonus) - (GrossRevenueCash + GrossRevenueBonus)) as valor,GameId as cod_juego,id_producido
    FROM producido_temporal
    WHERE producido_temporal.id_producido = '%d'
    ",$producido->id_producido);

    $pdo->exec($query);
/*
    $query = sprintf(" DELETE FROM producido_temporal
                       WHERE id_producido = '%d'
                       ",$producido->id_producido);

    $pdo->exec($query);
*/
    DB::connection()->enableQueryLog();

    /*



    $query = sprintf(" DELETE FROM producido_temporal
                       WHERE id_producido = '%d'
                       ",$producido->id_producido);

    $pdo->exec($query);

    $cantidad_maquinas = Maquina::where('id_casino','=',$casino)->whereHas('estado_maquina',function($q){
                                  $q->where('descripcion','=','Ingreso')->orWhere('descripcion','=','ReIngreso');})->count();

   
    $cantidad_registros = DetalleProducido::where('id_producido','=',$producido->id_producido)->count();
    // implementacion para contemplar los casos donde ciertas maquinas no reportan producido
    $mtms= Maquina::select("id_maquina","nro_admin")
                    ->where("id_casino","=",$casino)
                    ->whereNull("deleted_at")
                    ->whereIn("id_estado_maquina",[1,2,4,5,7])
                    ->get();
    $cant_mtm_forzadas=0;
    $id_mtm_forzadas=array();
    foreach($mtms as $m){
      $cant=DetalleProducido::where("id_maquina","=",$m->id_maquina)
                            ->where("id_producido","=", $producido->id_producido)
                            ->count();

      if(!$cant){
        $daux= new DetalleProducido;
        $daux->valor=0;
        $daux->id_maquina=$m->id_maquina;
        $daux->id_producido=$producido->id_producido;
        $daux->save();
        $cant_mtm_forzadas=$cant_mtm_forzadas+1;
        array_push($id_mtm_forzadas,$m->id_maquina);
      }
    }
    $producido->cant_mtm_forzadas=$cant_mtm_forzadas;
    $producido->id_mtm_forzadas=implode(",",$id_mtm_forzadas);
    $producido->save();
  //fin de implementacion
    return ['id_producido' => $producido->id_producido,'fecha' => $producido->fecha,'casino' => $producido->casino->nombre,'cantidad_registros' => $cantidad_registros,'tipo_moneda' => Producido::find($producido->id_producido)->tipo_moneda->descripcion, 'cant_mtm_forzadas' => $cant_mtm_forzadas];*/
  }
  // importarBeneficioSantaFeMelincue se crea temporal insertando todos los valores del csv
  // solo se toma la linea de beneficio para insertar en la tabla real
  // luego se elimina los temporales
  public function importarBeneficioSantaFeMelincue($archivoCSV,$casino){

    $pdo = DB::connection('mysql')->getPdo();
    DB::connection()->disableQueryLog();
    $path = $archivoCSV->getRealPath();

    $cantidad_maquinas = Maquina::where('id_casino','=',$casino)->whereHas('estado_maquina',function($q){
                                   $q->where('descripcion','=','Ingreso')->orWhere('descripcion','=','ReIngreso');})->count();

    $query = sprintf("LOAD DATA local INFILE '%s'
                      INTO TABLE beneficio
                      FIELDS TERMINATED BY ';'
                      OPTIONALLY ENCLOSED BY '\"'
                      ESCAPED BY '\"'
                      LINES STARTING BY 'CTR' TERMINATED BY '\\n'
                      (@0,@1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13)
                      SET id_casino = '%d',
                              fecha = STR_TO_DATE(SUBSTRING(@1,5,8),'%s'),
                             coinin = CAST(REPLACE(@4,',','.') as DECIMAL(15,2)),
                            coinout = CAST(REPLACE(@5,',','.') as DECIMAL(15,2)),
                            jackpot = CAST(REPLACE(@6,',','.') as DECIMAL(15,2)),
                              valor = CAST(REPLACE(@9,',','.') as DECIMAL(15,2)),
              porcentaje_devolucion = 100*(CAST(REPLACE(@5,',','.') as DECIMAL(15,2)) + CAST(REPLACE(@6,',','.') as DECIMAL(15,2)))/(CAST(REPLACE(@4,',','.') as DECIMAL(15,2))),
                  cantidad_maquinas = '%d',
               promedio_por_maquina = CAST(REPLACE(@9,',','.') as DECIMAL(15,2))/'%d'
                      ",$path,$casino,"%Y%m%d",$cantidad_maquinas,$cantidad_maquinas);

    $pdo->exec($query);
    //usar query en vez de exec

    $ben = Beneficio::find(DB::table('beneficio')->max('id_beneficio'));
    if($ben != null){
      $fecha=explode("-", $ben->fecha);
      $beneficios = Beneficio::where([['id_beneficio','<>',$ben->id_beneficio],['id_casino','=',$casino]])
                              ->whereYear('fecha','=',$fecha[0])
                              ->whereMonth('fecha','=', $fecha[1])
                              ->get();
      //['fecha','=',$ben->fecha]])->get();
      if($beneficios != null){
        foreach($beneficios as $beneficio){
          $query = sprintf(" DELETE FROM beneficio
                             WHERE id_beneficio = '%d'
                             ",$beneficio->id_beneficio);
          $pdo->exec($query);
        }
      }
    }

    $pdo=null;


    return ['id_beneficio' => $ben->id_beneficio,'fecha' => $ben->fecha,'casino' => $ben->casino->nombre,'tipo_moneda' => $ben->tipo_moneda->descripcion];
  }
}
