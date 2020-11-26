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
// Route::get('/',function(){
//     return view('inicioNuevo');
// });
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
  Route::get('/obtenerTurno/{id}','CasinoController@obtenerTurno');
  Route::post('/modificarCasino','CasinoController@modificarCasino');
  Route::delete('/eliminarCasino/{id}','CasinoController@eliminarCasino');
  Route::get('/get', 'CasinoController@getAll');
  Route::get('/getCasinos', 'CasinoController@getParaUsuario');
  Route::get('/getMeses/{id_casino}', 'CasinoController@meses');
  Route::get('/getFichas','CasinoController@getFichas');
});

/***********
Expedientes
***********/
Route::get('expedientes','ExpedienteController@buscarTodo')->middleware('tiene_permiso:ver_seccion_expedientes');
Route::get('expedientes/obtenerExpediente/{id}','ExpedienteController@obtenerExpediente');
Route::post('expedientes/guardarExpediente','ExpedienteController@guardarExpediente');
Route::post('expedientes/modificarExpediente','ExpedienteController@modificarExpediente');
Route::delete('expedientes/eliminarExpediente/{id}','ExpedienteController@eliminarExpediente');
Route::post('expedientes/buscarExpedientes','ExpedienteController@buscarExpedientes');
Route::get('expedientes/buscarExpedientePorNumero/{busqueda}','ExpedienteController@buscarExpedientePorNumero');
Route::get('expedientes/buscarExpedientePorCasinoYNumero/{id_casino}/{busqueda}','ExpedienteController@buscarExpedientePorCasinoYNumero');
Route::get('expedientes/tiposMovimientos/{id_expediente}','ExpedienteController@tiposMovimientos');
Route::get( 'expedientes/obtenerMovimiento/{id}','LogMovimientoController@obtenerMovimiento');
Route::post('expedientes/movimientosSinExpediente','LogMovimientoController@movimientosSinExpediente');
/***********
Usuarios
***********/
Route::get('usuarios','UsuarioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_usuarios');
Route::post('usuarios/buscar','UsuarioController@buscarUsuarios');
Route::get('usuarios/buscar/{id}','UsuarioController@buscarUsuario');
Route::get('usuarios/quienSoy' ,'UsuarioController@quienSoy');
Route::post('usuarios/guardarUsuario','UsuarioController@guardarUsuario');
Route::post('usuarios/modificarUsuario','UsuarioController@modificarUsuario');
Route::delete('usuarios/eliminarUsuario','UsuarioController@eliminarUsuario');
Route::get('usuarios/imagen','UsuarioController@leerImagenUsuario');
Route::get('usuarios/buscarUsuariosPorNombre/{nombre}','UsuarioController@buscarUsuariosPorNombre');
Route::get('usuarios/buscarUsuariosPorNombre/{nombre}/relevamiento/{id_relevamiento}','UsuarioController@buscarUsuariosPorNombreYRelevamiento');
Route::get('usuarios/buscarUsuariosPorNombreYCasino/{id_casino}/{nombre}','UsuarioController@buscarUsuariosPorNombreYCasino');
Route::get('usuarios/usuarioTienePermisos','AuthenticationController@usuarioTienePermisos');
Route::post('usuarios/reestablecerContraseña','UsuarioController@reestablecerContraseña');
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
Route::get('permiso/getAll','PermisoController@getAll');
Route::get('rol/getAll','RolController@getAll');
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
  Route::get('/obtenerJuego/{id?}','JuegoController@obtenerJuego');
  Route::post('/guardarJuego','JuegoController@guardarJuego');
  Route::post('/modificarJuego','JuegoController@modificarJuego');
  Route::delete('/eliminarJuego/{id}','JuegoController@eliminarJuego');
  Route::post('/buscar','JuegoController@buscarJuegos');
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
Route::get('notas/consulta-nota/{id}','NotaController@consultaMovimientosNota');
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
});

