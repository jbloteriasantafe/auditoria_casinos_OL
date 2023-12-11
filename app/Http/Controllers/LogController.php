<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthenticationController;

class LogController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new LogController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Log Actividades' , 'logActividades');
    //Agregar indices si estas querys se vuelven muy pesadas
    $usuarios = DB::table('usuario')->select('nombre')->distinct()
    ->whereNull('deleted_at')->get()->pluck('nombre')->sort();
    $tablas = DB::table('log')->select('tabla')->distinct()
    ->get()->pluck('tabla')->sort();
    $acciones = DB::table('log')->select('accion')->distinct()
    ->get()->pluck('accion')->sort();
    return view('seccionLogActividades',compact('usuarios','tablas','acciones'));
  }

  public function guardarLog($accion,$tabla,$id_entidad){
      $id_usuario = AuthenticationController::getInstancia()->obtenerIdUsuario();
      $log = new Log;
      $log->accion = $accion;
      $log->tabla = $tabla;
      $log->id_entidad = $id_entidad;
      $log->fecha = date_create();
      $log->usuario()->associate($id_usuario);
      $log->save();
      return $log;
  }

  public function obtenerLogActividad($id){
    $log = Log::find($id);
    return ['log' => $log,
            'usuario' => $log->usuario->nombre,
            'detalles' => $log->detalles];
  }

  public function buscarLogActividades(Request $request){
    $reglas = Array();
    if(!empty($request->usuario))
      $reglas[]=['usuario.nombre', 'like', '%'.$request->usuario.'%'];
    if(!empty($request->tabla))
      $reglas[]=['log.tabla', 'like', '%'.$request->tabla.'%'];
    if(!empty($request->accion))
      $reglas[]=['log.accion', 'like', '%'.$request->accion.'%'];
    if(!empty($request->fecha)){
      $fechaMax = date("Y-m-d", strtotime("+1 day", strtotime($request->fecha)));
      $reglas[]=['log.fecha', '>=', $request->fecha];
      $reglas[]=['log.fecha', '<=', $fechaMax];
    }

    $sort_by = $request->sort_by;
     $resultados=DB::table('log')
    ->select('log.*','usuario.nombre')
    ->join('usuario','usuario.id_usuario','=','log.id_usuario')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)->paginate($request->page_size);

    return $resultados;
  }
}
