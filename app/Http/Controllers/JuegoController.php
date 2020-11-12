<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\Casino;
use App\GliSoft;
use App\Usuario;
use App\TipoMoneda;
use App\CategoriaJuego;
use App\EstadoJuego;
use App\LogJuego;
use Validator;

class JuegoController extends Controller
{
  private static $atributos = [ ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new JuegoController();
    }
    return self::$instance;
  }

  public function buscarTodo($id = null){
    $uc = UsuarioController::getInstancia();
    $uc->agregarSeccionReciente('Juegos','juegos');
    $usuario = $uc->quienSoy()['usuario'];
    $casinos = $usuario->casinos;
    return view('seccionJuegos' , 
    ['casinos' => $casinos,
     'certificados' => GliSoftController::getInstancia()->gliSoftsPorCasinos($casinos),
     'monedas' => TipoMoneda::all(),
     'categoria_juego' => CategoriaJuego::all(),
     'estado_juego' => EstadoJuego::all(),
    ]);
  }

  public function obtenerJuego($id){
    $juego = Juego::find($id);
    if(is_null($juego)){
      return $this->errorOut(['acceso'=>['']]);
    }

    $casinosUser = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinosUser as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }

    $acceso = $juego->casinos()->whereIn('casino_tiene_juego.id_casino',$reglaCasinos)->count();
    if($acceso == 0 && $juego->casinos()->count() != 0){
      return $this->errorOut(['acceso'=>['']]);
    }

    return ['juego' => $juego ,
            'certificadoSoft' => $this->obtenerCertificadosSoft($id),
            'casinosJuego' => $juego->casinos,
            'casinos' => $this->obtenerListaCodigosCasinos($juego)];
  }

  public function obtenerListaCodigosCasinos($juego,$sep=', '){
    $lista = '';
    $casinos_juego = $juego->casinos()->orderBy('codigo')->get();
    foreach($casinos_juego as $idx => $c){
      if($idx!=0) $lista = $lista . $sep;
      $lista = $lista . $c->codigo;
    }
    return $lista;
  }

  public function encontrarOCrear($juego){
        $resultado=$this->buscarJuegoPorNombre($juego);
        if(count($resultado)==0){
            $juegoNuevo=new Juego;
            $juegoNuevo->nombre_juego=trim($juego);
            $juegoNuevo->save();
        }else{
            $juegoNuevo=$resultado[0];
        }
        return $juegoNuevo;
  }

  public function guardarJuego(Request $request){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $ids_casinos=array();
    foreach($casinos as $casino){
      $ids_casinos[] = $casino->id_casino;
    }
    Validator::make($request->all(), [
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'id_estado_juego' => 'required|integer|exists:estado_juego,id_estado_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'codigo_proveedor' => 'nullable|string|max:100',
    ], array(), self::$atributos)->after(function ($validator) use ($ids_casinos) {
      $data = $validator->getData();
      $nombre_juego = $data['nombre_juego'];
      //El nombre del juego es unico POR LOS QUE ACCEDE EL ADMINISTRADOR
      $juegos_mismo_nombre = DB::table('juego as j')
      ->join('casino_tiene_juego as cj','cj.id_juego','=','j.id_juego')
      ->whereIn('cj.id_casino',$ids_casinos)
      ->where('j.nombre_juego',$nombre_juego);
      if($juegos_mismo_nombre->count() > 0){
        $validator->errors()->add('nombre_juego', 'validation.unique');
      }
      if(!$data['movil'] && !$data['escritorio']){
        $validator->errors()->add('movil','validation.required');
        $validator->errors()->add('escritorio','validation.required');
      }
    })->validate();

    $juego = new Juego;
    DB::transaction(function() use($juego,$ids_casinos,$request){
      $juego->nombre_juego = $request->nombre_juego;
      $juego->cod_juego = $request->cod_juego;
      $juego->denominacion_juego = $request->denominacion_juego;
      $juego->porcentaje_devolucion = $request->porcentaje_devolucion;
      $juego->escritorio = $request->escritorio;
      $juego->movil = $request->movil;
      $juego->codigo_operador = $request->codigo_operador;
      $juego->codigo_proveedor = $request->codigo_proveedor;
      $juego->id_tipo_moneda = $request->id_tipo_moneda;
      $juego->id_categoria_juego = $request->id_categoria_juego;
      $juego->id_estado_juego = $request->id_estado_juego;
      $juego->save();
      
      // asocio el nuevo juego con los casinos del usuario 
      $juego->casinos()->syncWithoutDetaching($ids_casinos);
  
      foreach($juego->gliSoft as $gli){
        $juego->gliSoft()->detach($gli->id_gli_soft);
      }
      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
      }
      $juego->save();

      $log = new LogJuego;
      $log->id_juego = $juego->id_juego;
      $log->fecha = date('Y-m-d h:i:s');
      $log->json = $request->all();
      $log->save();
    });

    return ['juego' => $juego];
  }

  public function modificarJuego(Request $request){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $ids_casinos = [];
    foreach($usuario->casinos as $c){
      $ids_casinos[] = $c->id_casino;
    }
    Validator::make($request->all(), [
      'id_juego' => 'required|integer|exists:juego,id_juego',
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'id_estado_juego' => 'required|integer|exists:estado_juego,id_estado_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'codigo_proveedor' => 'nullable|string|max:100',
    ], array(), self::$atributos)->after(function ($validator) use ($ids_casinos){
      $data = $validator->getData();
      $id_juego = $data['id_juego'];
      //Que el usuario tenga acceso a ese juego
      $acceso = DB::table('casino_tiene_juego')
      ->whereIn('id_casino',$ids_casinos)
      ->where('id_juego',$id_juego)->count();
      if($acceso == 0) $validator->errors()->add('id_juego', 'El usuario no puede acceder a este juego');

      $nombre_juego = $data['nombre_juego'];
      //El nombre del juego es unico POR LOS QUE ACCEDE EL ADMINISTRADOR
      //Si no es unico, que sea el mismo juego
      $juegos_mismo_nombre = DB::table('juego as j')
      ->join('casino_tiene_juego as cj','cj.id_juego','=','j.id_juego')
      ->whereIn('cj.id_casino',$ids_casinos)
      ->where('j.nombre_juego',$nombre_juego);
      if($juegos_mismo_nombre->count() > 0
      && $juegos_mismo_nombre->where('j.id_juego',$id_juego)->count() == 0){
        $validator->errors()->add('nombre_juego', 'validation.unique');
      }
      if(!$data['movil'] && !$data['escritorio']){
        $validator->errors()->add('movil','validation.required');
        $validator->errors()->add('escritorio','validation.required');
      }
    })->validate();


    $juego = Juego::find($request->id_juego);

    DB::transaction(function() use($request,$juego){
      $juego->nombre_juego= $request->nombre_juego;
      if($request->cod_juego!=null){
        $juego->cod_juego= $request->cod_juego;
      }

      $juego->denominacion_juego = $request->denominacion_juego;
      $juego->porcentaje_devolucion = $request->porcentaje_devolucion;
      $juego->escritorio = $request->escritorio;
      $juego->movil = $request->movil;
      $juego->codigo_operador = $request->codigo_operador;
      $juego->codigo_proveedor = $request->codigo_proveedor;
      $juego->id_tipo_moneda = $request->id_tipo_moneda;
      $juego->id_categoria_juego = $request->id_categoria_juego;
      $juego->id_estado_juego = $request->id_estado_juego;
      $juego->save();

      foreach($juego->gliSoft as $gli){
        $juego->gliSoft()->detach($gli->id_gli_soft);
      }
      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
      }
      $juego->save();
      $log = new LogJuego;
      $log->id_juego = $juego->id_juego;
      $log->fecha = date('Y-m-d h:i:s');
      $log->json = $request->all();
      $log->save();
    });

    return ['juego' => $juego];
  }

  public function eliminarJuego($id){
    $juego = Juego::find($id);
    if(is_null($juego)) return ['juego' => null];
    $juego->delete();
    return ['juego' => $juego];
  }

  public function getAll(){
    $todos=Juego::all();
    return $todos;
  }

  //busca juegos bajo el criterio "contiene". @param nombre_juego, cod_identificacion
  public function buscarJuegoPorCodigoYNombre($busqueda){
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
     }
    $resultados=Juego::distinct()
                      ->select('juego.*')
                      ->join('casino_tiene_juego','casino_tiene_juego.id_juego','=','juego.id_juego')
                      ->wherein('casino_tiene_juego.id_casino',$reglaCasinos)
                      ->where('nombre_juego' , 'like' , $busqueda . '%')->get();
                      //->orWhere('cod_identificacion' , 'like' , $busqueda . '%')->get();

    return ['resultados' => $resultados];
  }

    public function buscarJuegoPorCasinoYNombre($id_casino,$busqueda){
      $casino = Usuario::find(session('id_usuario'))
      ->casinos()->where('usuario_tiene_casino.id_casino',$id_casino)->get();
      if($casino->count() == 0) return ['resultados' => []];

      $resultados=Juego::distinct()
                        ->select('juego.*')
                        ->join('casino_tiene_juego','casino_tiene_juego.id_juego','=','juego.id_juego')
                        ->where('casino_tiene_juego.id_casino',$casino->first()->id_casino)
                        ->where('nombre_juego' , 'like' , $busqueda . '%')->get();
  
      return ['resultados' => $resultados];
    }

  //busca UN juego que coincida con el nombre  @param $nombre_juego
  public function buscarJuegoPorNombre($nombre_juego){
    $resultado=Juego::where('nombre_juego' , '=' , trim($nombre_juego))->get();
    return $resultado;
  }

  public function buscarJuegoMovimientos($nombre_juego){
    $resultado=Juego::where('nombre_juego' , 'like' , '%' .$nombre_juego.'%')->get();
    return ['juegos' =>$resultado];
  }


  public function buscarJuegos(Request $request){
    $reglas=array();
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    if(!empty($request->nombreJuego) ){
      $reglas[]=['juego.nombre_juego', 'like' , '%' . $request->nombreJuego  .'%'];
    }
    if(!empty($request->cod_Juego)){
      $reglas[]=['juego.cod_juego', 'like' , '%' . $request->cod_Juego  .'%'];
    }
    if(!empty($request->id_casino)){
      $reglas[] = ['casino_tiene_juego.id_casino','=',$request->id_casino];
    }
    if(!empty($request->id_categoria_juego)){
      $reglas[] = ['juego.id_categoria_juego','=',$request->id_categoria_juego];
    }
    if(!empty($request->id_estado_juego)){
      $reglas[] = ['juego.id_estado_juego','=',$request->id_estado_juego];
    }

    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }
    
    $sort_by = $request->sort_by;

    $resultados=DB::table('juego')
                  ->select('juego.*')
                  ->selectRaw("GROUP_CONCAT(DISTINCT(IFNULL(gli_soft.nro_archivo, '-')) separator ', ') as certificados")
                  ->leftjoin('juego_glisoft as jgl','jgl.id_juego','=','juego.id_juego')
                  ->leftjoin('gli_soft','gli_soft.id_gli_soft','=','jgl.id_gli_soft')
                  ->leftjoin('casino_tiene_juego','casino_tiene_juego.id_juego','=','juego.id_juego')
                  ->when($sort_by,function($query) use ($sort_by){
                                  return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                              })
                  ->where(function($query) use ($reglaCasinos){
                    return $query->wherein('casino_tiene_juego.id_casino',$reglaCasinos)->orWhereNull('casino_tiene_juego.id_casino');
                  })
                  ->whereNull('juego.deleted_at')
                  ->where($reglas);
    
    if(!empty($request->codigoId)){
      if(trim($request->codigoId) == '-'){//Si me envia un gion, significa sin certificado
        $resultados = $resultados->whereNull('gli_soft.id_gli_soft');
      }
      else {
        $codigos = explode(',',$request->codigoId);
        foreach($codigos as &$c) $c = trim($c);

        $resultados = $resultados->where(function ($query) use ($codigos){
          foreach($codigos as $idx => $c){
            if($idx == 0) $query->where('gli_soft.nro_archivo','like','%'.$c.'%');
            else $query->orWhere('gli_soft.nro_archivo','like','%'.$c.'%');
          }
        });

      }
    }

    $resultados = $resultados->groupBy('juego.id_juego');
    $resultados = $resultados->orderBy('juego.id_juego','desc');
    $resultados = $resultados->paginate($request->page_size);
    return $resultados;
  }

  public function desasociarGLI($id_gli_soft){
    $GLI = GliSoft::find($id_gli_soft);
    if($GLI===null) return;
    $juegos=$GLI->juegos;
    foreach ($juegos as $juego) {
      $juego->gliSoftOld()->dissociate();
      $juego->save();
    }
    $GLI->setearJuegos([]);
  }

  public function asociarGLI($listaJuegos , $id_gli_soft){
    foreach ($listaJuegos as $id_juego) {
       $juego=Juego::find($id_juego);
       $juego->gliSoftOld()->associate($id_gli_soft);
       $juego->save();
    }
    $GLI = GliSoft::find($id_gli_soft);
    if($GLI != null){
      $GLI->setearJuegos([]);
      //Por si manda varias veces el mismo juego lo filtro
      $GLI->setearJuegos(array_unique($listaJuegos),true);
      $GLI->save();
    }
  }
  public function obtenerCertificadosSoft($id){
    $juego=Juego::find($id);
    if($juego != null){
      $certificados = $juego->gliSoft;
      $ret = [];
      foreach($certificados as $c){
        $nombre_archivo = is_null($c->archivo)? null : $c->archivo->nombre_archivo;
        $ret[] = ['certificado' => $c, 'archivo' => $nombre_archivo];
      } 
      return $ret;
    }
    return ['certificadosSoft' => null];
  }

  private function errorOut($map){
    return response()->json($map,422);
  }
}
