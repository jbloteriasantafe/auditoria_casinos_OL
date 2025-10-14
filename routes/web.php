<?php

use App\Http\Controllers\InformesGeneralesController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;

/*NOTIF*/
Route::get('/marcarComoLeidaNotif',function(){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
  $usuario['usuario']->unreadNotifications->markAsRead();
});
/***********
Index
***********/
Route::get('/',function(){
  return redirect('/inicio');
});
Route::get('login',function(){
    return view('index');
});
Route::get('inicio',function(){
  $datos_inf = ['estado_dia' => (new InformesGeneralesController)->estadosDias()];
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  $datos_inf['ultimas_visitadas'] = $usuario->secciones_recientes;
  $datos_inf['plataformas'] = \App\Plataforma::all();
  return view('seccionInicio' ,$datos_inf);
});

Route::get('configCuenta','UsuarioController@configUsuario');
Route::post('configCuenta/modificarPassword','UsuarioController@modificarPassword');
Route::post('configCuenta/modificarImagen','UsuarioController@modificarImagen');
Route::post('configCuenta/modificarDatos','UsuarioController@modificarDatos');
Route::post('logout','AuthenticationController@logout');
Route::post('login','AuthenticationController@login');
/***********
Log Actividades
***********/
Route::get('logActividades','LogController@buscarTodo')->middleware('tiene_permiso:ver_seccion_logs_actividades');
Route::get('logActividades/buscarLogActividades','LogController@buscarLogActividades');
Route::get('logActividades/obtenerLogActividad/{id}','LogController@obtenerLogActividad');

/***********
Casinos
***********/
Route::group(['prefix' => 'casinos','middleware' => 'tiene_permiso:ver_seccion_casinos'], function () {
  Route::get('/','CasinoController@buscarTodo');
  Route::get('/obtenerCasino/{id?}','CasinoController@obtenerCasino');
  Route::post('/guardarCasino','CasinoController@guardarCasino');
  Route::post('/modificarCasino','CasinoController@modificarCasino');
  Route::delete('/eliminarCasino/{id}','CasinoController@eliminarCasino');
  Route::get('/get', 'CasinoController@getAll');
  Route::get('/getMeses/{id_casino}', 'CasinoController@meses');
});

