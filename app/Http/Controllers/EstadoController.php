<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Plataforma;
use View;
use Dompdf\Dompdf;
use App\Http\Controllers\LectorCSVController;
use App\EstadoJuegoImportado;
use App\ImportacionEstadoJuego;
use App\EstadoJuego;
use App\Jugador;
use App\ImportacionEstadoJugador;

class EstadoController extends Controller
{
  public function informeEstadoJugadores(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Estado Jugadores','informeEstadoJugadores');
    return view('seccionInformeEstadoJugadores' , [
      'plataformas' => $usuario->plataformas,
      'estados'     => DB::table('jugador')->select('estado')->distinct()->get()->pluck('estado'),
      'sexos'       => DB::table('jugador')->select('sexo')->distinct()->get()->pluck('sexo'),
    ]);
  }
  
  public function buscarJugadores(Request $request){
    $reglas = [];
    if(!is_null($request->plataforma)) $reglas[] = ['j.id_plataforma','=',$request->plataforma];
    if(!is_null($request->codigo)) $reglas[] = ['j.codigo','LIKE',$request->codigo];
    if(!is_null($request->estado)) $reglas[] = ['j.estado','LIKE',$request->estado];
    {
      $edad_sql = DB::raw("DATEDIFF(CURDATE(),j.fecha_nacimiento)/365.25");//@HACK: aproximado...
      if(!empty($request->edad_desde)){
        $reglas[] = [$edad_sql,'>=',$request->edad_desde];
      }
      if(!empty($request->edad_hasta)){
        $reglas[] = [$edad_sql,'<=',$request->edad_hasta];
      }
    }
    if(!is_null($request->sexo))    $reglas[] = ['j.sexo','LIKE',$request->sexo];
    if(!is_null($request->localidad)) $reglas[] = ['j.localidad','LIKE',$request->localidad];
    if(!is_null($request->provincia)) $reglas[] = ['j.provincia','LIKE',$request->provincia];
    if(!is_null($request->fecha_autoexclusion_desde)) $reglas[] = ['j.fecha_autoexclusion','>=',$request->fecha_autoexclusion_desde];
    if(!is_null($request->fecha_autoexclusion_hasta)) $reglas[] = ['j.fecha_autoexclusion','<=',$request->fecha_autoexclusion_hasta];
    if(!is_null($request->fecha_alta_desde)) $reglas[] = ['j.fecha_alta','>=',$request->fecha_alta_desde];
    if(!is_null($request->fecha_alta_hasta)) $reglas[] = ['j.fecha_alta','<=',$request->fecha_alta_hasta];
    if(!is_null($request->fecha_ultimo_movimiento_desde)) $reglas[] = ['j.fecha_ultimo_movimiento','>=',$request->fecha_ultimo_movimiento_desde];
    if(!is_null($request->fecha_ultimo_movimiento_hasta)) $reglas[] = ['j.fecha_ultimo_movimiento','<=',$request->fecha_ultimo_movimiento_hasta];

    $sort_by = [
      'orden' => 'asc',
      'columna' => 'j.id_plataforma',
    ];

    if(!is_null($request->sort_by)) $sort_by = $request->sort_by;

    //Retorno el ultimo estado del jugador
    $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas->map(function($c){
      return $c->id_plataforma;
    });

    $data = DB::table('jugador as j')
    ->select('j.*','p.codigo as plataforma')
    ->join('plataforma as p','p.id_plataforma','=','j.id_plataforma')
    ->whereNull('j.valido_hasta')
    ->where($reglas)->whereIn('j.id_plataforma',$plataformas)
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();
    
    $totales = DB::table('jugador as j')
    ->selectRaw('COUNT(distinct j.codigo) as total')
    ->whereNull('j.valido_hasta')
    ->where($reglas)->whereIn('j.id_plataforma',$plataformas);
    if(!is_null($request->plataforma)){
      $totales = $totales->where('j.id_plataforma','=',$request->plataforma);
    }
    $totales = $totales->groupBy('j.id_plataforma')->get()->pluck('total');
    $total = 0;
    foreach($totales as $t) $total+=$t;
    
    return [
      'current_page' => $request->page,
      'per_page' => $request->page_size,
      'from' => (($request->page-1)*$request->page_size + 1),
      'to' => (($request->page)*$request->page_size),
      'last_page' => ceil($total/$request->page_size),
      'total' => $total,
      'data' => $data,
    ];
  }
  public function historialJugador(Request $request){
     $sort_by = $request->sort_by ?? [
      'orden' => 'desc',
      'columna' => 'iej.fecha_importacion',
    ];
    $jug = Jugador::find($request->id_jugador);
    
    return DB::table('importacion_estado_jugador as iej')
    ->select('j.*','iej.fecha_importacion')
    ->leftJoin('jugador as j',function($j){
      return $j->on('j.id_plataforma','=','iej.id_plataforma')
      ->on('j.fecha_importacion','<=','iej.fecha_importacion')
      ->on(function($j){
        return $j->on('j.valido_hasta','>=','iej.fecha_importacion')
        ->orWhereNull('j.valido_hasta');
      });
    })
    ->where('iej.id_plataforma','=',$jug->id_plataforma)
    ->where('j.codigo','=',$jug->codigo)
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);
  }
  public function informeEstadoJuegos(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Estado Juego','informeEstadoJuegos');
    return view('seccionInformeEstadoJuegos' , [
      'plataformas' => $usuario->plataformas,
      'estados'     => DB::table('estado_juego_importado')->select('estado')->distinct()->get()->pluck('estado')->toArray(),
      'categorias'  => DB::table('datos_juego_importado')->select('categoria')->distinct()->get()->pluck('categoria')->toArray(),
      'tecnologias'  => DB::table('datos_juego_importado')->select('tecnologia')->distinct()->get()->pluck('tecnologia')->toArray(),
    ]);
  }
  public function buscarJuegos(Request $request){
    $reglas = [];
    if(!is_null($request->plataforma)) $reglas[] = ['p.id_plataforma','=',$request->plataforma];
    if(!is_null($request->codigo)) $reglas[] = ['dj.codigo','LIKE','%'.$request->codigo.'%'];
    if(!is_null($request->nombre)) $reglas[] = ['dj.nombre','LIKE','%'.$request->nombre.'%'];
    //Le agrego un keyword porque a veces han mandado este campo vacio
    if($request->estado != "!!TODO!!") $reglas[] = [DB::raw('TRIM(esj.estado)'),'=',DB::raw("TRIM('$request->estado')")];
    if($request->categoria != "!!TODO!!") $reglas[] = [DB::raw('TRIM(dj.categoria)'),'=',DB::raw("TRIM('$request->categoria0)")];
    if($request->tecnologia != "!!TODO!!") $reglas[] = [DB::raw('TRIM(dj.tecnologia)'),'=',DB::raw("TRIM('$request->tecnologia')")];
    
    $sort_by = [
      'orden' => 'asc',
      'columna' => 'codigo',
    ];

    if(!is_null($request->sort_by)) $sort_by = $request->sort_by;

    //Retorno el ultimo estado del jugador
    $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas->map(function($c){
      return $c->id_plataforma;
    });

    $ret = DB::table('datos_juego_importado as dj')
    ->selectRaw('p.codigo as plataforma,dj.codigo,dj.nombre,dj.categoria,dj.tecnologia,ej.estado,ej.id_estado_juego_importado')
    ->join('estado_juego_importado as ej','ej.id_datos_juego_importado','=','dj.id_datos_juego_importado')
    ->join('importacion_estado_juego as iej','iej.id_importacion_estado_juego','=','ej.id_importacion_estado_juego')
    ->join('plataforma as p','p.id_plataforma','=','iej.id_plataforma')
    ->where('ej.es_ultimo_estado_del_juego',1)
    ->where($reglas)->whereIn('p.id_plataforma',$plataformas)
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);
    return $ret;
  }
  public function historialJuego(Request $request){
    //$request := {id_estado_juego_importado,page,page_size,sort_by: {columna, orden}}
    $ej = EstadoJuegoImportado::find($request->id_estado_juego_importado);
    $dj = $ej->datos;
    $iej = $ej->importacion;
    $sort_by = $request->sort_by ?? [
      'orden' => 'desc',
      'columna' => 'fecha_importacion',
    ];
    return DB::table('datos_juego_importado as dj')->select('dj.*','ej.*','iej.*')
    ->join('estado_juego_importado as ej','ej.id_datos_juego_importado','=','dj.id_datos_juego_importado')
    ->join('importacion_estado_juego as iej','iej.id_importacion_estado_juego','=','ej.id_importacion_estado_juego')
    ->where('dj.codigo','=',$dj->codigo)
    ->where('iej.id_plataforma','=',$iej->id_plataforma)
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->paginate($request->page_size);
  }

  public function generarDiferenciasEstadosJuegos(Request $request){
    //Esto se puede pasar a usar una tabla temporal y hacerlo por SQL si demora mucho, no deberia porque pocas deberian reportar diferencias
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($user->plataformas()->where('plataforma.id_plataforma',$request->id_plataforma)->count() <= 0)
      return response()->json(["errores" => ["No puede acceder a la plataforma"]],422);
    
    $importacion = ImportacionEstadoJuego::where('fecha_importacion','=',$request->fecha_importacion)
    ->where('id_plataforma','=',$request->id_plataforma)->get()->first();
    if(is_null($importacion)){
      return response()->json(["errores" => ["No existe la importacion"]],422);
    }

    $tabla_juego = 'juego';
    $tabla_plataforma_juego = 'plataforma_tiene_juego';
    $id_tabla_juego = 'id_juego';
    $condicion_join = function($j){
      return $j->on('j.cod_juego','=','dji.codigo')->whereNull('j.deleted_at');
    };
    if($request->cambio_fecha_sistema){
      $tabla_juego = 'juego_log_norm';
      $tabla_plataforma_juego = 'plataforma_tiene_juego_log_norm';
      $id_tabla_juego = 'id_juego_log_norm';
      $fecha_sistema = $request->fecha_sistema;
      $condicion_join = function($j) use ($fecha_sistema){
        //Fue creado antes y se borro despues (o no se borro)
        return $j->on('j.cod_juego','=','dji.codigo')->where('j.created_at','<=',$fecha_sistema)->where(function ($q) use ($fecha_sistema){
          return $q->where('j.deleted_at','>=',$fecha_sistema)->orWhereNull('j.deleted_at');
        });
      };
    }

    $query = DB::table('importacion_estado_juego as iej')
    ->select('dji.nombre','dji.codigo','eji.estado as estado_recibido',DB::raw('IFNULL(ej.nombre,"No existe") as estado_esperado'))
    ->join('estado_juego_importado as eji','eji.id_importacion_estado_juego','=','iej.id_importacion_estado_juego')
    ->join('datos_juego_importado as dji','dji.id_datos_juego_importado','=','eji.id_datos_juego_importado')//FIN datos importacion
    ->leftJoin("$tabla_juego as j",$condicion_join)
    ->leftJoin("$tabla_plataforma_juego as pj",function($j) use ($id_tabla_juego){//Me quedo solo con los de la plataforma y saco el estado
      return $j->on('pj.id_plataforma','=','iej.id_plataforma')->on("pj.$id_tabla_juego",'=',"j.$id_tabla_juego");
    })
    ->leftJoin('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
    ->where('iej.fecha_importacion','=',$request->fecha_importacion)
    ->where('iej.id_plataforma','=',$request->id_plataforma)
    ->where(function ($w){
      return $w->whereRaw('LOCATE(CONCAT("|",eji.estado,"|"),ej.conversiones) = 0')->orWhereNull('ej.nombre');
    })
    ->orderBy('dji.nombre','asc')->orderBy('dji.codigo','asc');

    //Los que esperaba que estaban activos, inactivos, ausentes
    $resultado = ["No existe" => []];
    foreach(EstadoJuego::all() as $e){
      $resultado[$e->nombre] = [];
    }
    foreach($query->get() as $e){
      $resultado[$e->estado_esperado][] = [
        "juego" => $e->nombre,"codigo" => $e->codigo, "estado_recibido" => $e->estado_recibido,
      ];
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
  
  public function eliminarEstadoJugadores($id){
    return DB::transaction(function() use ($id){
      $imp = ImportacionEstadoJugador::find($id);
      $prox_imp = ImportacionEstadoJugador::where([
        ['id_plataforma','=',$imp->id_plataforma],
        ['fecha_importacion','>',$imp->fecha_importacion]
      ])->orderBy('fecha_importacion','asc')->first();
      $prox_imp = $prox_imp? $prox_imp->fecha_importacion : null;
      
      $query_prox = LectorCSVController::getInstancia()->query_jugProximos($imp->id_plataforma,$imp->fecha_importacion);
      $pdo = DB::connection('mysql')->getPdo();
      
      //Re-valido los invalidados por que se elimnaron en la importacion
      $pdo->prepare("UPDATE jugador j_ant
      LEFT JOIN ${query_prox['sql']} j_prox 
        ON (j_ant.id_plataforma = j_prox.id_plataforma
        AND j_ant.codigo = j_prox.codigo)
      SET j_ant.valido_hasta = DATE_SUB(
        LEAST(COALESCE(j_prox.fecha_importacion,:prox_imp1),COALESCE(:prox_imp2,j_prox.fecha_importacion)),
        INTERVAL 1 DAY
      )
      WHERE j_ant.id_plataforma = :id_plataforma AND j_ant.valido_hasta = DATE_SUB(:fecha_importacion1,INTERVAL 1 DAY)
      AND j_ant.fecha_importacion < :fecha_importacion2")->execute(array_merge([
        'prox_imp1' => $prox_imp,
        'prox_imp2' => $prox_imp,
        'id_plataforma' => $imp->id_plataforma,
        'fecha_importacion1' => $imp->fecha_importacion,
        'fecha_importacion2' => $imp->fecha_importacion,
      ],$query_prox['params']));
      
      //Si la proxima importacion depende de un jugador importado
      //lo muevo a esa importacion
      if(!is_null($prox_imp)){
        $pdo->prepare('UPDATE jugador j
        SET j.fecha_importacion = :prox_imp1
        WHERE j.id_plataforma = :id_plataforma AND j.fecha_importacion = :fecha_importacion
        AND (j.valido_hasta IS NULL OR j.valido_hasta >= :prox_imp2)')->execute([
          'prox_imp1' => $prox_imp,
          'id_plataforma' => $imp->id_plataforma,
          'fecha_importacion' => $imp->fecha_importacion,
          'prox_imp2' => $prox_imp,
        ]);
      }
      
      //Borro los que quedaron con fecha_importacion porque no se usan por la importacion posterior
      $pdo->prepare('DELETE FROM jugador 
        WHERE id_plataforma = :id_plataforma AND fecha_importacion = :fecha_importacion')->execute([
        'id_plataforma' => $imp->id_plataforma,'fecha_importacion' => $imp->fecha_importacion
      ]);
    
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
}

