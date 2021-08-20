<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Producido;
use App\DetalleProducido;
use App\ProducidoJugadores;
use App\DetalleProducidoJugadores;
use App\Plataforma;
use App\TipoMoneda;
use App\Juego;
use View;
use Dompdf\Dompdf;
use App\Http\Controllers\FormatoController;


class ProducidoController extends Controller
{
  private static $instance;

  private static $atributos=[];

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new ProducidoController();
    }
    return self::$instance;
  }

  //Asocia el detalle_producido con el juego (si lo tiene). Devuelve una tabla mas plana y facil de queryar
  //@HACK: Tal vez haya que cambiarlo para verificar la fecha del deleted_at contra la fecha del producido
  private static $view_DP_juego = "CREATE OR REPLACE VIEW detalle_producido_juego AS
  SELECT producido.fecha,producido.id_plataforma,producido.id_tipo_moneda,detalle_producido.*,plataforma_tiene_juego.id_juego
  FROM detalle_producido
  JOIN producido on producido.id_producido = detalle_producido.id_producido
  LEFT JOIN juego on detalle_producido.cod_juego = juego.cod_juego and juego.deleted_at IS NULL
  LEFT JOIN plataforma_tiene_juego on plataforma_tiene_juego.id_juego = juego.id_juego and plataforma_tiene_juego.id_plataforma = producido.id_plataforma";

  //Muestra el detalle_producido con las diferencias (contra si mismo y contra la BD)
  private static $view_diff_DP = 'CREATE OR REPLACE VIEW detalle_producido_diferencias AS
  SELECT ABS(dp.apuesta_bono    +dp.apuesta_efectivo  -dp.apuesta) <> 0.00 as apuesta,
  ABS(dp.premio_bono     +dp.premio_efectivo   -dp.premio)             <> 0.00 as premio,
  ABS(dp.beneficio_bono  +dp.beneficio_efectivo-dp.beneficio)          <> 0.00 as beneficio,
  ABS(dp.apuesta_efectivo-dp.premio_efectivo   -dp.beneficio_efectivo) <> 0.00 as beneficio_efectivo,
  ABS(dp.apuesta_bono    -dp.premio_bono       -dp.beneficio_bono)     <> 0.00 as beneficio_bono,
  (j.id_juego IS NULL) or (j.id_categoria_juego IS NULL) or (LOWER(cj.nombre) <> LOWER(dp.categoria)) as categoria,
  (j.id_juego IS NULL) or (dp.id_tipo_moneda <> j.id_tipo_moneda) as moneda,
  dp.id_detalle_producido,
  dp.id_producido
  FROM detalle_producido_juego dp
  LEFT JOIN juego j on (dp.id_juego = j.id_juego)
  LEFT JOIN categoria_juego cj on (j.id_categoria_juego = cj.id_categoria_juego)';

  private static $view_diff_P = 'CREATE OR REPLACE VIEW producido_diferencias AS
  SELECT producido.*,
  SUM(diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono OR diff.categoria OR diff.moneda) as diferencias
  FROM producido
  JOIN detalle_producido_diferencias as diff on (diff.id_producido = producido.id_producido)
  GROUP BY producido.id_producido';

  private static $view_diff_DPJ = 'CREATE OR REPLACE VIEW detalle_producido_jugadores_diferencias AS
  SELECT ABS(dp.apuesta_bono    +dp.apuesta_efectivo  -dp.apuesta) <> 0.00 as apuesta,
  ABS(dp.premio_bono     +dp.premio_efectivo   -dp.premio)             <> 0.00 as premio,
  ABS(dp.beneficio_bono  +dp.beneficio_efectivo-dp.beneficio)          <> 0.00 as beneficio,
  ABS(dp.apuesta_efectivo-dp.premio_efectivo   -dp.beneficio_efectivo) <> 0.00 as beneficio_efectivo,
  ABS(dp.apuesta_bono    -dp.premio_bono       -dp.beneficio_bono)     <> 0.00 as beneficio_bono,
  dp.id_detalle_producido_jugadores,
  p.id_producido_jugadores
  FROM detalle_producido_jugadores dp
  JOIN producido_jugadores p on (dp.id_producido_jugadores = p.id_producido_jugadores)';

  private static $view_diff_PJ = 'CREATE OR REPLACE VIEW producido_jugadores_diferencias AS 
  SELECT producido_jugadores.*,
  SUM(diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono) as diferencias
  FROM producido_jugadores
  JOIN detalle_producido_jugadores_diferencias as diff on (diff.id_producido_jugadores = producido_jugadores.id_producido_jugadores)
  GROUP BY producido_jugadores.id_producido_jugadores';

  public static function inicializarVistas(){//Llamado tambien desde informesController
    DB::beginTransaction();
    try{
      DB::statement(self::$view_DP_juego);
      DB::statement(self::$view_diff_DP);
      DB::statement(self::$view_diff_P);
      DB::statement(self::$view_diff_DPJ);
      DB::statement(self::$view_diff_PJ); 
    }
    catch(\Exception $e){
      DB::rollback();
      throw $e;
    } 
  }

  public function buscarTodo(){
    self::inicializarVistas();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Producidos' ,'producidos') ;
    return view('seccionProducidos' , ['plataformas' => $usuario->plataformas,'tipo_monedas' => TipoMoneda::all()]);
  }

  public function buscarProducidos(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plataformas = array();

    foreach ($usuario->plataformas as $plataforma) {
      $plataformas[] = $plataforma->id_plataforma;
    }
    
    $reglas = [];
    if($request->id_tipo_moneda != "")  $reglas[] = ['pdiff.id_tipo_moneda','=',$request->id_tipo_moneda];
    if(!empty($request->id_plataforma)) $reglas[] = ['pdiff.id_plataforma','=',$request->id_plataforma];   

    $fecha_inicio = '2020-01-01';
    $fecha_fin = date('Y-m-d');
    if(!empty($request->fecha_inicio)) $fecha_inicio = $request->fecha_inicio;
    if(!empty($request->fecha_fin))    $fecha_fin    = $request->fecha_fin;
  
    $resultados = DB::table('producido_diferencias as pdiff')->whereIn('pdiff.id_plataforma',$plataformas)->where($reglas)
    ->whereBetween('pdiff.fecha',[$fecha_inicio, $fecha_fin])
    ->leftJoin('producido_jugadores_diferencias as pjdiff',function($j){
      return $j->on('pdiff.fecha','=','pjdiff.fecha')
               ->on('pdiff.id_tipo_moneda','=','pjdiff.id_tipo_moneda')
               ->on('pdiff.id_plataforma','=','pjdiff.id_plataforma');
    })
    ->select('pdiff.*','pjdiff.id_producido_jugadores','pjdiff.diferencias as diferencias_jugadores','pjdiff.beneficio as beneficio_jugadores');

    if($request->correcto == "1") $resultados = $resultados->whereRaw('(pdiff.diferencias + IFNULL(pdiff.diferencias,0)) = 0');
    if($request->correcto == "0") $resultados = $resultados->whereRaw('(pdiff.diferencias + IFNULL(pdiff.diferencias,0)) > 0');
    
    $sort_by = ["columna" => "fecha", "orden" => "desc"];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }

    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    });

    $resultados = $resultados->paginate($request->page_size);
    return $resultados;
  }
  // eliminarProducido elimina el producido y los detalles producidos asociados
  public function eliminarProducido($id_producido){
    Validator::make(['id_producido' => $id_producido]
                   ,['id_producido' => 'required|exists:producido,id_producido']
                   , [], self::$atributos)->after(function($validator){})->validate();

    DB::transaction(function() use ($id_producido){
      $prod = Producido::find($id_producido);
      foreach($prod->detalles as $d) $d->delete();
      $prod->delete();
    });
  }

  public function eliminarProducidoJugadores($id_producido_jugadores){
    Validator::make(['id_producido_jugadores' => $id_producido_jugadores]
                   ,['id_producido_jugadores' => 'required|exists:producido_jugadores,id_producido_jugadores']
                   , [], self::$atributos)->after(function($validator){})->validate();

    DB::transaction(function() use ($id_producido_jugadores){
      $prod = ProducidoJugadores::find($id_producido_jugadores);
      foreach($prod->detalles as $d) $d->delete();
      $prod->delete();
    });
  }

  public function datosDetalle($id_detalle_producido){
    $d = DetalleProducido::find($id_detalle_producido);
    $diferencias = DB::table('detalle_producido_diferencias')->where('id_detalle_producido',$id_detalle_producido)->get()->first();
    
    $id_juego = DB::table('detalle_producido_juego')
    ->where('id_detalle_producido',$id_detalle_producido)->select('id_juego')->get()->pluck('id_juego')->first();
    $juego = is_null($id_juego)? null : Juego::find($id_juego);
    return ['detalle' => $d,'juego' => $juego,
      'categoria' => is_null($juego) || is_null($juego->categoria_juego)? '' : $juego->categoria_juego->nombre,
      'diferencias' => $diferencias
    ];
  }

  public function datosDetalleJugadores($id_detalle_producido_jugadores){
    $d = DetalleProducidoJugadores::find($id_detalle_producido_jugadores);
    return ['detalle' => $d,'juego' => '',
      'categoria' => '',
      'diferencias' => DB::table('detalle_producido_jugadores_diferencias')->where('id_detalle_producido_jugadores',$id_detalle_producido_jugadores)->get()->first()
    ];
  }

  public function detallesProducido($id_producido){
    $detalles = DB::table('detalle_producido as det')
    ->selectRaw('det.cod_juego as codigo, diff.id_detalle_producido as id_detalle, 
    (diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono OR diff.categoria OR diff.moneda) as diferencia')
    ->join('detalle_producido_diferencias as diff','diff.id_detalle_producido','=','det.id_detalle_producido')
    ->where('det.id_producido',$id_producido)
    ->orderBy('det.cod_juego','asc')->get();
    return ['detalles' => $detalles];
  }

  public function detallesProducidoJugadores($id_producido_jugadores){
    $detalles = DB::table('detalle_producido_jugadores as det')
    ->selectRaw('det.jugador as codigo, diff.id_detalle_producido_jugadores as id_detalle, 
    (diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono) as diferencia')
    ->join('detalle_producido_jugadores_diferencias as diff','diff.id_detalle_producido_jugadores','=','det.id_detalle_producido_jugadores')
    ->where('det.id_producido_jugadores',$id_producido_jugadores)
    ->orderBy('det.jugador','asc')->get();
    return ['detalles' => $detalles];
  }
  
  // generarPlanilla crea la planilla del producido total del dia
  public function generarPlanilla($id_producido){
    $producido = Producido::find($id_producido);
    $detalles = DB::table('producido')
    ->selectRaw('detalle_producido.cod_juego, detalle_producido.apuesta, detalle_producido.premio, detalle_producido.beneficio, juego.id_juego IS NOT NULL AS en_bd')
    ->join('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
    ->leftJoin('juego','juego.cod_juego','=','detalle_producido.cod_juego')
    ->leftJoin('plataforma_tiene_juego',function($j){
      return $j->on('producido.id_plataforma','=','plataforma_tiene_juego.id_plataforma')
      ->on('juego.id_juego','=','plataforma_tiene_juego.id_juego');
    })
    ->where('producido.id_producido',$id_producido)
    ->orderBy('detalle_producido.cod_juego','asc')->get();
    //Para debuggear ->orderBy('detalle_producido.id_detalle_producido','asc') es mejor porque ordena segun el orden de insercion del CSV

    $pro = new \stdClass();
    $pro->plataforma = $producido->plataforma->nombre;
    $pro->tipo_moneda = $producido->tipo_moneda->descripcion;
    if($pro->tipo_moneda == 'ARS'){
      $pro->tipo_moneda = 'Pesos';
    }
    else if ($pro->tipo_moneda == 'USD'){
      $pro->tipo_moneda = 'Dólares';
    }
    
    $fecha = explode("-",$producido->fecha);
    $pro->fecha_prod = $fecha[2].'-'.$fecha[1].'-'.$fecha[0];

    $view = View::make('planillaProducidos',compact('detalles','pro'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function generarPlanillaJugadores($id_producido_jugadores){
    $producido = ProducidoJugadores::find($id_producido_jugadores);
    //Hago una query y me quedo solo con los campos necesario, si hago un $producido->jugadores le agrega un monton de cosas al objeto y 
    //y para CCO, excede el limite de memoria de PHP (no quiero subirlo)
    $detalles = DB::table('producido_jugadores as pj')
    ->selectRaw('dpj.jugador,dpj.apuesta,dpj.premio,dpj.beneficio')
    ->join('detalle_producido_jugadores as dpj','dpj.id_producido_jugadores','=','pj.id_producido_jugadores')
    ->where('pj.id_producido_jugadores',$id_producido_jugadores)->orderBy('dpj.jugador','asc')->get();

    $pro = new \stdClass();
    $pro->plataforma = $producido->plataforma->nombre;
    $pro->tipo_moneda = $producido->tipo_moneda->descripcion;
    if($pro->tipo_moneda == 'ARS'){
      $pro->tipo_moneda = 'Pesos';
    }
    else if ($pro->tipo_moneda == 'USD'){
      $pro->tipo_moneda = 'Dólares';
    }
    
    $fecha = explode("-",$producido->fecha);
    $pro->fecha_prod = $fecha[2].'-'.$fecha[1].'-'.$fecha[0];

    $view = View::make('planillaProducidosJugadores',compact('detalles','pro'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }
}
