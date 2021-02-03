<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContadorHorario;
use App\Producido;
use App\Beneficio;
use App\BeneficioMensual;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RelevamientoController;
use App\Http\Controllers\LectorCSVController;
use App\Relevamiento;
use App\DetalleRelevamiento;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Plataforma;

use App\Services\LengthPager;

class ImportacionController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia() {
    if(!isset(self::$instance)) {
      self::$instance = new ImportacionController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Importaciones' , 'importaciones');
    return view('seccionImportaciones', ['tipoMoneda' => TipoMoneda::all(), 'plataformas' => UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas]);
  }

  public function previewBeneficios(Request $request){
    $benMensual = BeneficioMensual::find($request->id_beneficio_mensual);
    if(is_null($benMensual)) return response()->json("No existe el beneficio",422);
    return ['beneficio_mensual' => $benMensual, 'plataforma' => $benMensual->plataforma, 'tipo_moneda'  => $benMensual->tipo_moneda,
    'cant_detalles' => $benMensual->beneficios()->count(),
    'beneficios'=> $benMensual->beneficios()
    ->select('fecha','jugadores','TotalWager','TotalOut','GrossRevenue')
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }

  public function previewProducidos(Request $request){
    $producido = Producido::find($request->id_producido);
    if(is_null($producido)) return response()->json("No existe el producido",422);
    return ['producido' => $producido, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
    'cant_detalles' => $producido->detalles()->count(),
    'detalles_producido'=> $producido->detalles()
    ->select('cod_juego','categoria','jugadores','TotalWagerCash','TotalWagerBonus','TotalWager','GrossRevenueCash','GrossRevenueBonus','GrossRevenue','valor')
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }

  public function buscar(Request $request){
    $plataformas = [];
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->plataformas as $plataforma) {
      $plataformas[] = $plataforma->id_plataforma;
    }

    $reglas = [];
    if(isset($request->id_tipo_moneda) && $request->id_tipo_moneda !=0){
      $reglas[]=['tipo_moneda.id_tipo_moneda','=', $request->id_tipo_moneda];
    }
    if(isset($request->id_plataforma) && $request->id_plataforma !=0){
      $reglas[]=['plataforma.id_plataforma','=',$request->id_plataforma];
    }

    $sort_by = $request->sort_by;
    $resultados = ["data" => [],"total" => 0];
    if($request->tipo_archivo == 2){
      $resultados = DB::table('producido')->select('producido.id_producido as id','producido.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == 3){
      $resultados = DB::table('beneficio_mensual')->select('beneficio_mensual.id_beneficio_mensual as id','beneficio_mensual.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','beneficio_mensual.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','beneficio_mensual.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else return $resultados;
    $resultados = $resultados->where($reglas)
    ->whereIn('plataforma.id_plataforma' , $plataformas);
    if(!empty($request->fecha)){
      $fecha = explode("-",$request->fecha);
      $resultados = $resultados->whereYear('fecha' , '=' ,$fecha[0])->whereMonth('fecha','=', $fecha[1]);
    }
    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
                  return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->paginate($request->page_size);
    return  $resultados;
  }

  public function estadoImportacionesDePlataforma($id_plataforma,$fecha_busqueda = null,$orden = 'desc'){
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make([
         'id_plataforma' => $id_plataforma,
       ],
       [
         'id_plataforma' => 'required|exists:plataforma,id_plataforma' ,
       ] , array(), self::$atributos)->after(function ($validator){

    })->validate();

    $fecha = is_null($fecha_busqueda)? date('Y-m-d') : $fecha_busqueda;

    $fecha = new \DateTime($fecha);
    $fecha->modify('first day of this month');
    $fecha = $fecha->format('Y-m-d');

    $beneficiosMensuales = [];
    foreach(TipoMoneda::all() as $moneda){
      $beneficiosMensuales[$moneda->id_tipo_moneda] = BeneficioMensual::where([
        ['fecha',$fecha],['id_plataforma',$id_plataforma],['id_tipo_moneda',$moneda->id_tipo_moneda]
      ])->first();
    }

    $mes = date('m',strtotime($fecha));
    $arreglo = [];
    while(date('m',strtotime($fecha)) == $mes){
      $producido = [];
      $beneficio = [];
      foreach($beneficiosMensuales as $idmon => $bMensual){
        $producido[$idmon] = Producido::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $beneficio[$idmon] = !is_null($bMensual) && ($bMensual->beneficios()->where('fecha',$fecha)->count() >= 1);
      }
      $dia['producido'] = $producido;
      $dia['beneficio'] = $beneficio;
      $dia['fecha'] = $fecha;
      $arreglo[] = $dia;
      $fecha = date('Y-m-d' , strtotime($fecha . ' + 1 days'));
    }
    if($orden == 'asc'){
      $arreglo = array_reverse($arreglo);
    }
    return ['arreglo' => $arreglo];
  }

  public function importarProducido(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
        if($validator->getData()['fecha'] != null){
          $reglas = Array();
          $reglas[]=['fecha','=',$validator->getData()['fecha']];
          $reglas[]=['id_plataforma','=',$validator->getData()['id_plataforma']];
          if($validator->getData()['id_tipo_moneda'] != null){
            $reglas[]=['id_tipo_moneda','=',$validator->getData()['id_tipo_moneda']];
          }
          if(Producido::where($reglas)->count() > 0){
            //$validator->errors()->add('producido_validado', 'El Producido para esa fecha ya está validado y no se puede reimportar.');
          }
        }
    })->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $ret = LectorCSVController::getInstancia()->importarProducido($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
    });
    return $ret;
  }

  public function importarBeneficio(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $ret = LectorCSVController::getInstancia()->importarBeneficio($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
    });
    return $ret;
  }
}
