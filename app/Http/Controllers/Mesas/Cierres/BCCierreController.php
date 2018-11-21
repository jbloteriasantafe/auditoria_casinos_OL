<?php

namespace App\Http\Controllers\Mesas\Cierres;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\Ficha;
use App\Mesas\DetalleApertura;
use App\Mesas\CierreApertura;

//busqueda y consulta de cierres
class BCCierreController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_buscar_cierres']);
  }

  public function eliminarCierre($id){
    //VER CONDICIONES PARA QUE SE PUEDA BORRAR UN CIERRE
    $cierre = Cierre::find($id);
    $cierre->delete();
    //return ['cierre' => $cierre];
    //return 1;
    return response()->json(['cierre' => $cierre], 200);
  }

  public function buscarTodo(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    $cas = array();
    foreach($user->casinos as $casino){
      $casinos[]=$casino->id_casino;
      $cas[] = $casino;
    }
    $date = \Carbon\Carbon::today();

    $cierres = DB::table('cierre_mesa')
                  ->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','cierre_mesa.id_mesa_de_panio')
                  ->join('estado_cierre','estado_cierre.id_estado_cierre','=','cierre_mesa.id_estado_cierre')
                  ->join('casino','mesa_de_panio.id_casino','=','casino.id_casino')
                  ->whereMonth('cierre_mesa.fecha', $date->month)
                  ->whereYear('cierre_mesa.fecha',$date->year)
                  ->whereIn('mesa_de_panio.id_casino',$casinos)
                  ->orderBy('fecha' , 'desc')->first()
                  ->get();

    $estados = EstadoCierre::all();
    $juegos = JuegoMesa::whereIn('id_casino',$casinos)->get();
    $fichas = Ficha::all();

    return  view('CierresAperturas.CierresAperturas', ['cierres' => $cierres,
                             'estado_cierre' => $estados,
                             'juegos' => $juegos,
                             'casinos' => $cas,
                             'fichas' => $fichas,
                             'es_superusuario' => $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->es_superusuario,
                            ]);
  }

  public function getCierre($id){
    $cierre = Cierre::find($id);
    $mesa = $cierre->mesa;
    if(!empty($cierre)){
      $detalles = DB::table('detalle_cierre')
                      ->rightJoin('ficha','ficha.id_ficha','=','detalle_cierre.id_ficha')
                      ->where('detalle_cierre.id_detalle_cierre','=',$id)
                      ->where('ficha.id_moneda','=',$mesa->moneda->id_moneda)
                      ->get();
      $detalleC = array();
      foreach ($detalles as $init) {
        $detalleC[] = [ 'monto_ficha' => $init->monto_ficha,
                        'valor_ficha' => $init->valor_ficha,
                        'id_ficha' => $init->id_ficha,
                        'id_detalle_cierre' => $init->id_detalle_cierre,
                        ];
      }
      $juegoCI = $cierre->mesa->juego;

      //Apertura asociada
      $conjunto = null;
      $apertura = null;
      $detalleAP = null;
      if(isset($cierre->cierre_apertura)){
        $conjunto = $cierre->cierre_apertura;
        $apertura = $conjunto->apertura;
        $juegoAP = $apertura->mesa->juego;
        $detalleAP = array();
        foreach ($apertura->detalles as $det) {
          $cant = $det->cantidad_ficha;
          $valor = $det->ficha->valor_ficha;
          $detalleAP[] = ['monto_ficha' => ($cant * $valor),
                          'valor_ficha' => $valor,
                          ];
        }
      }
      return response()->json(['cierre' => $cierre,
              'estado' => $cierre->estado,
              'cargador' => $cierre->fiscalizador,
              'casino' => $cierre->casino,
              'mesa' => $mesa,
              'detallesC' => $detalleC,//detalles del cierre
              'apertura' => $apertura,
              'detalleAP' => $detalleAP,
              'nombre_juego' => $juegoCI->nombre_juego,
            ], 200);
    }else{
      return response()->json(['error' => 'Cierre no encontrado.'], 404);
    }
  }

  public function filtros(Request $request)
  {
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    $filtros = array();
    if(!empty($request->nro_mesa)){
      $filtros[]= ['mesa_de_panio.nro_mesa','like','%'.$request->nro_mesa.'%'];
    }
    if(!empty($request->id_juego)){
      $filtros[]= ['mesa_de_panio.id_juego','=',$request->id_juego];
    }
    if(!empty($request->id_casino)){
      $cas[] = $request->id_casino;
    }else{
      foreach ($user->casinos as $cass) {
        $cas[]=$cass->id_casino;
      }
    }

    if(empty($request->fecha)){
      $resultados = DB::table('cierre_mesa')->join('mesa_de_panio','mesa_de_panio.id_mesa_de_panio','=','cierre_mesa.id_mesa_de_panio')
                              ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                              ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                              ->where($filtros)
                              ->orderBy('cierre_mesa.fecha','desc')
                              ->take(31)
                              ->get();
    }else{
      $fecha=explode("-", $request->fecha);
      $resultados = DB::table('cierre_mesa')->join('mesa_de_panio','cierre_mesa.id_mesa_de_panio','=','mesa_de_panio.id_mesa_de_panio')
                              ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                              ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                              ->where($filtros)
                              ->whereYear('cierre_mesa.fecha' , '=', $fecha[0])
                              ->whereMonth('cierre_mesa.fecha','=', $fecha[1])
                              ->orderBy('cierre_mesa.fecha','desc')
                              ->take(31)
                              ->get();
    }

    return response()->json(['cierre' => $resultados], 200);


  }

}