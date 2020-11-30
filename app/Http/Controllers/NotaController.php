<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Expediente;
use App\Disposicion;
use App\Plataforma;
use App\Nota;
use Validator;

class NotaController extends Controller
{
  private static $atributos = [
    'id_expediente' => 'Expediente',
    'id_estado_juego' => 'Estado del juego',
    'id_plataforma' => 'Plataforma',
    'fecha' => 'Fecha de creación de nota',
    'disposiciones' => 'Disposiciones',
    'disposiciones.*.nro_disposicion' => 'Nro Disposición',
    'disposiciones.*.nro_disposicion_anio' => 'Nro Disposición Año'
  ];
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new NotaController();
      }
      return self::$instance;
  }

  public function buscarTodoNotas(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $plataformas = array();
      foreach($usuario->plataformas as $p){
        $plataformas[] = $p->id_plataforma;
      }
      $notas=DB::table('nota')
                      ->join('expediente' , 'expediente.id_expediente' ,'=' , 'nota.id_expediente')
                      ->join('expediente_tiene_plataforma','expediente_tiene_plataforma.id_expediente','=','expediente.id_expediente')
                      ->join('plataforma', 'expediente_tiene_plataforma.id_plataforma', '=', 'plataforma.id_plataforma')
                      ->leftJoin('estado_juego','estado_juego.id_estado_juego','=','nota.id_estado_juego')
                      ->whereIn('plataforma.id_plataforma' ,$plataformas)
                      ->where('es_disposicion','=',0)
                      ->orderBy('nota.identificacion','asc')
                      ->take(30)
                      ->get();

      $plataformas = Plataforma::all();
      return view('seccionNotasExpediente' , ['notas' => $notas , 'plataformas' => $plataformas]);
  }

  public function guardarNota($request, $id_expediente, $id_plataforma)// se usa desde expedienteController
  {
    $nota = new Nota;
    $nota->expediente()->associate($id_expediente);
    $nota->plataforma()->associate($id_plataforma); //asumiendo que los expedientes anuales son uno por plataforma copio el id_plataforma del expediente
    $nota->es_disposicion = 0;
    $nota->fecha = $request['fecha'];
    $nota->detalle = $request['detalle'];
    $nota->identificacion = $request['identificacion'];
    $nota->save();

    if(!empty($request['id_estado_juego']) || $request['id_estado_juego']!= 0 ){
      $nota->estado_juego()->associate($request['id_estado_juego']);
      $nota->save();
    }
    $nota->save();
  }

  public function guardarNotaParaDisposicion($id_expediente, $id_plataforma,$nro_disposicion,$id_estado_juego)// se usa desde expedienteController
  {
    $nota = new Nota;
    $nota->expediente()->associate($id_expediente);
    $nota->plataforma()->associate($id_plataforma); //asumiendo que los expedientes anuales son uno por plataforma copio el id_plataforma del expediente
    $nota->estado_juego()->associate($id_estado_juego);
    $nota->fecha = date('Y-m-d');
    $nota->detalle = $nro_disposicion;
    $nota->identificacion = 'Disposición Nro '.$nro_disposicion;
    $nota->es_disposicion = 1;
    $nota->save();
    return $nota->id_nota;
  }

  public function eliminarNota($id)
  {
    $nota = Nota::find($id);
    $disposiciones = $nota->disposiciones;
    if(!empty($disposiciones)){
      foreach($disposiciones as $disposicion){
        DisposicionController::getInstancia()->eliminarDisposicion($disposicion->id_disposicion);
      }
    }
    $nota->expediente()->dissociate();
    $nota = Nota::destroy($id);
    return ['nota' => $nota];
  }

  public function buscarNotas(Request $request){
    $reglas = array();
    if(!empty($request->nro_exp_org)){
      $reglas[]=['expediente.nro_exp_org' , 'like' ,'%' . $request->nro_exp_org . '%'];
    }
    if(!empty($request->nro_exp_interno)){
      $reglas[]=['expediente.nro_exp_interno', 'like' , '%' . $request->nro_exp_interno . '%'];
    }
    if(!empty($request->nro_exp_control)){
      $reglas[]=['expediente.nro_exp_control', 'like' ,'%' . $request->nro_exp_control .'%'];
    }
    if($request->plataforma!= 0){
      $reglas[]=['expediente.id_plataforma', '=' ,  $request->plataforma ];
    }

    if(!empty($request->identificacion)){
      $reglas[]=['nota.identificacion', 'like' ,  '%' . $request->identificacion.'%'];
    }


    if(empty($request->fecha)){
        $resultados=DB::table('expediente')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_plataforma','expediente_tiene_plataforma.id_expediente','=','expediente.id_expediente')
        ->join('plataforma', 'expediente_tiene_plataforma.id_plataforma', '=', 'plataforma.id_plataforma')
        ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
        ->where('es_disposicion','=',0)
        ->orderBy('nota.identificacion','asc')
        ->where($reglas)
        ->take(50)
        ->get();
    }else{
        $fecha=explode("-", $request->fecha);
        $resultados=DB::table('expediente')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_plataforma','expediente_tiene_plataforma.id_expediente','=','expediente.id_expediente')
        ->join('plataforma', 'expediente_tiene_plataforma.id_plataforma', '=', 'plataforma.id_plataforma')
        ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
        ->where('es_disposicion','=',0)
        ->orderBy('nota.identificacion','asc')
        ->where($reglas)
        ->whereYear('fecha_iniciacion' , '=' ,$fecha[0])
        ->whereMonth('fecha_iniciacion','=', $fecha[1])
        ->take(50)
        ->get();
      }
        return ['resultados' => $resultados];
  }

  public function eliminarNotaCompleta($id_nota){
    $nota = Nota::find($id_nota);
    $nota->estado_juego()->dissociate();
    $nota->expediente()->dissociate();
    $nota->delete();
    return 1;
  }
}
