<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContadorHorario;
use App\Producido;
use App\Beneficio;
use App\TipoMoneda;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RelevamientoController;
use App\Http\Controllers\LectorCSVController;
use App\Relevamiento;
use App\DetalleRelevamiento;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Plataforma;

use App\Services\LengthPager;

class ImportacionController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia() {
    if(!isset(self::$instance)) {
      self::$instance = new ImportacionController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Importaciones' , 'importaciones');
    return view('seccionImportaciones', ['tipoMoneda' => TipoMoneda::all(), 'plataformas' => UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas]);
  }

  public function eliminarBeneficios(Request $request){
    //el request contiene mes anio id_tipo_moneda id_plataforma
    $beneficios = Beneficio::where([['id_tipo_moneda','=',$request['id_tipo_moneda']],['id_plataforma','=',$request['id_plataforma']]])
                            ->whereYear('fecha','=',$request['anio'])
                            ->whereMonth('fecha','=',$request['mes'])
                            ->get();
    if(isset($beneficios)){
      foreach ($beneficios as $b){
        BeneficioController::getInstancia()->eliminarBeneficio($b->id_beneficio);
      }
    }
    return 1;
  }

  public function previewBeneficios(Request $request){
    //el request contiene mes anio id_tipo_moneda id_plataforma
    $plataforma = Plataforma::find($request->id_plataforma);
    $tipo_moneda = TipoMoneda::find($request->id_tipo_moneda);

    $beneficios = Beneficio::where([['id_tipo_moneda','=',$request['id_tipo_moneda']],['id_plataforma','=',$request['id_plataforma']]])
                            ->whereYear('fecha','=',$request['anio'])
                            ->whereMonth('fecha','=',$request['mes'])
                            ->get();

    return ['beneficios'=>$beneficios, 'plataforma' => $plataforma, 'tipo_moneda' => $tipo_moneda];
  }

  public function obtenerVistaPrevia($tipo_importacion,$id){
    $detalles_contador=null;
    $detalles_producido=null;
    $contador=null;
    $producido=null;

    switch ($tipo_importacion) {
      case 1: //contador
        $contador = ContadorHorario::find($id);

        //$contadores = (ContadorHorario::find($id))->detalles;
        $detalles_contador = DB::table('contador_horario')
                          ->select('detalle_contador_horario.*','maquina.nro_admin')
                          ->join('detalle_contador_horario','detalle_contador_horario.id_contador_horario','=','contador_horario.id_contador_horario')
                          ->join('maquina','maquina.id_maquina','=','detalle_contador_horario.id_maquina')
                          ->where('contador_horario.id_contador_horario','=',$id)
                          ->take(30)
                          ->get();

        $tipo_moneda = $contador->tipo_moneda;
        $plataforma = $contador->plataforma;
        break;
      case 2: //producidos
        $producido = Producido::find($id);
        //$producidos = (Producido::find($id))->detalles;
        $detalles_producido = DB::table('producido')
                          ->select('detalle_producido.*','maquina.nro_admin')
                          ->join('detalle_producido','detalle_producido.id_producido','=','producido.id_producido')
                          ->join('maquina','maquina.id_maquina','=','detalle_producido.id_maquina')
                          ->where('producido.id_producido','=',$id)
                          ->take(30)
                          ->get();

        $tipo_moneda = $producido->tipo_moneda;
        $plataforma = $producido->plataforma;
        break;
      default:
        //nothing :)
        break;
    }

    return ['contador' => $contador , 'producido' => $producido,
            'tipo_moneda'  => $tipo_moneda, 'plataforma' => $plataforma,
            'detalles_contador' => $detalles_contador,'detalles_producido'=> $detalles_producido];
  }

