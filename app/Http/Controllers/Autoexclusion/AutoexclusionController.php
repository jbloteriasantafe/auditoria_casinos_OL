<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Validator;

use App\Autoexclusion\Autoexcluido;
use App\Autoexclusion\ContactoAE;
use App\Autoexclusion\EstadoAE;
use App\Autoexclusion\NombreEstadoAutoexclusion;
use App\Autoexclusion\Encuesta;

use Illuminate\Support\Facades\DB;

class AutoexclusionController extends Controller
{
    private static $atributos = [
    ];

    public function index(){
      $juegos =  DB::table('ae_juego_preferido')->get();
      $ocupaciones =  DB::table('ae_ocupacion')->get();
      $frecuencias = DB::table('ae_frecuencia_asistencia')->get();
      $casinos = DB::table('casino')->get();
      $estados_autoexclusion = DB::table('ae_nombre_estado')->get();

      return view('Autoexclusion.index', ['juegos' => $juegos,
                                          'ocupaciones' => $ocupaciones,
                                          'casinos' => $casinos,
                                          'frecuencias' => $frecuencias,
                                          'estados_autoexclusion' => $estados_autoexclusion
                                        ]);
    }

    //Función para buscar los autoexcluidos existentes en el sistema
    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      //filtro de búsqueda por apellido
      if(!empty($request->apellido)){
        $reglas[]=['ae_datos.apellido','LIKE', '%' . $request->apellido . '%'];
      }

      //filtro de búsqueda por dni
      if(!empty($request->dni)){
        $reglas[]=['ae_datos.nro_dni','=',$request->dni];
      }

      //filtro de búsqueda por estado
      if(!empty($request->estado)){
        $reglas[]=['ae_estado.id_nombre_estado','=',$request->estado];
      }

      //filtro de búsqueda por casino
      if(!empty($request->casino)){
        $reglas[]=['ae_estado.id_casino','=',$request->casino];
      }

      //filtro de búsqueda por fecha de autoexclusion
      if(!empty($request->fecha_autoexclusion)){
        $reglas[]=['ae_estado.fecha_ae','=',$request->fecha_autoexclusion];
      }

      //filstro de búsqueda por fecha de vencimiento
      if(!empty($request->fecha_vencimiento)){
        $reglas[]=['ae_estado.fecha_vencimiento','=',$request->fecha_vencimiento];
      }

      //filtro de búsqueda por fecha de finalización
      if(!empty($request->fecha_finalizacion)){
        $reglas[]=['ae_estado.fecha_renovacion','=',$request->fecha_finalizacion];
      }

      //filtro de búsqueda por fecha de cierre definitivo
      if(!empty($request->fecha_cierre_definitivo)){
        $reglas[]=['ae_estado.fecha_cierre_ae','=',$request->fecha_cierre_definitivo];
      }

      if(!empty($request->id_autoexcluido)) {
        $reglas[]=['ae_datos.id_autoexcluido','=',$request->id_autoexcluido];
      }


      $sort_by = $request->sort_by;
      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_datos_contacto.*', 'ae_encuesta.*', 'ae_estado.*', 'ae_nombre_estado.descripcion')
        //hago un left join de datos contacto y encuesta porque son opcionales, sino solo me devolveria
        //los autoexcluidos que tienen datos de contacto y de encuesta existentes
        ->leftJoin('ae_datos_contacto' , 'ae_datos.id_autoexcluido' , '=' , 'ae_datos_contacto.id_autoexcluido')
        ->leftJoin('ae_encuesta' , 'ae_datos.id_autoexcluido' , '=' , 'ae_encuesta.id_autoexcluido')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->paginate($request->page_size);


