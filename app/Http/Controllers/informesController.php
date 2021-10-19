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

  public function informePlataformaObtenerEstado(Request $request,$id_plataforma){
    return ['estadisticas' => $this->obtenerEstadisticas($id_plataforma),
            'juegos_faltantes' => $this->obtenerJuegosFaltantes($id_plataforma),
            'alertas' => $this->obtenerAlertas($id_plataforma,$request->beneficio_alertas)];
  }

  private function obtenerEstadisticas($id_plataforma){
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

    Notar que avago usamos AVG() en vez de sum. Esto es porque da lo mismo
      AVG(A)/AVG(B) = ((A1+A2+A3)/3) / ((B1+B2+B3)/3) = (A1+A2+A3)/(B1+B2+B3) = SUM(A) / SUM (B)
    CREO que tiene mejor comportamiento para numeros flotantes, porque ∑P_i y ∑A_i son numeros muy grandes.
    Depende de la implementación de SUM y AVG... en todo caso no hace mal
    */

    //Uso la vista detalle_producido_juego que ya asocia el detalle_producido con el juego
    ProducidoController::inicializarVistas();
    //Auxiliares para simplificar la query
    //NULL es ignorado cuando MySQL hace AVG
    //El esperado no lo tengo que multiplicar por 100 porque el porcentaje_devolucion esta en 0-100 en vez de 0-1
    $avg_esperado = 'AVG(j.porcentaje_devolucion*dp.apuesta)/AVG(dp.apuesta)';
    $avg_producido = '100*AVG(dp.premio)/AVG(dp.apuesta)';
  
    $select_pdev = $avg_esperado.'  as pdev_esperado,'.$avg_producido.' as pdev_producido';

    $juegos_plataforma = DB::table('plataforma as p')
    ->join('plataforma_tiene_juego as pj','pj.id_plataforma','=','p.id_plataforma')
    ->join('juego as j',function($j){
      //Si estuvo y esta borrado no lo consideramos en la BD
      return $j->on('j.id_juego','=','pj.id_juego')->whereRaw('j.deleted_at IS NULL');
    })->where('p.id_plataforma',$id_plataforma);

    //Devuelve las estadisticas como son esperadas en el frontend
    function estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs){
      //Junto las 3 querys en un arreglo asociado 
      $juntas = [];
      foreach($cantidad_y_pdev as $c){
        $K = $c->{$clasificador};
        if(!array_key_exists($K,$juntas)) $juntas[$K] = [];
        $juntas[$K][$clasificador] = $K;
        $juntas[$K]['juegos']      = $c->juegos;
        $juntas[$K]['pdev']        = $c->pdev;
      }
      foreach($producido_pdevs as $c){
        $K = $c->{$clasificador};
        if(!array_key_exists($K,$juntas)) $juntas[$K] = [];
        $juntas[$K][$clasificador]    = $K;
        $juntas[$K]['pdev_esperado']  = $c->pdev_esperado;
        $juntas[$K]['pdev_producido'] = $c->pdev_producido;
      }
      //Retorno los valores, sin las llaves, porque asi lo espera el frontend
      return array_values($juntas);
    }

    $estadisticas = [];
    {//ESTADO
      $clasificador = 'Estado';

      $cantidad_y_pdev = (clone $juegos_plataforma)
      ->selectRaw('ej.nombre as '.$clasificador.', COUNT(distinct j.cod_juego) as juegos, AVG(j.porcentaje_devolucion) as pdev')
      ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
      ->groupBy('pj.id_estado_juego')->get();

      $producido_pdevs = DB::table('producido as p')
      ->selectRaw('ej.nombre as '.$clasificador.', '.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego as j','j.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego as pj',function($j){
        return $j->on('pj.id_juego','=','j.id_juego')->on('pj.id_plataforma','=','p.id_plataforma');
      })
      ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
      ->where('p.id_plataforma',$id_plataforma)
      ->groupBy('pj.id_estado_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs);
    }

    {//TIPO
      $clasificador = 'Tipo';

      $tipo = '(CASE 
        WHEN (j.movil+j.escritorio) = 2 THEN "Escritorio/Movil"
        WHEN j.movil = 1 THEN "Movil"
        WHEN j.escritorio = 1 THEN "Escritorio"
        ELSE "(ERROR) Sin tipo asignado"
      END) as '.$clasificador;
      
      $cantidad_y_pdev = (clone $juegos_plataforma)
      ->selectRaw($tipo.', COUNT(distinct j.cod_juego) as juegos, AVG(j.porcentaje_devolucion) as pdev')
      ->groupBy(DB::raw('j.movil, j.escritorio'))->get();

      $producido_pdevs = DB::table('producido as p')
      ->selectRaw($tipo.','.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego as j','j.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego as pj',function($j){
        return $j->on('pj.id_juego','=','j.id_juego')->on('pj.id_plataforma','=','p.id_plataforma');
      })
      ->where('p.id_plataforma',$id_plataforma)
      ->groupBy(DB::raw('j.movil, j.escritorio'))->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs);
    }

    {//CATEGORIA
      $clasificador = 'Categoria';

      $cantidad_y_pdev = (clone $juegos_plataforma)
      ->selectRaw('cj.nombre as '.$clasificador.', COUNT(distinct j.cod_juego) as juegos, AVG(j.porcentaje_devolucion) as pdev')
      ->join('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
      ->groupBy('j.id_categoria_juego')->get();

      $producido_pdevs = DB::table('producido as p')
      ->selectRaw('cj.nombre as '.$clasificador.', '.$select_pdev)
      ->join('detalle_producido_juego as dp','dp.id_producido','=','p.id_producido')
      ->join('juego as j','j.id_juego','=','dp.id_juego')
      ->join('plataforma_tiene_juego as pj',function($j){
        return $j->on('pj.id_juego','=','j.id_juego')->on('pj.id_plataforma','=','p.id_plataforma');
      })
      ->join('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
      ->where('p.id_plataforma',$id_plataforma)
      ->groupBy('j.id_categoria_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs);
    }

    {//Categoria Informada, esta es mas simple con 1 sola query pero lo hago asi para mantener el patron
      $clasificador = 'Categoria Informada (NO EN BD)';

      $cantidad_y_pdev = DB::table('detalle_producido_juego as dp')
      ->selectRaw('dp.categoria as "'.$clasificador.'", COUNT(distinct dp.cod_juego) as juegos, NULL as pdev')
      ->groupBy('dp.categoria')->where('dp.id_plataforma',$id_plataforma)->whereNull('dp.id_juego')->get();

      $producido_pdevs = DB::table('detalle_producido_juego as dp')
      ->selectRaw('dp.categoria as "'.$clasificador.'", NULL as pdev_esperado,'.$avg_producido.'as pdev_producido')
      ->groupBy('dp.categoria')->where('dp.id_plataforma',$id_plataforma)->whereNull('dp.id_juego')->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs);
    }

    {//El PDEV total
      $clasificador = 'Total';

      $cantidad_y_pdev = (clone $juegos_plataforma)
      ->selectRaw('"Total" as '.$clasificador.', COUNT(distinct j.cod_juego) as juegos, AVG(j.porcentaje_devolucion) as pdev')
      ->groupBy(DB::raw('"constant"'))->get();//Agrupo por una constante para promediar todo

      $producido_pdevs = DB::table('detalle_producido_juego as dp')
      ->selectRaw('"Total" as '.$clasificador.', '.$select_pdev)
      ->join('juego as j','j.id_juego','=','dp.id_juego')
      ->where('dp.id_plataforma',$id_plataforma)
      ->groupBy(DB::raw('"constant"'))->get();

      $estadisticas[$clasificador] = estadisticas($clasificador,$cantidad_y_pdev,$producido_pdevs);
    }
    return $estadisticas;
  }

  private function obtenerJuegosFaltantes($id_plataforma){
    $juegos_faltantes = DB::table('detalle_producido_juego as dp')
    ->selectRaw('dp.cod_juego,GROUP_CONCAT(distinct dp.categoria SEPARATOR ", ") as categoria,ROUND(100*AVG(dp.premio)/AVG(dp.apuesta),2) as pdev,
    SUM(dp.apuesta_efectivo)   as apuesta_efectivo,  SUM(dp.apuesta_bono)   as apuesta_bono,  SUM(dp.apuesta) as apuesta,
    SUM(dp.premio_efectivo)    as premio_efectivo,   SUM(dp.premio_bono)   as premio_bono,    SUM(dp.premio) as premio,
    SUM(dp.beneficio_efectivo) as beneficio_efectivo,SUM(dp.beneficio_bono) as beneficio_bono,SUM(dp.beneficio) as beneficio')
    ->whereNull('dp.id_juego')->where('dp.id_plataforma',$id_plataforma)
    ->groupBy('dp.cod_juego')->orderBy('dp.cod_juego');

    $total = DB::table('detalle_producido_juego as dp')
    ->selectRaw('"-TOTAL-" as cod_juego,COUNT(distinct dp.cod_juego) as categoria,ROUND(100*AVG(dp.premio)/AVG(dp.apuesta),2) as pdev,
    SUM(dp.apuesta_efectivo)   as apuesta_efectivo,  SUM(dp.apuesta_bono)   as apuesta_bono,  SUM(dp.apuesta) as apuesta,
    SUM(dp.premio_efectivo)    as premio_efectivo,   SUM(dp.premio_bono)   as premio_bono,    SUM(dp.premio) as premio,
    SUM(dp.beneficio_efectivo) as beneficio_efectivo,SUM(dp.beneficio_bono) as beneficio_bono,SUM(dp.beneficio) as beneficio')
    ->whereNull('dp.id_juego')->where('dp.id_plataforma',$id_plataforma)
    ->groupBy(DB::raw('"constant"'));

    $juegos_faltantes->union($total);
    return $juegos_faltantes->get();
  }

  private function obtenerAlertas($id_plataforma,$limite_beneficio){
    $monedas = DB::table('tipo_moneda')->get();
    $alertas = ['juegos' => [],'jugadores' => []];
    foreach($monedas as $m){
      $alertas_juegos = DB::table('detalle_producido as dp')
      ->selectRaw('p.fecha, dp.cod_juego as id, dp.apuesta, dp.premio, dp.beneficio, IF(dp.apuesta = 0,"",ROUND(100*dp.premio/dp.apuesta,3)) as pdev')
      ->join('producido as p','p.id_producido','=','dp.id_producido')
      ->where('p.id_plataforma',$id_plataforma)->where('p.id_tipo_moneda',$m->id_tipo_moneda)
      ->whereRaw('ABS(dp.beneficio) > '.$limite_beneficio)
      ->orderBy('p.fecha','desc')->orderBy('dp.cod_juego','asc')->get();
      $alertas_jugadores = DB::table('detalle_producido_jugadores as dp')
      ->selectRaw('p.fecha, dp.jugador as id, dp.apuesta, dp.premio, dp.beneficio, IF(dp.apuesta = 0,"",ROUND(100*dp.premio/dp.apuesta,3)) as pdev')
      ->join('producido_jugadores as p','p.id_producido_jugadores','=','dp.id_producido_jugadores')
      ->where('p.id_plataforma',$id_plataforma)->where('p.id_tipo_moneda',$m->id_tipo_moneda)
      ->whereRaw('ABS(dp.beneficio) > '.$limite_beneficio)
      ->orderBy('p.fecha','desc')->orderBy('dp.jugador','asc')->get();
      $alertas['juegos'][$m->descripcion] = $alertas_juegos;
      $alertas['jugadores'][$m->descripcion] = $alertas_jugadores;
    }
    return $alertas;
  }

  public function buscarTodoInformeContable(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable Juegos/Jugadores' , 'informeContableJuego');
    return view('informe_juego', ['plataformas' => $usuario->plataformas]);
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

  public function informesGenerales(){
    $beneficios_mensuales = DB::table('beneficio_mensual as bm')
    ->selectRaw('p.nombre as plataforma,YEAR(fecha) as año, MONTH(fecha) as mes, beneficio')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')->orderBy('fecha','asc')
    ->get();
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
    return view('seccionInformesGenerales',['beneficios_mensuales' => $beneficios_mensuales,'estado_dia' => $estado_dia]);
  }

  private function estado_dia($f){//@HACK: generalizar a multiples monedas si alguna vez se utiliza otra que no sea pesos
    $plat_count = DB::table('plataforma')->count();
    $p = DB::table('producido')
    ->join('plataforma as plat','plat.id_plataforma','=','producido.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count()/$plat_count;
    $pj = DB::table('producido_jugadores')
    ->join('plataforma as plat','plat.id_plataforma','=','producido_jugadores.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count()/$plat_count;
    $b = DB::table('beneficio')
    ->join('beneficio_mensual','beneficio_mensual.id_beneficio_mensual','=','beneficio.id_beneficio_mensual')
    ->join('plataforma as plat','plat.id_plataforma','=','beneficio_mensual.id_plataforma')
    ->where('beneficio.fecha',date('Y-m-d',strtotime($f)))
    ->where('id_tipo_moneda',1)
    ->count()/$plat_count;
    return ($p+$pj+$b)/3;
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
    return ['total' => $this->estado_dia($dia),
    'producidos' => $producidos,'producidos_jugadores' => $producidos_jugadores,'beneficios' => $beneficios];
  }
}

