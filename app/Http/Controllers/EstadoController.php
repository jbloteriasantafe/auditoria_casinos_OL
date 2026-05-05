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
    //Retorno el ultimo estado del jugador
    $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']
    ->plataformas->pluck('id_plataforma')->toArray();
    if(!in_array($request->plataforma ?? null,$plataformas)){
      return [];
    }
    
    $reglas = [];
    if(!is_null($request->codigo)) $reglas[] = ['j.codigo','LIKE',$request->codigo];
    if(!is_null($request->estado)) $reglas[] = ['j.estado','LIKE',$request->estado];
    $hoy = date('Y-m-d');
    {
      $edad_sql = DB::raw("DATEDIFF('$hoy',j.fecha_nacimiento)/365.25");//@HACK: aproximado...
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
    if(!is_null($request->sort_by)) $sort_by = $request->sort_by;

    $sort_by = [
      'orden' => 'asc',
      'columna' => 'j.codigo',
    ];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $data = DB::table('jugador as j')
    ->select('j.*','p.codigo as plataforma')
    ->join('plataforma as p','p.id_plataforma','=','j.id_plataforma')
    ->where('j.id_plataforma','=',$request->plataforma)
    ->where('j.fecha_importacion','<=',$hoy)
    ->where('j.valido_hasta','>=',$hoy)
    ->where($reglas)
    ->orderBy($sort_by['columna'] ?? 'j.id_plataforma',$sort_by['orden'] ?? 'asc')
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get()->transform(function(&$j){
      unset($j->hash);
      return $j;
    });
    
    $total = DB::table('jugador as j')
    ->where('j.id_plataforma','=',$request->plataforma)
    ->where('j.fecha_importacion','<=',$hoy)
    ->where('j.valido_hasta','>=',$hoy)
    ->count();
    
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
    $attrs = LectorCSVController::getInstancia()->jugador_prefix_attrs('j.');
    return DB::table('importacion_estado_jugador as iej')
    ->selectRaw(implode(',',$attrs).',iej.fecha_importacion')
    ->leftJoin('jugador as j',function($j){
      return $j->on('j.id_plataforma','=','iej.id_plataforma')
      ->on('j.fecha_importacion','<=','iej.fecha_importacion')
      ->on('j.valido_hasta','>=','iej.fecha_importacion');
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
      
      $prev_imp = ImportacionEstadoJugador::where([
        ['id_plataforma','=',$imp->id_plataforma],
        ['fecha_importacion','<',$imp->fecha_importacion]
      ])->orderBy('fecha_importacion','desc')->first();
      $prev_imp = $prev_imp? $prev_imp->fecha_importacion : null;
      
      $prox_imp = ImportacionEstadoJugador::where([
        ['id_plataforma','=',$imp->id_plataforma],
        ['fecha_importacion','>',$imp->fecha_importacion]
      ])->orderBy('fecha_importacion','asc')->first();
      $prox_imp = $prox_imp? $prox_imp->fecha_importacion : null;
      
      //Busco los jugadores anteriores que fueron invalidados
      //por la importación y los seteo validos hasta la proxima importación
      //si no hay proxima fecha de importacion setea la fecha maxima
      DB::statement("UPDATE jugador j_prev
      SET j_prev.valido_hasta = COALESCE(DATE_SUB(:prox_imp, INTERVAL 1 DAY),'9999-12-31')
      WHERE 
          j_prev.id_plataforma = :id_plataforma 
      AND j_prev.fecha_importacion < :fecha_importacion1
      AND j_prev.valido_hasta = DATE_SUB(:fecha_importacion2,INTERVAL 1 DAY)",[
        'prox_imp' => $prox_imp,
        'id_plataforma' => $imp->id_plataforma,
        'fecha_importacion1' =>$imp->fecha_importacion,
        'fecha_importacion2' => $imp->fecha_importacion
      ]);
      
      //Si la proxima importacion depende de un jugador importado
      //lo muevo a esa importacion
      if($prox_imp !== null){
        DB::statement('UPDATE jugador j
        SET j.fecha_importacion = :prox_imp1
        WHERE j.id_plataforma = :id_plataforma 
        AND j.fecha_importacion = :fecha_importacion
        AND j.valido_hasta >= :prox_imp2',[
          'prox_imp1' => $prox_imp,
          'id_plataforma' => $imp->id_plataforma,
          'fecha_importacion' => $imp->fecha_importacion,
          'prox_imp2' => $prox_imp,
        ]);
      }
      
      //Borro los que quedaron porque quiere decir que no son importantes
      //para la proxima importación
      DB::statement('DELETE FROM jugador 
        WHERE id_plataforma = :id_plataforma 
        AND fecha_importacion = :fecha_importacion',[
        'id_plataforma' => $imp->id_plataforma,
        'fecha_importacion' => $imp->fecha_importacion
      ]);
      
      //Me fijo si puedo mergear jugadores que quedaron iguales (en estados a -> b -> a y se elimina b, queria a -> a, queremos que quede solo a)
      if ($prev_imp && $prox_imp) {
        // 1. Extendemos el 'valido_hasta' de los registros anteriores que coinciden con los nuevos
        $j_prev_igual_j_prox = LectorCSVController::getInstancia()->jugador_comp_attrs('j_prev.','=','j_prox.','AND');
        DB::statement("UPDATE jugador j_prev
        JOIN jugador j_prox ON 
            j_prox.codigo = j_prev.codigo AND 
            j_prox.id_plataforma = j_prev.id_plataforma AND
            j_prox.fecha_importacion = :prox_imp1
        
        SET j_prev.valido_hasta = j_prox.valido_hasta
        
        WHERE 
            j_prev.id_plataforma = :id_plataforma
        AND j_prev.fecha_importacion <= :prev_imp
        AND j_prev.valido_hasta = DATE_SUB(:prox_imp2, INTERVAL 1 DAY)
        AND j_prev.hash = j_prox.hash
        AND( ".$j_prev_igual_j_prox." )", [
            'id_plataforma' => $imp->id_plataforma,
            'prev_imp' => $prev_imp,
            'prox_imp1' => $prox_imp,
            'prox_imp2' => $prox_imp
        ]);

        // 2. Borramos los registros de la 'prox_imp' que ahora están cubiertos por la 'prev_imp'
        DB::statement("DELETE j_prox FROM jugador j_prox
        JOIN jugador j_prev ON 
          j_prev.codigo = j_prox.codigo AND 
          j_prev.id_plataforma = j_prox.id_plataforma AND
          j_prev.fecha_importacion < j_prox.fecha_importacion AND
          j_prev.valido_hasta >= j_prox.valido_hasta
        WHERE j_prox.id_plataforma = :id_plataforma
        AND   j_prox.fecha_importacion = :prox_imp", [
            'id_plataforma' => $imp->id_plataforma,
            'prox_imp' => $prox_imp
        ]);
      }
    
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
  
