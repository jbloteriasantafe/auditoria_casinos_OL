<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CategoriaJuego;

class InformePlataformaController extends Controller
{
  public function informePlataforma(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Estado de Plataforma','informePlataforma');
    return view('seccionInformePlataforma' , [
      'plataformas' => $usuario->plataformas,
      'obtenerJuegoFaltantesSelect'   => self::$obtenerJuegoFaltantesSelect,
      'obtenerJugadorFaltantesSelect' => self::obtenerJugadorFaltantesSelect(),
      'obtenerAlertasJuegoSelect'     => self::$obtenerAlertasJuegoSelect,
      'obtenerAlertasJugadorSelect'   => self::$obtenerAlertasJugadorSelect,
    ]);
  }
  
  private function juegosPlataforma($id_plataforma){
    return DB::table('plataforma as p')
    ->join('plataforma_tiene_juego as pj','pj.id_plataforma','=','p.id_plataforma')
    ->join('juego as j',function($j){
      //Si estuvo y esta borrado no lo consideramos en la BD
      return $j->on('j.id_juego','=','pj.id_juego')->whereRaw('j.deleted_at IS NULL');
    })->where('p.id_plataforma',$id_plataforma);
  }

  private function producidosPlataforma($id_plataforma,$fecha_desde,$fecha_hasta){
    $ret = DB::table('producido as p')
    ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
    ->join('juego as j',function($j){
      return $j->on('j.cod_juego','=','dp.cod_juego')->whereNull('j.deleted_at');
    })
    ->join('plataforma_tiene_juego as pj',function($j){
      return $j->on('pj.id_juego','=','j.id_juego')->on('pj.id_plataforma','=','p.id_plataforma');
    })->where('p.id_plataforma',$id_plataforma);
    if(!empty($fecha_desde)) $ret = $ret->where('p.fecha','>=',$fecha_desde);
    if(!empty($fecha_hasta)) $ret = $ret->where('p.fecha','<=',$fecha_hasta);
    return $ret;
  }

  private function producidosSinJuegoPlataforma($id_plataforma,$fecha_desde,$fecha_hasta){
    $ret = DB::table('producido as p')
    ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
    ->where('p.id_plataforma',$id_plataforma)
    ->whereRaw('NOT EXISTS (
    	SELECT pj.id_juego
      FROM juego as j
      JOIN plataforma_tiene_juego as pj on pj.id_juego = j.id_juego
      WHERE j.deleted_at IS NULL AND j.cod_juego = `dp`.`cod_juego` AND pj.id_plataforma = `p`.`id_plataforma`
    )');
    
    if(!empty($fecha_desde)) $ret = $ret->where('p.fecha','>=',$fecha_desde);
    if(!empty($fecha_hasta)) $ret = $ret->where('p.fecha','<=',$fecha_hasta);
    return $ret;
  }
  
   /*
    PdevTeorico = Si cada juego se jugara en igual cantidad
      Osea si cada juego tiene una apuesta "A", con "N" juegos
      PdevTeorico = (∑Pdev_i A)/(N*A), i = 1..N
      PdevTeorico = A(∑Pdev_i)/(N*A)
      PdevTeorico = (∑Pdev_i)/N
      Termina siendo solo el promedio de los porcentajes de devolución
    PdevEsperado = Se pondera sobre la cantidad apostada para cada juego
      PdevEsperado = (∑Pdev_i*A_i)/(∑A_i)
      La interpretacion es que Pdev_i*A_i es el "premio esperado" para una apuesta A_i en el juego i
    PdevProducido = Se calcula el premio acumulado sobre la apuesta acumulada
      PdevProducido = (∑P_i)/(∑A_i)
	*/
  
  public function obtenerCantidadesPdevs(Request $request){
    $cc = CacheController::getInstancia();
    $codigo = 'obtenerCantidadesPdevs';
    $subcodigo = '|'.implode('|',[$request->id_plataforma,$request->fecha_desde,$request->fecha_hasta]).'|';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }
    $select_clases_bd = '
      ej.nombre as clase_estado,
      (CASE 
       WHEN (j.movil+j.escritorio) = 2 THEN "Escritorio/Movil"
       WHEN j.movil = 1 THEN "Movil"
       WHEN j.escritorio = 1 THEN "Escritorio"
       ELSE "(ERROR) Sin tipo asignado"
       END) as clase_tipo,
      cj.nombre as clase_categoria,
      NULL as clase_categoria_informada
    ';
    