      return $resultados;
    }

    //Función para agregar un nuevo autoexcluido complet, o editar uno existente
    public function agregarAE(Request $request, $esNuevo){
      //Validación de los datos
      Validator::make($request->datos_personales,$request->ae_estado, [
            'datos_personales.*.nro_dni' => 'required|numeric',
            'datos_personales.*.apellido' => 'required|string',
            'datos_personales.*.nombres' => 'required|string',
            'datos_personales.*.fecha_nacimiento' => 'required',
            'datos_personales.*.id_sexo' => 'required|numeric',
            'datos_personales.*.domicilio' => 'required|string',
            'datos_personales.*.nro_domicilio' => 'required|numeric',
            'datos_personales.*.nombre_localidad' => 'required|string',
            'datos_personales.*.nombre_provincia' => 'required|string',
            'datos_personales.*.telefono' => 'required|string',
            'datos_personales.*.correo' => 'required|string',
            'datos_personales.*.id_ocupacion' => 'required|numeric',
            'datos_personales.*.id_capacitacion' => 'required|numeric',
            'datos_personales.*.id_estado_civil' => 'required|numeric',
            'ae_estado.*.id_nombre_estado' => 'required',
            'ae_estado.*.id_casino' => 'required',
            'ae_estado.*.fecha_autoexlusion' => 'required',
            'ae_estado.*.fecha_vencimiento_periodo' => 'required',
            'ae_estado.*.fecha_renovacion' => 'required',
            'ae_estado.*.fecha_cierre_definitivo' => 'required',
        ], array(), self::$atributos)->after(function($validator){
        })->validate();

        dd($request->ae_importacion['foto1']);

       DB::transaction(function() use($request, $esNuevo){

         //creo un nuevo Autoexcluido y cargo sus datos personales
         $ae = $this->cargarDatos($request['datos_personales'], $esNuevo);

         $id_autoexcluido = $ae->id_autoexcluido;
         $id_usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario;

         //cargo los datos de contacto (si hubiere)
         if ($request['datos_contacto']['hay_datos_contacto']) {
           $this->cargarContacto($request['datos_contacto'], $id_autoexcluido, $esNuevo);
         }

         //cargo los datos de estado/fecha
         $this->cargarEstado($request['ae_estado'], $id_usuario, $id_autoexcluido, $esNuevo);

         //cargo los datos de la encuesta (si hubiere)
         if ($request['ae_encuesta']['hay_encuesta']) {
           $this->cargarEncuesta($request['ae_encuesta'], $id_autoexcluido, $esNuevo);
         }
       });

      return ['codigo' => 200];
    }

    //Función para obtener los datos a partir de un DNI
    public function existeAutoexcluido($dni){
      $autoexcluido = DB::table('ae_datos')->where('ae_datos.nro_dni','=',$dni)->first();

      if ($autoexcluido != null) {
        $datos_contacto = DB::table('ae_datos_contacto')->where('ae_datos_contacto.id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
        $estado = DB::table('ae_estado')->where('ae_estado.id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();
        $encuesta = DB::table('ae_encuesta')->where('ae_encuesta.id_autoexcluido','=',$autoexcluido->id_autoexcluido)->first();

        $resultados = array(
          'autoexcluido' => $autoexcluido,
          'datos_contacto'=> $datos_contacto,
          'estado' => $estado,
          'encuesta' => $encuesta
        );
      }
      else {
        $resultados = -1;
      }

      return $resultados;
    }

    //Función para cargar los datos del ae
    protected function cargarDatos($datos_personales, $esNuevo){
      if ($esNuevo) {
        $ae = new Autoexcluido;
        $ae->nro_dni = $datos_personales['nro_dni'];
      }
      else {
        $ae = Autoexcluido::where('nro_dni', '=', $datos_personales['nro_dni'])->first();
      }

      $ae->apellido = $datos_personales['apellido'];
      $ae->nombres = $datos_personales['nombres'];
      $ae->fecha_nacimiento = $datos_personales['fecha_nacimiento'];
      $ae->id_sexo = $datos_personales['id_sexo'];
      $ae->domicilio = $datos_personales['domicilio'];
      $ae->nro_domicilio = $datos_personales['nro_domicilio'];
      $ae->nombre_localidad = $datos_personales['nombre_localidad'];
      $ae->nombre_provincia = $datos_personales['nombre_provincia'];
      $ae->telefono = $datos_personales['telefono'];
      $ae->correo = $datos_personales['correo'];
      $ae->id_ocupacion = $datos_personales['id_ocupacion'];
      $ae->id_capacitacion = $datos_personales['id_capacitacion'];
      $ae->id_estado_civil = $datos_personales['id_estado_civil'];

      //guardo los datos
      $ae->save();

      return $ae;
    }

    //Función para cargar los datos de contacto
    protected function cargarContacto($datos, $id_autoexcluido, $esNuevo){
      if ($esNuevo) {
        //creo un nuevo contacto de ae con los datos;
        $c = new ContactoAE;
        $c->id_autoexcluido = $id_autoexcluido;
      }
      else {
        $c = ContactoAE::where('id_autoexcluido', '=', $id_autoexcluido)->first();
      }

      $c->nombre_apellido = $datos['nombre_apellido'];
      $c->domicilio = $datos['domicilio_vinculo'];
      $c->nombre_localidad = $datos['nombre_localidad_vinculo'];
      $c->nombre_provincia = $datos['nombre_provincia_vinculo'];
      $c->telefono = $datos['telefono_vinculo'];
      $c->vinculo = $datos['vinculo'];

      $c->save();
    }

    //Función para cargar los datos de estado / fecha
    protected function cargarEstado($datos, $id_usuario, $id_autoexcluido, $esNuevo){
      //modifico las fechas para guardarlas en formato aaaa-mm-dd
      /*
      $fecha = explode('-', $datos['fecha_vencimiento_periodo']);
      $fecha_vencimiento = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

      $fecha = explode('-', $datos['fecha_renovacion']);
      $fecha_renovacion = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];

      $fecha = explode('-', $datos['fecha_cierre_definitivo']);
      $fecha_cierre_definitivo = $fecha[2] . '-' . $fecha[1] . '-' . $fecha[0];
      */

      if ($esNuevo) {
        //creo un nuevo estado con los datos
        $e = new EstadoAE;
        $e->id_autoexcluido = $id_autoexcluido;
      }
      else {
        $e = EstadoAE::where('id_autoexcluido', '=', $id_autoexcluido)->first();
      }

      $e->id_casino = $datos['id_casino'];
      $e->id_nombre_estado = $datos['id_nombre_estado'];
      $e->fecha_ae = $datos['fecha_autoexlusion'];
      $e->fecha_vencimiento = $datos['fecha_vencimiento_periodo'];
      $e->fecha_renovacion = $datos['fecha_renovacion'];
      $e->fecha_cierre_ae = $datos['fecha_cierre_definitivo'];
      $e->id_usuario = $id_usuario;

      $e->save();
    }

    //Función para cargar la encuesta
    protected function cargarEncuesta($datos, $id_autoexcluido, $esNuevo){
      if ($esNuevo) {
        //creo una nueva encuesta con los datos
        $e = new Encuesta;
        $e->id_autoexcluido = $id_autoexcluido;
      }
      else {
        $e = Encuesta::where('id_autoexcluido', '=', $id_autoexcluido)->first();
      }

      $e->id_juego_preferido = $datos['juego_preferido'];
      $e->id_frecuencia_asistencia = $datos['id_frecuencia_asistencia'];
      $e->veces = $datos['veces'];
      $e->tiempo_jugado = $datos['tiempo_jugado'];
      $e->club_jugadores = $datos['socio_club_jugadores'];
      $e->juego_responsable = $datos['juego_responsable'];
      $e->recibir_informacion = $datos['recibir_informacion'];
      $e->autocontrol_juego = $datos['autocontrol_juego'];
      $e->medio_recibir_informacion = $datos['medio_recepcion'];
      $e->como_asiste = $datos['como_asiste'];
      $e->observacion = $datos['observaciones'];

      $e->save();
    }

  public function buscarAutoexcluido ($id) {
    $autoexcluido = Autoexcluido::find($id);
    $datos_contacto = ContactoAE::where('id_autoexcluido', '=', $id)->first();
    $estado = EstadoAE::where('id_autoexcluido', '=', $id)->first();
    $encuesta = Encuesta::where('id_autoexcluido', '=', $id)->first();

    return ['autoexcluido' => $autoexcluido, 'datos_contacto' => $datos_contacto, 'estado' => $estado, 'encuesta' => $encuesta];
  }
}
