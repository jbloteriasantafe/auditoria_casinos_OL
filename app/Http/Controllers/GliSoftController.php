<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\GliSoft;
use App\Archivo;
use App\Casino;
use Illuminate\Support\Facades\DB;
use App\Juego;

use Validator;

class GliSoftController extends Controller
{
  private static $atributos = [
    'nro_certificado' => 'Nro certificado',
    'observaciones' => 'Niveles de Progresivo',
    'file' => 'Archivo GLI',
  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new GliSoftController();
      }
      return self::$instance;
  }

  public function buscarTodo($buscar = null){
      $glisofts=GliSoft::all();
      $uc = UsuarioController::getInstancia();
      $uc->agregarSeccionReciente('Certificados Software' , 'certificadoSoft');
      $user = $uc->quienSoy()['usuario'];
      $casinos_ids = [];
      foreach($user->casinos as $c){
        $casinos_ids[] = $c->id_casino;
      }
      //Ordenar por nombre ascendiente ignorando mayusculas
      $query = DB::table('juego')->select('juego.id_juego')
      ->join('casino_tiene_juego as cj','juego.id_juego','=','cj.id_juego')
      ->whereIn('cj.id_casino',$casinos_ids)
      ->groupBy('juego.id_juego')
      ->orderBy('juego.nombre_juego','ASC')
      ->get();
      //Hay juegos con el mismo nombre, los agrupo
      $juegosarr = [];
      foreach($query as $q){
        $j = Juego::find($q->id_juego);
        $nombre = $j->nombre_juego . ' #';
        foreach($j->casinos->sortBy('codigo') as $c){
          $nombre = $nombre . ' ' . $c->codigo;
        }
        if(!isset($juegosarr[$nombre])){
          $juegosarr[$nombre] = [];
        }
        $juegosarr[$nombre][] = $j;
      }
      $codigo_defecto_busqueda = '';
      if(!is_null($buscar)){
        $codigo_defecto_busqueda = $buscar;
      }
      //formato juegosarr = {'juego1' => [j1,j2],'juego2' => [j3],...}
      return view('seccionGLISoft' , 
      ['glis' => $glisofts,
      'casinos' => $user->casinos,
      'juegos' => $juegosarr,
      'codigo_defecto_busqueda' => $codigo_defecto_busqueda])->render();
  }

  public function obtenerGliSoft($id){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos_ids = [];
    foreach($user->casinos as $c){
      $casinos_ids[] = $c->id_casino;
    }

    $glisoft = GliSoft::find($id);

    if(!empty($glisoft->archivo)){
      $nombre_archivo = $glisoft->archivo->nombre_archivo;
      $size=$glisoft->archivo->archivo;
    }else{
      $nombre_archivo = null;
    }
    $juegosYTPagos = array();
    foreach ($glisoft->juegos as $juego) {
      $juego_casinos = $juego->casinos();
      $visible = $juego_casinos->whereIn('casino.id_casino',$casinos_ids)->count();
      if($visible>0){
        $juego_casinos = $juego_casinos->get(['casino.id_casino','casino.nombre','casino.codigo']);
        $juegosYTPagos[]= ['juego'=> $juego, 'tablas_de_pago' => $juego->tablasPago,'casinos' => $juego_casinos];
      }
    }
    return ['glisoft' => $glisoft , 'expedientes' => $glisoft->expedientes, 'nombre_archivo' => $nombre_archivo , 'juegos' => $juegosYTPagos];
  }

  public function leerArchivoGliSoft($id){
    $data = DB::table('gli_soft')->select('archivo.archivo','archivo.nombre_archivo')
                                ->join('archivo','archivo.id_archivo','=','gli_soft.id_archivo')
                                ->where('gli_soft.id_gli_soft','=',$id)->first();


    return Response::make(base64_decode($data->archivo), 200, [ 'Content-Type' => 'application/pdf',
                                                      'Content-Disposition' => 'inline; filename="'. $data->nombre_archivo  . '"']);
  }

  //METODO QUE RESPONDEN A GUARDAR
  public function guardarGliSoft(Request $request){
    Validator::make($request->all(), [
      'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:gli_soft,nro_archivo'],
      'observaciones' => 'nullable|string',
      'file' => 'sometimes|mimes:pdf',
      'juegos' => 'required|string',
    ], array(), self::$atributos)->after(function ($validator){
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $casinos_ids = [];
        foreach($user->casinos as $c){
          $casinos_ids[] = $c->id_casino;
        }
        $data = $validator->getData();
        if(isset($data['juegos'])){
          $juegos = explode(",",$data['juegos']);
          $juegos_user = DB::table('casino_tiene_juego')->whereIn('id_casino',$casinos_ids);
          $tiene_juegos = false;
          foreach($juegos as $j){
            $acceso = (clone $juegos_user)->where('id_juego',$j)->count();
            if($acceso == 0){
              $validator->errors()->add($j, 'No puede acceder a ese juego');
            }
            else{ $tiene_juegos = true; }
          }
          if(!$tiene_juegos){
            $validator->errors()->add('juegos', 'No puede crear un certificado de software sin juegos.');
          } 
        }
    })->validate();

    $GLI = null;
    $nombre_archivo = null;

    DB::transaction(function() use($GLI,$nombre_archivo,$request){
      $GLI=new GliSoft;
      $GLI->nro_archivo =$request->nro_certificado;
      $GLI->observaciones=$request->observaciones;
  
      if($request->file != null){
        $file=$request->file;
        $archivo=new Archivo;
        $data=base64_encode(file_get_contents($file->getRealPath()));
        $nombre_archivo=$file->getClientOriginalName();
        $archivo->nombre_archivo=$nombre_archivo;
        $archivo->archivo=$data;
        $archivo->save();
        $GLI->archivo()->associate($archivo->id_archivo);
      }
  
      $GLI->save();
  
      if(!empty($request->expedientes)){
        $expedientesReq = explode(',',$request->expedientes);
      }else{
        $expedientesReq=null;
      }
      if($expedientesReq != null){
        foreach ($expedientesReq as $exp) {
          if($this->noEstabaEnLista($exp,$GLI->expedientes)){
            $GLI->expedientes()->attach($exp);
          }
        }
      }
      if(isset($request->juegos)){
        $juegos=explode("," , $request->juegos);
        JuegoController::getInstancia()->asociarGLI($juegos , $GLI->id_gli_soft);
      }
  
      $GLI->save();
  
      //obtengo solo el nombre del archivo para devolverlo a la vista
      if(!empty($GLI->archivo)){
        $nombre_archivo = $GLI->archivo->nombre_archivo;
      }
    });
    
    return ['gli_soft' => $GLI,  'nombre_archivo' =>$nombre_archivo];
  }

  public function guardarGliSoft_gestionarMaquina($nro_certificado,$observaciones,$file){
        $GLI=new GliSoft;
        $GLI->nro_archivo =$nro_certificado;
        $GLI->observaciones=$observaciones;
        if($file != null){
          $archivo=new Archivo;
          $data=base64_encode(file_get_contents($file->getRealPath()));
          $nombre_archivo=$file->getClientOriginalName();
          $archivo->nombre_archivo=$nombre_archivo;
          $archivo->archivo=$data;
          $archivo->save();
          $GLI->archivo()->associate($archivo->id_archivo);
        }
        $GLI->save();
        return $GLI;
  }

  public function buscarGliSofts(Request $request){
    $reglas = array();
    if(!empty($request->certificado)){
      $reglas[]=['gli_soft.nro_archivo' , 'like' , '%' .  $request->certificado . '%'];
    }
    if(!empty($request->nombre_archivo)){
      $reglas[]=['archivo.nombre_archivo' , 'like' , '%' . $request->nombre_archivo . '%' ];
    }
    if(isset($request->id_juego)){
      $reglas[]=['juego_glisoft.id_juego' , '=' , $request->id_juego];
    }
    $sort_by = $request->sort_by;
    $resultados=DB::table('gli_soft')
    ->select('gli_soft.*', 'archivo.nombre_archivo')
    ->leftJoin('archivo' , 'archivo.id_archivo' , '=' , 'gli_soft.id_archivo')
    ->leftJoin('juego_glisoft','juego_glisoft.id_gli_soft','=','gli_soft.id_gli_soft')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->where($reglas);
    //Elimino duplicados y pagino.
    $resultados = $resultados->groupBy('gli_soft.id_gli_soft')->paginate($request->page_size);

    foreach ($resultados as $index => $resultado) {
      $gli = GliSoft::find($resultado->id_gli_soft);
      $resultados[$index]->eliminar =  ($gli->expedientes->count() && $gli->juegos->count() && $gli->maquinas->count());
    }

    return ['resultados' => $resultados];
  }

  public function modificarGliSoft(Request $request){
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $casinos_ids = [];
      foreach($user->casinos as $c){
        $casinos_ids[] = $c->id_casino;
      }
      Validator::make($request->all(), [
        'id_gli_soft' => 'required|exists:gli_soft,id_gli_soft',
        'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:gli_soft,nro_archivo,'.$request->id_gli_soft.',id_gli_soft'],
        'observaciones' => 'nullable|string',
        'file' => 'sometimes|mimes:pdf',
        'expedientes' => 'nullable',
        'juegos' => 'required|string'
      ])->after(function ($validator) use ($casinos_ids){
        $data = $validator->getData();
        if(isset($data['juegos'])){
          $juegos = explode(",",$data['juegos']);
          $juegos_user = DB::table('casino_tiene_juego')->whereIn('id_casino',$casinos_ids);
          $tiene_juegos = false;
          foreach($juegos as $j){
            //Se necesita clonar porque el where y count modifican la estructura
            $acceso = (clone $juegos_user)->where('id_juego',$j)->count();
            if($acceso == 0){
              $validator->errors()->add($j, 'No puede acceder a ese juego');
            }
            else{ $tiene_juegos = true; }
          }
          if(!$tiene_juegos){
            $validator->errors()->add('juegos', 'No puede crear/modificar un certificado de software sin juegos.');
          }
        }
      })->validate();

      $GLI = null;
      $nombre_archivo = null;
      DB::transaction(function() use($request,$casinos_ids,$GLI,$nombre_archivo){
        $GLI=GliSoft::find($request->id_gli_soft);
        $GLI->nro_archivo =$request->nro_certificado;
        $GLI->observaciones=$request->observaciones;
  
        if(!empty($request->expedientes)){
          $expedientesReq = explode(',',$request->expedientes);
        }else{
          $expedientesReq=null;
        }
        if(isset($GLI->expedientes)){
          foreach ($GLI->expedientes as $expediente) {
            if($this->noEstaEnLista($expediente ,  $expedientesReq )){
              $GLI->expedientes()->detach($expediente->id_expediente);
            }
          }
        }
  
        if($request->expedientes != null){
          for($i=0; $i<count(  $expedientesReq); $i++){
            if($this->noEstabaEnLista(  $expedientesReq[$i],$GLI->expedientes)){
              $GLI->expedientes()->attach(  $expedientesReq[$i]);
            }
          }
        }
        

        //Saco los viejos que puede ver el usuario
        $juegos_accesibles = DB::table('gli_soft as gl')->select('j.id_juego')
        ->join('juego_glisoft as jgl','jgl.id_gli_soft','=','gl.id_gli_soft')
        ->join('juego as j','j.id_juego','=','jgl.id_juego')
        ->join('casino_tiene_juego as cj','cj.id_juego','=','j.id_juego')
        ->where('gl.id_gli_soft',$GLI->id_gli_soft)
        ->whereIn('cj.id_casino',$casinos_ids)->get();

        foreach($juegos_accesibles as $j){
          $juego = Juego::find($j->id_juego);
          $juego->gliSoftOld()->dissociate();
          $juego->gliSoft()->detach($GLI->id_gli_soft);
          $juego->save();
        }

        //Agrego los nuevos
        if(!empty($request->juegos)){
          $juegos=explode("," , $request->juegos);
          foreach($juegos as $id_juego){
            $juego = Juego::find($id_juego);
            $juego->gliSoftOld()->associate($GLI->id_gli_soft);
            $juego->gliSoft()->attach($GLI->id_gli_soft);
            $juego->save();
          }
        }
  
        $GLI->save();
  
        if(!empty($request->file)){
            if($GLI->archivo != null){
              $archivoAnterior=$GLI->archivo;
              $GLI->archivo()->dissociate();
              $GLI->save();
              $archivoAnterior->delete();
            }
  
            $file=$request->file;
            $archivo=new Archivo;
            $archivo->nombre_archivo=$file->getClientOriginalName();
            $data=base64_encode(file_get_contents($file->getRealPath()));
            $archivo->archivo=$data;
            $archivo->save();
            $GLI->archivo()->associate($archivo->id_archivo);
            $GLI->save();
  
        }else{
            if($request->borrado == "true"){
              $archivoAnterior=$GLI->archivo;
              $GLI->archivo()->dissociate();
              $GLI->save();
              $archivoAnterior->delete();
            }
        }
  
        $GLI->save();
        $GLI=GliSoft::find($request->id_gli_soft);
        if(!empty($GLI->archivo)){
          $nombre_archivo = $GLI->archivo->nombre_archivo;
        }
      });

      return ['gli_soft' => $GLI , 'nombre_archivo' => $nombre_archivo ];
  }

  public function getGli($id){
    return GliSoft::find($id);
  }

  public function buscarGliSoftsPorNroArchivo($nro_archivo){
    $reglas = Array();
    if(!empty($nro_archivo))
      $reglas[]=['nro_archivo', 'like', '%'.$nro_archivo.'%'];
    $resultado = GliSoft::where($reglas)->get();
    return ['gli_softs' => $resultado];
  }

  private function noEstaEnLista($expediente ,$expedientes ){//expediente posta ,expedientes del request
    for($i=0; $i<count($expedientes); $i++){
      if($expedientes[$i] == $expediente->id_expediente){
        return false; //si esta en lista
      }
    }
    return true;
  }
  private function noEstabaEnLista($expediente ,$expedientes ){//expediente del request ,expedientes del gli
    if(isset($expedientes)){
      foreach ($expedientes as $exp) {
        if($exp->id_expediente == $expediente){
          return false; //si esta en lista
        }
      }
    }
    return true;
  }

  public function eliminarGLI($id){
    $GLI=GliSoft::find($id);
    $se_borro = false;
    if(is_null($GLI)) return ['gli' => $GLI,'se_borro' => $se_borro];

    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $casinos_ids=array();
    foreach($casinos as $c){
      $casinos_ids [] = $c->id_casino;
    }

    $juegos_accesibles = DB::table('gli_soft as gl')->select('j.id_juego')
    ->join('juego_glisoft as jgl','jgl.id_gli_soft','=','gl.id_gli_soft')
    ->join('juego as j','j.id_juego','=','jgl.id_juego')
    ->join('casino_tiene_juego as cj','cj.id_juego','=','j.id_juego')
    ->where('gl.id_gli_soft',$GLI->id_gli_soft)
    ->whereIn('cj.id_casino',$casinos_ids);

    $juegos_old = DB::table('gli_soft as gl')->select('j.id_juego')
    ->join('juego as j','j.id_gli_soft','=','gl.id_gli_soft')
    ->join('casino_tiene_juego as cj','cj.id_juego','=','j.id_juego')
    ->where('gl.id_gli_soft',$GLI->id_gli_soft)
    ->whereIn('cj.id_casino',$casinos_ids);

    $juegos_accesibles = $juegos_accesibles->union($juegos_old)->distinct()->get();

    DB::transaction(function() use ($GLI,$juegos_accesibles,$se_borro){
      foreach($juegos_accesibles as $j){
        $juego = Juego::find($j->id_juego);
        $juego->gliSoftOld()->dissociate();
        $juego->gliSoft()->detach($GLI->id_gli_soft);
        $juego->save();
      }
      $GLI->save();
      $cant_juegos = $GLI->juegos()->count()+$GLI->juegosOld()->count();
      if($cant_juegos == 0){//Si el GLI no tiene mas juegos, lo borro
        $GLI->expedientes()->sync([]);
        $archivo = $GLI->archivo;
        $GLI->archivo()->dissociate();
        $GLI->save();
        $GLI->delete();
        if(!empty($archivo)){
          $archivo->delete();
        }
        $se_borro = true;
      }
    });

    return ['gli' => $GLI,'se_borro' => $se_borro];
  }

}
