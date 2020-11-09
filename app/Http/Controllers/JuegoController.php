<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\TablaPago;
use App\Casino;
use App\GliSoft;
use App\Maquina;
use App\Usuario;
use App\UnidadMedida;
use App\TipoMoneda;
use Validator;

class JuegoController extends Controller
{
  private static $atributos = [
    'nombre_juego' => 'Nombre de Juego',
    'cod_identificacion' => 'Código de Identificación',
    'tablasDePago.*.codigo' => 'Código de Identificación',
  ];

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
    $maquinas_casinos = [];
    foreach($casinos as $c) $maquinas_casinos[$c->id_casino] = $c->maquinas->toArray();
    return view('seccionJuegos' , 
    ['casinos' => $casinos,
     'maquinas_casinos' => $maquinas_casinos,
     'certificados' => GliSoftController::getInstancia()->gliSoftsPorCasinos($casinos),
     'unidades_medida' => UnidadMedida::all(),
     'monedas' => TipoMoneda::all()
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
    if($acceso == 0){
      return $this->errorOut(['acceso'=>['']]);
    }

    $maquinas= [];
    $maquina_agregada = [];
    foreach ($juego->maquinas_juegos->whereIn('id_casino',$reglaCasinos) as $key => $mtm) {
      $maquina = new \stdClass();
      $maquina->id_maquina = $mtm->id_maquina;
      $maquina->id_casino = $mtm->id_casino;
      $maquina->nro_admin = $mtm->nro_admin;
      $maquina->porcentaje_devolucion =  $mtm->pivot->porcentaje_devolucion;
      $maquina->denominacion = $mtm->pivot->denominacion;
      $maquina->activo = $mtm->juego_activo->id_juego == $id;
      $maquinas[] = $maquina;
      $maquina_agregada[$maquina->id_maquina] = true;
    }
    //Puede ser que en la BD queden maquinas con juegos activos, pero sin juegos asociados
    //por la tabla maquina_tiene_juego, los mandamos por aca.
    foreach ($juego->maquinas->whereIn('id_casino',$reglaCasinos) as $key => $mtm) {
      if(!array_key_exists($mtm->id_maquina,$maquina_agregada)){
        $maquina = new \stdClass();
        $maquina->id_maquina = $mtm->id_maquina;
        $maquina->id_casino = $mtm->id_casino;
        $maquina->nro_admin = $mtm->nro_admin;
        $maquina->porcentaje_devolucion =  null;
        $maquina->denominacion = null;
        $maquina->activo = true;
        $maquinas[] = $maquina;
      }
    }

    $packJuego=DB::table('pack_juego')
                  ->select('pack_juego.*')
                  ->distinct()
                  ->join('pack_tiene_juego','pack_tiene_juego.id_pack','=','pack_juego.id_pack')
                  ->join('pack_juego_tiene_casino','pack_juego_tiene_casino.id_pack','=','pack_juego.id_pack')
                  ->where('pack_tiene_juego.id_juego','=',$juego->id_juego)
                  ->wherein('pack_juego_tiene_casino.id_casino',$reglaCasinos)
                  ->get();

    $tabla = TablaPago::where('id_juego', '=', $id)->get();


    return ['juego' => $juego ,
            'tablasDePago' => $tabla,
            'maquinas' => $maquinas,
            'pack'=>$packJuego,
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
      'tabla_pago.*' => 'nullable',
      'tabla_pago.*.codigo' => 'required|max:150',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required|integer|exists:maquina,nro_admin',
      'maquinas.*.id_casino' => 'required|integer|exists:casino,id_casino',
      'maquinas.*.id_maquina' => 'required|integer',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
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
    })->validate();

    $juego = new Juego;
    DB::transaction(function() use($juego,$ids_casinos,$request){
      $juego->nombre_juego = $request->nombre_juego;
      $juego->cod_juego = $request->cod_juego;
      $juego->save();
      
      // asocio el nuevo juego con los casinos del usuario 
      $juego->casinos()->syncWithoutDetaching($ids_casinos);
  
      if(isset($request->maquinas)){
        foreach ($request->maquinas as $maquina) {
          if($maquina['id_maquina'] == 0){
            $mtm = Maquina::where([['id_casino' , $maquina['id_casino']] , ['nro_admin' , $maquina['nro_admin']]])->first();
          }else {
            $mtm = Maquina::find($maquina['id_maquina']);
          }
          if($mtm != null){
            $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
            $mtm->save();
          }
        }
      }
  
      if(!empty($request->tabla_pago)){
        foreach ($request->tabla_pago as $tabla){
          TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
        }
      }

      foreach($juego->gliSoft as $gli){
        $juego->gliSoft()->detach($gli->id_gli_soft);
      }
      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
      }
      $juego->save();
    });