  public function buscar(Request $request){
    $reglas = array();
    $plataformas = array();
    if($request->plataformas !=0){
      $plataformas[] = $request->plataformas;
    }else {
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      foreach ($usuario->plataformas as $plataforma) {
        $plataformas[] = $plataforma->id_plataforma;
      }
    }

    if(isset($request->tipo_moneda) && $request->tipo_moneda !=0)
      $reglas[]=['tipo_moneda.id_tipo_moneda','=', $request->tipo_moneda];


    $contadores =array();
    $producidos =array();
    $beneficios =array();

    $sort_by = $request->sort_by;

    switch ($request->seleccion) {
      case 1://contadores
        if(!isset($request->fecha)){
          $contadores = DB::table('contador_horario')->select('contador_horario.id_contador_horario as id_contador_horario','contador_horario.fecha as fecha'
                                                              ,'plataforma.nombre as plataforma' , 'plataforma.id_plataforma as id_plataforma','tipo_moneda.descripcion as tipo_moneda','contador_horario.cerrado as cerrado')
                                                     ->join('plataforma','contador_horario.id_plataforma','=','plataforma.id_plataforma')
                                                     ->join('tipo_moneda','contador_horario.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                                     ->where($reglas)
                                                     ->whereIn('plataforma.id_plataforma' , $plataformas)
                                                     ->when($sort_by,function($query) use ($sort_by){
                                                                     return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                                 })
                                                     ->paginate($request->page_size);

          }else{
            $fecha=explode("-", $request->fecha);
            $contadores = DB::table('contador_horario')
                             ->select('contador_horario.id_contador_horario as id_contador_horario','contador_horario.fecha as fecha'
                                      ,'plataforma.nombre as plataforma' , 'plataforma.id_plataforma as id_plataforma','tipo_moneda.descripcion as tipo_moneda','contador_horario.cerrado as cerrado')
                             ->join('plataforma','contador_horario.id_plataforma','=','plataforma.id_plataforma')
                             ->join('tipo_moneda','contador_horario.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                             ->where($reglas)
                             ->whereIn('plataforma.id_plataforma' , $plataformas)
                             ->whereYear('contador_horario.fecha' , '=' ,$fecha[0])
                             ->whereMonth('contador_horario.fecha','=', $fecha[1])
                             ->when($sort_by,function($query) use ($sort_by){
                                             return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                         })

                             ->paginate($request->page_size);
          }
          break;
      case 2://producidos
        if(empty($request->fecha)){
          $producidos = DB::table('producido')->select('producido.id_producido as id_producido','producido.fecha as fecha'
                                                      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','producido.validado as validado')
                                              ->join('plataforma','producido.id_plataforma','=','plataforma.id_plataforma')
                                              ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                              ->where($reglas)
                                              ->whereIn('plataforma.id_plataforma' , $plataformas)
                                              ->when($sort_by,function($query) use ($sort_by){
                                                              return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                          })

                                              ->paginate($request->page_size);

        }else{
          $fecha=explode("-", $request->fecha);
          $producidos = DB::table('producido')->select('producido.id_producido as id_producido','producido.fecha as fecha'
                                                      ,'plataforma.nombre as plataforma','tipo_moneda.descripcion as tipo_moneda','producido.validado as validado')
                                              ->join('plataforma','producido.id_plataforma','=','plataforma.id_plataforma')
                                              ->join('tipo_moneda','producido.id_tipo_moneda','=','tipo_moneda.id_tipo_moneda')
                                              ->where($reglas)
                                              ->whereIn('plataforma.id_plataforma' , $plataformas)
                                              ->whereYear('producido.fecha' , '=' ,$fecha[0])
                                              ->whereMonth('producido.fecha','=', $fecha[1])
                                              ->when($sort_by,function($query) use ($sort_by){
                                                              return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                                          })

                                              ->paginate($request->page_size);
        }
        break;
      case 3://beneficios
      $reglas2 = array();

      if($request->sort_by['columna'] == "beneficio.fecha"){
        $sort_by['columna'] = 'anio,mes';

      }


      if(!empty($request->tipo_moneda) && $request->tipo_moneda !=0)
        $reglas2[]=['id_tipo_moneda','=', $request->tipo_moneda];

        $createTempTables = DB::unprepared(DB::raw("CREATE TEMPORARY TABLE beneficios_temporal
                                                            AS (
                                                                SELECT MONTH(beneficio.fecha) as mes,
                                                                       YEAR(beneficio.fecha) as anio,
                                                                       plataforma.*,
                                                                       tipo_moneda.*
                                                                FROM beneficio inner join plataforma on beneficio.id_plataforma = plataforma.id_plataforma
                                                                     inner join tipo_moneda on beneficio.id_tipo_moneda = tipo_moneda.id_tipo_moneda
                                                                );
                                             "
                                             )
                                       );

        if(empty($request->fecha)){// si fecha esta vacio
        if($createTempTables){
           $beneficios = DB::table('beneficios_temporal')->select('mes','anio','nombre as plataforma','id_plataforma','id_tipo_moneda','descripcion as tipo_moneda')
                             ->where($reglas2)
                             ->whereIn('id_plataforma' , $plataformas)
                             ->groupBy('mes','anio','nombre','descripcion','id_plataforma','id_tipo_moneda')->when($sort_by,function($query) use ($sort_by){
                                              return $query->orderBy(DB::raw($sort_by['columna']),$sort_by['orden']);
                                         })
                            ->paginate($request->page_size);
           $query1 = DB::statement(DB::raw("
                                              DROP TABLE beneficios_temporal
                                          "));
         }else {
                $error = "ERROR MESSAGE";
                dd($error);
        }

        }else{
          $fecha=explode("-", $request->fecha);

          if($createTempTables){
            $beneficios = DB::table('beneficios_temporal')->select('mes','anio','nombre as plataforma','descripcion as tipo_moneda','id_plataforma','id_tipo_moneda')
                              ->where($reglas2)
                              ->where('anio' , '=' ,$fecha[0])
                              ->where('mes','=', $fecha[1])
                              ->groupBy('mes','anio','nombre','descripcion','id_plataforma','id_tipo_moneda')->when($sort_by,function($query) use ($sort_by){
                                               return $query->orderBy(DB::raw($sort_by['columna']),$sort_by['orden']);
                                          })
                             ->paginate($request->page_size);
            $query1 = DB::statement(DB::raw("
                                               DROP TABLE beneficios_temporal
                                           "));
          }else {
                 $error = "ERROR MESSAGE";
                 dd($error);
         }


        }
        break;
    default:
      //nada
        break;
    }

    foreach ($contadores as $index => $contador){
      if($contador->id_plataforma == 1 || $contador->id_plataforma == 2){
        $contadores[$index]->fecha_archivo = date('Y-m-d' , strtotime($contador->fecha . ' - 1 days'));
      }else {//rosario
        $contadores[$index]->fecha_archivo = $contador->fecha;
      }
    }

      return  ['contadores' => $contadores, 'producidos' => $producidos, 'beneficios' => $beneficios];
  }



  public function estadoImportacionesDePlataforma($id_plataforma,$fecha_busqueda = null,$orden = 'desc'){
    //modficar para que tome ultimos dias con datos, no solo los ultimos dias
    Validator::make([
         'id_plataforma' => $id_plataforma,
       ],
       [
         'id_plataforma' => 'required|exists:plataforma,id_plataforma' ,
       ] , array(), self::$atributos)->after(function ($validator){

    })->validate();

    $fecha = is_null($fecha_busqueda)? date('Y-m-d') : $fecha_busqueda;
    $aux = new \DateTime($fecha);
    $aux->modify('last day of this month');
    $fecha = $aux->format('Y-m-d');
    $mes = date('m',strtotime($fecha));

    $arreglo = array();

    while(date('m',strtotime($fecha)) == $mes){
      if($id_plataforma == 3){//si es rosario tengo $ y DOL
        $producido['pesos'] = Producido::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , 1]])->count() >= 1 ? true : false;
        $beneficio['pesos'] = Beneficio::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , 1]])->count() >= 1 ? true : false;
        $producido['dolares'] = Producido::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , 2]])->count() >= 1 ? true : false;
        $beneficio['dolares'] = Beneficio::where([['fecha' , $fecha],['id_plataforma', $id_plataforma] ,['id_tipo_moneda' , 2]])->count() >= 1 ? true : false;
      }else{
        $producido['pesos'] = Producido::where([['fecha',$fecha],['id_plataforma',$id_plataforma]])->count() >= 1 ? true : false;
        $beneficio['pesos'] = Beneficio::where([['fecha' , $fecha],['id_plataforma',$id_plataforma]])->count() >= 1 ? true : false;
      }
      $dia['producido'] = $producido;
      $dia['beneficio'] = $beneficio;
      $dia['fecha'] = $fecha;
      $arreglo[] = $dia;
      $fecha = date('Y-m-d' , strtotime($fecha . ' - 1 days'));
    }
    if($orden == 'asc'){
      $arreglo = array_reverse($arreglo);
    }
    return ['arreglo' => $arreglo];
  }

  public function importarProducido(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'fecha' => 'nullable|date',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
        if($validator->getData()['fecha'] != null){
          $reglas = Array();
          $reglas[]=['fecha','=',$validator->getData()['fecha']];
          $reglas[]=['id_plataforma','=',$validator->getData()['id_plataforma']];
          if($validator->getData()['id_tipo_moneda'] != null){
            $reglas[]=['id_tipo_moneda','=',$validator->getData()['id_tipo_moneda']];
          }
          if(Producido::where($reglas)->count() > 0){
            //$validator->errors()->add('producido_validado', 'El Producido para esa fecha ya está validado y no se puede reimportar.');
          }
        }
    })->validate();

    DB::transaction(function() use ($request){
      LectorCSVController::getInstancia()->importarProducido($request->archivo,$request->fecha,$request->id_tipo_moneda);
    });
  }

  public function importarBeneficio(Request $request){
    Validator::make($request->all(),[
        'id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
        'archivo' => 'required|mimes:csv,txt',
        'id_tipo_moneda' => 'nullable|exists:tipo_moneda,id_tipo_moneda'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();
    switch($request->id_plataforma){
      case 1:
        return LectorCSVController::getInstancia()->importarBeneficioSantaFeMelincue($request->archivo,1);
        break;
      case 2:
        return LectorCSVController::getInstancia()->importarBeneficioSantaFeMelincue($request->archivo,2);
        break;
      case 3:
        return LectorCSVController::getInstancia()->importarBeneficioRosario($request->archivo,$request->id_tipo_moneda);
        break;
      default:
        break;
    }
  }


}
