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

  public function previewImportacion(Request $request){
    if($request->tipo_importacion == 'beneficio_juegos'){
      $benMensual = BeneficioMensual::find($request->id);
      if(is_null($benMensual)) return response()->json("No existe el beneficio",422);
      return ['fecha' => $benMensual->fecha, 'plataforma' => $benMensual->plataforma, 'tipo_moneda'  => $benMensual->tipo_moneda,
      'cant_detalles' => $benMensual->beneficios()->count(),
      'detalles'=> $benMensual->beneficios()
      ->select('fecha','jugadores','depositos','retiros','apuesta','premio','beneficio')
      ->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'beneficio_poker'){
      $benMensual = BeneficioMensualPoker::find($request->id);
      if(is_null($benMensual)) return response()->json("No existe el beneficio",422);
      return ['fecha' => $benMensual->fecha, 'plataforma' => $benMensual->plataforma, 'tipo_moneda'  => $benMensual->tipo_moneda,
      'cant_detalles' => $benMensual->beneficios()->count(),
      'detalles'=> $benMensual->beneficios()
      ->select('fecha','jugadores','mesas','buy','rebuy','total_buy','cash_out','otros_pagos','total_bonus','utilidad')
      ->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'producido_juegos'){
      $producido = Producido::find($request->id);
      if(is_null($producido)) return response()->json("No existe el producido",422);
      return ['fecha' => $producido->fecha, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
      'cant_detalles' => $producido->detalles()->count(),
      'detalles'=> $producido->detalles()
      ->select('cod_juego','categoria','jugadores',
      'apuesta_efectivo','apuesta_bono','apuesta',
      'premio_efectivo','premio_bono','premio',
      'beneficio_efectivo','beneficio_bono','beneficio')
      ->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'producido_jugadores'){
      $producido = ProducidoJugadores::find($request->id);
      if(is_null($producido)) return response()->json("No existe el producido",422);
      return ['fecha' => $producido->fecha, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
      'cant_detalles' => $producido->detalles()->count(),
      'detalles'=> $producido->detalles()
      ->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'producido_poker'){
      $producido = ProducidoPoker::find($request->id);
      if(is_null($producido)) return response()->json("No existe el producido",422);
      return ['fecha' => $producido->fecha, 'plataforma' => $producido->plataforma, 'tipo_moneda'  => $producido->tipo_moneda,
      'cant_detalles' => $producido->detalles()->count(),
      'detalles' => $producido->detalles()
      ->select('cod_juego','categoria','jugadores','droop','utilidad')
      ->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'estado_juegos'){
      $importacion = ImportacionEstadoJuego::find($request->id);
      if(is_null($importacion)) return response()->json("No existe la importación",422);
      $detalles = DB::table('importacion_estado_juego as iej')
      ->select('dj.codigo','dj.nombre','dj.categoria','ej.estado','dj.tecnologia')
      ->join('estado_juego_importado as ej','ej.id_importacion_estado_juego','=','iej.id_importacion_estado_juego')
      ->join('datos_juego_importado as dj','dj.id_datos_juego_importado','=','ej.id_datos_juego_importado')
      ->where('iej.id_importacion_estado_juego','=',$request->id);
      return ['fecha' => $importacion->fecha_importacion, 'plataforma' => $importacion->plataforma, 'tipo_moneda'  => null,
      'cant_detalles' => (clone $detalles)->count(),
      'detalles' => (clone $detalles)->skip($request->page*$request->size)->take($request->size)->get()];
    }
    else if($request->tipo_importacion == 'estado_jugadores'){
      $importacion = ImportacionEstadoJugador::find($request->id);
      if(is_null($importacion)) return response()->json("No existe la importación",422);
    
      $detalles = DB::table('jugador as j')
      ->select('j.*')
      ->where('j.id_plataforma','=',$importacion->id_plataforma)
      ->where('j.fecha_importacion','<=',$importacion->fecha_importacion)
      ->where(function($q) use ($importacion){
        return $q->where('j.valido_hasta','>=',$importacion->fecha_importacion)
        ->orWhereNull('j.valido_hasta');
      })
      ->orderBy('j.codigo','asc')
      ->skip($request->page*$request->size)->take($request->size)->get();
          
      $cant_detalles = DB::table(DB::raw('jugador as j FORCE INDEX(unq_jugador_importacion)'))
      ->selectRaw('COUNT(distinct j.codigo) as total')
      ->where('j.id_plataforma','=',$importacion->id_plataforma)
      ->where('j.fecha_importacion','<=',$importacion->fecha_importacion)
      ->where(function($q) use ($importacion){
        return $q->where('j.valido_hasta','>=',$importacion->fecha_importacion)
        ->orWhereNull('j.valido_hasta');
      })
      ->groupBy('j.id_plataforma')->first()->total;
      
      return ['fecha' => $importacion->fecha_importacion, 'plataforma' => $importacion->plataforma, 'tipo_moneda'  => null,
      'cant_detalles' => $cant_detalles,
      'detalles' => $detalles];
    }
    else return response()->json("No existe",422);
  }

  public function buscar(Request $request){
    $plataformas = [];
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->plataformas as $plataforma) {
      $plataformas[] = $plataforma->id_plataforma;
    }

    $reglas = [];
    $es_estado = strpos($request->tipo_archivo,'estado') !== false;
    if(isset($request->id_tipo_moneda) && $request->id_tipo_moneda !=0 && !$es_estado){
      $reglas[]=['tipo_moneda.id_tipo_moneda','=', $request->id_tipo_moneda];
    }
    if(isset($request->id_plataforma) && $request->id_plataforma !=0){
      $reglas[]=['plataforma.id_plataforma','=',$request->id_plataforma];
    }

    $sort_by = $request->sort_by;
    $resultados = ["data" => [],"total" => 0];
    if($request->tipo_archivo == "producido_juegos"){
      $resultados = DB::table('producido')->select('producido.id_producido as id','producido.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "producido_jugadores"){
      $resultados = DB::table('producido_jugadores')->select('producido_jugadores.id_producido_jugadores as id',
      'producido_jugadores.fecha as fecha','plataforma.nombre as plataforma',
      'tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido_jugadores.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido_jugadores.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "beneficio_juegos"){
      $resultados = DB::table('beneficio_mensual')->select('beneficio_mensual.id_beneficio_mensual as id','beneficio_mensual.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','beneficio_mensual.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','beneficio_mensual.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "producido_poker"){
      $resultados = DB::table('producido_poker')->select('producido_poker.id_producido_poker as id','producido_poker.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','producido_poker.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','producido_poker.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == "beneficio_poker"){
      $resultados = DB::table('beneficio_mensual_poker')->select('beneficio_mensual_poker.id_beneficio_mensual_poker as id','beneficio_mensual_poker.fecha as fecha'
      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','plataforma.id_plataforma')
      ->join('plataforma','beneficio_mensual_poker.id_plataforma','=','plataforma.id_plataforma')
      ->join('tipo_moneda','beneficio_mensual_poker.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda');
    }
    else if($request->tipo_archivo == 'estado_juegos'){
      $resultados = DB::table('importacion_estado_juego')->select('id_importacion_estado_juego as id','fecha_importacion as fecha'
      ,'plataforma.nombre as plataforma',DB::raw('"-" as tipo_moneda'),'plataforma.id_plataforma')
      ->join('plataforma','importacion_estado_juego.id_plataforma','=','plataforma.id_plataforma');
    }
    else if($request->tipo_archivo == 'estado_jugadores'){
      $resultados = DB::table('importacion_estado_jugador')->select('id_importacion_estado_jugador as id','fecha_importacion as fecha'
      ,'plataforma.nombre as plataforma',DB::raw('"-" as tipo_moneda'),'plataforma.id_plataforma')
      ->join('plataforma','importacion_estado_jugador.id_plataforma','=','plataforma.id_plataforma');
    }
    else return $resultados;

    $resultados = $resultados->where($reglas)
    ->whereIn('plataforma.id_plataforma' , $plataformas);
    if(!empty($request->fecha)){
      $fecha = explode("-",$request->fecha);
      $fecha_col = $es_estado? 'fecha_importacion' : 'fecha';
      $resultados = $resultados->whereYear($fecha_col , '=' ,$fecha[0])->whereMonth($fecha_col,'=', $fecha[1]);
    }
    $ordenar = $sort_by && ($sort_by['columna'] != 'tipo_moneda.descripcion' || ($sort_by['columna'] == 'tipo_moneda.descripcion' && !$es_estado));
    $resultados = $resultados->when($ordenar,function($query) use ($sort_by){
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
      $estado_juegos = [];
      $estado_jugadores = [];
      $hay_estado_juego_fecha   = ImportacionEstadoJuego::where(  [['fecha_importacion' , $fecha],['id_plataforma', $id_plataforma]])->count() >= 1;
      $hay_estado_jugador_fecha = ImportacionEstadoJugador::where([['fecha_importacion' , $fecha],['id_plataforma', $id_plataforma]])->count() >= 1;
      foreach($beneficiosMensuales as $idmon => $bMensual){
        $producido[$idmon] = Producido::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $prod_jug[$idmon]  = ProducidoJugadores::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $beneficio[$idmon] = !is_null($bMensual) && ($bMensual->beneficios()->where('fecha',$fecha)->count() >= 1);
        $prod_poker[$idmon] = ProducidoPoker::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , $idmon]])->count() >= 1;
        $benef_poker[$idmon] = !is_null($beneficiosMensualesPoker[$idmon]) && ($beneficiosMensualesPoker[$idmon] ->beneficios()->where('fecha',$fecha)->count() >= 1);
        $estado_juegos[$idmon] = $hay_estado_juego_fecha;
        $estado_jugadores[$idmon] = $hay_estado_jugador_fecha;
      }
      $dia['producido'] = $producido;
      $dia['prod_jug']  = $prod_jug;
      $dia['beneficio'] = $beneficio;
      $dia['prod_poker'] = $prod_poker;
      $dia['benef_poker'] = $benef_poker;
      $dia['estado_juegos'] = $estado_juegos;
      $dia['estado_jugadores'] = $estado_jugadores;
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
      Producido::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducido($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes(['producido']);
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
      ProducidoJugadores::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducidoJugadores($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes(['producido_jugadores']);
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
      ProducidoPoker::where([['fecha','=',$request->fecha],['id_plataforma','=',$request->id_plataforma],['id_tipo_moneda','=',$request->id_tipo_moneda]])->get();
      $ret = LectorCSVController::getInstancia()->importarProducidoPoker($request->archivo,$request->fecha,$request->id_plataforma,$request->id_tipo_moneda);
      CacheController::getInstancia()->invalidarDependientes(['producido_poker']);
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
        $this->eliminarEstadoJugadores($i->id_importacion_estado_jugador);
      }
      $importacion = LectorCSVController::getInstancia()->importarJugadores($request->archivo,$request->md5,$request->fecha,$request->id_plataforma);
      return 1;
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
        $this->eliminarEstadoJuegos($i->id_importacion_estado_juego);
      }

      return LectorCSVController::getInstancia()->importarEstadosJuegos($request->archivo,$request->md5,$request->fecha,$request->id_plataforma);
    });
  }

  public function eliminarEstadoJugadores($id){
    return DB::transaction(function() use ($id){
      $imp = ImportacionEstadoJugador::find($id);
      $prox_imp = ImportacionEstadoJugador::where([
        ['id_plataforma','=',$imp->id_plataforma],
        ['fecha_importacion','>',$imp->fecha_importacion]
      ])->orderBy('fecha_importacion','asc')->first();

      /*
      1) Para todos los jugadores con fecha_importacion = A_ELIMINAR
      Me fijo jugador si existe fecha_importacion = PROX_IMPORTACION
        Si existe -> la importacion proxima NO depende de este dato
          no hago nada
        Si no existe -> la importacion proxima depende de este dato de jugador
          Seteo la fecha_importacion en PROX_IMPORTACION

      2) Borro todos los jugadores que quedaron con fecha_importacion = A_ELIMINAR
      */
      if(!is_null($prox_imp)){
        $err = DB::statement("UPDATE jugador j
        LEFT JOIN jugador prox_j ON (
              j.codigo                 = prox_j.codigo 
          AND j.id_plataforma          = prox_j.id_plataforma 
          AND prox_j.fecha_importacion = ?
        )
        SET j.fecha_importacion = ?
        WHERE prox_j.id_jugador IS NULL
        AND j.id_plataforma     = ? 
        AND j.fecha_importacion = ?",
          [$prox_imp->fecha_importacion,$prox_imp->fecha_importacion,$imp->id_plataforma,$imp->fecha_importacion]
        );
        if(!$err){
          throw new \Exception('Error 1 al borrar jugadores');
        }
      }
      
      //Borro los que quedaron con fecha_importacion porque no se usan por la importacion posterior
      $err = DB::statement("DELETE FROM jugador
        WHERE id_plataforma = ? AND fecha_importacion = ?",
        [$imp->id_plataforma,$imp->fecha_importacion]
      );
      if(!$err){
        throw new \Exception('Error 2 al borrar jugadores');
      }
    
      //Borro la importacion
      $imp->delete();
            
      return 1;
    });
  }

  public function eliminarEstadoJuegos($id){
    return DB::transaction(function() use ($id){
      $importacion = ImportacionEstadoJuego::find($id);
      $importacion->estados()->delete();
      DB::table('juego_importado_temporal')->where('id_importacion_estado_juego','=',$id)->delete();
      //Borro los datos sin estados
      DB::table('datos_juego_importado')
      ->select('datos_juego_importado.*')
      ->whereRaw('NOT EXISTS(
        select id_estado_juego_importado
        from estado_juego_importado
        where estado_juego_importado.id_datos_juego_importado = datos_juego_importado.id_datos_juego_importado
      )')->delete();
      $importacion->delete();
      return 1;
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
