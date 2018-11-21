<?php

namespace App\Http\Controllers\Mesas\Aperturas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use DateTime;
use Dompdf\Dompdf;

use PDF;
use View;
use App\Usuario;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;
use App\Mesas\Cierre;
use App\Mesas\DetalleCierre;
use App\Mesas\EstadoCierre;
use App\Mesas\TipoCierre;
use App\Mesas\Ficha;
use App\Mesas\MesasSorteadas;
use App\Http\Controllers\UsuarioController;
use Carbon\Carbon;
use Exception;
use Zipper;
use File;
use App\Http\Controllers\Mesas\Mesas\SorteoMesasController;
use App\Http\Controllers\Mesas\InformesMesas\ABCMesasSorteadasController;


//busqueda y consulta de cierres
class ABMCRelevamientosAperturaController extends Controller
{
  private static $atributos = [
    'id_cierre_mesa' => 'Identificacion del Cierre',
    'fecha' => 'Fecha',
    'hora_inicio' => 'Hora de Apertura',
    'hora_fin' => 'Hora del Cierre',
    'total_pesos_fichas_c' => 'Total de pesos en Fichas',
    'total_anticipos_c' => 'Total de Anticipos',
    'id_fiscalizador'=>'Fiscalizador',
    'id_tipo_cierre'=> 'Tipo de Cierre',
    'id_mesa_de_panio'=> 'Mesa de Paño',
    'id_estado_cierre'=>'Estado',
  ];


  private static $cantidad_dias_backup = 5;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
        $this->middleware(['tiene_permiso:m_sortear_mesas']);
  }
  /*
  * Esta funcion inicia el proceso de generacion de planillas :D
  */
  public function generarRelevamiento(){
      $informesSorteadas = new ABCMesasSorteadasController;
      $fecha_hoy = Carbon::now()->format("Y-m-d"); // fecha de hoy
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $cas = $user->casinos->first();
      $codigo_casino = $cas->codigo;
      $informesSorteadas->chequearSorteadas($fecha_hoy,$cas->id_casino);
      $arregloRutas = array();
      //creo planillas para hoy y los dias de backup
      for ($i=0; $i < self::$cantidad_dias_backup; $i++) {
        $fecha_backup = Carbon::now()->addDays($i)->format("Y-m-d");
        $dompdf = $this->crearPlanilla($cas, $fecha_backup);

        $output = $dompdf->output();
        $ruta = "Relevamiento-Aperturas-".$fecha_backup.".pdf";
        file_put_contents($ruta, $output);
        $arregloRutas[] = $ruta;

      }



      $nombreZip = 'Planillas-Aperturas-'.$codigo_casino
                .'-'.$fecha_hoy.' al '.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".self::$cantidad_dias_backup." day"))
                .'.zip';

      Zipper::make($nombreZip)->add($arregloRutas)->close();
      File::delete($arregloRutas);

      return ['url_zip' => 'sorteo-aperturas/descargarZip/'.$nombreZip];
  }


  public function descargarZip($nombre){

    $file = public_path() . "/" . $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);

  }

  /*
  *
  * Genera la planilla, llama a la funcion de sortear que está en
  * Controllers\Mesas\SorteoMesasController;
  *
  */
  private function crearPlanilla($cas,$fecha_backup){
    //try{
      $sorteoController = new SorteoMesasController;
      $rel = new \stdClass();
      //mesas sorteadas
      $sorteo = $sorteoController->sortear($cas->id_casino,$fecha_backup);


      $rel->sorteadas =  new \stdClass();
      $rel->sorteadas->ruletasDados = $sorteo['ruletasDados'];
      $rel->sorteadas->cartas = $sorteo['cartas'];


      $rmesas = Mesa::whereIn('id_casino',[$cas->id_casino])->get();
      $rel->mesas = $rmesas->sortBy('codigo_mesa');
      $rel->fecha = \Carbon\Carbon::today();
      $año = substr($rel->fecha,0,4);
      $mes = substr($rel->fecha,5,2);
      $dia = substr($rel->fecha,8,2);
      $rel->fecha = $dia."-".$mes."-".$año;
      $rel->casino = $cas->nombre;

      $rel->fichas = Ficha::select('valor_ficha')->distinct('valor_ficha')->orderBy('valor_ficha','DESC')->get();
      $rel->cant_fichas = $rel->fichas->count();
      if($rel->cant_fichas > 15){
        $rel->paginas = [1,2]; //->cantidad de mesas que se deben relevar obligatoriamente (de a pares)
      }else{
        $rel->paginas = [1,2,3,4];
      }

      $view = View::make('Mesas.Planillas.PlanillaRelevamientoAperturaSorteadas', compact('rel'));
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'portrait');
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 815, $cas->codigo."/".$rel->fecha, $font, 10, array(0,0,0));
      $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
      return $dompdf;//->stream('sorteoAperturas.pdf', Array('Attachment'=>0));
    // }catch(Exeption $e){
    //   if($e instanceof \App\Exceptions\PlanillaException){
    //     throw $e;
    //   }else{
    //     throw new \App\Exceptions\PlanillaException('No se pudo generar la planilla para relevar aperturas de mesas.');
    //   }
    // }
  }




}