    return ['juego' => $juego];
  }

  public function guardarJuego_gestionarMaquina($nombre_juego,$arreglo_tablas){
    //funcion encargada de crear juego si este fue creado en "GESTIONAR MÁQUINA"
    Validator::make(['nombre_juego' => $nombre_juego], [
      'nombre_juego' => 'required|unique:juego,nombre_juego|max:100',
    ], array(), self::$atributos)->validate();

    $juego = new Juego;
    $juego->nombre_juego = $nombre_juego;
    $juego->save();

    if(!empty($arreglo_tablas)){//si no viene vacio
      foreach ($arreglo_tablas as $tabla){
        TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
      }
    }

    return $juego;
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
      'tabla_pago.*' => 'nullable',
      'tabla_pago.*.codigo' => 'required|max:150',
      'maquinas.*' => 'nullable',
      'maquinas.*.nro_admin' => 'required|integer|exists:maquina,nro_admin',
      'maquinas.*.id_casino' => 'required|integer|exists:casino,id_casino',
      'maquinas.*.id_maquina' => 'required|integer',
      'maquinas.*.denominacion' => 'nullable',
      'maquinas.*.porcentaje' => 'nullable',
      'maquinas.*.activo' => 'required|boolean',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_contable' => 'required|numeric|between:0,100',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_unidad_medida' => 'required|integer|exists:unidad_medida,id_unidad_medida',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
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
    })->validate();


    $juego = Juego::find($request->id_juego);


    //Solo toco las maquinas que no tienen el juego como activo, del casino del usuario
    $mtms_accesibles = $juego->maquinas_juegos()
    ->whereIn('id_casino',$ids_casinos)->where('maquina.id_juego','<>',$juego->id_juego)->get();

    DB::transaction(function() use($request,$mtms_accesibles,$juego){
      $juego->nombre_juego= $request->nombre_juego;
      if($request->cod_juego!=null){
        $juego->cod_juego= $request->cod_juego;
      }

      $juego->denominacion_contable = $request->denominacion_contable;
      $juego->denominacion_juego = $request->denominacion_juego;
      $juego->porcentaje_devolucion = $request->porcentaje_devolucion;
      $juego->id_unidad_medida = $request->id_unidad_medida;
      $juego->id_tipo_moneda = $request->id_tipo_moneda;
      
      $juego->save();

      //Le saco las tablas de pago
      foreach ($juego->tablasPago as $tabla) {
        $tabla->delete();
      };

      //Seteo las enviadas
      if(isset($request->tabla_pago)){
        foreach ($request->tabla_pago as $key => $tabla) {
          TablaPagoController::getInstancia()->guardarTablaPago($tabla,$juego->id_juego);
        };
      }

      //Al juego le saco las maquinas
      foreach($mtms_accesibles as $mtm){
        $mtm->juegos()->detach($juego->id_juego);
        $mtm->save();
      }
      
      if(isset($request->maquinas)){
        //Agrego las que me mande
        foreach ($request->maquinas as $maquina){
          if ($maquina['id_maquina'] == 0) {
            $mtm = Maquina::where([['id_casino' , $maquina['id_casino']],['nro_admin', $maquina['nro_admin']]])->first();
          }else {
            $mtm = Maquina::find($maquina['id_maquina']);
          }
          $mtm->juegos()->syncWithoutDetaching([$juego->id_juego => ['denominacion' => $maquina['denominacion'] ,'porcentaje_devolucion' => $maquina['porcentaje']]]);
          $mtm->save();
        }
      }
      
      foreach($juego->gliSoft as $gli){
        $juego->gliSoft()->detach($gli->id_gli_soft);
      }
      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
      }
      $juego->save();
    });

    return ['juego' => $juego];
  }

  public function eliminarJuego($id){
    $casinos = Usuario::find(session('id_usuario'))->casinos;
    $reglaCasinos=array();
    foreach($casinos as $casino){
      $reglaCasinos [] = $casino->id_casino;
    }

    $juego = Juego::find($id);
    if(is_null($juego)) return ['juego' => null];


    $mtms_accesibles_con_juego_activo = $juego->maquinas()
    ->whereIn('id_casino',$reglaCasinos);

    if($mtms_accesibles_con_juego_activo->count()>0){
      $errores = [];
      foreach($mtms_accesibles_con_juego_activo->get() as $mtm){
        $errores[] = $mtm->nro_admin;
      }
      return $this->errorOut(['maquina_juego_activo' => $errores]);
    }

    $mtms_accesibles = $juego->maquinas_juegos()
    ->whereIn('id_casino',$reglaCasinos)->get();

    DB::transaction(function() use($juego,$reglaCasinos,$mtms_accesibles){
      foreach($mtms_accesibles as $mtm){
        $mtm->juegos()->detach($juego->id_juego);
        $mtm->save();
      }
      $juego->casinos()->detach($reglaCasinos);
      $juego->save();
      // @TODO: Si tuvieramos GLISOFT por casino, podriamos detachearlo aca nomas
      // Solo si no queda asociado a ningun casino se puede eliminar el juego
      $casRestantes= DB::table('casino_tiene_juego')->where('casino_tiene_juego.id_juego','=',$juego->id_juego)->count();
      if ($casRestantes==0){
        foreach ($juego->tablasPago as $tabla) {
          TablaPagoController::getInstancia()->eliminarTablaPago($tabla->id_tabla_pago);
        }        
        $juego->setearGliSofts([]);
        $juego->delete();
      }
    });

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
                  ->wherein('casino_tiene_juego.id_casino',$reglaCasinos)
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

  public function obtenerTablasDePago($id){
    $juego=Juego::find($id);
    if($juego != null){
    return['tablasDePago' => $juego->tablasPago];
  }else{
    return['tablasDePago' => null];
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
