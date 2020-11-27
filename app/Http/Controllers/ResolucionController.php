<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Expediente;
use App\Resolucion;
use App\Disposicion;
use App\Plataforma;

class ResolucionController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new ResolucionController();
      }
      return self::$instance;
  }

  public function buscarTodoResoluciones(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $resoluciones=array();
    foreach($usuario['usuario']->plataformas as $p){
      $auxiliar=DB::table('resolucion')->join('expediente' , 'expediente.id_expediente' ,'=' , 'resolucion.id_expediente')->join('plataforma', 'plataforma.id_plataforma', '=' , 'expediente.id_plataforma')->where('plataforma.id_plataforma' , '=' ,$p->id_plataforma)->get()->toArray();
        $resoluciones=array_merge($resoluciones,$auxiliar);
    }
    $plataformas=Plataforma::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Resoluciones' , 'resoluciones');
    return view('seccionResoluciones' , ['resoluciones' => $resoluciones , 'plataformas' => $plataformas]);
  }

  public function buscarResolucion(Request $request){
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
      $reglas[]=['expediente.id_plataforma', '=' , $request->plataforma ];
    }
    if(!empty($request->nro_resolucion)){
      $reglas[]=['resolucion.nro_resolucion', 'like' ,'%' . $request->nro_resolucion . '%'];
    }
    if(!empty($request->nro_resolucion_anio)){
      $reglas[]=['resolucion.nro_resolucion_anio', 'like' , '%' . $request->nro_resolucion_anio . '%'];
    }

      $resultados=DB::table('expediente')
      ->join('resolucion', 'resolucion.id_expediente' , '=' , 'expediente.id_expediente')
      ->join('plataforma', 'plataforma.id_plataforma' , '=' , 'expediente.id_plataforma')
      ->where($reglas)
      ->get();
        return ['resultados' => $resultados , 'dato' => $request->nro_exp_org];
  }

  public function guardarResolucion($res,$id_expediente){
    $resolucion = new Resolucion;
    $resolucion->nro_resolucion = $res['nro_resolucion'];
    $resolucion->nro_resolucion_anio = $res['nro_resolucion_anio'];
    $resolucion->expediente()->associate($id_expediente);
    $resolucion->save();
  }

  public function updateResolucion($res,$id_expediente){
    if(count($res)>0){
      $id_res_actuales=array();
      $res_crear=array();
      foreach($res as $r){
        if($r['id_resolucion']!="-1"){
          array_push($id_res_actuales,$r['id_resolucion']);
        }else{
          array_push($res_crear,$r);
        }
      }

      if($id_res_actuales){
        $res_elim=Resolucion::select("id_resolucion")
                ->where('id_expediente',$id_expediente)
                ->whereNotIn("id_resolucion",$id_res_actuales)
                ->get();
        foreach($res_elim as $r){
          Resolucion::destroy($r->id_resolucion);
        }      
      }else{
        Resolucion::where("id_expediente",$id_expediente)->delete();
      }
      
      if ($res_crear){
        foreach($res_crear as $rc){
          $this->guardarResolucion($rc,$id_expediente);
        }
      }
    }else{
      Resolucion::where("id_expediente",$id_expediente)->delete();
    }
  }

  public function eliminarResolucion($id){
    $resolucion = Resolucion::destroy($id);
    return ['resolucion' => $resolucion];
  }
}
