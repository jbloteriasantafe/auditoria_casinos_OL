<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;
use App\TipoMoneda;
use App\Beneficio;
use View;
use Dompdf\Dompdf;
use App\BeneficioMensual;
use Illuminate\Validation\Rule;

class BeneficioController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private static $atributos=[];

  public function eliminarBeneficio($id_beneficio){
    Validator::make(['id_beneficio' => $id_beneficio]
                   ,['id_beneficio' => 'required|exists:beneficio,id_beneficio']
                   , array(), self::$atributos)->after(function($validator){
                   })->validate();

    Beneficio::destroy($id_beneficio);
  }

  private $diffs_view = "SELECT diff_bm.id_beneficio_mensual,
  SUM((diff_p.beneficio IS NULL AND diff_b.beneficio IS NOT NULL) 
      OR (diff_p.beneficio <> (diff_b.beneficio+diff_b.ajuste_auditoria+diff_b.ajuste))) as diferencias
  FROM beneficio_mensual diff_bm
  LEFT JOIN beneficio diff_b ON diff_b.id_beneficio_mensual = diff_bm.id_beneficio_mensual
  LEFT JOIN producido diff_p ON (diff_p.id_plataforma = diff_bm.id_plataforma AND diff_p.id_tipo_moneda = diff_bm.id_tipo_moneda AND diff_p.fecha = diff_b.fecha)
  GROUP BY diff_bm.id_beneficio_mensual";

  public function buscarTodo(){
    DB::statement(sprintf("CREATE OR REPLACE VIEW beneficios_diferencias_dias AS %s",$this->diffs_view));

    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Beneficios' ,'beneficios');
    return view('seccionBeneficios',['plataformas' => $usuario->plataformas,'tipos_moneda' => TipoMoneda::all()]);
  }

  public function buscarBeneficios(Request $request){
    Validator::make($request->all(), [//Validar filtros para evitar SQL injection mas abajo
            'id_tipo_moneda' => 'nullable|integer|exists:tipo_moneda,id_tipo_moneda',
            'id_plataforma' => 'nullable|integer|exists:plataforma,id_plataforma',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after:fecha_desde',
            'page' => 'integer',
            'page_size' => 'integer',
            'sort_by' => 'nullable|array',
            'sort_by.columna' => [Rule::in(['p.nombre','bm.fecha','tm.descripcion','diferencias_mes'])],
            'sort_by.orden' => [Rule::in(['desc','asc'])]
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    //La version de Laravel no tiene joinSub... tengo que hacer la query asi... u_u
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $plataformas = [];
    foreach($usuario['usuario']->plataformas as $p){
      $plataformas[] = $p->id_plataforma;
    }
    $reglas = [];
    if(!empty($request->id_tipo_moneda) && $request->id_tipo_moneda != 0){
      $reglas[] = ['bm.id_tipo_moneda','=',$request->id_tipo_moneda];
    }
    if(!empty($request->fecha_desde)){
      $reglas[] = ['bm.fecha','>=',$request->fecha_desde];
    }
    if(!empty($request->fecha_hasta)){
      $reglas[] = ['bm.fecha','<=',$request->fecha_hasta];
    }
    if($request->id_plataforma != 0){
      $reglas[] = ['bm.id_plataforma','=',$request->id_plataforma];
    }

    $resultados = DB::table('beneficio_mensual as bm')
    ->selectRaw('MONTH(bm.fecha) as mes, YEAR(bm.fecha) as anio, p.id_plataforma, p.nombre as plataforma,
     tm.id_tipo_moneda, tm.descripcion as tipo_moneda, diff.diferencias as diferencias_mes,
     bm.id_beneficio_mensual, bm.validado')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','bm.id_tipo_moneda')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->join('beneficios_diferencias_dias as diff','diff.id_beneficio_mensual','=','bm.id_beneficio_mensual')
    ->where($reglas)
    ->whereIn('bm.id_plataforma',$plataformas);

    $sort_by = ["columna" => "bm.fecha", "orden" => "desc"];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }
    $resultados = $resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    });

    //Elimino duplicados y pagino.
    $resultados = $resultados->groupBy('bm.id_beneficio_mensual')->paginate($request->page_size);
    return $resultados;
  }

  public function obtenerBeneficios($id_beneficio_mensual){
    $resultados = DB::table('beneficio_mensual')->selectRaw(
      'beneficio.id_beneficio, beneficio.fecha,
      (IFNULL(producido.beneficio,0)) AS beneficio_calculado,
      (IFNULL(beneficio.beneficio,0)) as beneficio,
      (IFNULL(beneficio.ajuste,0)) as ajuste,
      (IFNULL(beneficio.ajuste_auditoria,0)) as ajuste_auditoria,
      (IFNULL(producido.beneficio,0) - IFNULL(beneficio.beneficio,0) - IFNULL(beneficio.ajuste,0) - IFNULL(beneficio.ajuste_auditoria,0)) AS diferencia,
      producido.id_producido as id_producido,
      IFNULL(beneficio.observacion,"") as observacion'
    )
    ->leftJoin('beneficio','beneficio_mensual.id_beneficio_mensual','=','beneficio.id_beneficio_mensual')
    ->leftJoin('producido',function($j){
      return $j->on('producido.id_plataforma','=','beneficio_mensual.id_plataforma')->on('producido.id_tipo_moneda','=','beneficio_mensual.id_tipo_moneda')
      ->on('producido.fecha','=','beneficio.fecha');
    })
    ->where('beneficio_mensual.id_beneficio_mensual',$id_beneficio_mensual)
    ->orderBy('beneficio.fecha','asc')
    ->get();
    return $resultados;
  }

  public function ajustarBeneficio(Request $request){
    Validator::make($request->all(), [
            'valor' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
            'id_beneficio' => 'required|exists:beneficio,id_beneficio'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $b = Beneficio::find($request->id_beneficio);
    $b->ajuste_auditoria += $request->valor;
    $b->save();
    $benMensual = $b->beneficio_mensual;
    $benMensual->ajuste_auditoria += $request->valor;
    $benMensual->save();
    return ['ajuste_auditoria' => $b->ajuste_auditoria,'diferencia' => $b->diferencia];
  }

  public function validarBeneficios(Request $request){
    $validator = Validator::make($request->all(), [
            'id_beneficio_mensual' => 'required|exists:beneficio_mensual,id_beneficio_mensual',
            'validar_beneficios_sin_producidos' => 'required|integer',
            'beneficios' => 'nullable|array',
            'beneficios.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
            'beneficios.*.observacion' => 'nullable|max:256'
    ], array(), self::$atributos)->after(function($validator){
      $data = $validator->getData();
      $bMensual = BeneficioMensual::find($data['id_beneficio_mensual']);
      $beneficios = [];
      $validar_prods = intval($data['validar_beneficios_sin_producidos']) > 0;
      foreach($bMensual->beneficios as $b){
        $beneficios[$b->id_beneficio] = $b;
        if($validar_prods && is_null($b->producido)){
          $validator->errors()->add($b->id_beneficio,'No hay producidos cargados.'); 
          continue;
        }
        $diff_round = round($b->diferencia,2);
        if($diff_round != 0.00){
          $validator->errors()->add($b->id_beneficio, 'Falta ajustar.');
        }
      }
      $mes = date("n",strtotime($bMensual->fecha));
      $anio = date("Y",strtotime($bMensual->fecha));
      $dias_en_mes = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
      if($dias_en_mes != count($beneficios)){
        $validator->errors()->add('id_beneficio_mensual','Faltan importar beneficios.');
        return;
      }
      foreach($data['beneficios'] as $b){
        if(!array_key_exists($b['id_beneficio'],$beneficios)){
          $validator->errors()->add('id_beneficio_mensual', 'El beneficio '.$b['id_beneficio'].' no corresponde al beneficio mensual, informar al area de informatica.');
        }
      }
    })->validate();

    $benMensual = BeneficioMensual::find($request->id_beneficio_mensual);
    foreach($request->beneficios as $b){
      $ben = $benMensual->beneficios()->where('id_beneficio',$b['id_beneficio'])->first();
      $ben->observacion = $b['observacion']?? '';
      $ben->save();
    }
    $benMensual->validado = 1;
    $benMensual->save();
    return 1;
  }

  public function generarPlanilla($id_beneficio_mensual){
    $benMensual = BeneficioMensual::find($id_beneficio_mensual);
    $ben = new \stdClass();
    $ben->plataforma = $benMensual->plataforma->nombre;
    $ben->moneda = $benMensual->tipo_moneda->descripcion;
    if($ben->moneda == 'ARS'){
      $ben->moneda = 'Pesos';
    }
    else if($ben->moneda == 'USD'){
      $ben->moneda = 'Dólares';
    }
    $mes_arr = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    $fecha = explode("-",$benMensual->fecha);
    $ben->mes = $mes_arr[intval($fecha[1])-1];
    $ben->anio = $fecha[0];

    $resultados = 
    DB::table('beneficio_mensual')
    ->selectRaw('beneficio.id_beneficio as id_beneficio, beneficio.fecha as fecha,
                IFNULL(producido.beneficio,0) AS beneficio_calculado,
                IFNULL(beneficio.beneficio,0) as beneficio,
                IFNULL(beneficio.ajuste,0) as ajuste,
                IFNULL(beneficio.ajuste_auditoria,0) as ajuste_auditoria,
                (IFNULL(producido.beneficio,0) - IFNULL(beneficio.beneficio,0) - IFNULL(beneficio.ajuste,0) - IFNULL(beneficio.ajuste_auditoria,0)) AS diferencia')
    ->leftJoin('beneficio','beneficio.id_beneficio_mensual','=','beneficio_mensual.id_beneficio_mensual')
    ->leftJoin('producido',function ($leftJoin) use ($benMensual){
      $leftJoin->on('producido.fecha','=','beneficio.fecha')
      ->where('producido.id_plataforma',$benMensual->id_plataforma)
      ->where('producido.id_tipo_moneda',$benMensual->id_tipo_moneda);
    })
    ->where('beneficio_mensual.id_beneficio_mensual',$id_beneficio_mensual)
    ->orderBy('beneficio.fecha','asc')
    ->get();

    $dias = array();
    foreach ($resultados as $resultado){
      $res = new \stdClass();
      $fecha = explode("-",$resultado->fecha);
      $res->fecha      = $fecha[2].'-'.$fecha[1].'-'.$fecha[0];
      $res->bcalculado = number_format($resultado->beneficio_calculado, 2, ",", ".");
      $res->bimportado = number_format($resultado->beneficio, 2, ",", ".");
      $res->ajuste     = number_format($resultado->ajuste, 2, ",", ".");
      $res->ajuste_auditoria = number_format($resultado->ajuste_auditoria, 2, ",", ".");
      $res->dif        = number_format($resultado->diferencia, 2, ",", ".");
      $dias[] = $res;
    }

    $view = View::make('planillaBeneficios',compact('dias','ben'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  private function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
  {//https://stackoverflow.com/questions/13108157/php-array-to-csv
      $f = fopen('php://memory', 'r+');
      foreach ($data as $item) {
        fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
      }
      rewind($f);
      return stream_get_contents($f);
  }

  public function arrayInformeCompleto($id_beneficio_mensual){
    $pdev = function($num,$div,$nombre){
      $division = "IFNULL(".$num.",0)/".$div;
      return sprintf("IF((%s) = 0,'',(%s)) as %s",$div,$division,$nombre);
    };
    $dias = DB::table('beneficio_mensual as bm')
    ->selectRaw('b.fecha              as Fecha,
                 b.jugadores          as Usuarios,
                 b.depositos          as Depositos,
                 b.retiros            as Retiros, 
                 p.apuesta_efectivo   as MontosJugadosEfectivo,
                 p.apuesta_bono       as MontosJugadosBonos,
                 p.apuesta            as MontosJugados, 
                 p.premio_efectivo    as PremioEfectivo,
                 p.premio_bono        as PremioBonos,
                 p.premio             as Premio,'
                 .$pdev('p.premio_efectivo','p.apuesta_efectivo','PdevEfectivo').','
                 .$pdev('p.premio_bono'    ,'p.apuesta_bono'    ,'PdevBonos').','
                 .$pdev('p.premio'         ,'p.apuesta'         ,'Pdev').',
                 p.beneficio_efectivo as ResultadoEfectivo,
                 p.beneficio_bono     as ResultadoBonos,
                 p.beneficio          as Resultado, 
                 b.beneficio          as ResultadoFinal,
                 b.ajuste             as AjustesInformados,
                 b.ajuste_auditoria   as AjustesAuditoria')
    ->leftJoin('beneficio as b','b.id_beneficio_mensual','=','bm.id_beneficio_mensual')
    ->leftJoin('producido as p',function ($leftJoin){
      $leftJoin->on('p.fecha','=','b.fecha')->on('p.id_plataforma','=','bm.id_plataforma')->on('p.id_tipo_moneda','=','bm.id_tipo_moneda');
    })
    ->where('bm.id_beneficio_mensual',$id_beneficio_mensual)
    ->orderBy('b.fecha','asc')->get()->toArray();


    $total = DB::table('beneficio_mensual as bm')
    ->selectRaw('"Totales"                as Fecha,
                SUM(b.jugadores)          as Usuarios,
                bm.depositos              as Depositos,
                bm.retiros                as Retiros,
                SUM(p.apuesta_efectivo)   as MontosJugadosEfectivo,
                SUM(p.apuesta_bono)       as MontosJugadosBonos,
                SUM(p.apuesta)            as MontosJugados,
                SUM(p.premio_efectivo)    as PremioEfectivo,
                SUM(p.premio_bono)        as PremioBonos,
                SUM(p.premio)             as Premio,
                '.$pdev('SUM(p.premio_efectivo)','SUM(p.apuesta_efectivo)','PdevEfectivo').',
                '.$pdev('SUM(p.premio_bono)'    ,'SUM(p.apuesta_bono)'    ,'PdevBonos').',
                '.$pdev('SUM(p.premio)'         ,'SUM(p.apuesta)'         ,'Pdev').',
                SUM(p.beneficio_efectivo) as ResultadoEfectivo,
                SUM(p.beneficio_bono)     as ResultadoBonos,
                SUM(p.beneficio)          as Resultado,
                bm.beneficio              as ResultadoFinal,
                bm.ajuste                 as AjustesInformados,
                bm.ajuste_auditoria       as AjustesAuditoria')
    ->leftJoin('beneficio as b','b.id_beneficio_mensual','=','bm.id_beneficio_mensual')
    ->leftJoin('producido as p',function ($leftJoin){
      $leftJoin->on('p.fecha','=','b.fecha')->on('p.id_plataforma','=','bm.id_plataforma')->on('p.id_tipo_moneda','=','bm.id_tipo_moneda');
    })
    ->where('bm.id_beneficio_mensual',$id_beneficio_mensual)
    ->groupBy('bm.id_beneficio_mensual')->get()->toArray();

    if(count($dias) == 0) return "SIN DATOS";
    $columnas = array_keys(get_object_vars($dias[0]));

    //Agrego espacios
    foreach($columnas as &$campo){
      $new_campo = "";
      foreach(str_split($campo) as $idx => $c){
        if($idx > 0 && $c === strtoupper($c)){
          $new_campo .= ' ';
        }
        $new_campo .= $c;
      }
      $campo = $new_campo;
    }

    array_unshift($dias,$columnas);
    array_push($dias,$total[0]);

    //Cambio punto por coma
    foreach($dias as $d) foreach($d as $k=>&$campo){
      if(is_numeric($campo)){
        $digitos = strpos($k,'Pdev') !== false? 3 : 2;
        $campo = number_format($campo,$digitos,",",".");
      }
    }

    return json_decode(json_encode($dias),true);
  }

  public function informeCompleto($id_beneficio_mensual){
    $csv = $this->array2csv($this->arrayInformeCompleto($id_beneficio_mensual));

    $bm = BeneficioMensual::find($id_beneficio_mensual);
    $plat = $bm->plataforma->codigo;
    $bm_fecha = explode("-",$bm->fecha);
    $filename = "beneficio_mensual_".$plat."_".$bm_fecha[0].$bm_fecha[1].".csv";

    header("Content-Type: text/csv");
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header("Content-Length: ".strlen($csv));
    return $csv;
  }
}