//Lo necesitan los auditores
Route::get('maquinas/getMoneda/{nro}','MTMController@getMoneda');
//Estos por si las moscas lo pongo ... Son todos GET por lo menos
//Es muy posible que usuarios que no tienen el permiso ver_seccion_maquinas las use
Route::get('maquinas/obtenerMTM/{id}', 'MTMController@obtenerMTM');
Route::get('maquinas/obtenerMTMEnCasino/{casino}/{id}', 'MTMController@obtenerMTMEnCasino');

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
Route::delete('producidos/eliminarProducido/{id}','ProducidoController@eliminarProducido');
Route::delete('beneficios/eliminarBeneficio/{id}','BeneficioController@eliminarBeneficio');
Route::get('importaciones','ImportacionController@buscarTodo')->middleware('tiene_permiso:ver_seccion_importaciones');
Route::post('importaciones/buscar','ImportacionController@buscar');
Route::get('importaciones/{id_casino}/{fecha_busqueda?}/{orden?}','ImportacionController@estadoImportacionesDeCasino');
Route::post('importaciones/importarProducido','ImportacionController@importarProducido');
Route::post('importaciones/importarBeneficio','ImportacionController@importarBeneficio');
Route::get('importaciones/obtenerVistaPrevia/{tipo_importacion}/{id}','ImportacionController@obtenerVistaPrevia');
Route::post('importaciones/previewBeneficios','ImportacionController@previewBeneficios');
Route::get('cotizacion/obtenerCotizaciones/{mes}','CotizacionController@obtenerCotizaciones');
Route::post('cotizacion/guardarCotizacion','CotizacionController@guardarCotizacion');

/*******************
PRODUCIDOS-AJUSTES PRODUCIDO
******************/
Route::get('producidos','ProducidoController@buscarTodo')->middleware('tiene_permiso:ver_seccion_producidos');
Route::get('producidos/buscarProducidos','ProducidoController@buscarProducidos');
Route::get('producidos/generarPlanilla/{id_producido}','ProducidoController@generarPlanilla');
Route::get('producidos/checkEstado/{id}','ProducidoController@checkEstado');
Route::post('producidos/guardarAjusteProducidos','ProducidoController@guardarAjuste');
Route::get('producidos/ajustarProducido/{id_maquina}/{id_producidos}','ProducidoController@datosAjusteMTM');
Route::get('producidos/maquinasProducidos/{id_producido}','ProducidoController@ajustarProducido');

/**********
 Beneficios
***********/

Route::get('beneficios','BeneficioController@buscarTodo')->middleware('tiene_permiso:ver_seccion_beneficios');
Route::post('beneficios/buscarBeneficios','BeneficioController@buscarBeneficios');
Route::post('beneficios/obtenerBeneficiosParaValidar','BeneficioController@obtenerBeneficiosParaValidar');
Route::post('beneficios/ajustarBeneficio','BeneficioController@ajustarBeneficio');
Route::post('beneficios/validarBeneficios','BeneficioController@validarBeneficios');
Route::post('beneficios/validarBeneficiosSinProducidos','BeneficioController@validarBeneficiosSinProducidos');
Route::get('beneficios/generarPlanilla/{id_casino}/{id_tipo_moneda}/{anio}/{mes}','BeneficioController@generarPlanilla');
Route::post('beneficios/cargarImpuesto','BeneficioController@cargarImpuesto');

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

Route::get('informeEstadoParque' , 'informesController@obtenerInformeEstadoParque');
Route::get('informesMTM/obtenerEstadoParqueDeCasino/{id_casino}','informesController@obtenerInformeEstadoParqueDeParque');

Route::get('informeContableMTM','informesController@buscarTodoInformeContable');//carga pagina
Route::get('obtenerInformeContableDeMaquina/{id_maquina}','informesController@obtenerInformeContableDeMaquina');//informe ultimos 30 dias

//seccion informes mtm (pestaña informes)
Route::get('informesMTM','informesController@obtenerUltimosBeneficiosPorCasino');
Route::get('informesMTM/generarPlanilla/{year}/{mes}/{id_casino}/{id_tipo_moneda}','informesController@generarPlanilla');

/*calendario*/
Route::get('calendario_eventos',function(){
    return view('calendar');
});


//nuevo buscador de usuarios para la seccion de USUARIOS
Route::get('usuarios/get/{id}','UsuarioController@buscarUsuarioSecUsuarios');