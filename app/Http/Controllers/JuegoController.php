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
use App\Plataforma;
use Validator;
use Storage;
use View;
use Dompdf\Dompdf;
use App\Http\Controllers\CacheController;

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
    $plataformas = $usuario->plataformas;
    $proveedores = DB::table('juego')->select('proveedor')
    ->whereNull('deleted_at')->distinct()
    ->orderBy('proveedor','asc')->get()->pluck('proveedor')->toArray();
    return view('seccionJuegos' , 
    ['certificados' => GliSoftController::getInstancia()->gliSoftsPorPlataformas($plataformas),
     'monedas' => TipoMoneda::all(),
     'categoria_juego' => CategoriaJuego::all(),
     'estado_juego' => EstadoJuego::all(),
     'plataformas' => $plataformas,
     'proveedores' => $proveedores
    ]);
  }

  public function obtenerJuego($id){
    $juego = Juego::find($id);
    if(is_null($juego)){
      return response()->json(['acceso'=>['']],422);
    }

    return [
      'juego' => $juego , 
      'certificados' => $juego->gliSoft ,
      'plataformas' => DB::table('plataforma_tiene_juego')->join('plataforma','plataforma.id_plataforma','=','plataforma_tiene_juego.id_plataforma')
      ->where('id_juego',$id)->get()
    ];
  }

  public function obtenerLogs($id){
    //Empiezo con el actual... antes no se logeaba cuando hacia guardarJuego... mala mia, saldran duplicados (?)
    $juego = $this->obtenerJuego($id);
    $logs = $juego['juego']->logs()->orderBy('updated_at','desc')->get();
    $juego['juego'] = $juego['juego']->toArray();
    $juego['juego']['updated_at'] = 'ACTUAL ' . $juego['juego']['updated_at'];
    $ret = [$juego];
    foreach($logs as &$l){
      $ret[] = [
        'juego' => $l, 
        'certificados' => $l->gliSoft,
        'plataformas' => DB::table('plataforma_tiene_juego')->join('plataforma','plataforma.id_plataforma','=','plataforma_tiene_juego.id_plataforma')
        ->where('id_juego',$id)->get(),
        'usuario' => $l->usuario,
      ];
    }
    return $ret;
  }

  public function guardarJuego(Request $request){
    Validator::make($request->all(), [
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'proveedor' => 'nullable|string|max:100',
      'plataformas' => 'required|array',
      'plataformas.*.id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
      'plataformas.*.id_estado_juego' => 'nullable|integer|exists:estado_juego,id_estado_juego',
    ], array(), self::$atributos)->after(function ($validator) {
      $data = $validator->getData();
      if($data['movil'] == 0 && $data['escritorio'] == 0){
        $validator->errors()->add('tipos','validation.required');
      }
      if($validator->errors()->any()) return;

      $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas;
      $plataformas_usuario = [];
      foreach($plataformas as $p){
        $plataformas_usuario[] = $p->id_plataforma;
      }

      foreach($data['plataformas'] as $p){
        if(!in_array($p['id_plataforma'],$plataformas_usuario)){
          $validator->errors()->add('id_juego', 'El usuario no puede acceder a este juego');
          break;
        }
      }

      if(!is_null($data['cod_juego'])){
        //El codigo del juego es unico
        $juegos_mismo_codigo = DB::table('juego as j')
        ->where('j.cod_juego',$data['cod_juego']);
        if($juegos_mismo_codigo->count() > 0){
          $validator->errors()->add('cod_juego', 'validation.unique');
        }
      }
    })->validate();

    $juego = new Juego;
    $log  = new LogJuego;
    DB::transaction(function() use($juego,$log,$request){
      foreach(['nombre_juego','cod_juego','denominacion_juego','porcentaje_devolucion','escritorio',
               'movil','codigo_operador','proveedor','id_tipo_moneda','id_categoria_juego'] as $attr){
        $juego->{$attr} = $request->{$attr};
        $log->{$attr} = $request->{$attr};//Se guarda todo lo que mando en un log nuevo siempre
      }
      $juego->save();

      $updated_at = date('Y-m-d H:i:s');//@HACK: No puedo usar el updated_at del juego por la transacción, me agarra el valor anterior...
      $log->id_juego   = $juego->id_juego;
      $log->motivo     = $request->motivo ?? '';
      $log->created_at = $updated_at;
      $log->updated_at = $updated_at;
      $log->deleted_at = null;
      $log->id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $log->save();
      
      $syncarr = [];
      foreach($request->plataformas as $p){
        if(!is_null($p['id_estado_juego'])) $syncarr[$p['id_plataforma']] = ['id_estado_juego' => $p['id_estado_juego']];
      }

      $juego->plataformas()->sync($syncarr);
      $log->plataformas()->sync($syncarr);
  
      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
        $log->setearGliSofts($request->certificados,True);
      }

      $juego->save();
      $log->save();

      CacheController::getInstancia()->invalidarDependientes('juego');
    });

    return ['juego' => $juego];
  }

  public function modificarJuego(Request $request){
    $plataformas_usuario = [];
    Validator::make($request->all(), [
      'id_juego' => 'required|integer|exists:juego,id_juego',
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable','regex:/^\d?\w(.|-|_|\d|\w)*$/','max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'proveedor' => 'nullable|string|max:100',
      'plataformas' => 'required|array',
      'plataformas.*.id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
      'plataformas.*.id_estado_juego' => 'nullable|integer|exists:estado_juego,id_estado_juego',
    ], array(), self::$atributos)->after(function ($validator) use(&$plataformas_usuario){
      $data = $validator->getData();
      if($data['movil'] == 0 && $data['escritorio'] == 0){
        $validator->errors()->add('tipos','validation.required');
      }
      if($validator->errors()->any()) return;


      $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas;
      foreach($plataformas as $p){
        $plataformas_usuario[] = $p->id_plataforma;
      }

      foreach($data['plataformas'] as $p){
        if(!in_array($p['id_plataforma'],$plataformas_usuario)){
          $validator->errors()->add('id_juego', 'El usuario no puede acceder a este juego');
          break;
        }
      }

      if(!is_null($data['cod_juego'])){
        //El codigo del juego es unico
        $juegos_mismo_codigo = DB::table('juego as j')
        ->where('j.cod_juego',$data['cod_juego'])
        ->where('j.id_juego','<>',$data['id_juego']);
        if($juegos_mismo_codigo->count() > 0){
          $validator->errors()->add('cod_juego', 'validation.unique');
        }
      }
    })->validate();


    $juego = Juego::find($request->id_juego);
    $log = new LogJuego;
    DB::transaction(function() use($request,$log,$juego,$plataformas_usuario){
      foreach(['nombre_juego','cod_juego','denominacion_juego','porcentaje_devolucion','escritorio',
      'movil','codigo_operador','proveedor','id_tipo_moneda','id_categoria_juego'] as $attr){
        $juego->{$attr} = $request->{$attr};
        $log->{$attr} = $request->{$attr};//Se guarda todo lo que mando en un log nuevo siempre
      }
      $juego->save();

      $updated_at = date('Y-m-d H:i:s');//@HACK: No puedo usar el updated_at del juego por la transacción, me agarra el valor anterior...
      $log->id_juego   = $request->id_juego;
      $log->motivo     = $request->motivo ?? '';
      $log->created_at = $updated_at;
      $log->updated_at = $updated_at;
      $log->deleted_at = null;
      $log->id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $log->save();

      $log_anterior = LogJuego::where('id_juego',$juego->id_juego)->whereNull('deleted_at')
      ->where('id_juego_log_norm','<>',$log->id_juego_log_norm)
      ->orderBy('updated_at','desc')->take(1)
      ->get()->first();

      if(!is_null($log_anterior)){
        $log_anterior->deleted_at = $updated_at;
        $log_anterior->save();
      }

      $plataformas_enviadas = [];
      foreach($request->plataformas as $p){
        if(!is_null($p['id_estado_juego'])) $plataformas_enviadas[$p['id_plataforma']] = ['id_estado_juego' => $p['id_estado_juego']];
      }

      $syncarr = [];
      foreach(Plataforma::all() as $p){
        $id = $p->id_plataforma;
        $le_pertenece = in_array($id,$plataformas_usuario);
        $lo_envio = array_key_exists($id,$plataformas_enviadas);
        if($le_pertenece && $lo_envio){
          //Lo seteamos
          $syncarr[$id] = $plataformas_enviadas[$id];
        }
        else if($le_pertenece && !$lo_envio){
          //Se ignora, eliminandolo de las plataformas al syncear
        }
        else if(!$le_pertenece && $lo_envio){
          //No deberia pasar porque se chequea en la validacion, retornaria error antes
        }
        else if(!$le_pertenece && !$lo_envio){
          //Lo mantenemos
          $relacion = DB::table('plataforma_tiene_juego')->where('id_juego',$juego->id_juego)->where('id_plataforma',$id)->first();
          if(!is_null($relacion)) $syncarr[$id] = ['id_estado_juego' =>  $relacion->id_estado_juego];
        }
      }

      $juego->plataformas()->sync($syncarr);
      $log->plataformas()->sync($syncarr);
  
      foreach($juego->gliSoft as $gli){
        $juego->gliSoft()->detach($gli->id_gli_soft);
      }

      if(isset($request->certificados)){
        $juego->setearGliSofts($request->certificados,True);
        $log->setearGliSofts($request->certificados,True);
      }

      $juego->save();
      $log->save();

      CacheController::getInstancia()->invalidarDependientes('juego');
    });

    return ['juego' => $juego];
  }

  public function eliminarJuego($id){
    $juego = Juego::find($id);
    if(is_null($juego)) return ['juego' => null];
    $juego->delete();
    return ['juego' => $juego];
  }

  public function buscarJuegos(Request $request){
    $reglas=array();
    if(!empty($request->nombreJuego) ){
      $reglas[]=['juego.nombre_juego', 'like' , '%' . $request->nombreJuego  .'%'];
    }
    if(!empty($request->cod_juego) && $request->cod_juego != '-'){
      $reglas[]=['juego.cod_juego', 'like' , '%' . $request->cod_juego  .'%'];
    }
    if(!empty($request->proveedor) && $request->proveedor != '-'){//Si manda 1 guion significa sin proveedor
      //Tengo que hacer esto porque no tiene validacion de regex cuando se guarda, puede mandar solo guiones
      //Si manda n+1 guiones, significa n guiones
      $proveedor = $request->proveedor;
      if(substr_count($request->proveedor,"-") == count($request->proveedor))
        $proveedor = substr($proveedor,1);
      $reglas[]=['juego.proveedor', 'like' , '%' . $proveedor  .'%'];
    }
    if(!empty($request->id_plataforma)){
      $reglas[] = ['plataforma_tiene_juego.id_plataforma','=',$request->id_plataforma];
    }
    if(!empty($request->id_categoria_juego)){
      $reglas[] = ['juego.id_categoria_juego','=',$request->id_categoria_juego];
    }
    if(!empty($request->sistema)){
      $escritorio = $request->sistema == "1";
      $movil      = $request->sistema == "2";
      $escritorio_y_movil = $request->sistema == "3";
      $reglas[] = ['juego.escritorio','=',$escritorio || $escritorio_y_movil];
      $reglas[] = ['juego.movil','=',$movil || $escritorio_y_movil];
    }
    if(!is_null($request->pdev_menor)){
      $reglas[] = ['juego.porcentaje_devolucion','>=',$request->pdev_menor];
    }
    if(!is_null($request->pdev_mayor)){
      $reglas[] = ['juego.porcentaje_devolucion','<=',$request->pdev_mayor];
    }

    $sort_by = $request->sort_by;

    $resultados = DB::table('juego')
    ->selectRaw("juego.*,GROUP_CONCAT(DISTINCT(IFNULL(gli_soft.nro_archivo, '-')) separator ', ') as certificados")
    ->leftjoin('juego_glisoft as jgl','jgl.id_juego','=','juego.id_juego')
    ->leftjoin('gli_soft',function($j){
      return $j->on('gli_soft.id_gli_soft','=','jgl.id_gli_soft')->wherenull('gli_soft.deleted_at');
    })
    ->leftjoin('plataforma_tiene_juego','plataforma_tiene_juego.id_juego','=','juego.id_juego')
    ->leftjoin('plataforma_tiene_casino','plataforma_tiene_juego.id_plataforma','=','plataforma_tiene_casino.id_plataforma')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->whereNull('juego.deleted_at')
    ->where($reglas);
    
    $plataformas_usuario = [];
    foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas as $p){
      $plataformas_usuario[] = $p->id_plataforma;
    }
              
    if(!empty($request->id_estado_juego)){
      $resultados = $resultados->where('plataforma_tiene_juego.id_estado_juego','=',$request->id_estado_juego);
      $resultados = $resultados->whereIn('plataforma_tiene_juego.id_plataforma',$plataformas_usuario);
    }

    if($request->cod_juego == '-') $resultados = $resultados->whereNull('juego.cod_juego');
    if($request->proveedor == '-') $resultados = $resultados->whereNull('juego.proveedor');

    if(!empty($request->certificado)){
      if(trim($request->certificado) == '-'){//Si me envia un gion, significa sin certificado
        $resultados = $resultados->whereNull('gli_soft.id_gli_soft');
      }
      else {
        $codigos = explode(',',$request->certificado);
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
    $resultados = $resultados->toArray();

    $resultados['data'] = array_map(function($v) use ($plataformas_usuario){
      $juego = Juego::find($v->id_juego);
      $plats = [];
      foreach($juego->plataformas as $p){
        if(in_array($p->id_plataforma,$plataformas_usuario))
          $plats[] = $p->codigo . ": " . EstadoJuego::find($p->pivot->id_estado_juego)->codigo;
      }
      $v->estado = implode(", ",$plats);
      return $v;
    },$resultados['data']);
    return $resultados;
  }

  public function asociarGLI($listaJuegos, $id_gli_soft, $mantener_los_de_plataformas = []){
    $lista_limpia = [];
    foreach ($listaJuegos as $id_juego) {
       $juego=Juego::find($id_juego);
       if(is_null($juego)) continue;
       $lista_limpia[] = $id_juego;
    }
    //Por si manda varias veces el mismo juego lo filtro
    $lista_limpia = array_unique($lista_limpia);
    $GLI = GliSoft::find($id_gli_soft);
    if($GLI != null){
      $mantenidos = [];
      foreach($GLI->juegos as $j){
        $mantener = $j->plataformas()->whereIn('plataforma.id_plataforma',$mantener_los_de_plataformas)->count() > 0;
        if($mantener) $mantenidos[] = $j->id_juego;
      }
      $asociar = array_unique(array_merge($lista_limpia,$mantenidos));
      $GLI->setearJuegos([]);
      $GLI->setearJuegos($asociar,true);
      $GLI->save();
    }
  }

  public function generarDiferenciasEstadosJuegos(Request $request){
    //Esto se puede pasar a usar una tabla temporal y hacerlo por SQL si demora mucho, no deberia porque pocas deberian reportar diferencias
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($user->plataformas()->where('plataforma.id_plataforma',$request->id_plataforma)->count() <= 0)
      return response()->json(["errores" => ["No puede acceder a la plataforma"]],422);
    
    $resultado = [];
    $codigo_idx = false;
    $nombre_idx = false;
    $estado_idx = false;

    $query = DB::table('plataforma_tiene_juego')
    ->select('estado_juego.nombre')
    ->join('juego','juego.id_juego','=','plataforma_tiene_juego.id_juego')
    ->join('estado_juego','estado_juego.id_estado_juego','=','plataforma_tiene_juego.id_estado_juego')
    ->where('plataforma_tiene_juego.id_plataforma','=',$request->id_plataforma);

    //Los que esperaba que estaban activos, inactivos, ausentes(-1)
    $resultado = ["No existe" => []];
    foreach(EstadoJuego::all() as $e){
      $resultado[$e->nombre] = [];
    }

    if (($gestor = fopen($request->archivo->getRealPath(), "r")) !== FALSE) {
      if(($datos = fgetcsv($gestor, 1000, ",")) !== FALSE){
        $codigo_idx = array_search("GameCode",$datos);
        $nombre_idx = array_search("GameName",$datos);
        $estado_idx = array_search("IsPublished",$datos);
        if($codigo_idx === false || $nombre_idx === false || $estado_idx === false){
          fclose($gestor);
          return response()->json(["errores" => ["Error en el formato del archivo."]],422);
        }
      }
      else return response()->json(["errores" => ["Error en el formato del archivo."]],422);

      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
        $r = [];
        $r["juego"] = utf8_encode($datos[$nombre_idx]);//CCO viene con codificacion en latino... necesito encodearlo en utf8 para mostrarlo
        $cod_juego = $datos[$codigo_idx];
        $r["codigo"] = $cod_juego;
        $estado   = strtoupper($datos[$estado_idx]);
        $estado_t = $estado == "TRUE"  || $estado == "HABILITADO-ACTIVO";
        $estado_f = $estado == "FALSE" || $estado == "HABILITADO-INACTIVO";
        $r["estado_recibido"] = $estado_t? "Activo": ($estado_f? "Inactivo" : $estado);
        $estado_esperado = (clone $query)->where('juego.cod_juego','=',$cod_juego)->first();
        if(is_null($estado_esperado)) $estado_esperado = "No existe";
        else $estado_esperado = $estado_esperado->nombre;
        if($estado_esperado != $r["estado_recibido"])
          $resultado[$estado_esperado][] = $r;
      }
      fclose($gestor);
    }
    foreach($resultado as &$v){
      usort($v,function($a,$b){
        return strnatcmp($a["juego"],$b["juego"])?? strnatcmp($a["codigo"],$b["codigo"]);
      });
    }
    $plataforma = Plataforma::find($request->id_plataforma)->codigo;
    $view = View::make('planillaDiferenciasEstadosJuegos',compact('resultado','plataforma'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return base64_encode($dompdf->output());
  }
}