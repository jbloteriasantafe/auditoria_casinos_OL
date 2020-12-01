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
    UsuarioController::getInstancia()->agregarSeccionReciente('Resoluciones' , 'resoluciones');
    return view('seccionResoluciones' , ['plataformas' => $usuario['usuario']->plataformas]);
  }

  public function buscarResolucion(Request $request){
    $usuario =  UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

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
      $reglas[]=['plataforma.id_plataforma', '=' , $request->plataforma ];
    }
    if(!empty($request->nro_resolucion)){
      $reglas[]=['resolucion.nro_resolucion', 'like' ,'%' . $request->nro_resolucion . '%'];
    }
    if(!empty($request->nro_resolucion_anio)){
      $reglas[]=['resolucion.nro_resolucion_anio', 'like' , '%' . $request->nro_resolucion_anio . '%'];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('expediente')
    ->join('resolucion', 'resolucion.id_expediente' , '=' , 'expediente.id_expediente')
    ->join('expediente_tiene_plataforma','expediente_tiene_plataforma.id_expediente','=','expediente.id_expediente')
    ->join('plataforma','plataforma.id_plataforma','=','expediente_tiene_plataforma.id_plataforma')
    ->whereIn('plataforma.id_plataforma',$plats)
    ->where($reglas)
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })->paginate($request->page_size);

    return ['resultados' => $resultados];
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
