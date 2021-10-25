<?php
use App\Http\Controllers\UsuarioController;

/*NOTIF*/
Route::get('/marcarComoLeidaNotif',function(){
  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
  $usuario['usuario']->unreadNotifications->markAsRead();
});

/***********
Index
***********/
Route::get('/',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('seccionInicio' ,['ultimas_visitadas' =>$usuario->secciones_recientes]);
});
Route::get('login',function(){
    return view('index');
});
Route::get('inicio',function(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    return view('seccionInicio' ,['ultimas_visitadas' =>$usuario->secciones_recientes]);
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
  Route::get('/','UsuarioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_usuarios');
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
Route::group(['prefix' => 'juegos','middleware' => 'tiene_permiso:ver_seccion_juegos'], function () {
  Route::get('/','JuegoController@buscarTodo');
  Route::get('/{id}','JuegoController@buscarTodo');
  Route::get('/obtenerLogs/{id}','JuegoController@obtenerLogs');
  Route::get('/obtenerJuego/{id?}','JuegoController@obtenerJuego');
  Route::post('/guardarJuego','JuegoController@guardarJuego');
  Route::post('/modificarJuego','JuegoController@modificarJuego');
  Route::delete('/eliminarJuego/{id}','JuegoController@eliminarJuego');
  Route::post('/buscar','JuegoController@buscarJuegos');
  Route::get('/obtenerValor/{tipo}/{id}','JuegoController@obtenerValor');
  Route::post('/generarDiferenciasEstadosJuegos','JuegoController@generarDiferenciasEstadosJuegos');
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
Contadores
***********/
Route::group(['prefix' => 'importaciones','middleware' =>'tiene_permiso:ver_seccion_importaciones'],function(){
  Route::get('/','ImportacionController@buscarTodo')->middleware('tiene_permiso:ver_seccion_importaciones');
  Route::post('/buscar','ImportacionController@buscar');
  Route::get('/{id_plataforma}/{fecha_busqueda?}/{orden?}','ImportacionController@estadoImportacionesDePlataforma');
  Route::post('/importarProducido','ImportacionController@importarProducido');
  Route::post('/importarProducidoJugadores','ImportacionController@importarProducidoJugadores');
  Route::post('/importarBeneficio','ImportacionController@importarBeneficio');
  Route::post('/previewBeneficio','ImportacionController@previewBeneficio');
  Route::post('/previewProducido','ImportacionController@previewProducido');
  Route::post('/previewProducidoJugadores','ImportacionController@previewProducidoJugadores');
  Route::delete('/eliminarProducido/{id}','ProducidoController@eliminarProducido');
  Route::delete('/eliminarProducidoJugadores/{id}','ProducidoController@eliminarProducidoJugadores');
  Route::delete('/eliminarBeneficioMensual/{id}','BeneficioMensualController@eliminarBeneficioMensual');
});

Route::get('cotizacion/obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
Route::post('cotizacion/guardarCotizacion','CotizacionController@guardarCotizacion');

/*******************
PRODUCIDOS
******************/
Route::group(['prefix' => 'producidos','middleware' =>'tiene_permiso:ver_seccion_producidos'],function(){
  Route::get('/','ProducidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_producidos');
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
Route::group(['prefix' => 'informePlataforma'],function(){
  Route::get('/' , 'informesController@informePlataforma');
  Route::get('/obtenerEstadisticas/{id_plataforma}','informesController@obtenerEstadisticas');
  Route::get('/obtenerJuegosFaltantes/{id_plataforma}','informesController@obtenerJuegosFaltantes');
  Route::get('/obtenerAlertasJuegos/{id_plataforma}','informesController@obtenerAlertasJuegos');
  Route::get('/obtenerAlertasJugadores/{id_plataforma}','informesController@obtenerAlertasJugadores');
});

Route::group(['prefix' => 'informeContableJuego'],function(){
  Route::get('/','informesController@buscarTodoInformeContable');
  Route::get('obtenerJuegoPlataforma/{id_plataforma}/{cod_juego?}', 'informesController@obtenerJuegoPlataforma');
  Route::get('obtenerJugadorPlataforma/{id_plataforma}/{jugador?}', 'informesController@obtenerJugadorPlataforma');
  Route::get('obtenerInformeDeJuego/{id_juego}','informesController@obtenerInformeDeJuego');
  Route::get('obtenerProducidosDeJuego/{id_plataforma}/{cod_juego}/{offset?}/{size}','informesController@obtenerProducidosDeJuego');
  Route::get('obtenerProducidosDeJugador/{id_plataforma}/{jugador}/{offset?}/{size}','informesController@obtenerProducidosDeJugador');
});

//@TODO: Agregar y asignar privilegios para esta sección
Route::group(['prefix' => 'informesJuegos'],function(){
  Route::get('/','informesController@obtenerBeneficiosPorPlataforma');
  Route::get('/generarPlanilla/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}/{simplificado}','informesController@generarPlanilla');
  Route::get('/informeCompleto/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}','informesController@informeCompleto');
});

Route::group(['prefix' => 'informesGenerales'],function(){
  Route::get('/','informesController@informesGenerales');
  Route::get('/infoAuditoria/{dia}','informesController@infoAuditoria');
});

/*calendario*/
Route::get('calendario_eventos',function(){
    return view('calendar');
});

Route::post('hashearArchivo/{tipo}','ImportacionController@hashearArchivo');