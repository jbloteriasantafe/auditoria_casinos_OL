<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\TipoMoneda;
use App\Plataforma;
use App\Beneficio;
use View;
use Dompdf\Dompdf;
use App\Juego;
use \Datetime;
use App\BeneficioMensual;
use App\Cotizacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class informesController extends Controller
{

  private static $atributos = ['cod_juego' => 'Código del juego',
                               'id_plataforma' => 'ID de plataforma'];

    /*
      CONTROLADOR ENCARGADO DE OBTENER DATOS
      PARA PANTALLAS DE INFORMES
    */

  private function obtenerMes($mes_num){
    $mes_map = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    return $mes_map[intval($mes_num)-1];
  }

  public function generarPlanilla($anio,$mes,$id_plataforma,$id_tipo_moneda,$simplificado){
    $dias = DB::table('beneficio')->select(
      DB::raw('CONCAT(LPAD(DAY(beneficio.fecha)  ,2,"00"),"-",
                      LPAD(MONTH(beneficio.fecha),2,"00"),"-",
                      YEAR(beneficio.fecha)) as fecha'),
    'beneficio.jugadores','beneficio.apuesta','beneficio.premio','beneficio.ajuste','beneficio.beneficio','cotizacion.valor as cotizacion')
    ->join('beneficio_mensual','beneficio_mensual.id_beneficio_mensual','=','beneficio.id_beneficio_mensual')
    ->leftJoin('cotizacion',function($j){
      return $j->on('cotizacion.fecha','=','beneficio.fecha')->on('cotizacion.id_tipo_moneda','=','beneficio_mensual.id_tipo_moneda');
    })
    ->where([['beneficio_mensual.id_plataforma','=',$id_plataforma],['beneficio_mensual.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('beneficio_mensual.fecha','=',$anio)
    ->whereMonth('beneficio_mensual.fecha','=',$mes)
    ->orderBy('beneficio.fecha','asc')->get();

    $total = DB::table('beneficio_mensual')->select(DB::raw('"" as jugadores'),'apuesta','premio','ajuste','beneficio')
    ->where([['beneficio_mensual.id_plataforma','=',$id_plataforma],['beneficio_mensual.id_tipo_moneda','=',$id_tipo_moneda]])
    ->whereYear('fecha','=',$anio)
    ->whereMonth('fecha','=',$mes)->first();

    if(is_null($total)){
      $total = new \stdClass;
      $total->jugadores = 0;
      $total->apuesta = 0;
      $total->premio = 0;
      $total->ajuste = 0;
      $total->beneficio = 0;
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
        $total_beneficio += $ultima_cotizacion*$d->beneficio;
      }
    }

    $mesTexto = $this->obtenerMes($mes);
    $view = View::make('planillaInformesJuegos',compact('mesTexto','dias','cotizacionDefecto','total_beneficio','total','simplificado'));

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

  public function informePlataformaObtenerEstado($id_plataforma){
    ProducidoController::inicializarVistas();
    //Auxiliares para simplificar la query
    //NULL es ignorado cuando MySQL hace AVG
    $avg_esperado =  'AVG(IF(dp.id_detalle_producido IS NULL,
                                NULL,
                                juego.porcentaje_devolucion))';
    $avg_producido = '100*AVG(IF(dp.apuesta = 0 or dp.apuesta IS NULL,
                                 NULL,
                                 dp.premio/dp.apuesta))';
  
    $select_pdev = $avg_esperado.'  as pdev_esperado,'.$avg_producido.' as pdev_producido';

    $juegos_plataforma = DB::table('plataforma')
    ->join('plataforma_tiene_juego','plataforma.id_plataforma','=','plataforma_tiene_juego.id_plataforma')
    ->join('juego',function($j){
      //Si estuvo y esta borrado no lo consideramos en la BD
      return $j->on('juego.id_juego','=','plataforma_tiene_juego.id_juego')->whereRaw('juego.deleted_at IS NULL');
    })->where('plataforma.id_plataforma',$id_plataforma);

    //Junta las 3 querys en un arreglo asociado 
    function juntar($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs){
      $ret = [];
      foreach($cantidad_juegos as $c){
        $K = $c->{$clasificador};
        if(!array_key_exists($K,$ret)) $ret[$K] = [];
        $ret[$K][$clasificador] = $K;
        $ret[$K]['juegos']      = $c->juegos;
      }
      foreach($avg_pdev as $c){
        $K = $c->{$clasificador};
        if(!array_key_exists($K,$ret)) $ret[$K] = [];
        $ret[$K][$clasificador] = $K;
        $ret[$K]['pdev']        = $c->pdev;
      }
      foreach($producido_pdevs as $c){
        $K = $c->{$clasificador};
        if(!array_key_exists($K,$ret)) $ret[$K] = [];
        $ret[$K][$clasificador]    = $K;
        $ret[$K]['pdev_esperado']  = $c->pdev_esperado;
        $ret[$K]['pdev_producido'] = $c->pdev_producido;
      }
      return $ret;
    }

    //Devuelve las estadisticas como son esperadas en el frontend
    function estadisticas($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs){
      $juntos = juntar($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs);
      $ret = [];
      foreach($juntos as $fila){
        $ret[] = $fila;
      }
      return $ret;
    }

    $estadisticas = [];
    {//ESTADO
      $clasificador = 'Estado';

      $cantidad_juegos = (clone $juegos_plataforma)->selectRaw('estado_juego.nombre as '.$clasificador.', COUNT(distinct juego.cod_juego) as juegos')
      ->join('estado_juego','estado_juego.id_estado_juego','=','plataforma_tiene_juego.id_estado_juego')
      ->groupBy('plataforma_tiene_juego.id_estado_juego')->get();

      $avg_pdev = (clone $juegos_plataforma)->selectRaw('estado_juego.nombre as '.$clasificador.', AVG(juego.porcentaje_devolucion) as pdev')
      ->join('estado_juego','estado_juego.id_estado_juego','=','plataforma_tiene_juego.id_estado_juego')
      ->groupBy('plataforma_tiene_juego.id_estado_juego')->get();

      $producido_pdevs = DB::table('producido as p')
      ->selectRaw('estado_juego.nombre as '.$clasificador.', '.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego','juego.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego',function($j) use ($id_plataforma){
        return $j->on('plataforma_tiene_juego.id_juego','=','juego.id_juego')->where('plataforma_tiene_juego.id_plataforma','=',$id_plataforma);
      })
      ->join('estado_juego','estado_juego.id_estado_juego','=','plataforma_tiene_juego.id_estado_juego')
      ->groupBy('plataforma_tiene_juego.id_estado_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs);
    }

    {//TIPO
      $clasificador = 'Tipo';

      $tipo = '(CASE 
        WHEN (juego.movil+juego.escritorio) = 2 THEN "Escritorio/Movil"
        WHEN juego.movil = 1 THEN "Movil"
        WHEN juego.escritorio = 1 THEN "Escritorio"
        ELSE "(ERROR) Sin tipo asignado"
      END) as '.$clasificador;
      
      $cantidad_juegos = (clone $juegos_plataforma)->selectRaw($tipo.', COUNT(distinct juego.cod_juego) as juegos')
      ->groupBy(DB::raw('juego.movil, juego.escritorio'))->get();

      $avg_pdev = (clone $juegos_plataforma)->selectRaw($tipo.', AVG(juego.porcentaje_devolucion) as pdev')
      ->groupBy(DB::raw('juego.movil, juego.escritorio'))->get();

      $select_pdev = $avg_esperado.'  as pdev_esperado,'.$avg_producido.' as pdev_producido';
      $producido_pdevs = DB::table('producido as p')
      ->selectRaw($tipo.','.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego','juego.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego',function($j) use ($id_plataforma){
        return $j->on('plataforma_tiene_juego.id_juego','=','juego.id_juego')->where('plataforma_tiene_juego.id_plataforma','=',$id_plataforma);
      })
      ->groupBy(DB::raw('juego.movil, juego.escritorio'))->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs);
    }

    {//CATEGORIA
      $clasificador = 'Categoria';

      $cantidad_juegos = (clone $juegos_plataforma)->selectRaw('categoria_juego.nombre as '.$clasificador.', COUNT(distinct juego.cod_juego) as juegos')
      ->join('categoria_juego','categoria_juego.id_categoria_juego','=','juego.id_categoria_juego')
      ->groupBy('juego.id_categoria_juego')->get();

      $avg_pdev = (clone $juegos_plataforma)->selectRaw('categoria_juego.nombre as '.$clasificador.', AVG(juego.porcentaje_devolucion) as pdev')
      ->join('categoria_juego','categoria_juego.id_categoria_juego','=','juego.id_categoria_juego')
      ->groupBy('juego.id_categoria_juego')->get();

      $producido_pdevs = DB::table('producido as p')
      ->selectRaw('categoria_juego.nombre as '.$clasificador.', '.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego','juego.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego',function($j) use ($id_plataforma){
        return $j->on('plataforma_tiene_juego.id_juego','=','juego.id_juego')->where('plataforma_tiene_juego.id_plataforma','=',$id_plataforma);
      })
      ->join('categoria_juego','categoria_juego.id_categoria_juego','=','juego.id_categoria_juego')
      ->groupBy('juego.id_categoria_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs);
    }

    {//Categoria Informada, esta es mas simple con 1 sola query pero lo hago asi para mantener el patron
      $clasificador = 'Categoria Informada (NO EN BD)';

      $cantidad_juegos = DB::table('detalle_producido_juego as dp')
      ->selectRaw('dp.categoria as "'.$clasificador.'", COUNT(distinct dp.cod_juego) as juegos')
      ->groupBy('dp.categoria')->where('dp.id_plataforma',$id_plataforma)->whereNull('dp.id_juego')->get();

      $avg_pdev = DB::table('detalle_producido_juego as dp')
      ->selectRaw('dp.categoria as "'.$clasificador.'", NULL as pdev')
      ->groupBy('dp.categoria')->where('dp.id_plataforma',$id_plataforma)->whereNull('dp.id_juego')->get();

      $producido_pdevs = DB::table('detalle_producido_juego as dp')
      ->selectRaw('dp.categoria as "'.$clasificador.'", NULL as pdev_esperado,'.$avg_producido.'as pdev_producido')
      ->groupBy('dp.categoria')->where('dp.id_plataforma',$id_plataforma)->whereNull('dp.id_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_juegos,$avg_pdev,$producido_pdevs);
    }

    return ['estadisticas' => $estadisticas];
  }

  public function buscarTodoInformeContable(){//@TODO: Esto se va a cambiar cuando se refactorizen los informes
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos [] = $casino;
    }
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable MTM' , 'informeContableMTM');

    return view('contable_mtm', ['casinos' => $casinos]);
  }

  public function obtenerInformeContableDeMaquina($id_maquina){//@TODO: Esto se va a cambiar cuando se refactorizen los informes
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make([
         'id_maquina' => $id_maquina,
       ],
       [
         'id_maquina' => 'required|exists:maquina,id_maquina' ,
       ] , array(), self::$atributos)->after(function ($validator){

    })->validate();

    $maquina = Maquina::find($id_maquina);
    $sector = isset($maquina->isla->sector) ? $sector = $maquina->isla->sector->descripcion : $sector = "-";
    $fecha= date('Y-m-d');//hoy
    //No tiene sentido mostrar el producido del dia de hoy porque siempre se carga
    //Con un delay de un dia, empezamos desde ayer.
    $fecha=date('Y-m-d' , strtotime($fecha . ' - 1 days')); 
    $fin = true;
    $i= 0;
    $suma = 0;
    $datos = $arreglo = array();
    
    //Hay logs repetidos con ids distintos por algun motivo...
    //Los agrupamos, para eso necesitamos cada campo, menos el id_log_maquina
    //Antes se hacia asi y  retornaba duplicados! no cambiar 
    //Sin saber esto
    /*$logs = LogMaquina::join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','log_maquina.id_tipo_movimiento')
    ->where('id_maquina' , $id_maquina)->orderBy('fecha', 'desc')->get();*/
    
    $columnas_str = "";
    $columnas = Schema::getColumnListing('log_maquina');
    foreach($columnas as $col){
      if($col != "id_log_maquina"){
        $columnas_str .= ", l.".$col;
      }
    }
    $columnas = Schema::getColumnListing('tipo_movimiento');
    foreach($columnas as $col){
      $columnas_str .= ", t.".$col;
    }

    $query="SELECT 
    GROUP_CONCAT(DISTINCT(l.id_log_maquina) separator '/') as ids_logs_maquinas
    "
    .
    $columnas_str
    .
    "
    from log_maquina l
    join tipo_movimiento t on (l.id_tipo_movimiento = t.id_tipo_movimiento)
    where l.id_maquina = :id_maquina
    GROUP BY
    " 
    .
    substr($columnas_str,1)//saco la coma
    ."
    order by l.fecha desc";
        
    $parametros = ['id_maquina' => $id_maquina];
    $logs = DB::select(DB::raw($query),$parametros);
    usort($logs,function($a,$b){
      //Comparo primero por fecha y si son iguales por el id mas chico.
      //Se simplificaria si tuviera hora minuto segundo...
      $fecha_a = strtotime($a->fecha);
      $fecha_b = strtotime($a->fecha);
      if($fecha_a < $fecha_b) return 1;
      else if($fecha_a > $fecha_b) return -1;

      $ids_a = explode('/',$a->ids_logs_maquinas);
      $ids_b = explode('/',$b->ids_logs_maquinas);
      $smallest_a = $ids_a[0];
      $smallest_b = $ids_b[0];
      foreach($ids_a as $ida){
        if($ida < $smallest_a) $smallest_a = $ida;
      }
      foreach($ids_b as $idb){
        if($idb < $smallest_b) $smallest_b = $idb;
      }
      if($smallest_a < $smallest_b) return 1;
      else if($smallest_a > $smallest_b) return -1;
      return 0;
    });

    while($fin){
      $estado = $this->checkEstadoMaquina($fecha, $maquina->id_maquina);
      $aux= new \stdClass();
      $valor = 0;
      if($estado['estado_producido']['detalle']!= null) $valor = $estado['estado_producido']['detalle']->valor;
      $suma+= $valor;
      $datos[] = ['valor' => $valor, 'fecha' => strftime('%d %b %y' ,  strtotime($fecha))];
      $arreglo[] = $estado;//suma total
      $fecha=date('Y-m-d' , strtotime($fecha . ' - 1 days'));

      //condiciones finalizacion
      $i++;
      if($i == 15) $fin = false;
    }
    $fechax = Carbon::now()->format('Y-m-d');
    $detalles_5 = DB::table('detalle_relevamiento')
    ->select('detalle_relevamiento.*','maquina.nro_admin','relevamiento.*')
    ->join('maquina','maquina.id_maquina','=','detalle_relevamiento.id_maquina')
    ->join('relevamiento','relevamiento.id_relevamiento','=','detalle_relevamiento.id_relevamiento')
    ->where('maquina.id_maquina','=',$id_maquina)
    ->where('relevamiento.fecha_carga','<>',$fechax)//$fechax->year().'-'.$fechax->month().'-'.$fechax->day())
    ->orderBy('relevamiento.fecha_carga','desc')
    ->take(5)->get();

    $juego = $maquina->juego_activo;
    return ['arreglo' => array_reverse($arreglo),
            'datos' => array_reverse($datos),
            'nro_admin' => $maquina->nro_admin  ,
            'marca' => $maquina->marca,
            'casino' => $maquina->casino->nombre,
            'moneda' => $maquina->tipoMoneda,
            'isla' => 
            [
              'nro_isla' =>  (is_null($maquina->isla))? null: $maquina->isla->nro_isla , 
              'codigo' => (is_null($maquina->isla))? null: $maquina->isla->codigo
            ],
            'sector' => $sector,
            'juego' => $juego->nombre_juego,
            'producido' => $suma,
            'movimientos' => $logs,
            'denominacion_juego' => $maquina->obtenerDenominacion(),
            'porcentaje_devolucion' => $maquina->obtenerPorcentajeDevolucion(),
            'relevamientos' => $detalles_5,
            'tipos_causa_no_toma' => TipoCausaNoToma::all()
            ];
  }

  public function checkEstadoMaquina($fecha , $id_maquina){//@TODO: Esto se va a cambiar cuando se refactorizen los informes
      //checkeo el estado de la maquina para un dia determinado
      //CERRADO(PRODUCIDO AJUSTADO/VALIDADO), VALIDADO(RELEVACION VALIDADA) Y RELEVADO(TUVO RELEVAMIENTO PARA DICHO DIA)
      $estado_contadores = ContadorController::getInstancia()->estaCerradoMaquina($fecha,$id_maquina);

      $estado_producido = ProducidoController::getInstancia()->estaValidadoMaquina($fecha,$id_maquina);

      $estado_relevamiento = RelevamientoController::getInstancia()->estaRelevadoMaquina($fecha,$id_maquina);

      return ['estado_contadores' => $estado_contadores,
              'estado_relevamiento' => $estado_relevamiento,
              'estado_producido' => $estado_producido];
      //contador SE MUESTRA POR PANTALLA YA QUE NO SIEMPRE EXISTE RELEVAMIENTO PARA ESA MAQUINA EN ESA FECHA
  }
}
