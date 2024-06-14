<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Plataforma;
use View;
use Dompdf\Dompdf;
use \Datetime;
use App\BeneficioMensual;
use App\Cotizacion;

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

  public function generarPlanilla($anio,$mes,$id_plataforma,$id_tipo_moneda,$simplificado){
    //@HACK: si el beneficio no esta importado, no muestra el poker del dia
    //Como creo que nunca pasaria lo dejo asi porque es mas simple el query
    //Octavio 11 Noviembre 2022
    $dias = DB::table('beneficio')->select(
      DB::raw('CONCAT(LPAD(DAY(beneficio.fecha)  ,2,"00"),"-",
                      LPAD(MONTH(beneficio.fecha),2,"00"),"-",
                      YEAR(beneficio.fecha)) as fecha'),
      'beneficio.jugadores','beneficio.apuesta','beneficio.premio',
      'beneficio.ajuste','beneficio.ajuste_auditoria','beneficio.beneficio','cotizacion.valor as cotizacion',
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
      'ajuste','ajuste_auditoria','beneficio',
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
      $total->ajuste_auditoria = 0;
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
      'beneficio'=>0.0,'ajuste'=>0.0,'ajuste_auditoria' => 0.0,'poker'=>0.0
    ];
    {
      $ultima_cotizacion = $cotizacionDefecto;
      foreach($dias as $d){
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion; 
        $d->cotizacion     = $ultima_cotizacion;
        $total_cotizado->beneficio += $d->cotizacion*$d->beneficio;
        $total_cotizado->ajuste    += $d->cotizacion*$d->ajuste;
        $total_cotizado->ajuste_auditoria += $d->cotizacion*$d->ajuste_auditoria;
        $total_cotizado->poker     += $d->cotizacion*$d->poker;
      }
    }

    $mesTexto = $this->obtenerMes($mes);
    $view = View::make('planillaInformesJuegos',compact(
      'mesTexto','dias','cotizacionDefecto','total_cotizado',
      'total','simplificado'
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
}