public function informeDemografico(Request $request){
    $result = $this->_validateAnioMesPlataforma($request);
    if(count($result['errores'])){
       return response()->json($result,422);
    }
    extract($result);
    //Lo necesito para el indice de resumen_mensual
    //la busqueda igual es sobre todas las monedas
    $id_tms = \App\TipoMoneda::all()->pluck('id_tipo_moneda');
    
    //Aproximado... redondea para arriba si jugo con 17 años a inicio de mes... habria que verificar
    //despues los que tienen 18- años buscando dia a dia sobre cada detalle_producido_jugadores del mes
    $ultima_fecha_producido_mes = DB::table('producido_jugadores as pj')
    ->select('pj.fecha')
    ->where('pj.id_plataforma','=',$id_plataforma)
    ->where(DB::raw('YEAR(pj.fecha)'),'=',$anio)
    ->where(DB::raw('MONTH(pj.fecha)'),'=',$mes)
    ->orderBy('pj.fecha','desc')
    ->first();
    
    $primer_fecha_producido_mes = DB::table('producido_jugadores as pj')
    ->select('pj.fecha')
    ->where('pj.id_plataforma','=',$id_plataforma)
    ->where(DB::raw('YEAR(pj.fecha)'),'=',$anio)
    ->where(DB::raw('MONTH(pj.fecha)'),'=',$mes)
    ->orderBy('pj.fecha','asc')
    ->first();
            
    $anio_mes = str_pad($anio,4,'0',STR_PAD_LEFT).'-'.str_pad($mes,2,'0',STR_PAD_LEFT);
    $primer_dia_mes = $anio_mes.'-01';
    $ultimo_dia_mes = $anio_mes.'-'.str_pad(cal_days_in_month(CAL_GREGORIAN,$mes,$anio),2,'0',STR_PAD_LEFT);   
   
    $ultima_fecha_producido_mes = $ultima_fecha_producido_mes === null? 
      $ultimo_dia_mes 
    : $ultima_fecha_producido_mes->fecha;
    
    $primer_fecha_producido_mes = $primer_fecha_producido_mes === null? 
      $primer_dia_mes 
    : $primer_fecha_producido_mes->fecha;
    
    //Uso la ultima base de datos informada en el mes seleccionado
    $edad_max = "TIMESTAMPDIFF(YEAR,j.fecha_nacimiento,'$primer_fecha_producido_mes')";
    $edad_min = "TIMESTAMPDIFF(YEAR,j.fecha_nacimiento,'$ultima_fecha_producido_mes')";
    $edad_avg = "((COALESCE($edad_max,$edad_min)+COALESCE($edad_min,$edad_max))/2.0)";
    
    $sexo = 'CASE
     WHEN j.sexo IS NULL       THEN "-"
     WHEN j.sexo LIKE "hombre" THEN "HOMBRE"
     WHEN j.sexo LIKE "mujer"  THEN "MUJER"
     ELSE                           "X"
    END';
    
    $q = DB::table('jugador as j')
    ->where('j.id_plataforma','=',$id_plataforma)
    ->where('j.fecha_importacion','<=',$ultimo_dia_mes)
    ->where('j.valido_hasta','>=',$ultimo_dia_mes);
    
    $selectRaw = "j.codigo as jugador,COALESCE($edad_avg,'-') as edad,$sexo as sexo";
    
    $rjts = [];//Lo hago asi para evitar tener que agrupar por jugador y usar MAX() en todas las columnas
    foreach($id_tms as $id_tm){
      $rjt = "rj{$id_tm}";
      $rjts[] = $rjt;
      $q = $q->leftJoin("resumen_mensual_producido_jugadores as $rjt",function($j) use ($rjt,$id_plataforma,$id_tm,$primer_dia_mes){
        return $j->where("$rjt.id_plataforma",'=',$id_plataforma)
        ->where("$rjt.id_tipo_moneda",'=',$id_tm)
        ->where("$rjt.aniomes",'=',$primer_dia_mes)
        ->on("$rjt.jugador",'=','j.codigo');
      });
    }
    $apuesta   = implode("+",array_map(function($rjt){ return "IFNULL($rjt.apuesta <> 0,0)";   },$rjts));
    $premio    = implode("+",array_map(function($rjt){ return "IFNULL($rjt.premio <> 0,0)";    },$rjts));
    $beneficio = implode("+",array_map(function($rjt){ return "IFNULL($rjt.beneficio <> 0,0)"; },$rjts));
    
    $selectRaw .= ",($apuesta+$premio+$beneficio) > 0 as jugo";
    
    $en_bd = $q->selectRaw($selectRaw)->get();
    
    $aux = $en_bd->keyBy('jugador');
    assert($aux->count() == $en_bd->count());//Sanity check
    $en_bd = $aux;
        
    $no_en_bd = DB::table('resumen_mensual_producido_jugadores as rj')
    ->selectRaw("rj.jugador as jugador,SUM(IFNULL(rj.apuesta <> 0,0)+IFNULL(rj.premio <> 0,0)+IFNULL(rj.beneficio <> 0,0)) > 0 as jugo")
    ->leftJoin('jugador as j',function($j) use ($id_plataforma,$ultimo_dia_mes){
      return $j->where('j.id_plataforma','=',$id_plataforma)
      ->where('j.fecha_importacion','<=',$ultimo_dia_mes)
      ->where('j.valido_hasta','>=',$ultimo_dia_mes)
      ->on('rj.jugador','=','j.codigo');
    })
    ->where('rj.id_plataforma','=',$id_plataforma)
    ->where('rj.aniomes','=',$primer_dia_mes)
    ->whereIn('rj.id_tipo_moneda',$id_tms)
    ->whereNull('j.codigo')
    ->groupBy('rj.jugador')
    ->get()
    ->keyBy('jugador');
        
    $posibles_menores_en_bd = $en_bd->filter(function($j){
      return $j->edad < 18;
    });
        
    //Hago una busca mas fina para los menores de 18
    $menores = DB::table('jugador as j')
    ->selectRaw("j.codigo as jugador,
      CASE
       WHEN MAX(j.sexo) IS NULL       THEN '-'
       WHEN MAX(j.sexo) LIKE 'hombre' THEN 'HOMBRE'
       WHEN MAX(j.sexo) LIKE 'mujer'  THEN 'MUJER'
       ELSE                           'X'
      END as sexo,
      TIMESTAMPDIFF(
        YEAR,
        MAX(j.fecha_nacimiento),
        MIN(IF(
          IFNULL(dpj.apuesta <> 0,0)+IFNULL(dpj.premio <> 0,0)+IFNULL(dpj.beneficio <> 0,0),
          pj.fecha,
          '$ultimo_dia_mes'
        ))
      ) as edad,
      SUM(IFNULL(dpj.apuesta <> 0,0)+IFNULL(dpj.premio <> 0,0)+IFNULL(dpj.beneficio <> 0,0)) > 0 as jugo")
    ->where('j.id_plataforma','=',$id_plataforma)
    ->where('j.fecha_importacion','<=',$ultimo_dia_mes)
    ->where('j.valido_hasta','>=',$ultimo_dia_mes)
    ->whereIn('j.codigo',$posibles_menores_en_bd->pluck('jugador'))
    ->leftJoin('producido_jugadores as pj',function($j) use ($id_plataforma,$id_tms,$primer_fecha_producido_mes,$ultima_fecha_producido_mes){
      return $j->where("pj.id_plataforma",'=',$id_plataforma)
      ->whereIn("pj.id_tipo_moneda",$id_tms)
      ->where("pj.fecha",">=",$primer_fecha_producido_mes)
      ->where("pj.fecha","<=",$ultima_fecha_producido_mes);
    })
    ->leftJoin('detalle_producido_jugadores as dpj',function($j){
      return $j->on("dpj.id_producido_jugadores",'=',"pj.id_producido_jugadores")
      ->on("dpj.jugador",'=','j.codigo');
    })
    ->groupBy('j.codigo')
    ->get()
    ->keyBy('jugador');
    
    //Corrijo con exactitud la query original
    foreach($menores as $jugador => &$j){
      if($j->edad >= 18.0){
        $en_bd[$jugador]->edad = $j->edad;//Edad exacta!
        unset($menores[$jugador]);
      }
    }
    
    $plataforma = Plataforma::find($id_plataforma);
    $cod_plataforma = $plataforma->codigo;
    $plataforma = $plataforma->nombre;
    
    $pF = (new \DateTimeImmutable($primer_fecha_producido_mes))->format('ymd');
    $uF = (new \DateTimeImmutable($ultima_fecha_producido_mes))->format('ymd');
    $bdF = DB::table('importacion_estado_jugador')
    ->select('fecha_importacion')
    ->where('id_plataforma','=',$id_plataforma)
    ->where('fecha_importacion','<=',$ultimo_dia_mes)
    ->orderBy('fecha_importacion','desc')
    ->first();
    
    $bdF = $bdF === null? '(sin)' : (new \DateTimeImmutable($bdF->fecha_importacion))->format('ymd');
    $view = View::make('planillaInformeDemografico',compact('plataforma','anio','mes','en_bd','no_en_bd','menores'));
    $dompdf = new Dompdf();
    //$dompdf->set_paper('A4', 'landscape');
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 565, "[Producido $pF-$uF | Jugadores $bdF]", $font, 10, array(0,0,0));
    //$dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(20, 815, "[Producido $pF-$uF | Jugadores $bdF]", $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream("informeDemografico-$cod_plataforma-{$pF}a{$uF}BD{$bdF}.pdf",['Attachment'=>0]);
  }
  
  private function _validateAnioMesPlataforma(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $errores = [];
    $id_plataforma = $request->id_plataforma ?? -1;
    if($user->plataformas()->where('plataforma.id_plataforma',$id_plataforma)->count() <= 0){
      $errores[] = "No puede acceder a la plataforma";
    }
    
    $anio_mes = $request->anio_mes ?? '';
    $anio_mes = explode('-',$anio_mes);
    $anio = intval($anio_mes[0] ?? -1);
    $mes = intval($anio_mes[1] ?? -1);
    if($mes == -1 || $anio == -1){
      $errores[] = "Fecha faltante o formato incorrecto";
    }
    
    return compact('anio','mes','id_plataforma','errores');
  }
  
  public function jugadoresZIP(Request $request){
    $result = $this->_validateAnioMesPlataforma($request);
    if(count($result['errores']) > 0){
      return response()->json($result,422);
    }
    
    extract($result);
    $anio_mes = str_pad($anio,4,'0',STR_PAD_LEFT).'-'.str_pad($mes,2,'0',STR_PAD_LEFT);
    $ultimo_dia_mes = $anio_mes.'-'.str_pad(cal_days_in_month(CAL_GREGORIAN,$mes,$anio),2,'0',STR_PAD_LEFT); 
      
    $LCSVC = LectorCSVController::getInstancia();
    $csvfhandle = tmpfile();
    $LCSVC->jugadoresExportCSV($csvfhandle,$id_plataforma,$ultimo_dia_mes);
    rewind($csvfhandle);
    
    $timestamp = new \DateTimeImmutable();
    $codigo_plat = Plataforma::find($id_plataforma)->codigo;
    $ultimo_dia_mes = str_replace('-','',$ultimo_dia_mes);
    $filename = 'jugadores_'.$codigo_plat.'_'.$ultimo_dia_mes.'_'.$timestamp->format('Ymdhis');
    
    extract($this->CSV_a_ZIP($csvfhandle,$filename));
    
    return response()->download($filepathzip,$filenamezip,$headers)->deleteFileAfterSend(true);
  }
  
  private function CSV_a_ZIP($csvfhandle,$filename){
    $filepathcsv = stream_get_meta_data($csvfhandle)['uri'];
    $md5 = md5_file($filepathcsv);
    if($md5 === false){
      return response()->json(['error' => ['NO SE PUEDE CREAR EL MD5']],500);
    }
    
    $filenamezip = $filename.'.zip';
    $filepathzip = tempnam('','');
    $zip = new \ZipArchive();
    if($zip->open($filepathzip,\ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true){
      return response()->json(['error' => ['NO SE PUEDE CREAR EL ZIP']],500);
    }
    
    $zip->addFromString($filename.'.md5',$md5);
    $zip->addFile(stream_get_meta_data($csvfhandle)['uri'],$filename.'.csv');
    $zip->close();
    
    $headers = [
      "Content-type" => "application/zip",
    ];
    
    return compact('filepathzip','filenamezip','headers');
  }
}

