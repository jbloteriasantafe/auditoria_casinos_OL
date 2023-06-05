<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Plataforma;
use View;
use Dompdf\Dompdf;
use App\Juego;
use \Datetime;
use App\BeneficioMensual;
use App\Cotizacion;
use App\Http\Controllers\CacheController;
use App\CategoriaJuego;
use App\EstadoJugador;
use App\EstadoJuegoImportado;
use App\ImportacionEstadoJuego;
use App\EstadoJuego;
use App\Jugador;
use App\ImportacionEstadoJugador;

class informesController extends Controller
{
  private function obtenerMes($mes_num){
    $mes_map = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    return $mes_map[intval($mes_num)-1];
  }

  /*
  DESCARGO DE RESPONSABILIDAD 

  12:25 - 4 de Noviembre del 2021.

  Yo, Octavio Garcia Aguirre, dejo en claro que:

  Por pedido del Director de Casinos de la Caja de Asistencia Social, Lotería de Santa Fe, Gustavo Rivera, se agrega
  una planilla especial sin considerar los ajustes manuales informados al momento de calcular el beneficio de la plataforma.

  No garantizo la validez de la información ni tampoco avalo o aconsejo cualquier acción que se realice a partir de esta.
  */
  public function generarPlanillaSinAjuste($anio,$mes,$id_plataforma,$id_tipo_moneda){
    return $this->generarPlanilla($anio,$mes,$id_plataforma,$id_tipo_moneda,1,1);
  }