    $select_clases_no_bd = '
      NULL as clase_estado,
      NULL as clase_tipo,
      NULL as clase_categoria,
      dp.categoria as clase_categoria_informada
    ';
  
    $juegos_plat = $this->juegosPlataforma($request->id_plataforma)
    ->selectRaw($select_clases_bd)
    ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
    ->join('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
    ->groupBy(DB::raw('ej.id_estado_juego,j.movil,j.escritorio,cj.id_categoria_juego'));
    
    $prods_plat = $this->producidosPlataforma($request->id_plataforma,$request->fecha_desde,$request->fecha_hasta)
    ->selectRaw($select_clases_bd)
    ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
    ->join('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
    ->groupBy(DB::raw('ej.id_estado_juego,j.movil,j.escritorio,cj.id_categoria_juego'));
    
    $prods_plat_no_bd = $this->producidosSinJuegoPlataforma($request->id_plataforma,$request->fecha_desde,$request->fecha_hasta)
    ->selectRaw($select_clases_no_bd)
    ->groupBy('dp.categoria');
        
    $teorico_cant_bd = (clone $juegos_plat)->selectRaw('
      SUM(0.01*j.porcentaje_devolucion) as sum_pdev,
      COUNT(j.id_juego) as cantidad
    ');
    $teorico_cant_nobd = (clone $prods_plat_no_bd)->selectRaw('
      NULL as sum_pdev,
      COUNT(distinct dp.cod_juego) as cantidad
    ');
    $prod_bd = (clone $prods_plat)->selectRaw('
      SUM(dp.premio) as sum_premio,
      SUM(0.01*j.porcentaje_devolucion*dp.apuesta) as sum_premio_esperado,
      SUM(dp.apuesta) as sum_apuesta
    ');
    $prod_no_bd = (clone $prods_plat_no_bd)->selectRaw('
      SUM(dp.premio) as sum_premio,
      NULL as sum_premio_esperado,
      SUM(dp.apuesta) as sum_apuesta
    ');
    
    $teorico_cant = $teorico_cant_bd->union($teorico_cant_nobd)->get();
    $prod         = $prod_bd->union($prod_no_bd)->get();
    
    //Hago GROUP en PHP porque Laravel5.4 no permite subquerys    
    $clases = [];
    preg_match_all('/clase_[a-z]+(_?[a-z]+)*/', $select_clases_no_bd, $clases);
    $clases = count($clases) > 0? $clases[0] : [];
    $ret = [];
    foreach($clases as $c){//$c = clase_estado,clase_tipo,etc
      $aggr_clase = [];
      //Si $c = clase_estado => $valor_clase = Activo,Inactivo,Restringido, $valores = Filas para cada uno
      //Ignoro el valor_clase nulo (por el UNION)
      $filtro = function($v,$k) use ($c){
        return ($v->{$c} ?? null) !== null;
      };
      foreach($teorico_cant->filter($filtro)->groupBy($c) as $valor_clase => $valores){
        $aggr_clase[$valor_clase] = (object)[
          'cantidad' => null,'sum_pdev' => null,'sum_premio' => null,
          'sum_premio_esperado' => null,'sum_apuesta' => null,
          'pdev' => null,'pdev_esperado' => null,'pdev_producido' => null
        ];
        foreach($valores as $v){
          $aggr_clase[$valor_clase]->cantidad += intval($v->cantidad);
          $aggr_clase[$valor_clase]->sum_pdev += floatval($v->sum_pdev);
        }
      }
      foreach($prod->filter($filtro)->groupBy($c) as $valor_clase => $valores){
        foreach($valores as $v){
          $aggr_clase[$valor_clase]->sum_premio += floatval($v->sum_premio);
          $aggr_clase[$valor_clase]->sum_premio_esperado += floatval($v->sum_premio_esperado);
          $aggr_clase[$valor_clase]->sum_apuesta += floatval($v->sum_apuesta);
        }
      }
      foreach($aggr_clase as $valor_clase => &$sumas){
        if($sumas->cantidad){
          $sumas->pdev = 100*$sumas->sum_pdev / $sumas->cantidad;
        }
        if($sumas->sum_apuesta){
          $sumas->pdev_esperado  = 100*$sumas->sum_premio_esperado / $sumas->sum_apuesta;
          $sumas->pdev_producido = 100*$sumas->sum_premio / $sumas->sum_apuesta;
        }
      }
      //Pasa 'clase_foo_bar' a 'Foo Bar'
      $clase_formateada = ucwords(str_replace('_',' ',str_replace('clase_','',$c)));
      $ret[$clase_formateada] = $aggr_clase;
    }
    $cc->agregar($codigo,$subcodigo,json_encode($ret),['producido_jugadores','juego']);
    return $ret;
  }
  
  
  //No encontre mejor forma de hacerlo... necesito la consulta para ordernar desde el front
  public static $obtenerJuegoFaltantesSelect =
  [ "dp.cod_juego as cod_juego"                       ,"GROUP_CONCAT(distinct dp.categoria SEPARATOR ', ') as categoria",
    "SUM(dp.apuesta_efectivo)   as apuesta_efectivo"  ,"SUM(dp.apuesta_bono)               as apuesta_bono",
    "SUM(dp.apuesta)            as apuesta"           ,"SUM(dp.premio_efectivo)            as premio_efectivo",
    "SUM(dp.premio_bono)        as premio_bono"       ,"SUM(dp.premio)                     as premio",
    "SUM(dp.beneficio_efectivo) as beneficio_efectivo","SUM(dp.beneficio_bono)             as beneficio_bono" ,
    "SUM(dp.beneficio)          as beneficio"         ,"100*AVG(dp.premio)/AVG(dp.apuesta) as pdev"
  ];

  public function obtenerJuegoFaltantesConMovimientos(Request $request){
    $columna = empty($request->columna)? 'dp.cod_juego' : $request->columna;
    $orden   = empty($request->orden)?   'asc'          : $request->orden;
    
    //Saca los producidos en "0"
    $numericos_distinto_de_cero = array_map(function($s){
      $columna = substr($s,strpos($s,'('),strpos($s,')')-1);
      return "(($columna) <> 0)";
    },array_slice(self::$obtenerJuegoFaltantesSelect,2,-1));//Saco el juego, categoria y el pdev
    $numericos_distinto_de_cero = '('.implode(' OR ',$numericos_distinto_de_cero).')';
    
    $q = $this->producidosSinJuegoPlataforma($request->id_plataforma,$request->fecha_desde,$request->fecha_hasta)
    ->whereRaw($numericos_distinto_de_cero);
    
    $juegos_faltantes = (clone $q)->selectRaw(implode(",",self::$obtenerJuegoFaltantesSelect))
    ->groupBy('dp.cod_juego')
    ->orderByRaw($columna.' '.$orden)
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();
    
    $total = (clone $q)->selectRaw("'TOTAL' as cod_juego,COUNT(distinct dp.cod_juego) as count,".implode(",",array_slice(self::$obtenerJuegoFaltantesSelect,1)))
    ->groupBy(DB::raw('"constant"'))
    ->get();

    $count = $total->first()? $total->first()->count : 0;

    return ['data' => $juegos_faltantes->merge($total->map(function($r){
      unset($r->count);
      return $r;
    })), 'total' => $count];
  }

  private static $attrs_pjug = ['apuesta_efectivo','apuesta_bono','apuesta','premio_efectivo','premio_bono','premio','beneficio_efectivo','beneficio_bono','beneficio'];
  public static function obtenerJugadorFaltantesSelect(){
    return array_merge(
      ["rmpj.jugador as jugador"],
      array_map(function($s){ return "SUM(rmpj.$s) as $s"; }, self::$attrs_pjug ),
      ["100*AVG(rmpj.premio)/NULLIF(AVG(rmpj.apuesta),0) as pdev"]
    );
  }

  public function obtenerJugadorFaltantesConMovimientos(Request $request){
    $columna = empty($request->columna)? 'rmpj.jugador' : $request->columna;
    $orden   = empty($request->orden)? 'asc' : $request->orden;
    
    //Saca los producidos en "0"
    $numericos_distinto_de_cero = array_map(function($s){
      return "((rmpj.$s) <> 0)";
    },self::$attrs_pjug);
    $numericos_distinto_de_cero = '('.implode(' OR ',$numericos_distinto_de_cero).')';
    
    $reglas_fechas = [];
    if(!empty($request->fecha_desde)) $reglas_fechas[] = ['rmpj.aniomes','>=',$request->fecha_desde];
    if(!empty($request->fecha_hasta)) $reglas_fechas[] = ['rmpj.aniomes','<=',$request->fecha_hasta];
    
    $q = DB::table('resumen_mensual_producido_jugadores as rmpj')
    ->where('rmpj.id_tipo_moneda','=',$request->id_tipo_moneda)
    ->where('rmpj.id_plataforma','=',$request->id_plataforma)
    ->where($reglas_fechas)
    ->whereRaw($numericos_distinto_de_cero)
    //Chequear fecha de importacion contra la del producido? Seria lo mas correcto
    //pero confundiria bastante al auditor ver un jugador importado 
    ->whereRaw('NOT EXISTS (
      SELECT 1
      FROM jugador as j
      WHERE j.id_plataforma = rmpj.id_plataforma AND j.codigo = rmpj.jugador AND j.valido_hasta IS NULL
      LIMIT 1
    )');
    
    $SELECT_JUG = implode(",",self::obtenerJugadorFaltantesSelect());
    $jugadores_faltantes = (clone $q)->selectRaw($SELECT_JUG)
    ->groupBy('rmpj.jugador')
    ->orderByRaw($columna.' '.$orden)
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();
    
    $SELECT_TOTAL = "'TOTAL' as jugador,COUNT(distinct rmpj.jugador) as count,".implode(",",array_slice(self::obtenerJugadorFaltantesSelect(),1));
    $total = (clone $q)->selectRaw($SELECT_TOTAL)
    ->groupBy(DB::raw('"constant"'))
    ->get();

    $count = $total->first()? $total->first()->count : 0;
    
    {//Fix resto los dias que no estan entre las fechas elegidas
      $q_fix = DB::table('producido_jugadores as pj')
      ->join('detalle_producido_jugadores as rmpj','rmpj.id_producido_jugadores','=','pj.id_producido_jugadores')
      ->where('pj.id_tipo_moneda','=',$request->id_tipo_moneda)
      ->where('pj.id_plataforma','=',$request->id_plataforma)
      ->whereRaw($numericos_distinto_de_cero)
      ->where(function($q) use ($request){
        $q->whereRaw(DB::raw('0'));
        if(!empty($request->fecha_desde)){
          $q->orWhere(function($q2) use ($request){
            $primer_dia_mes = date('Y-m-01',strtotime($request->fecha_desde));
            return $q2->where('pj.fecha','>=',$primer_dia_mes)
            ->where('pj.fecha','<',$request->fecha_desde);
          });
        }
        if(!empty($request->fecha_hasta)){
          $q->orWhere(function($q2) use ($request){
            $ultimo_dia_mes = date('Y-m-t',strtotime($request->fecha_hasta));
            return $q2->where('pj.fecha','>',$request->fecha_hasta)
            ->where('pj.fecha','<=',$ultimo_dia_mes);
          });
        }
        return $q;
      })
      ->whereRaw('NOT EXISTS (
        SELECT 1
        FROM jugador as j
        WHERE j.id_plataforma = pj.id_plataforma AND j.codigo = rmpj.jugador AND j.valido_hasta IS NULL
        LIMIT 1
      )')
      ->orderByRaw($columna.' '.$orden);
      
      $fix_j = (clone $q_fix)
      ->selectRaw($SELECT_JUG)
      ->whereIn('rmpj.jugador',$jugadores_faltantes->map(function($j){return $j->jugador;})->toArray())
      ->groupBy('rmpj.jugador')
      ->get()->keyBy('jugador');
      
      $fix_total = (clone $q_fix)
      ->selectRaw($SELECT_TOTAL)
      ->get();
      
      foreach($jugadores_faltantes as $jidx => $j){
        foreach(self::$attrs_pjug as $attr){
          $jugadores_faltantes[$jidx]->{$attr} -= $fix_j[$j->jugador]->{$attr} ?? 0;
        }
        if($jugadores_faltantes[$jidx]->apuesta != 0){
          $jugadores_faltantes[$jidx]->pdev = $jugadores_faltantes[$jidx]->premio / $jugadores_faltantes[$jidx]->apuesta;
        }
        else{
          $jugadores_faltantes[$jidx]->pdev = null;
        }
      }
      
      if(count($total) == 0 && count($fix_total) > 0){
        $total = $fix_total;
        foreach(self::$attrs_pjug as $attr){
          $total[0]->{$attr} = -($total[0]->{$attr} ?? 0);
        }
      }
      else if(count($total) > 0 && count($fix_total) > 0){
        foreach(self::$attrs_pjug as $attr){
          $total[0]->{$attr} -= $fix_total[0]->{$attr} ?? 0;
        }
      }
      
      if($total[0]->apuesta != 0){
        $total[0]->pdev = $total[0]->premio / $total[0]->apuesta;
      }
      else{
        $total[0]->pdev = null;
      }
    }
    
    return ['data' => $jugadores_faltantes->merge($total->map(function($r){
      unset($r->count);
      return $r;
    })), 'total' => $count];
  }

  public static $obtenerAlertasJuegoSelect =
  [
    'p.fecha as fecha','dp.cod_juego as cod_juego','cj.nombre as categoria',
    'dp.apuesta as apuesta','dp.premio as premio','dp.beneficio as beneficio',
    '100*dp.premio/dp.apuesta as pdev',
    'j.porcentaje_devolucion as pdev_juego'
  ];

  public function obtenerJuegoAlertasDiarias(Request $request){
    $alertas = [];

    $columna = !empty($request->columna)? DB::raw($request->columna) : 'p.fecha';
    $orden   =   !empty($request->orden)? $request->orden : 'desc';
    $columna2 = 'dp.cod_juego';
    $orden2 = 'asc';
    if($columna == 'dp.cod_juego'){
      $columna2 = 'p.fecha';
      $orden2 = 'desc';
    }

    $query = $this->producidosPlataforma($request->id_plataforma,$request->fecha_desde,$request->fecha_hasta)
    ->join('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
    ->where('p.id_tipo_moneda',$request->id_tipo_moneda)
    ->whereRaw('ABS(dp.beneficio) >= '.$request->beneficio_alertas)
    ->whereRaw('ABS((100*dp.premio/dp.apuesta) - j.porcentaje_devolucion) >='.$request->pdev_alertas);

    $data = (clone $query)
    ->selectRaw(implode(',',self::$obtenerAlertasJuegoSelect))
    ->orderBy($columna,$orden)->orderBy($columna2,$orden2)
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();

    $total = (clone $query)->selectRaw('COUNT(dp.id_detalle_producido) as total')
    ->groupBy(DB::raw('"constant"'))->get()->first();

    return ['data' => $data, 'total' => $total->total ?? 0];
  }

  public static $obtenerAlertasJugadorSelect =
  [
    'p.fecha as fecha','dp.jugador as jugador','dp.apuesta as apuesta',
    'dp.premio as premio','dp.beneficio as beneficio','100*dp.premio/dp.apuesta as pdev'
  ];

  public function obtenerJugadorAlertasDiarias(Request $request){
    $alertas = [];

    $columna = !empty($request->columna)? DB::raw($request->columna) : 'p.fecha';
    $orden   =   !empty($request->orden)? $request->orden : 'desc';
    $columna2 = 'dp.jugador';
    $orden2 = 'asc';
    if($columna == 'dp.jugador'){
      $columna2 = 'p.fecha';
      $orden2 = 'desc';
    }

    $query = DB::table('detalle_producido_jugadores as dp')
    ->join('producido_jugadores as p','p.id_producido_jugadores','=','dp.id_producido_jugadores')
    ->where('p.id_plataforma',$request->id_plataforma)
    ->where('p.id_tipo_moneda',$request->id_tipo_moneda)
    ->whereRaw('ABS(dp.beneficio) >= '.$request->beneficio_alertas);
    if(!empty($request->fecha_desde)) $query = $query->where('p.fecha','>=',$request->fecha_desde);
    if(!empty($request->fecha_hasta)) $query = $query->where('p.fecha','<=',$request->fecha_hasta);

    $data = (clone $query)
    ->selectRaw(implode(',',self::$obtenerAlertasJugadorSelect))
    ->orderBy($columna,$orden)->orderBy($columna2,$orden2)
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();

    $total = (clone $query)->selectRaw('COUNT(dp.id_detalle_producido_jugadores) as total')
    ->groupBy(DB::raw('"constant"'))->get()->first();

    return ['data' => $data, 'total' => $total->total ?? 0];
  }

  public function obtenerEvolucionCategorias(Request $request){
    $ret = [];
    foreach(CategoriaJuego::all() as $cj){
      $ret[$cj->nombre] = $this->producidosPlataforma($request->id_plataforma,$request->fecha_desde,$request->fecha_hasta)
      ->selectRaw('p.fecha as x, (ROUND(AVG(dp.premio)/AVG(dp.apuesta)*100,2))+0E0 as y')
      ->where('j.id_categoria_juego','=',$cj->id_categoria_juego)
      ->groupBy('p.fecha')->orderBy('p.fecha','asc')->get();
    }
    return $ret;
  }
}

