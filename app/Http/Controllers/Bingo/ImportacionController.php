<?php

namespace App\Http\Controllers\Bingo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use App\Bingo\ImportacionBingo;
use App\Bingo\ReporteEstado;
use Validator;
use App\Casino;

use App\Http\Controllers\Bingo\ReportesController;
class ImportacionController extends Controller
{
    private static $atributos = [
    ];
    public function index(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      UsuarioController::getInstancia()->agregarSeccionReciente('Bingo' , 'bingo');


      return view('Bingo.importacion', ['casinos' => $casinos]);
    }

    public function buscarRelevamiento(Request $request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));

      $casinos=array();
      foreach($usuario['usuario']->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }

      $reglas = array();
      if(isset($request->fecha)){
        $reglas[]=['importacion_bingo.fecha', '=', $request->fecha];
      }
      if($request->casino!=0){
        $reglas[]=['casino.id_casino', '=', $request->casino];
      }

      //obtener solo la primera partida de cada importación sólo para maracar la sesión
      $reglas[] = ['importacion_bingo.num_partida','=','1'];

      $sort_by = $request->sort_by;

      $resultados = DB::table('importacion_bingo')
                         ->select('importacion_bingo.*', 'casino.codigo', 'usuario.nombre')
                         ->leftJoin('casino' , 'importacion_bingo.id_casino','=','casino.id_casino')
                         ->leftJoin('usuario', 'importacion_bingo.id_usuario', '=', 'usuario.id_usuario')
                         ->when($sort_by,function($query) use ($sort_by){
                          return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                        },function($query){
                          return $query->orderBy('id_importacion','desc');
                        })
                      ->where($reglas)
                      ->whereIn('casino.id_casino', $casinos)
                      // ->orderBy('id_importacion', 'desc')
                      ->paginate($request->page_size);


      UsuarioController::getInstancia()->agregarSeccionReciente('Bingo' ,'bingo');

     return $resultados;
    }

    public function eliminarImportacion($id){
      $importaciones = $this->obtenerImportacionCompleta($id);

      //Guardo la información para el reporte de estado
      //si no tiene cargada una sesion, relevamiento o cerrada, elimino el reporte

      //armo las reglas para la busqueda
      $reglas = array();
      $reglas [] =['fecha_sesion','=', $importaciones[0]->fecha];
      $reglas [] =['id_casino','=', $importaciones[0]->id_casino];
      //busco el reporte que cumpla con las reglas
      $reporte = ReporteEstado::where($reglas)->first();
    
      if($reporte->sesion_abierta == null || $reporte->sesion_abierta == 0){
        if($reporte->sesion_cerrada == null || $reporte->sesion_cerrada == 0) $reporte->delete();
      }
      else{
      //pongo en 0 el reporte de estado
      app(\App\Http\Controllers\Bingo\ReportesController::class)->eliminarReporteEstado($importaciones[0]->id_casino, $importaciones[0]->fecha, 1);
      }

      foreach ($importaciones as $importacion) {
        $eliminar = ImportacionBingo::findOrFail($importacion->id_importacion);
        $eliminar->delete();
      }

      return ['importaciones' => $importaciones];
    }

    public function guardarImportacion(Request $request){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;
      $nombre = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->nombre;
      $casino = Casino::findOrFail($request->id_casino)->codigo;

      //obtengo el archivo
      $archivoCSV = $request->archivo;
      //obtengo la dirección del archivo
      $path = $archivoCSV->getRealPath();
      //abro el archivo
      $fileHandle = fopen($path, "r");
      //obtengo la fecha del archivo importado para luego validar
      $nfecha = $this->fechaArchivo($path);

      //si decidió guardar igual un archivo con fecha y casino ya importado, borro
      if($request->guarda_igual == 1){
         $id = $this->obtenerImportacionSimple($nfecha, $request->id_casino);
         $this->eliminarImportacion($id);
      }
      //valido la importacion
      $this->validarImportacion($request->id_casino, $nfecha);

      //guardo todas las filas en lines, como csv corta con ',', habrá más columnas por fila
      $lines = array();
      while( ($row = fgetcsv($fileHandle)) !== FALSE ) {
      	$lines[] = $row;
      }

      $resultado = array(); //variable auxiliar para guardar  el array separando

      //si es de MEL/StaFe
      if($request->id_casino == '1' || $request->id_casino == '2'){
        foreach ($lines as $line) {
          //limpio las lineas, guardando todo el array en una sola linea
          $puntoxnada = str_replace('.','',implode(',',$line));
          $comaxpunto = str_replace(',','.', $puntoxnada);
          $sinpeso = str_replace('$','', $comaxpunto);
          $final = str_replace(' ', '', $sinpeso);
          //creo el array separando las columnas de la fila
          $resultado[] = explode(';', $final);
        }
        $una_importacion = $this->guardarMelStaFe($resultado,$request->id_casino, $usuario);
      }else{ //es de rosario
        foreach ($lines as $line) {
          $final = str_replace(',','',implode(',',$line));
          $sinpeso = str_replace('$','', $final);
          $resultado[] = explode(';', $sinpeso);
        }
        $una_importacion = $this->guardarRosario($resultado, $usuario);
      }
      //paso lo recibido a collection para agregarle los datos que faltan para mostrarse
      $una_importacion = collect($una_importacion);
      //Agrego nombre de usuario y código de casino
      $una_importacion = array_add($una_importacion, 'nombre',$nombre);
      $una_importacion = array_add($una_importacion, 'codigo',$casino);
      //retorno $una_importacion para poder actualizar las filas
      return $una_importacion;
    }

    public function obtenerImportacionCompleta($id){
      $importacion = ImportacionBingo::findOrFail($id);

      $reglas = array();
      $reglas[]=['importacion_bingo.fecha', '=', $importacion->fecha];
      $reglas[]=['importacion_bingo.id_casino', '=', $importacion->id_casino];

      $respuesta = DB::table('importacion_bingo')
                        ->select('importacion_bingo.*')
                        ->where($reglas)
                        ->get();

      return $respuesta;
    }

    public function obtenerImportacionFC($fecha, $id_casino){
      $importacion = ImportacionBingo::where('id_casino','=',$id_casino)->where('fecha','=',$fecha)->first();
      if($importacion != null) return $this->obtenerImportacionCompleta($importacion->id_importacion);

      return -1;

    }

    protected function obtenerImportacionSimple($fecha, $id_casino){
      //Si viene desde "guardar igual", hay que armar la fecha
      $pos = strpos($fecha, '-');
      if($pos === false) {
        $dd = substr($fecha,0,2);
        $mm = substr($fecha,2,2);
        $aa = substr($fecha,4,4);
        $fecha = $aa . '-' . $mm .'-' .$dd;
      }
      //busca las importaciones cargadas que cumplan con "regla"
      $regla = array();
      $regla [] =['importacion_bingo.id_casino', '=', $id_casino];
      $regla [] =['importacion_bingo.fecha', '=', $fecha];
      $importacion = ImportacionBingo::where($regla)->first();

      return $importacion->id_importacion;
    }
    //Función auxiliar para guardar los datos de importación para sta fe y melincue
    protected function guardarMelStaFe($resultado, $id, $usuario){
      $una_importacion = new ImportacionBingo;

      foreach ($resultado as $row) {

        $fecha = explode('/', $row[19]);
        $nfecha = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

        //creo una nueva importación para cargar los datos
        $importacion = new ImportacionBingo;
        $importacion->id_casino = $id;
        $importacion->id_usuario = $usuario;
        $importacion->num_partida = $row[0];
        $importacion->hora_inicio = $row[1];
        $importacion->serieA = $row[3];
        $importacion->carton_inicio_A = $row[4];
        $importacion->carton_fin_A = $row[5];
        $importacion->cartones_vendidos = $row[6];
        $importacion->serieB = $row[7];
        $importacion->carton_inicio_B = $row[8];
        $importacion->carton_fin_B = $row[9];
        $importacion->valor_carton = $row[12];
        $importacion->cant_bola = $row[13];
        $importacion->recaudado = $row[14];
        $importacion->premio_linea = $row[15];
        $importacion->premio_bingo = $row[16];
        $importacion->pozo_dot = $row[17];
        $importacion->pozo_extra = $row[18];
        $importacion->fecha = $nfecha;

        $importacion->save();

        $una_importacion = $importacion;
      }
      //Guardo la información para el reporte de estados
      $fff = $resultado[0];
      $ff = explode('/', $fff[19]);
      $f = $ff[2] . '-' . $ff[1] . '-' . $ff[0];
      app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado($id, $f, 1);

      return $una_importacion;
    }
    //Función auxiliar para guardar los datos de importación para rosario
    protected function guardarRosario($resultado, $usuario){
      $una_importacion = new ImportacionBingo;
      foreach ($resultado as $row) {

        $fecha = explode('/', $row[18]);
        $nfecha = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

        //creo una nueva importación para cargar los datos
        $importacion = new ImportacionBingo;
        $importacion->id_casino = '3';
        $importacion->id_usuario = $usuario;
        $importacion->num_partida = $row[0];
        $importacion->hora_inicio = $row[1];
        $importacion->serieA = $row[3];
        $importacion->carton_inicio_A = $row[4];
        $importacion->carton_fin_A = $row[5];
        $importacion->cartones_vendidos = $row[9];
        if($row[6] != '')$importacion->serieB = $row[6];
        if($row[7] != '')$importacion->carton_inicio_B = $row[7];
        if($row[8] != '')$importacion->carton_fin_B = $row[8];
        $importacion->valor_carton = $row[11];

        if($row[12] != '')$importacion->cant_bola = $row[12];

        $importacion->recaudado = $row[13];
        $importacion->premio_linea = $row[14];
        $importacion->premio_bingo = $row[15];
        $importacion->pozo_dot = $row[16];
        $importacion->pozo_extra = $row[17];
        $importacion->fecha = $nfecha;

        $importacion->save();
        $una_importacion = $importacion;
      }

      //Guardo la información para el reporte de estados
      $fff = $resultado[0];
      $ff = explode('/', $fff[18]);
      $f = $ff[2] . '-' . $ff[1] . '-' . $ff[0];
      app(\App\Http\Controllers\Bingo\ReportesController::class)->guardarReporteEstado(3, $f, 1);;

      return $una_importacion;
    }
    //Funcion auxiliar para obtener la fecha de la importacion
    protected function fechaArchivo($path){
      $fileHandle_validator = fopen($path, "r");
      $file_line = fgetcsv($fileHandle_validator);
      $end_line = end($file_line);
      $pos = strpos($end_line,'/');
      $file_date = substr($end_line, $pos-2,11);
      $fecha = explode('/', $file_date);
      return $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
    }
    //Función para validar la importacion
    protected function validarImportacion($id_casino, $fecha){
      Validator::make(['id_casino' => $id_casino, 'fecha' =>$fecha],[
          'id_casino' => 'required'
      ], array(), self::$atributos)->after(function($validator){
        //valido que no exista importacion para el mismo día en el casino
        $regla_carga = array();
        $regla_carga [] =['importacion_bingo.id_casino', '=', $validator->getData()['id_casino']];
        $regla_carga [] =['importacion_bingo.fecha', '=', $validator->getData()['fecha']];
        $carga = ImportacionBingo::where($regla_carga)->count();
        if($carga > 0){
          $validator->errors()->add('importacion_cargada','La importación para esta fecha se encuentra cargarda.');
        }
      })->validate();
    }
}