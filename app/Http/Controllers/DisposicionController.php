<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Disposicion;

class DisposicionController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new DisposicionController();
      }
      return self::$instance;
  }

  public function buscarTodoDisposiciones(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    UsuarioController::getInstancia()->agregarSeccionReciente('Disposiciones' , 'disposiciones');
    return view('seccionDisposiciones' , ['plataformas' => $usuario['usuario']->plataformas]);
  }

  public function guardarDisposicion($disp, $id_expediente){
    $disposicion = new Disposicion;
    $disposicion->nro_disposicion = $disp['nro_disposicion'];
    $disposicion->nro_disposicion_anio = $disp['nro_disposicion_anio'];
    $disposicion->descripcion = $disp['descripcion'];
    $disposicion->save();
    $disposicion->expediente()->associate($id_expediente);
    $disposicion->save();
    if(!empty($disp['id_estado_juego']) || $disp['id_estado_juego']!= 0){
      $e = $disposicion->expediente;
      $id_plat = $e->plataformas->first()->id_plataforma;
      $id_nota = NotaController::getInstancia()->guardarNotaParaDisposicion($id_expediente,$id_plat,$disposicion->nro_disposicion,$disp['id_estado_juego']);
      $disposicion->nota()->associate($id_nota);
      $disposicion->save();
    }
  }

  public function guardarDisposicionNota($disp, $id_nota){
    $disposicion = new Disposicion;
    $disposicion->nro_disposicion = $disp['nro_disposicion'];
    $disposicion->nro_disposicion_anio = $disp['nro_disposicion_anio'];
    $disposicion->nota()->associate($id_nota);
    $disposicion->save();
  }

  public function eliminarDisposicion($id){
    $disposicion = Disposicion::find($id);
    $nota = $disposicion->nota;
    DB::transaction(function() use($disposicion,$nota){
      $disposicion->delete();
      if(!is_null($nota)){
        $nota->delete();
      }
    });

    return ['disposicion' => $disposicion];
  }


  public function buscarDispocisiones(Request $request){
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
      $reglas[]=['plataforma.id_plataforma', '=' ,  $request->plataforma ];
    }
    if(!empty($request->nro_disposicion)){
      $reglas[]=['disposicion.nro_disposicion', 'like' ,'%' . $request->nro_disposicion . '%'];
    }
    if(!empty($request->nro_disposicion_anio)){
      $reglas[]=['disposicion.nro_disposicion_anio', 'like' , '%' . $request->nro_disposicion_anio . '%'];
    }

    $resultados=DB::table('expediente')
    ->join('disposicion', 'disposicion.id_expediente' , '=' , 'expediente.id_expediente')
    ->join('expediente_tiene_plataforma','expediente_tiene_plataforma.id_expediente','=','expediente.id_expediente')
    ->join('plataforma', 'plataforma.id_plataforma' , '=' , 'expediente_tiene_plataforma.id_plataforma')
    ->leftJoin('nota', 'nota.id_nota', '=', 'disposicion.id_nota')
    ->whereIn('plataforma.id_plataforma',$plats)
    ->where($reglas);

    $sort_by = $request->sort_by;
    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })->paginate($request->page_size);

    return ['resultados' => $resultados];
  }
}
