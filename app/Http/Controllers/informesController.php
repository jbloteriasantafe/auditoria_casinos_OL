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