/***********
Expedientes
***********/
Route::group(['prefix' => 'expedientes','middleware' => 'tiene_permiso:ver_seccion_expedientes'], function () {
  Route::get('/','ExpedienteController@buscarTodo');
  Route::get('/obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
  Route::post('/guardarExpediente','ExpedienteController@guardarExpediente');
  Route::post('/modificarExpediente','ExpedienteController@modificarExpediente');
  Route::delete('/eliminarExpediente/{id}','ExpedienteController@eliminarExpediente');
  Route::post('/buscarExpedientes','ExpedienteController@buscarExpedientes');
});

/***********
Usuarios
***********/
Route::group(['prefix' => 'usuarios','middleware' => 'tiene_permiso:ver_seccion_usuarios'],function(){
  Route::get('/','UsuarioController@buscarTodo');
  Route::post('/buscar','UsuarioController@buscarUsuarios');
  Route::get('/buscarUsuario/{id_usuario}','UsuarioController@buscarUsuario');
  Route::post('/guardarUsuario','UsuarioController@guardarUsuario');
  Route::delete('/eliminarUsuario/{id_usuario}','UsuarioController@eliminarUsuario');
  Route::get('/reestablecerContraseña/{id_usuario}','UsuarioController@reestablecerContraseña');
});
Route::get('usuarios/quienSoy','UsuarioController@quienSoy');//Los pongo aca porque no pertenecen a la seccion Usuarios (no necesitan del permiso)
Route::get('usuarios/imagen','UsuarioController@leerImagenUsuario');

/***********
Roles y permisos
***********/
Route::post('roles/buscar','RolController@buscarRoles');
Route::post('permisos/buscar','PermisoController@buscarPermisos');
Route::get('roles','RolController@buscarTodo')->middleware('tiene_permiso:ver_seccion_roles_permisos');
Route::post('permiso/guardar','PermisoController@guardarPermiso');
Route::post('rol/guardar','RolController@guardarRol');
Route::post('rol/modificar','RolController@modificarRol');
Route::post('permiso/modificar','PermisoController@modificarPermiso');
Route::post('permiso/buscarPermisosPorRoles',"PermisoController@buscarPermisosPorRoles");
/***********
Borrar permiso
***********/
Route::delete('permiso/{id}','PermisoController@eliminarPermiso');
Route::delete('rol/{id}','RolController@eliminarRol');
Route::get('rol/{id}','RolController@getRol');
Route::get('permiso/{id}','PermisoController@getPermiso');
/***********
Juegos
***********/

Route::get('/migrarLog','JuegoController@migrarLogJuegos');
Route::get('/juegosCSV','JuegoController@juegos_csv')->middleware('tiene_permiso:ver_seccion_juegos');
Route::group(['prefix' => 'juegos','middleware' => 'tiene_permiso:ver_seccion_juegos'], function () {
  Route::get('/','JuegoController@buscarTodo');
  Route::get('/obtenerLogs/{id}','JuegoController@obtenerLogs');
  Route::get('/obtenerJuego/{id?}','JuegoController@obtenerJuego');
  Route::post('/guardarJuego','JuegoController@guardarJuego');
  Route::post('/modificarJuego','JuegoController@modificarJuego');
  Route::delete('/eliminarJuego/{id}','JuegoController@eliminarJuego');
  Route::post('/buscar','JuegoController@buscarJuegos');
  Route::post('/generarDiferenciasEstadosJuegos','JuegoController@generarDiferenciasEstadosJuegos');
  Route::get('/{id}','JuegoController@buscarTodo');
  Route::post('/parsearArchivo','JuegoController@parsearArchivo');
  Route::post('/validarCargaMasiva','JuegoController@validarCargaMasiva');
  Route::post('/guardarCargaMasiva','JuegoController@guardarCargaMasiva');
});

/***********
Disposiciones
***********/
Route::get('disposiciones','DisposicionController@buscarTodoDisposiciones')->middleware('tiene_permiso:ver_seccion_disposiciones');
Route::get('resoluciones','ResolucionController@buscarTodoResoluciones');
Route::post('resoluciones/buscar','ResolucionController@buscarResolucion')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::post('disposiciones/buscar','DisposicionController@buscarDispocisiones');
/***********
Notas
***********/
Route::get('notas','NotaController@buscarTodoNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::post('notas/buscar','NotaController@buscarNotas')->middleware('tiene_permiso:ver_seccion_resoluciones');
Route::delete('notas/eliminar-nota/{id}','NotaController@eliminarNotaCompleta');
 
//! NUEVA SECCION DE CARGA DE NOTAS PARA CASINOS
Route::group(['prefix' => 'cargar-notas','middleware' => 'tiene_permiso:ver_cargar_notas'], function () {
  Route::get('/','NotasCasino\NotasCasinoController@index');
  Route::get('/notas/archivo/{id}/{tipo}', 'NotasCasino\NotasCasinoController@descargarArchivo');
  Route::post('subir', 'NotasCasino\NotasCasinoController@subirNota');
  Route::post('modificar', 'NotasCasino\NotasCasinoController@modificarNota');
  Route::post('paginar', 'NotasCasino\NotasCasinoController@paginarNotas');
  Route::get('/juegosSeleccionados/{id}','NotasCasino\NotasCasinoController@juegosSeleccionadosById');
  Route::get('/juegos/buscar', 'NotasCasino\NotasCasinoController@buscarJuegos');
  Route::get('/juegos/buscar/{id}', 'NotasCasino\NotasCasinoController@buscarJuegoPorId');
});

//! NUEVA SECCION PARA GENERAR INFORMES TECNICO DE LAS NOTAS
Route::group(['prefix' => 'informesTecnicos','middleware'=>'tiene_permiso:ver_seccion_informes_tecnicos'],function (){
  Route::get('/','NotasCasino\InformesTecnicosController@index');
  Route::post('paginar', 'NotasCasino\InformesTecnicosController@paginarNotas');
  Route::get('/notas/archivo/{id}/{tipo}', 'NotasCasino\InformesTecnicosController@descargarArchivo');
  Route::get('/juegos/buscar', 'NotasCasino\InformesTecnicosController@buscarJuegos');
  Route::get('/juegosSeleccionados','NotasCasino\InformesTecnicosController@juegosSeleccionados');
});

/***********
    GLI soft
 ************/
Route::group(['prefix' => 'certificadoSoft','middleware' =>'tiene_permiso:ver_seccion_glisoft'],function(){
  Route::get('/','GliSoftController@buscarTodo');
  Route::get('/{id}','GliSoftController@buscarTodo');
  Route::post('/guardarGliSoft','GliSoftController@guardarGliSoft');
  Route::get('/pdf/{id}','GliSoftController@leerArchivoGliSoft');
  Route::get('/obtenerGliSoft/{id}','GliSoftController@obtenerGliSoft');
  Route::delete('/eliminarGliSoft/{id}','GliSoftController@eliminarGLI');
  Route::post('/buscarGliSoft','GliSoftController@buscarGliSofts');
  Route::post('/modificarGliSoft','GliSoftController@modificarGliSoft');
  Route::get('/buscarLabs/{codigo?}','GliSoftController@buscarLabs');
  Route::get('/obtenerLab/{id_laboratorio}','GliSoftController@obtenerLab');
  Route::get('/buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
});

//Lo necesitan los auditores
Route::get('maquinas/getMoneda/{nro}','MTMController@getMoneda');
//Estos por si las moscas lo pongo ... Son todos GET por lo menos
//Es muy posible que usuarios que no tienen el permiso ver_seccion_maquinas las use
Route::get('maquinas/obtenerMTM/{id}', 'MTMController@obtenerMTM');

/******
CALENDARIO
******/
 Route::get('calendario_eventos','CalendarioController@calendar');
 Route::post('calendario_eventos/crearEvento','CalendarioController@crearEvento');
 Route::get('calendario_eventos/buscarEventos', 'CalendarioController@buscarEventos');
 Route::post('calendario_eventos/modificarEvento', 'CalendarioController@modificarEvento');
 Route::get('calendario_eventos/eliminarEvento/{id}','CalendarioController@eliminarEvento');
 Route::get('calendario_eventos/verMes/{month}/{year}','CalendarioController@verMes');
 Route::get('calendario_eventos/getEvento/{id}', 'CalendarioController@getEvento');
 Route::get('calendario_eventos/getOpciones', 'CalendarioController@getOpciones');
 Route::post('calendario_eventos/crearTipoEvento', 'CalendarioController@crearTipoEvento');


/**********
IMPORTACIONES
***********/
Route::group(['prefix' => 'importaciones','middleware' =>'tiene_permiso:ver_seccion_importaciones'],function(){
  Route::get('/','ImportacionController@buscarTodo');
  Route::post('/buscar','ImportacionController@buscar');
  Route::get('/{id_plataforma}/{fecha_busqueda?}/{orden?}','ImportacionController@estadoImportacionesDePlataforma');
  Route::post('/importarProducido','ImportacionController@importarProducido');
  Route::post('/importarProducidoJugadores','ImportacionController@importarProducidoJugadores');
  Route::post('/importarProducidoPoker','ImportacionController@importarProducidoPoker');
  Route::post('/importarBeneficio','ImportacionController@importarBeneficio');
  Route::post('/importarBeneficioPoker','ImportacionController@importarBeneficioPoker');
  Route::post('/previewImportacion','ImportacionController@previewImportacion');
  Route::delete('/eliminarProducidoJuegos/{id}','ProducidoController@eliminarProducido');
  Route::delete('/eliminarProducidoJugadores/{id}','ProducidoController@eliminarProducidoJugadores');
  Route::delete('/eliminarProducidoPoker/{id}','ProducidoController@eliminarProducidoPoker');
  Route::delete('/eliminarBeneficioJuegos/{id}','BeneficioMensualController@eliminarBeneficioMensual');
  Route::delete('/eliminarBeneficioPoker/{id}','BeneficioMensualController@eliminarBeneficioMensualPoker');
  Route::delete('/eliminarEstadoJuegos/{id}','EstadoController@eliminarEstadoJuegos');
  Route::delete('/eliminarEstadoJugadores/{id}','EstadoController@eliminarEstadoJugadores');
});

Route::get('cotizacion/obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
Route::post('cotizacion/guardarCotizacion','CotizacionController@guardarCotizacion');

/*******************
PRODUCIDOS
******************/
Route::group(['prefix' => 'producidos','middleware' =>'tiene_permiso:ver_seccion_producidos'],function(){
  Route::get('/','ProducidoController@buscarTodo');
  Route::get('/buscarProducidos','ProducidoController@buscarProducidos');
  Route::get('/generarPlanilla/{id_producido}','ProducidoController@generarPlanilla');
  Route::get('/generarPlanillaJugadores/{id_producido_jugadores}','ProducidoController@generarPlanillaJugadores');
  Route::get('/datosDetalle/{id_detalle_producido}','ProducidoController@datosDetalle');
  Route::get('/datosDetalleJugadores/{id_detalle_producido_jugadores}','ProducidoController@datosDetalleJugadores');
  Route::get('/detallesProducido/{id_producido}','ProducidoController@detallesProducido');
  Route::get('/detallesProducidoJugadores/{id_producido_jugadores}','ProducidoController@detallesProducidoJugadores');
});

/**********
 Beneficios
***********/
Route::group(['prefix' => 'beneficios','middleware' =>'tiene_permiso:ver_seccion_beneficios'],function(){
  Route::get('/','BeneficioController@buscarTodo');
  Route::post('/buscarBeneficios','BeneficioController@buscarBeneficios');
  Route::get('/obtenerBeneficios/{id_beneficio_mensual}','BeneficioController@obtenerBeneficios');
  Route::post('/ajustarBeneficio','BeneficioController@ajustarBeneficio');
  Route::post('/validarBeneficios','BeneficioController@validarBeneficios');
  Route::get('/generarPlanilla/{id_beneficio_mensual}','BeneficioController@generarPlanilla');
  Route::get('/informeCompleto/{id_beneficio_mensual}','BeneficioController@informeCompleto');
});


/**************
 Estadisticas
**************/

Route::get('estadisticasGenerales', 'BeneficioMensualController@buscarTodoGenerales');
Route::get('estadisticasPorCasino','BeneficioMensualController@buscarTodoPorCasino');
Route::get('interanuales','BeneficioMensualController@buscarTodoInteranuales');
Route::post('estadisticasGenerales','BeneficioMensualController@cargarEstadisticasGenerales');
Route::post('estadisticasPorCasino','BeneficioMensualController@cargarSeccionEstadisticasPorCasino');
Route::post('interanuales','BeneficioMensualController@cargaSeccionInteranual');

/***********
Informes
***********/
//@TODO: Agregar y asignar privilegios para esta sección
Route::group(['prefix' => 'informePlataforma','middleware' =>'tiene_permiso:ver_seccion_estestadoparque'],function(){
  Route::get('/' , 'InformePlataformaController@informePlataforma');
  Route::get('/obtenerCantidadesPdevs','InformePlataformaController@obtenerCantidadesPdevs');
  Route::get('/obtenerJuegoFaltantesConMovimientos','InformePlataformaController@obtenerJuegoFaltantesConMovimientos');
  Route::get('/obtenerJugadorFaltantesConMovimientos','InformePlataformaController@obtenerJugadorFaltantesConMovimientos');
  Route::get('/obtenerJuegoAlertasDiarias','InformePlataformaController@obtenerJuegoAlertasDiarias');
  Route::get('/obtenerJugadorAlertasDiarias','InformePlataformaController@obtenerJugadorAlertasDiarias');
  Route::get('/obtenerEvolucionCategorias','InformePlataformaController@obtenerEvolucionCategorias');
});

Route::group(['prefix' => 'informeContableJuego','middleware' =>'tiene_permiso:ver_seccion_informecontable'],function(){
  Route::get('/','InformeContableController@informeContableJuego');
  Route::get('obtenerJuegoPlataforma/{id_plataforma}/{cod_juego?}', 'InformeContableController@obtenerJuegoPlataforma');
  Route::get('obtenerJugadorPlataforma/{id_plataforma}/{jugador?}', 'InformeContableController@obtenerJugadorPlataforma');
  Route::get('obtenerInformeDeJuego/{id_juego}','InformeContableController@obtenerInformeDeJuego');
  Route::get('obtenerProducidosDeJuego/{id_plataforma}/{cod_juego}/{offset?}/{size}','InformeContableController@obtenerProducidosDeJuego');
  Route::get('obtenerProducidosDeJugador/{id_plataforma}/{jugador}/{offset?}/{size}','InformeContableController@obtenerProducidosDeJugador');
  Route::get('{id_plataforma}/{modo}/{codigo}','InformeContableController@informeContableJuego');
});

Route::group(['prefix' => 'informeEstadoJugadores','middleware' =>'tiene_permiso:ver_seccion_informecontable'],function(){
  Route::get('/','EstadoController@informeEstadoJugadores');
  Route::post('/buscarJugadores','EstadoController@buscarJugadores');
  Route::get('/historial','EstadoController@historialJugador');
  Route::post('/importarJugadores','ImportacionController@importarJugadores');
  Route::get('/informeDemografico','EstadoController@informeDemografico');
});
Route::group(['prefix' => 'informeEstadoJuegos','middleware' =>'tiene_permiso:ver_seccion_informecontable'],function(){
  Route::get('/','EstadoController@informeEstadoJuegos');
  Route::get('/buscarJuegos','EstadoController@buscarJuegos');
  Route::get('/historial','EstadoController@historialJuego');
  Route::post('/importarEstadosJuegos','ImportacionController@importarEstadosJuegos');
  Route::post('/generarDiferenciasEstadosJuegos','EstadoController@generarDiferenciasEstadosJuegos');
});

//@TODO: Agregar y asignar privilegios para esta sección
Route::group(['prefix' => 'informesJuegos'],function(){
  Route::get('/','informesController@obtenerBeneficiosPorPlataforma');
  Route::get('/generarPlanilla/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}/{jol}','informesController@generarPlanilla');
  Route::get('/informeCompleto/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}','informesController@informeCompleto');
  Route::get('/generarPlanillaPoker/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}','informesController@generarPlanillaPoker');
});

Route::group(['prefix' => 'informesGenerales'],function(){
  Route::get('/beneficiosMensuales','InformesGeneralesController@beneficiosMensuales');
  Route::get('/beneficiosAnuales','InformesGeneralesController@beneficiosAnuales');
  Route::get('/jugadoresMensuales','InformesGeneralesController@jugadoresMensuales');
  Route::get('/jugadoresAnuales','InformesGeneralesController@jugadoresAnuales');
  Route::get('/estadosDias','InformesGeneralesController@estadosDias');
  Route::get('/infoAuditoria/{dia}','InformesGeneralesController@infoAuditoria');
  Route::get('/distribucionJugadores','InformesGeneralesController@distribucionJugadores');
});

/*calendario*/
Route::get('calendario_eventos',function(){
    return view('calendar');
});

Route::post('hashearArchivo/{tipo}','ImportacionController@hashearArchivo');
Route::get('actualizarTablaJugadoresNoEnBD','JugadoresNoBDController@actualizarTablaJugadoresNoEnBD');

Route::post('enviarTicket',function(Request $request){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
  $data = array(
    'name'      =>  $usuario->nombre,
    'email'     =>  $usuario->email,
    'subject'   =>  $request->subject,
    'message'   =>  $request->message,
    'ip'        =>  $_SERVER['REMOTE_ADDR'],
  );

  if(!empty($request->attachments)){
    $data['attachments'] = $request->attachments;
  }
  
  set_time_limit(30);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://10.1.121.25/osTicket/api/http.php/tickets.json');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'X-API-Key: 14C4C2A8161F6728C74D92C58B6DF990'));
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $result = curl_exec($ch);
  $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code != 201) return response()->json($result,422);
  $ticket_id = (int) $result;
  return $ticket_id;
});

//Lo dejo por si en algun momento se cambia estado_juego_importado a una estructura similar
//Route::get('migrarJugadores','LectorCSVController@migrarJugadores');
Route::get('regenerarResumenesMensualesProducidosJugadores','ResumenController@regenerarResumenesMensualesProducidosJugadores');

Route::group(['prefix' => 'backoffice','middleware' => 'tiene_permiso:informes_mtm'], function () {
  Route::get('/','BackOfficeController@index');
  Route::post('buscar','BackOfficeController@buscar');
  Route::post('descargar','BackOfficeController@descargar');
});