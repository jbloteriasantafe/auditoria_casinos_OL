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
use App\AjusteBeneficio;
use App\Producido;
use App\BeneficioMensual;
use App\Porcentaje;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Validation\Rule;

class BeneficioController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new BeneficioController();
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

  public function buscarTodo(){
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

    $reglas = "p.id_plataforma in (" . implode(",",$plataformas) . ")";
    if(!empty($request->id_tipo_moneda) && $request->id_tipo_moneda != 0){
      $reglas = $reglas . ' AND ' . 'bm.id_tipo_moneda = ' . $request->id_tipo_moneda;
    }
    if(!empty($request->fecha_desde)){
      $reglas = $reglas . ' AND ' . 'bm.fecha >= ' . $request->fecha_desde;
    }
    if(!empty($request->fecha_hasta)){
      $reglas = $reglas . ' AND ' . 'bm.fecha <= ' . $request->fecha_hasta;
    }
    if($request->id_plataforma != 0){
      $reglas = $reglas . ' AND ' . 'bm.id_plataforma = ' . $request->id_tipo_moneda;
    }

    $sort_by = $request->sort_by;

    //WHERE o HAVING de diff_bm.id_beneficio_mensual = bm.id_beneficio_mensual en la SUBQUERY da error???
    //En teoria lo podria hacer mas rapido...
    $diferencias_subquery = "SELECT diff_bm.id_beneficio_mensual, 
    SUM(CASE 
          WHEN (diff_p.valor IS NULL     AND diff_b.GrossRevenue IS NOT NULL) THEN 1
          WHEN (diff_p.valor IS NOT NULL AND diff_b.GrossRevenue IS NULL)     THEN 1
          WHEN (diff_p.valor IS NULL     AND diff_b.GrossRevenue IS NULL)     THEN 0
          WHEN (diff_p.valor - diff_b.GrossRevenue) <> 0                      THEN 1
          ELSE 0
        END) as diferencias,
    COUNT(diff_p.id_producido) as dias_p,
    COUNT(diff_b.id_beneficio) as dias_b
    FROM beneficio_mensual diff_bm
    LEFT JOIN beneficio diff_b ON diff_b.id_beneficio_mensual = diff_bm.id_beneficio_mensual
    LEFT JOIN producido diff_p ON 
      (diff_p.id_plataforma = diff_bm.id_plataforma AND diff_p.id_tipo_moneda = diff_bm.id_tipo_moneda AND diff_p.fecha = diff_b.fecha)
    GROUP BY diff_bm.id_beneficio_mensual";

    $pdo = DB::connection('mysql')->getPdo();
    //Busqueda preliminar para obtener la cantidad total que matchea con la query
    $query = sprintf("SELECT COUNT(bm.id_beneficio_mensual) as cantidad 
    FROM beneficio_mensual bm 
    JOIN tipo_moneda tm on tm.id_tipo_moneda = bm.id_tipo_moneda 
    JOIN plataforma p on p.id_plataforma = bm.id_plataforma
    WHERE (%s)",$reglas);
    $count = $pdo->query($query)->fetchAll()[0]["cantidad"];
    $pages = ceil($count/floatval($request->page_size));
    $page = min(max($request->page,1),$pages);

    $sort_by = ["columna" => "bm.fecha", "orden" => "desc"];
    if(!empty($request->sort_by)){
      $sort_by = $request->sort_by;
    }
    //TODO: order by
    $query = sprintf("SELECT MONTH(bm.fecha) as mes, YEAR(bm.fecha) as anio, p.id_plataforma, p.nombre as plataforma
    , tm.id_tipo_moneda, tm.descripcion as tipo_moneda, diff.diferencias as diferencias_mes, bm.id_beneficio_mensual,diff.dias_p,diff.dias_b
    FROM beneficio_mensual bm
    JOIN tipo_moneda tm on tm.id_tipo_moneda = bm.id_tipo_moneda
    JOIN plataforma p on p.id_plataforma = bm.id_plataforma
    JOIN (%s) diff on diff.id_beneficio_mensual = bm.id_beneficio_mensual
    WHERE (%s)
    ORDER BY %s %s
    LIMIT %d OFFSET %d",$diferencias_subquery,$reglas,$sort_by["columna"],$sort_by["orden"],$request->page_size,($page-1)*$request->page_size);
    $resultados = $pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
  
    return ['current_page' => $page,'total' => $pages,'per_page' => $request->page_size,'data' => $resultados];
  }

  public function obtenerBeneficiosParaValidar(Request $request){
    $resultados = $this->obtenerBeneficiosPorMes($request->id_plataforma,$request->id_tipo_moneda,$request->anio,$request->mes);
    return ['resultados' => $resultados];
  }
  // cami coments
  //desde el modal de ajustar beneficios,
  //directamente se ajusta desde ahi cada beneficio.
  //y en la pantalla le indica como queda la diferencia, segun los
  //valores que habia recibido cuando abrio el modal.
  //o sea, que si yo ajusto 20 mil veces la misma fecha,
  // a las modificaciones anteriores no las elimina!!! WTF!?
  public function ajustarBeneficio(Request $request){
    Validator::make($request->all(), [
            'valor' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
            'id_beneficio' => 'required|exists:beneficio,id_beneficio'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ajuste = new AjusteBeneficio();
    $ajuste->valor = $request->valor;
    $ajuste->id_beneficio = $request->id_beneficio;
    $ajuste->save();

    return ['ajuste' => $ajuste];
  }

  public function validarBeneficios(Request $request){
    $validator = Validator::make($request->all(), [
            'benficios_ajustados' => 'nullable',
            'benficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
            'benficios_ajustados.*.observacion' => 'nullable|max:500'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    if(isset($validator))
    {
      if ($validator->fails())
      {
        return [
              'errors' => $v->getMessageBag()->toArray()
          ];
      }
    }
    $errors = null;
    //dd($validator);
    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_plataforma',$ben->id_plataforma],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido sumandole el ajuste reciente
            $diff = $producido_calculado - $ben->valor;
            $diff_round = round($diff,2);
            if(!is_null($producido_calculado) && $diff_round == 0.00){
              $ben->validado = 1;
            }else{//si no lo valida, largo error
              $errors = new MessageBag;
              $errors->add('id_beneficio', 'No se ajustó el beneficio del día '.$fecha.'. Diferencia de '.round($producido_calculado - $ben->valor,2).'.');
            }
          }else{
            $errors = new MessageBag;
            $errors->add('id_producido', 'No hay producidos cargados para el beneficio del día '.$fecha.'.');
          }

          $ben->save();
        }else{
          $errors = new MessageBag;
          $errors->add('not_found', 'Beneficio del día '.$fecha.' no encontrado.');
        }
      }//fin for each
      if(isset($errors))
      {
        return response()->json($errors->toArray(), 404);

      }

      $ben = Beneficio::find($request->beneficios_ajustados[0]['id_beneficio']);
      $fecha = $ben->fecha;
      $mes = date("n",strtotime($fecha));
      $anio = date("Y",strtotime($fecha));
      // si estan los beneficios para todo el mes cargados y validados, guardo el beneficio mensual correspondiente
      $cant_dias = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
      $bandera = true;
      $acumulado = 0;

      for($i = 1; $i <= $cant_dias; $i++){ // plataforma, fecha, tipo_moneda
        $benef = Beneficio::where([['id_plataforma',$ben->plataforma->id_plataforma],['id_tipo_moneda',$ben->tipo_moneda->id_tipo_moneda]])
                          ->whereYear('fecha',$anio)
                          ->whereMonth('fecha',$mes)
                          ->whereDay('fecha',$i)
                          ->first();

        if($benef != null && $benef->validado == 1){
          $acumulado = $acumulado + $benef->valor;
        }
        else{
          $bandera = false;
          $i = $cant_dias;
        }
      }
      if($bandera){
        $beneficio_mensual = new BeneficioMensual;
        $beneficio_mensual->id_plataforma = $ben->id_plataforma;
        $beneficio_mensual->id_tipo_moneda = $ben->id_tipo_moneda;
        $beneficio_mensual->id_actividad = 1;
        $beneficio_mensual->anio_mes = ''.$anio.'-'.$mes.'-01'; // Ej: 2017-08-01
        $beneficio_mensual->bruto = $acumulado;
        $beneficio_mensual->save();
      }else{
        return response()->json("Faltan importar beneficios", 404);
      }

    }
    // TODO gestionar el error en el caso de que no se importaron los producidos
    // ene se caso no va dar error pero tampoco va generar el producido mensual
    return "true";
  }

  public function validarBeneficiosSinProducidos(Request $request){
    $validator = Validator::make($request->all(), [
            'benficios_ajustados' => 'nullable',
            'benficios_ajustados.*.id_beneficio' => 'required|exists:beneficio,id_beneficio',
            'benficios_ajustados.*.observacion' => 'nullable|max:500'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    if(isset($validator))
    {
      if ($validator->fails())
      {
        return [
              'errors' => $v->getMessageBag()->toArray()
          ];
      }
    }
    $errors = null;
    //dd($validator);
    if($request->beneficios_ajustados != null){
      foreach($request->beneficios_ajustados as $beneficio_ajustado){
        $ben = Beneficio::find($beneficio_ajustado['id_beneficio']);
        if($ben != null){
          $fecha = $ben->fecha;
          $ben->observacion = $beneficio_ajustado['observacion'];

          $prod = Producido::where([['fecha',$ben->fecha],['id_platafomra',$ben->id_plataforma],['id_tipo_moneda',$ben->id_tipo_moneda]])->first();
          if($prod != null){
            $producido_calculado = $prod->beneficio_calculado; //calcula atributo en el producido

            if(!is_null($producido_calculado) && round($producido_calculado - $ben->valor,2) == 0){
              $ben->validado = 1;
            }else{//si no lo valida, largo error
              $errors = new MessageBag;
              $errors->add('id_beneficio', 'No se ajustó el beneficio del día '.$fecha.'. Diferencia de '.round($producido_calculado - $ben->valor,2).'.');
            }
          }else{
            $ben->validado = 1;
          }

          $ben->save();
        }else{
          $errors = new MessageBag;
          $errors->add('not_found', 'Beneficio del día '.$fecha.' no encontrado.');
        }
      }//fin for each

      if(isset($errors))
      {
        return response()->json($errors->toArray(), 404);

      }

      $ben = Beneficio::find($request->beneficios_ajustados[0]['id_beneficio']);
      $fecha = $ben->fecha;
      $mes = date("n",strtotime($fecha));
      $anio = date("Y",strtotime($fecha));
      // si estan los beneficios para todo el mes cargados y validados, guardo el beneficio mensual correspondiente
      $cant_dias = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
      $bandera = true;
      $acumulado = 0;
      for($i = 1; $i <= $cant_dias; $i++){ // plataforma, fecha, tipo_moneda
        $benef = Beneficio::where([['id_plataforma',$ben->plataforma->id_plataforma],['id_tipo_moneda',$ben->tipo_moneda->id_tipo_moneda]])
                          ->whereYear('fecha',$anio)
                          ->whereMonth('fecha',$mes)
                          ->whereDay('fecha',$i)
                          ->first();
        if($benef != null && $benef->validado == 1){
          $acumulado = $acumulado + $benef->valor;
        }
        else{
          $i = $cant_dias;
        }
      }
     // como se esta intentando validar dias sin producidos, se genera el mensual de todas formas
        $beneficio_mensual = new BeneficioMensual;
        $beneficio_mensual->id_plataforma = $ben->id_plataforma;
        $beneficio_mensual->id_tipo_moneda = $ben->id_tipo_moneda;
        $beneficio_mensual->id_actividad = 1;
        $beneficio_mensual->anio_mes = ''.$anio.'-'.$mes.'-01'; // Ej: 2017-08-01
        $beneficio_mensual->bruto = $acumulado;
        $beneficio_mensual->save();
      
    }
    return "true";
  }

  public function cargarImpuesto(Request $request){
    Validator::make($request->all(), [
            'id_beneficio_mensual' => 'required|exists:beneficio_mensual,id_beneficio_mensual',
            'impuesto' => ['required','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/']
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $ben = BeneficioMensual::find($request->id_beneficio_mensual);
    $ben->iea = $request->impuesto;
    $porcentaje = Porcentaje::where([['id_plataforma',$ben->id_plataforma],['id_actividad',1]])->first();
    $ben->canon = ($ben->bruto - $ben->iea)*$porcentaje->valor;
    $ben->save();

    return $ben;
  }

  private function obtenerBeneficiosPorMes($id_plataforma,$id_tipo_moneda,$anio,$mes){
    $resultados = DB::table('beneficio')->select('beneficio.id_beneficio as id_beneficio','beneficio.fecha as fecha',
                                                  DB::raw('(CAST(beneficio.valor AS DECIMAL(15,2))) as beneficio'),
                                                  DB::raw('(CAST((SUM(IFNULL(detalle_producido.valor,0)) + IFNULL(ajuste_beneficio.valor,0)) AS DECIMAL(15,2))) AS beneficio_calculado'),
                                                  DB::raw('(CAST(((SUM(IFNULL(detalle_producido.valor,0)) + IFNULL(ajuste_beneficio.valor,0)) - beneficio.valor) AS DECIMAL(15,2))) AS diferencia'),
                                                  DB::raw('CASE WHEN ((IFNULL(producido.id_producido,0)) != 0) THEN 1 ELSE 0 END AS existe_producido'),
                                                  'producido.id_producido as id_producido')
                                        ->leftJoin('producido',function ($leftJoin) use ($id_plataforma,$id_tipo_moneda,$anio,$mes){
                                          $leftJoin->on('producido.fecha','=','beneficio.fecha')
                                               ->where([['producido.id_plataforma','=',$id_plataforma],['producido.id_tipo_moneda','=',$id_tipo_moneda]])
                                               ->whereMonth('producido.fecha',$mes)
                                               ->whereYear('producido.fecha',$anio);
                                          })
                                        ->leftJoin('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
                                        ->leftJoin('ajuste_beneficio','ajuste_beneficio.id_beneficio','=','beneficio.id_beneficio')
                                        ->where([['beneficio.id_plataforma',$id_plataforma],['beneficio.id_tipo_moneda',$id_tipo_moneda]])
                                        ->whereMonth('beneficio.fecha',$mes)
                                        ->whereYear('beneficio.fecha',$anio)
                                        ->groupBy('beneficio.valor','beneficio.fecha','beneficio.id_beneficio','producido.id_producido','ajuste_beneficio.valor')
                                        ->orderBy('beneficio.fecha','asc')
                                        ->get();
    return $resultados;
  }

  /*^^^^
  *|||||
  //con imaginacion son flechas
  *
  * retorna : un array con :
    id_beneficio	822
    fecha	2018-06-01
    beneficio	3465750.37
    beneficio_calculado	3465750.37
    diferencia	0.00
    existe_producido	1


    para cada fecha del mes
  */


  public function generarPlanilla($id_plataforma,$id_tipo_moneda,$anio,$mes){

    $nombrePlataforma = DB::table('plataforma')->select('plataforma.nombre as nombre')
                                         ->where('plataforma.id_plataforma',$id_plataforma)
                                         ->first();

    $tipoMoneda = DB::table('tipo_moneda')->select('tipo_moneda.descripcion as tipo_moneda')
                                           ->where('tipo_moneda.id_tipo_moneda',$id_tipo_moneda)
                                           ->first();

    $ben = new \stdClass();
    $ben->plataforma = $nombrePlataforma->nombre;
    $ben->moneda = $tipoMoneda->tipo_moneda;
    if($ben->moneda == 'ARS'){
         $ben->moneda = 'Pesos';
         }
       else{
         $ben->moneda = 'Dólares';
         }

    switch ($mes) {
         case 1:
             $mesEdit = "Enero";
             break;
         case 2:
             $mesEdit = "Febrero";
             break;
         case 3:
             $mesEdit = "Marzo";
             break;
         case 4:
             $mesEdit = "Abril";
             break;
         case 5:
             $mesEdit = "Mayo";
             break;
         case 6:
             $mesEdit = "Junio";
             break;
         case 7:
             $mesEdit = "Julio";
             break;
         case 8:
             $mesEdit = "Agosto";
             break;
         case 9:
             $mesEdit = "Septiembre";
             break;
         case 10:
             $mesEdit = "Octubre";
             break;
         case 11:
             $mesEdit = "Noviembre";
             break;
         case 12:
             $mesEdit = "Diciembre";
             break;
     }
    $ben->mes = $mesEdit;
    $ben->anio = $anio;

    $resultados = $this->obtenerBeneficiosPorMes($id_plataforma,$id_tipo_moneda,$anio,$mes);

    $ajustes = array();
    foreach ($resultados as $resultado){//resultado:
      $res = new \stdClass();

      $año = $resultado->fecha[0].$resultado->fecha[1].$resultado->fecha[2].$resultado->fecha[3];
      $mes = $resultado->fecha[5].$resultado->fecha[6];
      $dia = $resultado->fecha[8].$resultado->fecha[9];
      $res->fecha = $dia."-".$mes."-".$año;

      $res->bcalculado = number_format($resultado->beneficio_calculado, 2, ",", ".");
      $res->bimportado = number_format($resultado->beneficio, 2, ",", ".");
      $res->dif = number_format($resultado->diferencia, 2, ",", ".");
      $ajustes[] = $res;
    }

    $view = View::make('planillaBeneficios',compact('ajustes','ben'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

}