  public function generarPlanilla($anio,$mes,$id_plataforma,$id_tipo_moneda,$simplificado,$sin_ajuste = 0){
    //@HACK: si el beneficio no esta importado, no muestra el poker del dia
    //Como creo que nunca pasaria lo dejo asi porque es mas simple el query
    //Octavio 11 Noviembre 2022
    $dias = DB::table('beneficio')->select(
      DB::raw('CONCAT(LPAD(DAY(beneficio.fecha)  ,2,"00"),"-",
                      LPAD(MONTH(beneficio.fecha),2,"00"),"-",
                      YEAR(beneficio.fecha)) as fecha'),
      'beneficio.jugadores','beneficio.apuesta','beneficio.premio',
      'beneficio.ajuste','beneficio.beneficio','cotizacion.valor as cotizacion',
      'beneficio_poker.utilidad as poker'
    )
    ->join('beneficio_mensual','beneficio_mensual.id_beneficio_mensual','=','beneficio.id_beneficio_mensual')
    ->leftJoin('beneficio_mensual_poker',function($j){
      return $j->on('beneficio_mensual_poker.id_plataforma','=','beneficio_mensual.id_plataforma')
               ->on('beneficio_mensual_poker.id_tipo_moneda','=','beneficio_mensual.id_tipo_moneda')
               ->on('beneficio_mensual_poker.fecha','=','beneficio_mensual.fecha');
    })
    ->leftJoin('beneficio_poker',function($j){
      return $j->on('beneficio_poker.id_beneficio_mensual_poker','=','beneficio_mensual_poker.id_beneficio_mensual_poker')
               ->on('beneficio_poker.fecha','=','beneficio.fecha');
    })
    ->leftJoin('cotizacion',function($j){
      return $j->on('cotizacion.fecha','=','beneficio.fecha')
               ->on('cotizacion.id_tipo_moneda','=','beneficio_mensual.id_tipo_moneda');
    })
    ->where([['beneficio_mensual.id_plataforma','=',$id_plataforma],['beneficio_mensual.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('beneficio_mensual.fecha','=',$anio)
    ->whereMonth('beneficio_mensual.fecha','=',$mes)
    ->orderBy('beneficio.fecha','asc')->get();

    $total = DB::table('beneficio_mensual')
    ->select(
      DB::raw('"" as jugadores'),'apuesta','premio',
      'ajuste','beneficio',
      'beneficio_mensual_poker.utilidad as poker'
    )
    ->leftJoin('beneficio_mensual_poker',function($j){
      return $j->on('beneficio_mensual_poker.id_plataforma','=','beneficio_mensual.id_plataforma')
               ->on('beneficio_mensual_poker.id_tipo_moneda','=','beneficio_mensual.id_tipo_moneda')
               ->on('beneficio_mensual_poker.fecha','=','beneficio_mensual.fecha');
    })
    ->where([['beneficio_mensual.id_plataforma','=',$id_plataforma],['beneficio_mensual.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('beneficio_mensual.fecha','=',$anio)
    ->whereMonth('beneficio_mensual.fecha','=',$mes)->first();

    if(is_null($total)){
      $total = new \stdClass;
      $total->jugadores = 0;
      $total->apuesta   = 0;
      $total->premio    = 0;
      $total->ajuste    = 0;
      $total->beneficio = 0;
      $total->poker     = 0;
    }
    $total->fecha = '##-'.str_pad($mes,2,"0",STR_PAD_LEFT).'-'.$anio;
    $total->plataforma = Plataforma::find($id_plataforma)->nombre;
    $total->moneda = TipoMoneda::find($id_tipo_moneda)->descripcion;
    //Si no hubo ninguna en el mes me quedo con la ultima de la BD
    $cotizacionDefecto = Cotizacion::where('id_tipo_moneda',$id_tipo_moneda)->orderBy('fecha','desc')->first();
    if(is_null($cotizacionDefecto) || $id_tipo_moneda == 1) $cotizacionDefecto = 1.0;
    else $cotizacionDefecto = $cotizacionDefecto->valor;

    $total_cotizado = (object)[
      'beneficio'=>0.0,'ajuste'=>0.0,'poker'=>0.0
    ];
    {
      $ultima_cotizacion = $cotizacionDefecto;
      foreach($dias as $d){
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion; 
        $d->cotizacion     = $ultima_cotizacion;
        $total_cotizado->beneficio += $d->cotizacion*$d->beneficio;
        $total_cotizado->ajuste    += $d->cotizacion*$d->ajuste;
        $total_cotizado->poker     += $d->cotizacion*$d->poker;
      }
    }

    $mesTexto = $this->obtenerMes($mes);
    $view = View::make('planillaInformesJuegos',compact(
      'mesTexto','dias','cotizacionDefecto','total_cotizado',
      'total','simplificado','sin_ajuste'
    ));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function informeCompleto($anio,$mes,$id_plataforma,$id_tipo_moneda){
    $bm = BeneficioMensual::where([['id_plataforma','=',$id_plataforma],['id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('fecha','=',$anio)->whereMonth('fecha','=',$mes)->orderBy('id_beneficio_mensual','desc')->first();
    if(is_null($bm)) return "SIN BENEFICIO MENSUAL";

    $data = BeneficioController::getInstancia()->arrayInformeCompleto($bm->id_beneficio_mensual);

    $plataforma = $bm->plataforma->codigo;
    $moneda = $bm->tipo_moneda->descripcion;
    $f = explode('-',$bm->fecha);
    $fecha = $f[0].'-'.$f[1];
    $header = $data[0];
    $dias = array_slice($data,1,count($data)-2);
    $total = $data[count($data)-1];

    $view = View::make('planillaCompletaInformesJuegos',compact('fecha','plataforma','moneda','header','dias','total'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream("beneficio_mensual_".$plataforma."_".$f[0].$f[1].".pdf", Array('Attachment'=>0));
  }

  public function generarPlanillaPoker($anio,$mes,$id_plataforma,$id_tipo_moneda){
    $dias = DB::table('beneficio_poker')->select(
      DB::raw('CONCAT(LPAD(DAY(beneficio_poker.fecha)  ,2,"00"),"-",
                      LPAD(MONTH(beneficio_poker.fecha),2,"00"),"-",
                      YEAR(beneficio_poker.fecha)) as fecha'),
    'beneficio_poker.jugadores','beneficio_poker.total_buy as droop','beneficio_poker.utilidad','cotizacion.valor as cotizacion')
    ->join('beneficio_mensual_poker','beneficio_mensual_poker.id_beneficio_mensual_poker','=','beneficio_poker.id_beneficio_mensual_poker')
    ->leftJoin('cotizacion',function($j){
      return $j->on('cotizacion.fecha','=','beneficio_poker.fecha')->on('cotizacion.id_tipo_moneda','=','beneficio_mensual_poker.id_tipo_moneda');
    })
    ->where([['beneficio_mensual_poker.id_plataforma','=',$id_plataforma],['beneficio_mensual_poker.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('beneficio_poker.fecha','=',$anio)
    ->whereMonth('beneficio_poker.fecha','=',$mes)
    ->orderBy('beneficio_poker.fecha','asc')->get();

    $total = DB::table('beneficio_mensual_poker')->select('jugadores','total_buy as droop','utilidad')
    ->where([['beneficio_mensual_poker.id_plataforma','=',$id_plataforma],['beneficio_mensual_poker.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('fecha','=',$anio)
    ->whereMonth('fecha','=',$mes)->first();

    if(is_null($total)){
      $total = new \stdClass;
      $total->jugadores = "";
      $total->droop = 0;
      $total->utilidad = 0;
      $total->cotizacion = "";
    }
    $total->fecha = '##-'.str_pad($mes,2,"0",STR_PAD_LEFT).'-'.$anio;
    $total->plataforma = Plataforma::find($id_plataforma)->nombre;
    $total->moneda = TipoMoneda::find($id_tipo_moneda)->descripcion;
    //Si no hubo ninguna en el mes me quedo con la ultima de la BD
    $cotizacionDefecto = Cotizacion::where('id_tipo_moneda',$id_tipo_moneda)->orderBy('fecha','desc')->first();
    if(is_null($cotizacionDefecto) || $id_tipo_moneda == 1) $cotizacionDefecto = 1.0;
    else $cotizacionDefecto = $cotizacionDefecto->valor;

    $total_beneficio = 0.00;
    {
      $ultima_cotizacion = $cotizacionDefecto;
      foreach($dias as $d){
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion;
        $d->cotizacion = $ultima_cotizacion;
        $total_beneficio += $d->cotizacion*$d->utilidad;
      }
    }

    $mesTexto = $this->obtenerMes($mes);
    $view = View::make('planillaInformesPoker',compact('mesTexto','dias','cotizacionDefecto','total_beneficio','total'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function obtenerBeneficiosPorPlataforma(){
      $plataformas = Plataforma::all();
      $monedas = TipoMoneda::all();
      $fecha = date_create_from_format('Y-m-d','2020-10-01');//Inicio de casinos online
      $mes_que_viene = new Datetime();//devuelve hoy
      $mes_que_viene->modify('first day of next month');

      $resultados = [];
      foreach($plataformas as $p){
        $resultados[$p->id_plataforma] = ["beneficios" => [],"plataforma" => $p->nombre];
      }
      while($fecha->format('Y-m') != $mes_que_viene->format('Y-m')){
        foreach($plataformas as $p){
          foreach($monedas as $m){
            $anio = $fecha->format('Y');
            $mes = $fecha->format('m');
            $benefMensual = BeneficioMensual::where(
              [['id_plataforma','=',$p->id_plataforma],
               ['id_tipo_moneda','=',$m->id_tipo_moneda]]
            )->whereYear('fecha','=',$anio)->whereMonth('fecha','=',$mes)->first();
            $existe_beneficio = !is_null($benefMensual);
            $resultado = new \stdClass();
            $resultado->anio_mes = $this->obtenerMes($mes)." ".$anio;
            $resultado->anio = $anio;
            $resultado->mes = $mes;
            $resultado->moneda = $m->descripcion;
            $resultado->id_tipo_moneda = $m->id_tipo_moneda;
            $resultado->id_beneficio_mensual = $existe_beneficio? $benefMensual->id_beneficio_mensual : "";
            $resultado->existe = $existe_beneficio;
            $aux["beneficios"][] = $resultado;
            $resultados[$p->id_plataforma]["beneficios"][] = $resultado;
          }
        }
        $fecha->modify('first day of next month');
      }
      foreach($plataformas as $p){
        $resultados[$p->id_plataforma]["beneficios"] = array_reverse($resultados[$p->id_plataforma]["beneficios"]);
      }

      UsuarioController::getInstancia()->agregarSeccionReciente('Informes Juegos' ,'informesJuegos');

      return view('seccionInformesJuegos',['resultados' => $resultados, 'plataformas' => $plataformas, 'monedas' => $monedas]);
  }

  public function informePlataforma(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Estado de Plataforma','informePlataforma');
    return view('seccionInformePlataforma' , ['plataformas' => $usuario->plataformas]);
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
      ["100*AVG(rmpj.premio)/AVG(rmpj.apuesta) as pdev"]
    );
  }

  public function obtenerJugadorFaltantesConMovimientos(Request $request){
    $columna = empty($request->columna)? 'rmpj.jugador' : $request->columna;
    $orden   = empty($request->orden)? 'asc' : $request->orden;
    
    //Saca los producidos en "0"
    $numericos_distinto_de_cero = array_map(function($s){
      return "(($s) <> 0)";
    },self::$attrs_pjug);
    $numericos_distinto_de_cero = '('.implode(' OR ',$numericos_distinto_de_cero).')';
    
    $reglas_fechas = [];
    if(!empty($request->fecha_desde)) $reglas_fechas[] = ['rmpj.aniomes','>=',$request->fecha_desde];
    if(!empty($request->fecha_hasta)) $reglas_fechas[] = ['rmpj.aniomes','<=',$request->fecha_hasta];
    
    $q = DB::table('resumen_mensual_producido_jugadores as rmpj')
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
    
    $jugadores_faltantes = (clone $q)->selectRaw(implode(",",self::obtenerJugadorFaltantesSelect()))
    ->groupBy('rmpj.jugador')
    ->orderByRaw($columna.' '.$orden)
    ->skip(($request->page-1)*$request->page_size)->take($request->page_size)->get();
    
    $total = (clone $q)->selectRaw("'TOTAL' as jugador,COUNT(distinct rmpj.jugador) as count,".implode(",",array_slice(self::obtenerJugadorFaltantesSelect(),1)))
    ->groupBy(DB::raw('"constant"'))
    ->get();

    $count = $total->first()? $total->first()->count : 0;
    
    {//Fix resto los dias que no estan entre las fechas elegidas
      $jugadores_pagina = $jugadores_faltantes->map(function($j){return $j->jugador;})->toArray();
      $resta_inicio = collect([]);
      $resta_fin = collect([]);
      $q_restas = DB::table('producido_jugadores as pj')
      ->selectRaw(implode(",",self::obtenerJugadorFaltantesSelect()))
      ->join('detalle_producido_jugadores as rmpj','rmpj.id_producido_jugadores','=','pj.id_producido_jugadores')
      ->where('pj.id_plataforma','=',$request->id_plataforma)
      ->groupBy('rmpj.jugador')
      ->orderByRaw($columna.' '.$orden);
      
      if(!empty($request->fecha_desde)) {
        $primer_dia_mes = date('Y-m-01',strtotime($request->fecha_desde));
        $resta_inicio = (clone $q_restas)
        ->where('pj.fecha','>=',$primer_dia_mes)
        ->where('pj.fecha','<',$request->fecha_desde)->get()
        ->keyBy('jugador');
      }
      
      if(!empty($request->fecha_hasta)) {
        $ultimo_dia_mes = date('Y-m-t',strtotime($request->fecha_hasta));
        $resta_fin = (clone $q_restas)
        ->where('pj.fecha','>',$request->fecha_hasta)
        ->where('pj.fecha','<=',$ultimo_dia_mes)->get()
        ->keyBy('jugador');
      }
      
      foreach($jugadores_faltantes as $jidx => $j){
        foreach(self::$attrs_pjug as $attr){
          $jugadores_faltantes[$jidx]->{$attr} -= $resta_inicio[$j->jugador]->{$attr} ?? 0;
          $jugadores_faltantes[$jidx]->{$attr} -= $resta_fin[$j->jugador]->{$attr} ?? 0;
        }
        $jugadores_faltantes[$jidx]->pdev = $jugadores_faltantes[$jidx]->premio / $jugadores_faltantes[$jidx]->apuesta;
      }
      
      foreach($resta_inicio as $ri){
        foreach(self::$attrs_pjug as $attr){
          $total->{$attr} -= $ri->{attr} ?? 0;
        }
      }
      foreach($resta_fin as $rf){
        foreach(self::$attrs_pjug as $attr){
          $total->{$attr} -= $rf->{attr} ?? 0;
        }
      }
      
      $total->pdev = $total->premio / $total->apuesta;
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

  public function informeContableJuego($id_plataforma = null,$modo = null,$codigo = null){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable Juegos/Jugadores' , 'informeContableJuego');

    $mostrar = null;
    if(!is_null($id_plataforma) && !is_null($modo) && !is_null($codigo)){
      $busqueda = [];
      if($modo == 'juego'){
        $busqueda = $this->obtenerJuegoPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      else if($modo == 'jugador'){
        $busqueda = $this->obtenerJugadorPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      $plataforma = Plataforma::find($id_plataforma);
      if(count($busqueda) > 0 && $busqueda[0]->codigo == $codigo && !is_null($plataforma)){
        $mostrar['id_plataforma'] = $id_plataforma;
        $mostrar['codigo_plat']   = $plataforma->codigo;
        $mostrar['modo']          = $modo;
        $mostrar['codigo']        = $codigo;
        $mostrar['id']            = $busqueda[0]->id;
      }
    }
    return view('informe_juego', ['plataformas' => $usuario->plataformas,'mostrar' => $mostrar]);
  }

  public function obtenerJugadorPlataforma($id_plataforma,$jugador=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $jugadores = DB::table('producido_jugadores as p')
    ->join('detalle_producido_jugadores as dp','dp.id_producido_jugadores','=','p.id_producido_jugadores')
    ->selectRaw('-1 as id, dp.jugador as codigo')->distinct()
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.jugador','LIKE',$jugador.'%')
    ->orderBy('codigo','asc');

    if($id_plataforma != "0") $jugadores = $jugadores->where('p.id_plataforma',$id_plataforma);

    return ['busqueda' => $jugadores->get()];
  }

  public function obtenerJuegoPlataforma($id_plataforma,$cod_juego=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $j = DB::table('plataforma_tiene_juego as pj')
    ->select('j.id_juego as id','j.cod_juego as codigo')
    ->join('juego as j','j.id_juego','=','pj.id_juego')
    ->whereIn('pj.id_plataforma',$plats)
    ->whereNull('j.deleted_at')
    ->where('j.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j = $j->where('pj.id_plataforma',$id_plataforma);

    $j_no_en_bd = DB::table('detalle_producido_juego as dp')
    ->selectRaw('-1 as id, dp.cod_juego as codigo')->distinct()
    ->whereNull('dp.id_juego')
    ->whereIn('dp.id_plataforma',$plats)
    ->where('dp.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j_no_en_bd = $j_no_en_bd->where('dp.id_plataforma',$id_plataforma);

    return ['busqueda' => $j->union($j_no_en_bd)->orderBy('codigo','asc')->get()];
  }

  public function obtenerInformeDeJuego($id_juego){
    $juego = Juego::find($id_juego);

    $estados = DB::table('plataforma_tiene_juego as pj')
    ->select('pj.id_plataforma','p.codigo as plataforma','ej.nombre as estado')
    ->join('plataforma as p','p.id_plataforma','=','pj.id_plataforma')
    ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
    ->where('pj.id_juego',$id_juego)->get();

    return ['juego' => $juego, 'categoria' => $juego->categoria_juego, 'moneda' => $juego->tipo_moneda, 'estados' => $estados];
  }

  public function obtenerProducidosDeJuego($id_plataforma,$cod_juego,$offset=0,$size=30){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $q = DB::table('detalle_producido as dp')
    ->join('producido as p','p.id_producido','=','dp.id_producido')
    ->join('tipo_moneda as m','m.id_tipo_moneda','=','p.id_tipo_moneda')
    ->where('p.id_plataforma',$id_plataforma)
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.cod_juego',$cod_juego);

    $producidos = (clone $q)
    ->select('p.fecha','m.descripcion as moneda','dp.categoria','dp.jugadores',
      'dp.apuesta_efectivo',  'dp.apuesta_bono',  'dp.apuesta',
       'dp.premio_efectivo',   'dp.premio_bono',   'dp.premio',
    'dp.beneficio_efectivo','dp.beneficio_bono','dp.beneficio')
    ->orderBy('p.fecha','desc');

    if($size > 0) $producidos = $producidos->skip($offset)->take($size);
 
    $total = (clone $q)
    ->selectRaw('COUNT(p.fecha) as cantidad,SUM(dp.apuesta) as apuesta,SUM(dp.premio) as premio,
    SUM(dp.beneficio) as beneficio,AVG(dp.premio)/AVG(dp.apuesta) as pdev')
    ->groupBy('dp.cod_juego')->get()->first();

    return ['producidos' => $producidos->get(), 'total' => $total];
  }

  public function obtenerProducidosDeJugador($id_plataforma,$jugador,$offset=0,$size=30){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $q = DB::table('detalle_producido_jugadores as dp')
    ->join('producido_jugadores as p','p.id_producido_jugadores','=','dp.id_producido_jugadores')
    ->join('tipo_moneda as m','m.id_tipo_moneda','=','p.id_tipo_moneda')
    ->where('p.id_plataforma',$id_plataforma)
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.jugador',$jugador);

    $producidos = (clone $q)
    ->select('p.fecha','m.descripcion as moneda','dp.juegos',
      'dp.apuesta_efectivo',  'dp.apuesta_bono',  'dp.apuesta',
       'dp.premio_efectivo',   'dp.premio_bono',   'dp.premio',
    'dp.beneficio_efectivo','dp.beneficio_bono','dp.beneficio')
    ->orderBy('p.fecha','desc');

    if($size > 0) $producidos = $producidos->skip($offset)->take($size);
 
    $total = (clone $q)
    ->selectRaw('COUNT(p.fecha) as cantidad,SUM(dp.apuesta) as apuesta,SUM(dp.premio) as premio,
    SUM(dp.beneficio) as beneficio,AVG(dp.premio)/AVG(dp.apuesta) as pdev')
    ->groupBy('dp.jugador')->get()->first();

    return ['producidos' => $producidos->get(), 'total' => $total];
  }

  public function beneficiosMensuales(){
    return DB::table('beneficio_mensual as bm')
    ->selectRaw('p.nombre as plataforma,YEAR(fecha) as año, MONTH(fecha) as mes, beneficio')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->orderBy('fecha','asc')
    ->get();
  }

  public function beneficiosAnuales(){
    return DB::table('beneficio_mensual as bm')
    ->selectRaw('p.nombre as plataforma, SUM(beneficio) as beneficio')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->groupBy(DB::raw('p.id_plataforma'))
    ->orderByRaw('p.nombre asc')
    ->get();
  }

  public function jugadoresMensuales(){
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresMensuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }

    $ret = DB::table('plataforma as p')
    ->selectRaw('p.nombre as plataforma,YEAR(fecha) as año, MONTH(fecha) as mes, COUNT(distinct dpj.jugador) as jugadores')
    ->join('producido_jugadores as pj','pj.id_plataforma','=','p.id_plataforma')
    ->join('detalle_producido_jugadores as dpj','dpj.id_producido_jugadores','=','pj.id_producido_jugadores')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->groupBy(DB::raw('p.id_plataforma,YEAR(fecha),MONTH(fecha)'))
    ->orderByRaw('p.nombre asc,YEAR(fecha) asc,MONTH(fecha) asc')
    ->get();

    $cc->agregar($codigo,$subcodigo,json_encode($ret),['producido_jugadores','detalle_producido_jugadores','plataforma']);
    return $ret;
  }

  public function jugadoresAnuales(){
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresAnuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }

    $ret = DB::table('plataforma as p')
    ->selectRaw('p.nombre as plataforma, COUNT(distinct dpj.jugador) as jugadores')
    ->join('producido_jugadores as pj','pj.id_plataforma','=','p.id_plataforma')
    ->join('detalle_producido_jugadores as dpj','dpj.id_producido_jugadores','=','pj.id_producido_jugadores')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->groupBy(DB::raw('p.id_plataforma'))
    ->orderByRaw('p.nombre asc')
    ->get();

    $cc->agregar($codigo,$subcodigo,json_encode($ret),['producido_jugadores','detalle_producido_jugadores','plataforma']);
    return $ret;
  }

  public function estadosDias(){
    $estado_dia = [];
    {
      $fecha_mas_vieja_b = DB::table('beneficio')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $fecha_mas_vieja_p = DB::table('producido')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $fecha_mas_vieja_pj = DB::table('producido_jugadores')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $f = min($fecha_mas_vieja_b?: date('Y-m-d'),$fecha_mas_vieja_p?: date('Y-m-d'),$fecha_mas_vieja_pj?: date('Y-m-d'));
      $fecha_actual = date('Y-m-d');
      while($f != $fecha_actual){
        $estado_dia[$f] = $this->estado_dia($f);
        $f = date('Y-m-d',strtotime($f.' +1 day'));
      }
      $estado_dia[$f] = $this->estado_dia($f);
    }
    return $estado_dia;
  }

  private function estado_dia($f){//@HACK: generalizar a multiples monedas si alguna vez se utiliza otra que no sea pesos
    $p = DB::table('producido')
    ->join('plataforma as plat','plat.id_plataforma','=','producido.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count();
    $pj = DB::table('producido_jugadores')
    ->join('plataforma as plat','plat.id_plataforma','=','producido_jugadores.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count();
    $b = DB::table('beneficio as b')
    ->join('beneficio_mensual as bm','bm.id_beneficio_mensual','=','b.id_beneficio_mensual')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count();
    $bpk = DB::table('beneficio_poker as b')
    ->join('beneficio_mensual_poker as bm','bm.id_beneficio_mensual_poker','=','b.id_beneficio_mensual_poker')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count();
    $iejug = DB::table('importacion_estado_jugador as iej')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',date('Y-m-d',strtotime($f)))
    ->count();
    $iejue = DB::table('importacion_estado_juego as iej')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',date('Y-m-d',strtotime($f)))
    ->count();
    $plat_count = DB::table('plataforma')->count();
    return ($p+$pj+$b+$bpk+$iejug+$iejue)/(6*$plat_count);
  }

  public function infoAuditoria($dia){
    $producidos = DB::table('producido as p')->select('plat.codigo')
    ->join('plataforma as plat','plat.id_plataforma','=','p.id_plataforma')
    ->where('p.fecha',$dia)->get()->pluck('codigo');
    $producidos_jugadores = DB::table('producido_jugadores as p')->select('plat.codigo')
    ->join('plataforma as plat','plat.id_plataforma','=','p.id_plataforma')
    ->where('p.fecha',$dia)->get()->pluck('codigo');
    $beneficios = DB::table('beneficio_mensual as bm')->select('plat.codigo')
    ->join('beneficio as b','b.id_beneficio_mensual','=','bm.id_beneficio_mensual')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',$dia)->get()->pluck('codigo');
    $bpoker = DB::table('beneficio_mensual_poker as bm')->select('plat.codigo')
    ->join('beneficio_poker as b','b.id_beneficio_mensual_poker','=','bm.id_beneficio_mensual_poker')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',$dia)->get()->pluck('codigo');
    $ejugadores = DB::table('importacion_estado_jugador as iej')->select('plat.codigo')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',$dia)->get()->pluck('codigo');
    $ejuegos = DB::table('importacion_estado_juego as iej')->select('plat.codigo')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',$dia)->get()->pluck('codigo');

    return ['total' => $this->estado_dia($dia),
    'producido' => $producidos,'producido_jugadores' => $producidos_jugadores,'beneficio' => $beneficios,
    'beneficio_poker' => $bpoker,'estado_jugadores' => $ejugadores,'estado_juegos' => $ejuegos];
  }

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
}

