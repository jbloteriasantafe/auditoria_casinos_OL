<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Producido;
use App\ProducidoJugadores;
use App\ProducidoPoker;
use App\BeneficioMensual;
use App\BeneficioMensualPoker;
use App\ImportacionEstadoJugador;
use App\ImportacionEstadoJuego;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\LectorCSVController;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Plataforma;
use App\Http\Controllers\CacheController;

class ImportacionController extends Controller
{
  private static $atributos = [];
  private static $errores =       [
    'required' => 'El valor es requerido',
    'integer' => 'El valor no es un numero',
    'numeric' => 'El valor no es un numero',
    'exists' => 'El valor es invalido',
    'array' => 'El valor es invalido',
    'alpha_dash' => 'El valor tiene que ser alfanumérico opcionalmente con guiones',
    'regex' => 'El formato es incorrecto',
    'string' => 'El valor tiene que ser una cadena de caracteres',
    'string.min' => 'El valor es muy corto',
    'privilegios' => 'No puede realizar esa acción',
    'incompatibilidad' => 'El valor no puede ser asignado',
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

  public function previewBeneficio(Request $request){
    $benMensual = BeneficioMensual::find($request->id_beneficio_mensual);
    if(is_null($benMensual)) return response()->json("No existe el beneficio",422);
    return ['beneficio_mensual' => $benMensual, 'plataforma' => $benMensual->plataforma, 'tipo_moneda'  => $benMensual->tipo_moneda,
    'cant_detalles' => $benMensual->beneficios()->count(),
    'beneficios'=> $benMensual->beneficios()
    ->select('fecha','jugadores','depositos','retiros','apuesta','premio','beneficio')
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }

  public function previewBeneficioPoker(Request $request){
    $benMensual = BeneficioMensualPoker::find($request->id_beneficio_mensual_poker);
    if(is_null($benMensual)) return response()->json("No existe el beneficio",422);
    return ['beneficio_mensual_poker' => $benMensual, 'plataforma' => $benMensual->plataforma, 'tipo_moneda'  => $benMensual->tipo_moneda,
    'cant_detalles' => $benMensual->beneficios()->count(),
    'beneficios'=> $benMensual->beneficios()
    ->select('fecha','jugadores','mesas','buy','rebuy','total_buy','cash_out','otros_pagos','total_bonus','utilidad')
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }


  public function previewProducido(Request $request){
    $producido = Producido::find($request->id_producido);
    if(is_null($producido)) return response()->json("No existe el producido",422);
    return ['producido' => $producido, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
    'cant_detalles' => $producido->detalles()->count(),
    'detalles_producido'=> $producido->detalles()
    ->select('cod_juego','categoria','jugadores',
    'apuesta_efectivo','apuesta_bono','apuesta',
    'premio_efectivo','premio_bono','premio',
    'beneficio_efectivo','beneficio_bono','beneficio')
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }

  public function previewProducidoJugadores(Request $request){
    $producido = ProducidoJugadores::find($request->id_producido_jugadores);
    if(is_null($producido)) return response()->json("No existe el producido",422);
    return ['producido_jugadores' => $producido, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
    'cant_detalles' => $producido->detalles()->count(),
    'detalles_producido_jugadores'=> $producido->detalles()
    ->skip($request->page*$request->size)->take($request->size)->get()];
  }

  public function previewProducidoPoker(Request $request){
    $producido = ProducidoPoker::find($request->id_producido_poker);
    if(is_null($producido)) return response()->json("No existe el producido",422);
    return ['producido_poker' => $producido, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
    'cant_detalles' => $producido->detalles()->count(),
    'detalles_producido'=> $producido->detalles()
    ->select('cod_juego','categoria','jugadores','droop','utilidad')
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
    if($request->tipo_archivo == "PRODUCIDO"){
      $resultados = DB::table('producido')->select('producido.id_producido as id','producido.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "PRODJUG"){
      $resultados = DB::table('producido_jugadores')->select('producido_jugadores.id_producido_jugadores as id',
      'producido_jugadores.fecha as fecha','plataforma.nombre as plataforma',
      'tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido_jugadores.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido_jugadores.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "BENEFICIO"){
      $resultados = DB::table('beneficio_mensual')->select('beneficio_mensual.id_beneficio_mensual as id','beneficio_mensual.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','beneficio_mensual.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','beneficio_mensual.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "PRODPOKER"){
      $resultados = DB::table('producido_poker')->select('producido_poker.id_producido_poker as id','producido_poker.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido_poker.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido_poker.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "BENEFPOKER"){
      $resultados = DB::table('beneficio_mensual_poker')->select('beneficio_mensual_poker.id_beneficio_mensual_poker as id','beneficio_mensual_poker.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','beneficio_mensual_poker.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','beneficio_mensual_poker.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
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
    $beneficiosMensualesPoker = [];
    foreach(TipoMoneda::all() as $moneda){
      $beneficiosMensuales[$moneda->id_tipo_moneda] = BeneficioMensual::where([
        ['fecha',$fecha],['id_plataforma',$id_plataforma],['id_tipo_moneda',$moneda->id_tipo_moneda]
      ])->first();
      $beneficiosMensualesPoker[$moneda->id_tipo_moneda] = BeneficioMensualPoker::where([
        ['fecha',$fecha],['id_plataforma',$id_plataforma],['id_tipo_moneda',$moneda->id_tipo_moneda]
      ])->first();
    }

    $mes = date('m',strtotime($fecha));
    $arreglo = [];
    while(date('m',strtotime($fecha)) == $mes){
      $producido = [];
      $prod_jug = [];
      $beneficio = [];
      $prod_poker = [];
      $benef_poker = [];
      foreach($beneficiosMensuales as $idmon => $bMensual){
        $producido[$idmon] = Producido::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $prod_jug[$idmon]  = ProducidoJugadores::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $beneficio[$idmon] = !is_null($bMensual) && ($bMensual->beneficios()->where('fecha',$fecha)->count() >= 1);
        $prod_poker[$idmon] = ProducidoPoker::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $benef_poker[$idmon] = !is_null($beneficiosMensualesPoker[$idmon]) && ($beneficiosMensualesPoker[$idmon] ->beneficios()->where('fecha',$fecha)->count() >= 1);
      }
      $dia['producido'] = $producido;
      $dia['prod_jug']  = $prod_jug;
      $dia['beneficio'] = $beneficio;
      $dia['prod_poker'] = $prod_poker;
      $dia['benef_poker'] = $benef_poker;
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
    ], array(), self::$atributos)->after(function($validator){})->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $viejos = Producido::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducido($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes('producido');
    });
    return $ret;
  }

  public function importarProducidoJugadores(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){})->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $viejos = ProducidoJugadores::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducidoJugadores($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes('producido');
    });
    return $ret;
  }

  public function importarProducidoPoker(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){})->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $viejos = ProducidoPoker::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducidoPoker($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes('producido');
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

  public function importarBeneficioPoker(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ret = null;
    DB::transaction(function() use ($request,&$ret){
      $ret = LectorCSVController::getInstancia()->importarBeneficioPoker($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
    });
    return $ret;
  }

  public function importarJugadores(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|exists:plataforma,id_plataforma',
        'fecha' => 'required|date',
        'archivo' => 'required|mimes:csv,txt',
        'md5' => 'required|string|max:32',
    ], self::$errores, self::$atributos)->after(function($validator){})->validate();

    return DB::transaction(function() use ($request){
      $importaciones = ImportacionEstadoJugador::where([['fecha_importacion','=',$request->fecha],['id_plataforma','=',$request->id_plataforma]])->get();
      foreach($importaciones as $i){
        $i->estados()->delete();
        DB::table('jugadores_temporal')->where('id_importacion_estado_jugador','=',$i->id_importacion_estado_jugador)->delete();
      }
      //Borro los datos sin estados
      DB::table('datos_jugador')
      ->select('datos_jugador.*')
      ->whereRaw('NOT EXISTS(
        select id_estado_jugador
        from estado_jugador
        where estado_jugador.id_datos_jugador = datos_jugador.id_datos_jugador
      )')->delete();

      foreach($importaciones as $i){
        $i->delete();
      }

      return LectorCSVController::getInstancia()->importarJugadores($request->archivo,$request->md5,$request->fecha,$request->id_plataforma);
    });
  }

  public function importarEstadosJuegos(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|exists:plataforma,id_plataforma',
        'fecha' => 'required|date',
        'archivo' => 'required|mimes:csv,txt',
        'md5' => 'required|string|max:32',
    ], self::$errores, self::$atributos)->after(function($validator){})->validate();

    return DB::transaction(function() use ($request){
      $importaciones = ImportacionEstadoJuego::where([['fecha_importacion','=',$request->fecha],['id_plataforma','=',$request->id_plataforma]])->get();
      foreach($importaciones as $i){
        $i->estados()->delete();
        DB::table('juego_importado_temporal')->where('id_importacion_estado_juego','=',$i->id_importacion_estado_juego)->delete();
      }
      //Borro los datos sin estados
      DB::table('datos_juego_importado')
      ->select('datos_juego_importado.*')
      ->whereRaw('NOT EXISTS(
        select id_estado_juego_importado
        from estado_juego_importado
        where estado_juego_importado.id_datos_juego_importado = datos_juego_importado.id_datos_juego_importado
      )')->delete();

      foreach($importaciones as $i){
        $i->delete();
      }

      return LectorCSVController::getInstancia()->importarEstadosJuegos($request->archivo,$request->md5,$request->fecha,$request->id_plataforma);
    });
  }

  public function hashearArchivo(Request $request,$tipo){
    if($tipo != 'md5') return 'SIN IMPLEMENTAR';
    $file = $request->archivo->getRealPath();
    $content = file_get_contents($file);
    $resultado = DB::select(DB::raw('SELECT md5(?) as hash'),[$content]);
    return $resultado[0]->hash;
  }
}
