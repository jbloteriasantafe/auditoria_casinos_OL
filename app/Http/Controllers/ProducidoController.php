<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Producido;
use App\DetalleProducido;
use App\ProducidoJugadores;
use App\ProducidoPoker;
use App\DetalleProducidoJugadores;
use App\TipoMoneda;
use App\Juego;
use App\PdfParalelo;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\ResumenController;

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
  private static $view_DP_juego = "CREATE OR REPLACE VIEW detalle_producido_juego AS
  SELECT p.fecha,p.id_plataforma,p.id_tipo_moneda,dp.*,pj.id_juego,
  IF(pj.id_juego IS NULL,NULL,j.id_categoria_juego) as id_categoria_juego,
  IF(pj.id_juego IS NULL,NULL,j.id_tipo_moneda) as id_tipo_moneda_juego
  FROM producido p 
  JOIN detalle_producido dp on dp.id_producido = p.id_producido
  LEFT JOIN juego j on (j.cod_juego = dp.cod_juego and j.deleted_at IS NULL)
  LEFT JOIN plataforma_tiene_juego pj on (pj.id_plataforma = p.id_plataforma AND pj.id_juego = j.id_juego)";

  //Muestra el detalle_producido con las diferencias (contra si mismo y contra la BD)
  private static $view_diff_DP = 'CREATE OR REPLACE VIEW detalle_producido_diferencias AS
  SELECT
  id_producido,id_detalle_producido,
  (apuesta_bono    +apuesta_efectivo  )<> apuesta as apuesta,
  (premio_bono     +premio_efectivo   )<> premio  as premio,
  (beneficio_bono  +beneficio_efectivo)<> beneficio as beneficio,
  (apuesta_efectivo-premio_efectivo   )<> beneficio_efectivo as beneficio_efectivo,
  (apuesta_bono    -premio_bono       )<> beneficio_bono as beneficio_bono,
  (id_juego IS NULL) or (id_tipo_moneda <> id_tipo_moneda_juego) as moneda,
  (id_juego IS NULL) or (cj.nombre_lower <> LOWER(dp.categoria)) as categoria
  FROM detalle_producido_juego dp
  LEFT JOIN categoria_juego cj on (cj.id_categoria_juego = dp.id_categoria_juego)';

  public function buscarTodo(){
    DB::beginTransaction();
    try{
      DB::statement(self::$view_DP_juego);
      DB::statement(self::$view_diff_DP);
    }
    catch(\Exception $e){
      DB::rollback();
      throw $e;
    } 
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
    if($request->id_tipo_moneda != "")  $reglas[] = ['p.id_tipo_moneda','=',$request->id_tipo_moneda];
    if(!empty($request->id_plataforma)) $reglas[] = ['p.id_plataforma','=',$request->id_plataforma];   

    $fecha_inicio = '2020-01-01';
    $fecha_fin = date('Y-m-d');
    if(!empty($request->fecha_inicio)) $fecha_inicio = $request->fecha_inicio;
    if(!empty($request->fecha_fin))    $fecha_fin    = $request->fecha_fin;

    //Lo pongo en strings concatenadas pq sino no me funciona el resaltador de sintaxis del editor
    $diff_subquery = "EXISTS ("."SELECT NULL
    FROM producido p2
    LEFT JOIN detalle_producido dp2 ON dp2.id_producido = p2.id_producido
    LEFT JOIN juego j2 ON ((j2.cod_juego = dp2.cod_juego) AND (j2.deleted_AT IS NULL))
    LEFT JOIN plataforma_tiene_juego plj2 ON ((plj2.id_juego = j2.id_juego) AND (plj2.id_plataforma = p2.id_plataforma))
    LEFT JOIN categoria_juego cj2 ON cj2.id_categoria_juego = j2.id_categoria_juego
    WHERE p2.id_producido = p.id_producido AND (
      (p2.diferencia_montos > 0) OR (plj2.id_juego IS NULL) OR (j2.id_tipo_moneda <> p2.id_tipo_moneda) OR (cj2.nombre_lower <> LOWER(dp2.categoria))
    )".")";

    $data = DB::table('producido as p')
    ->selectRaw('p.fecha,p.id_producido,p.id_plataforma,p.id_tipo_moneda,p.beneficio, '.$diff_subquery.' as diferencias,
    pj.id_producido_jugadores,pj.diferencia_montos as diferencias_jugadores,pj.beneficio as beneficio_jugadores')
    ->leftJoin('producido_jugadores as pj',function($j){
      return $j->on('pj.fecha','=','p.fecha')->on('pj.id_tipo_moneda','=','p.id_tipo_moneda')->on('pj.id_plataforma','=','p.id_plataforma');
    })
    ->whereBetween('p.fecha',[$fecha_inicio, $fecha_fin])->where($reglas)
    ->whereIn('p.id_plataforma',$plataformas);

    if($request->correcto == "1"){
      $data = $data->whereRaw('NOT '.$diff_subquery);
    }
    else if ($request->correcto == "0"){
      $data = $data->whereRaw($diff_subquery);
    }
    
    if(empty($request->sort_by)){
      $data = $data->orderByRaw('fecha desc,id_producido desc,id_plataforma desc,id_tipo_moneda desc');
    }
    else{
      $sort_by = $request->sort_by;
      $data = $data->orderBy($sort_by['columna'],$sort_by['orden']);
    }

    return $data->paginate($request->page_size);
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
      CacheController::getInstancia()->invalidarDependientes(['producido']);
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
      CacheController::getInstancia()->invalidarDependientes(['producido_jugadores']);
      ResumenController::getInstancia()->generarResumenMensualProducidoJugadores(
        $prod->id_plataforma,$prod->id_tipo_moneda,$prod->fecha
      );
    });
  }

  public function eliminarProducidoPoker($id_producido_poker){
    Validator::make(['id_producido_poker' => $id_producido_poker]
                   ,['id_producido_poker' => 'required|exists:producido_poker,id_producido_poker']
                   , [], self::$atributos)->after(function($validator){})->validate();

    DB::transaction(function() use ($id_producido_poker){
      $prod = ProducidoPoker::find($id_producido_poker);
      foreach($prod->detalles as $d) $d->delete();
      $prod->delete();
      CacheController::getInstancia()->invalidarDependientes(['producido_poker']);
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
    $diferencias = DB::table('detalle_producido_jugadores as dp')
    ->selectRaw('(apuesta_bono    + apuesta_efectivo  )<> apuesta as apuesta,
                 (premio_bono     + premio_efectivo   )<> premio  as premio,
                 (beneficio_bono  + beneficio_efectivo)<> beneficio as beneficio,
                 (apuesta_efectivo- premio_efectivo   )<> beneficio_efectivo as beneficio_efectivo,
                 (apuesta_bono    - premio_bono       )<> beneficio_bono as beneficio_bono')
    ->where('id_detalle_producido_jugadores','=',$id_detalle_producido_jugadores)->get()->first();
    return ['detalle'     => DetalleProducidoJugadores::find($id_detalle_producido_jugadores),
            'juego'       => '',
            'categoria'   => '',
            'diferencias' => $diferencias];
  }

  public function detallesProducido($id_producido){
    $detalles = DB::table('detalle_producido as det')
    ->selectRaw('det.cod_juego as codigo, diff.id_detalle_producido as id_detalle, 
    (det.diferencia_montos OR diff.categoria OR diff.moneda) as diferencia')
    ->join('detalle_producido_diferencias as diff','diff.id_detalle_producido','=','det.id_detalle_producido')
    ->where('det.id_producido',$id_producido)
    ->orderBy('det.cod_juego','asc')->get();
    return ['detalles' => $detalles];
  }

  public function detallesProducidoJugadores($id_producido_jugadores){
    $detalles = DB::table('detalle_producido_jugadores as det')
    ->selectRaw('det.jugador as codigo, det.id_detalle_producido_jugadores as id_detalle,det.diferencia_montos as diferencia')
    ->where('det.id_producido_jugadores',$id_producido_jugadores)
    ->orderBy('det.jugador','asc')->get();
    return ['detalles' => $detalles];
  }
  
  // generarPlanilla crea la planilla del producido total del dia
  public function generarPlanilla($id_producido){
    $producido = Producido::find($id_producido);

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

    return $this->generarPlanillaParalelo($pro,$detalles->toArray(),'juegos');
  }

  public function generarPlanillaJugadores($id_producido_jugadores){
    $producido = ProducidoJugadores::find($id_producido_jugadores);

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

    $detalles = DB::table('producido_jugadores as pj')
    ->selectRaw('dpj.jugador,dpj.apuesta,dpj.premio,dpj.beneficio')
    ->join('detalle_producido_jugadores as dpj','dpj.id_producido_jugadores','=','pj.id_producido_jugadores')
    ->where('pj.id_producido_jugadores',$id_producido_jugadores)->orderBy('dpj.jugador','asc')->get();

    return $this->generarPlanillaParalelo($pro,$detalles->toArray(),'jugadores');
  }

  //Refactorizo a hacerlo en paralelo porque CCO tiene un monton de jugadores.
  //No andaba ni aumentandole el limite de memoria (excedia tiempo limite de dompdf...)
  private function generarPlanillaParalelo($pro,array $todos_los_detalles,$tipo){
    $cols_x_pag = 3;
    $filas_por_col = 68;

    $detalles_por_pagina = $cols_x_pag * $filas_por_col;
    $paginas_por_pdf = 5;
    $detalles_por_pdf = $paginas_por_pdf*$detalles_por_pagina;

    $cantidad_totales = count($todos_los_detalles);

    $chunked_detalles = array_chunk($todos_los_detalles,$detalles_por_pdf);
    $chunked_compacts = [];
    foreach($chunked_detalles as $chunk){
      $detalles = $chunk;
      $chunked_compacts[] = compact('pro','detalles','tipo','cantidad_totales','cols_x_pag','filas_por_col');
    }

    $paginas = ceil($cantidad_totales / $detalles_por_pagina);
    $salida = PdfParalelo::generarPdf('planillaProducidos',$chunked_compacts,"",$paginas_por_pdf,$paginas);

    if($salida['error'] == 0) return response()->file($salida['value'])->deleteFileAfterSend(true);
    return 'Error codigo: '.$salida['error'].'<br>'.implode('<br>',$salida['value']);
  }
}
