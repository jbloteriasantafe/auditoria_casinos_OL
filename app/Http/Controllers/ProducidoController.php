<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Producido;
use App\DetalleProducido;
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

  private $query_diff_DP = 'SELECT ABS(dp.apuesta_bono    +dp.apuesta_efectivo  -dp.apuesta) <> 0.00 as apuesta,
  ABS(dp.premio_bono     +dp.premio_efectivo   -dp.premio)             <> 0.00 as premio,
  ABS(dp.beneficio_bono  +dp.beneficio_efectivo-dp.beneficio)          <> 0.00 as beneficio,
  ABS(dp.apuesta_efectivo-dp.premio_efectivo   -dp.beneficio_efectivo) <> 0.00 as beneficio_efectivo,
  ABS(dp.apuesta_bono    -dp.premio_bono       -dp.beneficio_bono)     <> 0.00 as beneficio_bono,
  (j.id_juego IS NULL) or (j.id_categoria_juego IS NULL) or (LOWER(cj.nombre) <> LOWER(dp.categoria)) as categoria,
  (j.id_juego IS NULL) or (p.id_tipo_moneda <> j.id_tipo_moneda) as moneda,
  IF(j.id_juego IS NULL or dp.apuesta_efectivo = 0.00,NULL,
    (100*dp.premio_efectivo/dp.apuesta_efectivo)/j.porcentaje_devolucion) as efectivo_pdev,
  IF(j.id_juego IS NULL or dp.apuesta_bono = 0.00,NULL,
    (100*dp.premio_bono/dp.apuesta_bono)/j.porcentaje_devolucion) as bono_pdev,
  IF(j.id_juego IS NULL or dp.apuesta = 0.00,NULL,
    (100*dp.premio/dp.apuesta)/j.porcentaje_devolucion) as total_pdev,
  dp.id_detalle_producido,
  p.id_producido
  FROM detalle_producido dp
  JOIN producido p on (dp.id_producido = p.id_producido)
  LEFT JOIN juego j on (dp.cod_juego = j.cod_juego AND j.deleted_at IS NULL)
  LEFT JOIN categoria_juego cj on (j.id_categoria_juego = cj.id_categoria_juego)';

  public function buscarTodo(){
    DB::statement(sprintf("CREATE OR REPLACE VIEW detalle_producido_diferencias AS %s",$this->query_diff_DP));

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Producidos' ,'producidos') ;
    return view('seccionProducidos' , ['plataformas' => $usuario->plataformas,'tipo_monedas' => TipoMoneda::all()]);
  }

  public function buscarProducidos(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plataformas = array();
    if(!empty($request->id_plataforma)){
      $plataformas= [$request->id_plataforma];
    }
    else foreach ($usuario->plataformas as $plataforma) {
      $plataformas[] = $plataforma->id_plataforma;
    }
    
    $reglas = [];
    if($request->id_tipo_moneda != "") $reglas[] = ['id_tipo_moneda','=',$request->id_tipo_moneda];

    $fecha_inicio = '2020-01-01';
    $fecha_fin = date('Y-m-d');
    if(!empty($request->fecha_inicio)) $fecha_inicio = $request->fecha_inicio;
    if(!empty($request->fecha_fin))    $fecha_fin    = $request->fecha_fin;
  
    $resultados = DB::table('producido')->whereIn('id_plataforma',$plataformas)->where($reglas)
    ->whereBetween('fecha',[$fecha_inicio, $fecha_fin])
    ->selectRaw('producido.*, 
    SUM(diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono OR diff.categoria OR diff.moneda) as diferencias')
    ->join('detalle_producido_diferencias as diff','diff.id_producido','=','producido.id_producido')
    ->groupBy('producido.id_producido');
    if($request->correcto == "1") $resultados = $resultados->having('diferencias','=','0');
    if($request->correcto == "0") $resultados = $resultados->having('diferencias','>','0');

    $sort_by = ["columna" => "producido.fecha", "orden" => "desc"];
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
                   , array(), self::$atributos)->after(function($validator){
                   })->sometimes('id_producido','exists:producido,id_producido',function($input){
                      $prod = Producido::find($input['id_producido']);
                      return !$prod->validado;
                   })->validate();

     $pdo = DB::connection('mysql')->getPdo();

     $query = sprintf(" DELETE FROM detalle_producido
                        WHERE id_producido = '%d'
                        ",$id_producido);

     $pdo->exec($query);

     $query = sprintf(" DELETE FROM producido
                        WHERE id_producido = '%d'
                        ",$id_producido);

    $pdo->exec($query);
  }

  public function datosDetalle($id_detalle_producido){
    $d = DetalleProducido::find($id_detalle_producido);
    $j = Juego::where([['cod_juego','=',$d->cod_juego]])->whereNull('deleted_at')->first();

    return ['detalle' => $d,'juego' => $j,
      'categoria' => is_null($j) || is_null($j->categoria_juego)? '' : $j->categoria_juego->nombre,
      'diferencias' => DB::table('detalle_producido_diferencias')->where('id_detalle_producido',$d->$id_detalle_producido)->get()
    ];
  }

  public function detallesProducido($id_producido){
    $detalles = DB::table('detalle_producido as det')
    ->selectRaw('det.cod_juego, det.id_detalle_producido, 
    (diff.apuesta OR diff.premio OR diff.beneficio OR diff.beneficio_efectivo OR diff.beneficio_bono OR diff.categoria OR diff.moneda) as diferencia')
    ->join('detalle_producido_diferencias as diff','diff.id_detalle_producido','=','det.id_detalle_producido')
    ->where('det.id_producido',$id_producido)
    ->orderBy('det.cod_juego','asc')->get();
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
}
