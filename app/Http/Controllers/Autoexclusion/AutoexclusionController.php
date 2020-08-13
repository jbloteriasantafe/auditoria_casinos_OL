<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Dompdf\Dompdf;
use View;
use Validator;
use PDF;

use App\Casino;
use App\Autoexclusion as AE;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AutoexclusionController extends Controller
{
    private static $atributos = [
    ];

    public function index($dni = ''){
      UsuarioController::getInstancia()->agregarSeccionReciente('Autoexclusión' , 'autoexclusion');
      $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $estados_autoexclusion = AE\NombreEstadoAutoexclusion::all();
      $estados_elegibles = $estados_autoexclusion;
      if(!($usuario->es_superusuario || $usuario->es_administrador))
        $estados_elegibles = AE\NombreEstadoAutoexclusion::where('id_nombre_estado',3)->get();

      return view('Autoexclusion.index', ['juegos' => AE\JuegoPreferidoAE::all(),
                                          'ocupaciones' => AE\OcupacionAE::all(),
                                          'casinos' => Casino::all(),
                                          'usuario' => $usuario,
                                          'frecuencias' => AE\FrecuenciaAsistenciaAE::all(),
                                          'estados_autoexclusion' => $estados_autoexclusion,
                                          'estados_elegibles' => $estados_elegibles,
                                          'estados_civiles' => AE\EstadoCivilAE::all(),
                                          'capacitaciones' => AE\CapacitacionAE::all(),
                                          'dni' => $dni
                                        ]);
    }

    //Función para buscar los autoexcluidos existentes en el sistema
    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();

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

      $sort_by = ['columna' => 'ae_datos.id_autoexcluido', 'orden' => 'desc'];
      if(!empty($request->sort_by)){
        $sort_by = $request->sort_by;
      }

      $resultados = DB::table('ae_datos')
        ->select('ae_datos.*', 'ae_datos_contacto.*', 'ae_encuesta.*', 'ae_estado.*', 
        'ae_importacion.foto1','ae_importacion.foto2','ae_importacion.scandni','ae_importacion.solicitud_ae','ae_importacion.solicitud_revocacion','ae_importacion.caratula',
                 'ae_nombre_estado.descripcion as desc_estado','casino.nombre as casino')
        ->join('ae_datos_contacto' , 'ae_datos.id_autoexcluido' , '=' , 'ae_datos_contacto.id_autoexcluido')
        ->join('ae_encuesta'       , 'ae_datos.id_autoexcluido' , '=' , 'ae_encuesta.id_autoexcluido')
        ->join('ae_importacion'    , 'ae_datos.id_autoexcluido' , '=' , 'ae_importacion.id_autoexcluido')
        ->join('ae_estado'         , 'ae_datos.id_autoexcluido' , '=' , 'ae_estado.id_autoexcluido')
        ->join('ae_nombre_estado', 'ae_nombre_estado.id_nombre_estado', '=', 'ae_estado.id_nombre_estado')
        ->join('casino','ae_estado.id_casino','=','casino.id_casino')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->paginate($request->page_size);

      //Agrego algunos atributos dinamicos utiles para el frontend
      $resultados->getCollection()->transform(function ($row){
        $ae = AE\Autoexcluido::find($row->id_autoexcluido);
        $row->es_primer_ae = $ae->es_primer_ae;
        $row->estado_transicionable = $ae->estado_transicionable;
        return $row;
      });
      return $resultados;
    }

    //Función para agregar un nuevo autoexcluido complet, o editar uno existente
    public function agregarAE(Request $request){
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      Validator::make($request->all(), [
        'ae_datos.id_autoexcluido'  => 'nullable|integer|exists:ae_datos,id_autoexcluido',
        'ae_datos.nro_dni'          => 'required|integer',
        'ae_datos.apellido'         => 'required|string|max:100',
        'ae_datos.nombres'          => 'required|string|max:150',
        'ae_datos.fecha_nacimiento' => 'required|date',
        'ae_datos.id_sexo'          => 'required|integer',
        'ae_datos.domicilio'        => 'required|string|max:100',
        'ae_datos.nro_domicilio'    => 'required|integer',
        'ae_datos.piso'             => 'nullable|string|max:5',
        'ae_datos.dpto'             => 'nullable|string|max:5',
        'ae_datos.codigo_postal'    => 'nullable|string|max:10',
        'ae_datos.nombre_localidad' => 'required|string|max:200',
        'ae_datos.nombre_provincia' => 'required|string|max:200',
        'ae_datos.telefono'         => 'required|string|max:200',
        'ae_datos.correo'           => 'required|string|max:100',
        'ae_datos.id_ocupacion'     => 'required|integer|exists:ae_ocupacion,id_ocupacion',
        'ae_datos.id_capacitacion'  => 'required|integer|exists:ae_capacitacion,id_capacitacion',
        'ae_datos.id_estado_civil'  => 'required|integer|exists:ae_estado_civil,id_estado_civil',
        'ae_datos_contacto.nombre_apellido'  => 'nullable|string|max:200',
        'ae_datos_contacto.domicilio'        => 'nullable|string|max:200',
        'ae_datos_contacto.nombre_localidad' => 'nullable|string|max:200',
        'ae_datos_contacto.nombre_provincia' => 'nullable|string|max:200',
        'ae_datos_contacto.telefono'         => 'nullable|string|max:200',
        'ae_datos_contacto.vinculo'          => 'nullable|string|max:200',
        'ae_estado.id_nombre_estado'  => 'required|integer|exists:ae_nombre_estado,id_nombre_estado',
        'ae_estado.id_casino'         => 'required|integer|exists:casino,id_casino',
        'ae_estado.fecha_ae'          => 'required|date',
        'ae_estado.fecha_vencimiento' => 'required|date',
        'ae_estado.fecha_renovacion'  => 'required|date',
        'ae_estado.fecha_cierre_ae'   => 'required|date',
        'ae_encuesta.id_juego_preferido'        => 'nullable|integer|exists:ae_juego_preferido,id_juego_preferido',
        'ae_encuesta.id_frecuencia_asistencia'  => 'nullable|integer|exists:ae_frecuencia_asistencia,id_frecuencia',
        'ae_encuesta.veces'                     => 'nullable|integer',
        'ae_encuesta.tiempo_jugado'             => 'nullable|integer',
        'ae_encuesta.club_jugadores'            => 'nullable|string|max:2',
        'ae_encuesta.juego_responsable'         => 'nullable|string|max:2',
        'ae_encuesta.autocontrol_juego'         => 'nullable|string|max:2',
        'ae_encuesta.recibir_informacion'       => 'nullable|string|max:2',
        'ae_encuesta.medio_recibir_informacion' => 'nullable|string|max:100',
        'ae_encuesta.como_asiste'               => 'nullable|integer',
        'ae_encuesta.observacion'               => 'nullable|string|max:200',
        'ae_importacion.foto1'                => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.foto2'                => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.solicitud_ae'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.solicitud_revocacion' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.scandni'              => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'ae_importacion.caratula'              => 'nullable|file|mimes:jpg,jpeg,png,pdf',
      ], array(), self::$atributos)->after(function($validator) use ($user){
        $data = $validator->getData();
        $id_casino = $data['ae_estado']['id_casino'];
        $estado = $data['ae_estado']['id_nombre_estado'];
        if(!$user->es_superusuario && !$user->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a ese casino');
        }
        if($user->es_fiscalizador && $estado != 3){
          $validator->errors()->add('ae_estado.id_nombre_estado', 'No puede agregar autoexcluidos con ese estado');
        }

        $id_ae = $data['ae_datos']['id_autoexcluido'];
        $todos_vencidos = true;
        {
          $aes = AE\Autoexcluido::where('nro_dni','=', $data['ae_datos']['nro_dni'])->get();
          foreach($aes as $ae){
            $e = $ae->estado;
            $vencido = $e->id_nombre_estado == 4 || $e->id_nombre_estado == 5 || $ae->estado_transicionable == 5;
            $todos_vencidos = $vencido && $todos_vencidos;
            if(!$todos_vencidos) break;
          }
        }
        //Si es para crear uno nuevo y ya hay uno vigente, error
        if(is_null($id_ae) && !$todos_vencidos){
          $validator->errors()->add('ae_datos.nro_dni', 'Ya existe un autoexcluido vigente con ese DNI.');
        }
      })->validate();
      
      $esNuevo = false;
      DB::transaction(function() use($request, &$esNuevo, $user){
        $ae_datos = $request['ae_datos'];
        $ae = null;
        if(is_null($ae_datos['id_autoexcluido'])){
          $ae = new AE\AutoExcluido;
          $esNuevo = true;
        }
        else{
          $ae = AE\Autoexcluido::find($ae_datos['id_autoexcluido']);
          $esNuevo = false;
        }

        foreach($ae_datos as $key => $val){
          $ae->{$key} = $val;
        }
        $ae->save();

        $contacto = $ae->contacto;
        if(is_null($contacto)){
          $contacto = new AE\ContactoAE;
          $contacto->id_autoexcluido = $ae->id_autoexcluido;
        }

        $ae_datos_contacto = $request['ae_datos_contacto'];
        foreach($ae_datos_contacto as $key => $val){
          $contacto->{$key} = $val;
        }
        $contacto->save();

        $estado = $ae->estado;
        if(is_null($estado)){
          $estado = new AE\EstadoAE;
          $estado->id_autoexcluido = $ae->id_autoexcluido;
        }

        $estado->id_usuario = $user->id_usuario;
        $ae_estado = $request['ae_estado'];
        foreach($ae_estado as $key => $val){
          $estado->{$key} = $val;
        }
        $estado->save();

        $encuesta = $ae->encuesta;
        if(is_null($encuesta)){
          $encuesta = new AE\Encuesta;
          $encuesta->id_autoexcluido = $ae->id_autoexcluido;
        }
        $ae_encuesta = $request['ae_encuesta'];
        foreach($ae_encuesta as $key => $val){
          $encuesta->{$key} = $val;
        }
        $encuesta->save();
        $this->subirImportacionArchivos($ae,$request['ae_importacion']);
      });
      return ['nuevo' => $esNuevo];
    }
 
    public function subirImportacionArchivos($ae,$ae_importacion) {
      $importacion = $ae->importacion;
      if (is_null($importacion)){
        $importacion = new AE\ImportacionAE;
        $importacion->id_autoexcluido = $ae->id_autoexcluido;
      }

      //consigo el path del directorio raiz del proyecto
      $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';

      //fecha actual, sin formatear
      $ahora = date("dmY");

      $carpeta = [ 'foto1' => 'fotos/', 'foto2' => 'fotos/', 'scandni' => 'documentos/',
       'solicitud_ae' => 'solicitudes/', 'solicitud_revocacion' => 'solicitudes/', 'caratula' => 'solicitudes/' ];
      $numero_identificador = [ 'foto1' => '1', 'foto2' => '2', 'scandni' => '3', 
      'solicitud_ae' => '4', 'solicitud_revocacion' => '5', 'caratula' => 'CAR'];
      foreach($carpeta as $tipo => $ignorar){
        if(is_null($ae_importacion) || !array_key_exists($tipo,$ae_importacion)){//Si no viene en el request, lo borro
          $importacion->{$tipo} = NULL;
        }
      }
      if(!is_null($ae_importacion)) foreach($ae_importacion as $tipo => $file){
        //No esta en el arreglo de arriba, ignoro
        if(!array_key_exists($tipo,$carpeta) || !array_key_exists($tipo,$numero_identificador)) continue;
        if(is_null($file)){//Si viene en el request y es nulo, lo considero que es el mismo archivo
          continue;
        }
        $barra = strpos($file->getMimeType(),'/');
        $extension = substr($file->getMimeType(),$barra+1);
        $nombre_archivo = $ahora . '-' . $ae->nro_dni . '-' . $numero_identificador[$tipo] . '.' . $extension;
        $path = $pathCons . $carpeta[$tipo] . $nombre_archivo;
        copy($file->getRealPath(),$path);
        $importacion->{$tipo} = $nombre_archivo;
      }
      $importacion->save();
    }

    public function subirArchivo(Request $request) {
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      Validator::make($request->all(), [
          'id_autoexcluido' => 'required|integer|exists:ae_datos,id_autoexcluido',
          'tipo_archivo' => ['required','string',Rule::in(['foto1','foto2','scandni','solicitud_ae','solicitud_revocacion','caratula'])],
          'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf',
        ], array(), self::$atributos)->after(function($validator) use ($user){
          $id = $validator->getData()['id_autoexcluido'];
          $id_casino = AE\Autoexcluido::find($id)->estado->id_casino;
          if(!$user->usuarioTieneCasino($id_casino)){
            $validator->errors()->add('ae_estado.id_casino', 'No tiene acceso a ese casino');
            return;
          }
      })->validate();

      DB::transaction(function() use ($request){
        $ae_importacion = [
          'foto1' => null,//Necesito mandar nulo para que el metodo no lo borre de la BD
          'foto2' => null,
          'scandni' => null,
          'solicitud_ae' => null,
          'solicitud_revocacion' => null,
          'caratula' => null,
        ];
        $ae_importacion[$request->tipo_archivo] = $request->archivo;
        dump($ae_importacion);
        $this->subirImportacionArchivos(AE\Autoexcluido::find($request->id_autoexcluido),$ae_importacion);
      });
      return ['codigo' => 200];
    }

    //Función para obtener los datos de un autoexcluido a partir de un DNI
    public function existeAutoexcluido($dni){
      $aes = AE\Autoexcluido::where('nro_dni',$dni)->get();
      $todos_vencidos = true;
      foreach($aes as $ae){
        $e = $ae->estado;
        $vencido = $e->id_nombre_estado == 4 || $e->id_nombre_estado == 5 || $ae->estado_transicionable == 5;
        $todos_vencidos = $vencido && $todos_vencidos;
        if(!$todos_vencidos) break;
      }
      //Si estan todos los anteriores finalizados (o no hay), dejo crear uno nuevo.
      if($todos_vencidos) return 0;

      //Si llegue aca es porque hay uno en vigencia, lo devuelvo para mostrarl
      $ae = AE\Autoexcluido::where('nro_dni',$dni)->orderBy('id_autoexcluido','desc')->first();
      return $ae->id_autoexcluido;
  }

  public function buscarAutoexcluido ($id) {
    $ae = AE\Autoexcluido::find($id);
    return ['autoexcluido' => $ae,
            'datos_contacto' => $ae->contacto,
            'estado' => $ae->estado,
            'encuesta' => $ae->encuesta,
            'importacion' => $ae->importacion];
  }

  public function mostrarArchivo ($id_importacion,$tipo_archivo) {
    $imp = AE\ImportacionAE::where('id_importacion', '=', $id_importacion)->first();
    $pathCons = realpath('../') . '/public/importacionesAutoexcluidos/';

    $paths = [
      'foto1' => 'fotos', 'foto2' => 'fotos', 'scandni' => 'documentos', 'solicitud_ae' => 'solicitudes', 'solicitud_revocacion' => 'solicitudes',
      'caratula' => 'solicitudes'
    ];

    $path = $pathCons;
    if(array_key_exists($tipo_archivo,$paths)){
      $path = $path . $paths[$tipo_archivo] . '/' . $imp->{$tipo_archivo};
    }
    if($pathCons != $path && file_exists($path)) return response()->file($path);
    return "Archivo no encontrado o inaccesible";
  }

  public function mostrarFormulario ($id_formulario) {
    $pathForms = realpath('../') . '/public/importacionesAutoexcluidos/formularios/';

    if ($id_formulario == 1) {
      $path = $pathForms . 'Carátula AU 1°.pdf';
    }
    else if ($id_formulario == 2) {
      $path = $pathForms . 'Carátula AU 2°.pdf';
    }
    else if ($id_formulario == 3) {
      $path = $pathForms . 'Formulario AU 1°.pdf';
    }
    else if ($id_formulario == 4) {
      $path = $pathForms . 'Formulario AU 2°.pdf';
    }
    else if ($id_formulario == 5) {
      $path = $pathForms . 'Formulario Finalización AU.pdf';
    }
    else if ($id_formulario == 6){
      $path = $pathForms . 'RVE N° 983.pdf';
    }
    else return "Formulario invalido";
    if(!file_exists($path)) return "Formulario no cargado en el sistema, informar al administrador";
    return response()->file($path);
  }

  public function generarSolicitudAutoexclusion($id){
    $autoexcluido = AE\Autoexcluido::find($id);
    $estado = $autoexcluido->estado;
    $datos_estado = array(
      'fecha_ae' => date("d/m/Y",strtotime($estado->fecha_ae)),
      'fecha_vencimiento' => date("d/m/Y", strtotime($estado->fecha_vencimiento)),
      'fecha_cierre' => date("d/m/Y", strtotime($estado->fecha_cierre_ae))
    );
    $encuesta = $autoexcluido->encuesta;
    $es_primer_ae = $autoexcluido->es_primer_ae;
    if (is_null($encuesta)) {
      $encuesta = array(
        'id_frecuencia_asistencia' => -1,
        'veces' => -1,
        'tiempo_jugado' => -1,
        'como_asiste' => -1,
        'id_juego_preferido' => -1,
        'club_jugadores' => -1,
        'autocontrol_juego' => -1,
        'recibir_informacion' => -1,
        'medio_recibir_informacion' => -1
      );
    }
    $contacto = $autoexcluido->contacto;

    $view = View::make('Autoexclusion.planillaFormularioAE1', compact('autoexcluido', 'encuesta', 'datos_estado', 'contacto','es_primer_ae'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("solicitud_autoexclusion_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function generarConstanciaReingreso($id){
    $ae = AE\Autoexcluido::find($id);
    $datos = array(
      'apellido_y_nombre' => $ae->apellido . ', ' . $ae->nombres,
      'dni' => $ae->nro_dni,
      'domicilio_completo' => $ae->domicilio . ' ' . $ae->nro_domicilio,
      'localidad' => ucwords(strtolower($ae->nombre_localidad)),
      'fecha_cierre_definitivo' => date('d/m/Y', strtotime($ae->estado->fecha_cierre_ae))
    );

    $view = View::make('Autoexclusion.planillaConstanciaReingreso', compact('datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("constancia_reingreso_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function generarSolicitudFinalizacionAutoexclusion($id){
    $ae = AE\Autoexcluido::find($id);
    $estado = $ae->estado;
    $datos = array(
      'fecha_revocacion_ae' => date("d/m/Y", strtotime($estado->fecha_revocacion_ae)),
      'fecha_vencimiento'   => date("d/m/Y", strtotime($estado->fecha_vencimiento))
    );

    $view = View::make('Autoexclusion.planillaFormularioFinalizacionAE', compact('ae', 'datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 820, "Dirección General de Casinos y Bingos / Caja de Asistencia Social - Lotería de Santa Fe", $font, 8, array(0,0,0));
    $dompdf->getCanvas()->page_text(525, 820, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, array(0,0,0));

    return $dompdf->stream("solicitud_finalizacion_autoexclusion_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function cambiarEstadoAE($id,$id_estado){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $ae = AE\Autoexcluido::find($id);
    $estado = $ae->estado;
    Validator::make(
      ['id_autoexcluido' => $id],
      ['id_autoexcluido' => 'required|integer|exists:ae_datos,id_autoexcluido'], 
      array(), self::$atributos)->after(function($validator) use ($usuario,$estado,$ae,$id_estado){
        if(  !($usuario->es_superusuario || $usuario->es_administrador) 
          || is_null($estado) 
          || !$usuario->usuarioTieneCasino($estado->id_casino))
        {
          $validator->errors()->add('rol', 'No puede realizar esa acción');
          return;
        }
        if($ae->estado_transicionable != $id_estado){
          $validator->errors()->add('id_autoexcluido','No puede cambiar a ese estado');
        }
    })->validate();
    if($id_estado == 4){//Si es fin por AE guardo la fecha que lo pidio revocar.
      $estado->fecha_revocacion_ae = date('Y-m-d');
    }
    $estado->id_nombre_estado = $id_estado;
    $estado->save();
    return 1;
  }

  public function migrar(){
    DB::transaction(function(){
      $aes = DB::table('ae')->get();

      $rel_cas = [
        1 => 2,
        2 => 3,
        3 => 1
      ];
  
      $rel_prov = [
       1 => 'Buenos Aires',
       2 => 'Catamarca',
       3 => 'Chaco',
       4 => 'Chubut',
       5 => 'Ciudad Autónoma de Buenos Aires',
       6 => 'Córdoba',
       7 => 'Corrientes',
       8 => 'Entre Ríos',
       9 => 'Formosa',
       10 => 'Jujuy',
       11 => 'La Pampa',
       12 => 'La Rioja',
       13 => 'Mendoza',
       14 => 'Misiones',
       15 => 'Neuquen',
       16 => 'Río Negro',
       17 => 'Salta',
       18 => 'San Juan',
       19 => 'San Luis',
       20 => 'Santa Cruz',
       21 => 'Santa Fe',
       22 => 'Santiago del Estero',
       23 => 'Tierra del Fuego, Antártida e Islas del Atlántico Sur',
       24 => 'Tucumán'
      ];
      
      foreach($aes as $n => $ae){
        dump($n);
        $aebd = new AE\Autoexcluido;
        $aebd->apellido         = $ae->apellido;
        $aebd->nombres          = $ae->nombres;
        $aebd->nombre_localidad = $ae->localidad;
        $aebd->nombre_provincia = $rel_prov[$ae->provincia_id];
        $aebd->nro_domicilio    = $ae->nrodomic;
        $aebd->piso             = null;
        $aebd->dpto             = null;
        $aebd->codigo_postal    = null;
        $aebd->nro_dni          = $ae->dni;
        $aebd->telefono         = $ae->tel;
        $aebd->correo           = $ae->email;
        $aebd->domicilio        = $ae->domicilio;
        if($ae->sexo == 'M')      $aebd->id_sexo = 0;
        elseif ($ae->sexo == 'F') $aebd->id_sexo = 1;
        else                      $aebd->id_sexo = -1;
        $aebd->fecha_nacimiento = $ae->fecha_nacimiento;
        $aebd->id_ocupacion     = is_null($ae->id_ocup)? 12 : $ae->id_ocup;
        $aebd->id_estado_civil  = is_null($ae->id_estcivil)? 6 : $ae->id_estcivil;
        $aebd->id_capacitacion  = is_null($ae->id_capacitacion)? 6 : $ae->id_capacitacion;
        $aebd->save();

        $contactobd = new AE\ContactoAE;
        $contactobd->id_autoexcluido  = $aebd->id_autoexcluido;
        $contactobd->nombre_apellido  = $ae->nombrefam;
        $contactobd->domicilio        = null;
        $contactobd->nombre_localidad = null;
        $contactobd->nombre_provincia = null;
        $contactobd->telefono         = $ae->telfam;
        $contactobd->vinculo          = $ae->rolfam;
        $contactobd->save();

        $estadobd = new AE\EstadoAE;
        $estadobd->id_autoexcluido     = $aebd->id_autoexcluido;
        $estadobd->id_nombre_estado    = $ae->id_estado;
        $fecha_renovacion = date('Y-m-d',strtotime($ae->fecha_ae_orig.' + 150 days'));
        $fecha_vencimiento = date('Y-m-d',strtotime($ae->fecha_ae_orig.' + 180 days'));
        $fecha_cierre_ae = date('Y-m-d',strtotime($ae->fecha_ae_orig.' + 365 days'));
        $estadobd->fecha_ae            = $ae->fecha_ae_orig;
        $estadobd->fecha_renovacion    = $fecha_renovacion;
        $estadobd->fecha_vencimiento   = $fecha_vencimiento;
        $estadobd->fecha_cierre_ae     = $fecha_cierre_ae;
        $estadobd->fecha_revocacion_ae = $ae->fecha_revoc;
        $estadobd->id_usuario          = null;
        $estadobd->id_casino           = $rel_cas[$ae->id_cas];
        $estadobd->save();

        $importacionbd = new AE\ImportacionAE;
        $importacionbd->id_autoexcluido      = $aebd->id_autoexcluido;
        $importacionbd->foto1                = $ae->foto1;
        $importacionbd->foto2                = $ae->foto2;
        $importacionbd->solicitud_ae         = $ae->sol_autoex;
        $importacionbd->solicitud_revocacion = $ae->sol_revoc;
        $importacionbd->caratula             = $ae->caratula;
        $importacionbd->save();

        $encuestabd = new AE\Encuesta;
        $encuestabd->id_autoexcluido = $aebd->id_autoexcluido;
        $encuestabd->id_juego_preferido = $ae->id_tipojuego;
        $encuestabd->id_frecuencia_asistencia = $ae->id_frecuencia;
        $encuestabd->veces = $ae->cant_frecuencia;
        $encuestabd->tiempo_jugado = $ae->horas_jug;
        $encuestabd->club_jugadores = $ae->club_jugadores == 'NO CONTESTA' ? null : $ae->club_jugadores;
        $encuestabd->juego_responsable = $ae->conoce_juresp == 'NO CONTESTA'? null : $ae->conoce_juresp;
        $encuestabd->autocontrol_juego = $ae->decision_autoex == 'NO CONTESTA'? null : $ae->decision_autoex;
        $encuestabd->recibir_informacion = $ae->recib_info == 'NO CONTESTA'? null : $ae->recib_info;
        $encuestabd->medio_recibir_informacion = $ae->medio_recib_info;
        $encuestabd->como_asiste = $ae->id_tipoasist == 3? null : $ae->id_tipoasist;
        $encuestabd->observacion = null;
        $encuestabd->save();
      }
    });
  }
}