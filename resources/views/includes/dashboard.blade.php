 <?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="_token" content="{!! csrf_token() !!}"/>

    <link rel="icon" type="image/png" sizes="32x32" href="/img/logos/favicon.png">
    <title>CAS - Lotería de Santa Fe</title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrap-col-xl.css" rel="stylesheet">

    <link href="/css/estilosBotones.css" rel="stylesheet">
    <link href="/css/estilosModal.css" rel="stylesheet">
    <link href="/css/estilosFileInput.css" rel="stylesheet">
    <link href="/css/estilosPopUp.css" rel="stylesheet">
    <link href="/css/table-fixed.css" rel="stylesheet">
    <link href="/css/importacionFuentes.css" rel="stylesheet">
    <link href="/css/tarjetasMenues.css" rel="stylesheet">
    <link href="/css/flaticon.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="/css/style.css">

    <link rel="stylesheet" type="text/css" href="/css/component.css" />

    <!-- Custom Fonts -->
    <!-- <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css" > -->

    <!-- Animaciones de los LINKS en MENU -->
    <link rel="stylesheet" href="/css/animacionesMenu.css">

    <!-- Animaciones de alta -->
    <link rel="stylesheet" href="/css/animacionesAlta.css">

    <!-- Animación de carga de datos -->
    <link rel="stylesheet" href="/css/loadingAnimation.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">

    <!-- Estilos de imagenes en SVG -->
    <link rel="stylesheet" href="/css/estilosSVG.css">
    <link rel="stylesheet" href="/css/estiloDashboard.css">
    <link rel="stylesheet" href="/css/estiloDashboard_xs.css">

    <!-- Custom Fonts -->
    <!-- <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css"> -->
    <link rel="stylesheet" href="/web-fonts-with-css/css/fontawesome-all.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">

    <link rel="stylesheet" href="/css/perfect-scrollbar.css">



    @section('estilos')
    @show

  </head>
  <body>

    <!-- Contenedor de toda la página -->
    <div class="contenedor">

        <!-- Barra superior  -->
        <header>
            <nav>@section('headerLogo')
                 @show
              <h2 class="tituloSeccionPantalla"></h2>

              <ul class="nav nav-tabs nav-justified juegosSec" id="juegosSec" style=" width:70%;" hidden="true">
                <li id="b_juego" ><a href="#pant_juegos"  style="font-family:Roboto-condensed;font-size:20px; ">Juegos</a></li>
                <li id="b_sector"><a href="#pant_sectores"  style="font-family:Roboto-condensed;font-size:20px;">Sectores</a></li>
              </ul>
              <ul class="nav nav-tabs nav-justified cierreApertura" id="cierreApertura" style=" width:70%;" hidden="true">
                <li id="b_apertura" ><a href="#pant_aperturas"  style="font-family:Roboto-condensed;font-size:20px; ">Aperturas</a></li>
                <li id="b_cierre"><a href="#pant_cierres"  style="font-family:Roboto-condensed;font-size:20px;">Cierres</a></li>
             </ul>
              <ul class="nav nav-tabs nav-justified informesMes" id="informesMes" style=" width:70%;" hidden="true">
                <li id="gestInformes" ><a href="#gestionInfoMes"  style="font-family:Roboto-condensed;font-size:20px; ">Informes Mensuales</a></li>
                <li id="graficos"><a href="#graficosMes"  style="font-family:Roboto-condensed;font-size:20px;">Gráficos Mensuales</a></li>
              </ul>

              <ul class="nav nav-tabs nav-justified pestCanon" id="pestCanon" style=" width:70%;" hidden="true">
                <li id="canon1" ><a href="#pant_canon_pagos"  style="font-family:Roboto-condensed;font-size:20px; ">Detalles Canon y Pagos</a></li>
                <li id="canon2"><a href="#pant_canon_valores"  style="font-family:Roboto-condensed;font-size:20px;">Actualización Valores</a></li>
              </ul>

             <ul class="nav nav-tabs nav-justified pestImportaciones" id="pestImportaciones" style=" width:70%;" hidden="true">
               <li id="imp_diaria" ><a href="#pest_diaria"  style="font-family:Roboto-condensed;font-size:20px; ">Importaciones Diarias</a></li>
               <li id="imp_mensual"><a href="#pest_mensual"  style="font-family:Roboto-condensed;font-size:20px;">Importaciones Mensuales</a></li>
            </ul>

              <a href="#" id="btn-ayuda"><i class="iconoAyuda glyphicon glyphicon-question-sign" style="padding-top: 12px; padding-left: 10px; !important"></i></a>
              <ul class="opcionesBarraSuperior" style=" width:20%;float:right;">

                  <?php
                    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
                  ?>
                  <li class="dropdown" id="marcaLeido" onclick="markNotificationAsRead('{{count($usuario['usuario']->unreadNotifications)}}')" style="right:1%;">
                    <!-- <a href="#" class="iconoBarraSuperior"><i class="fa fa-times"></i></a> -->
                    <!--Icono de notificaciones -->

                  <a href="#" id="notificaciones" style="text-decoration:none;position:relative;top:1px;" class="dropdown-toggle" data-toggle="dropdown" type="button">
                    <i class="far fa-bell fa-2x" style="margin-right:5px;color:#333;"></i>
                    <span class="badge" style="font-size:20px; background:#333333;height:30px;padding-top:5px;position:relative;top:-5px;">{{count($usuario['usuario']->unreadNotifications)}}</span>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-right" style="max-height: 300px; overflow-y:auto; width:350px;">
                    @forelse ($usuario['usuario']->unreadNotifications as $notif)
                    <div style="background: #E6E6E6;">
                        @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                    </div>

                    @empty
                      @forelse($usuario['usuario']->lastNotifications() as $notif)
                        @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                      @empty
                        <a href="#" style="font-size:20px;">No hay nuevas Notificaciones</a>
                      @endforelse
                    @endforelse
                  </ul>

                  </li>
                  <li>
                    <a id="calendario" class="iconoBarraSuperior" onclick="window.location = window.location.protocol + '//' + window.location.host + '/calendario_eventos'" href="#"><i class="far fa-fw fa-calendar-alt fa-2x" style="margin-right:6px; margin-top: 1px; color: black;"></i></a>
                  </li>
                  <li>
                    <a href="#" class="etiquetaLogoSalida"><img src="/img/logos/salida_negrita.png" style="margin-top:4px; margin-right: 32px; width: 17px;"></a>
                  </li>
              </ul>
            </nav>
        </header>

        <!-- Menú lateral -->
        <aside>
            <div class="contenedorLogo">
                <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'"  href="#">
                  <img src="/img/logos/logo_nuevo_bn.png" alt="" width="48%" style="margin-top: 10px;">
                </a>
            </div>
            <!-- <div class="scrollMenu"> -->


              <div class="contenedorMenu">
                <div class="contenedorUsuario">
                  <?php
                    $plat = rand(0,1);
                    if($plat == 0){
                      echo '<div class="fondoOL1"></div>';
                    }
                    else if($plat == 1){
                      echo '<div class="fondoOL2"></div>';
                    }
                  ?>
                    <div class="infoUsuario">
                      <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#">
                        <?php
                          $tieneImagen = UsuarioController::getInstancia()->tieneImagen();
                          if($tieneImagen) {
                            echo '<img id="img_perfilBarra" src="/usuarios/imagen" class="img-circle">';
                          }
                          else {
                            echo '<img id="img_perfilBarra" src="/img/img_user.jpg" class="img-circle">';
                          }
                        ?>
                        <i id="iconConfig" class="fa fa-cog"></i>
                      </a>
                        <h3>{{$usuario['usuario']->nombre}}</h3>
                        <div class="nombreUsuario"><h4>{{'@'.$usuario['usuario']->user_name}}</h4></div>

                    </div>
                </div>

                <div class="opcionesMenu">

                    <!-- PRIMER NIVEL -->
                    <ul>
                        <div class="separadoresMenu">MENÚ</div>
                        <li>
                            <div id="opcInicio" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'" style="cursor: pointer;">
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('home','iconoHome')
                                </span>
                                <span>Inicio <small>[CASINO ONLINE]</small></span>
                            </div>
                        </li>
                        <!-- CASINOS -->
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_casinos'))
                        <li>
                            <div id="opcCasino" class="opcionesHover" 
                            @if(false)
                            onclick="window.location = window.location.protocol + '//' + window.location.host + '/casinos'" href="#" style="cursor: pointer;"
                            @else
                            style="color: grey;"
                            @endif
                            >
                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('casinos','iconoCasinos')
                                </span>
                                <span>Plataformas</span>
                            </div>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_usuarios','ver_seccion_roles_permisos','ver_seccion_casinos']))
                        <div class="separadoresMenu">GESTIÓN</div>
                        <li>
                            <div id="barraUsuarios" class="opcionesHover" data-target="#usuarios" data-toggle="collapse">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('usuario','iconoUsuarios')
                                </span>
                                <span>Usuarios</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="usuarios">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_usuarios'))
                              <li>
                                <div id="opcGestionarUsuarios" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/usuarios'" href="#" style="cursor: pointer;"
                                >
                                  <span>Gestionar usuarios</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_roles_permisos'))
                              <li>
                                <div id="opcRolesPermisos" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/roles'" href="#" style="cursor: pointer;"
                                >
                                  <span>Roles y permisos</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_logs_actividades'))
                              <li>
                                <div id="opcLogActividades" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/logActividades'" href="#" style="cursor: pointer;"
                                >
                                  <span>Log de actividades</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif
                        <!-- EXPEDIENTES -->
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_expedientes','ver_seccion_resoluciones','ver_seccion_disposiciones']))
                        <li>
                            <div id="barraExpedientes" class="opcionesHover" data-target="#expedientes" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 54px;">
                                  @svg('expedientes','iconoExpedientes')
                                </span>
                                <span>Expedientes</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="expedientes">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
                              <li>
                                <div id="opcGestionarExpedientes" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/expedientes'" href="#" style="cursor: pointer;"
                                >
                                  <span>Gestionar expedientes</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))
                              <li>
                                <div id="opcResoluciones" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/resoluciones'" href="#" style="cursor: pointer;"
                                >
                                  <span>Resoluciones</span>
                                </div>
                              </li>
                              <li>
                                <div id="opcNotas" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/notas'" href="#" style="cursor: pointer;"
                                >
                                  <span>Notas</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))
                              <li>
                                <div id="opcDisposiciones" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/disposiciones'" href="#" style="cursor: pointer;"
                                >
                                  <span>Disposiciones</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas',
                                                                                                            'ver_seccion_formulas','ver_seccion_juegos','ver_seccion_glisoft',
                                                                                                            'ver_seccion_glihard','ver_seccion_sectores']))
                        <li>
                            <div class="opcionesHover" data-target="#gestionarJuegos" data-toggle="collapse">
                              <span class="flechita">
                                <i class="fa fa-angle-right"></i>
                              </span>
                              <span class="icono" style="padding-bottom: 56px;">
                                @svg('maquinas','iconoMaquinas')
                              </span>
                                <span>Juegos</span>
                            </div>
                              <!-- CUARTO NIVEL -->
                              <ul class="subMenu2 collapse" id="gestionarJuegos">
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_juegos'))
                                  <li>
                                    <div id="opcJuegos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/juegos'" href="#" style="cursor: pointer;">
                                      <span>Juegos</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glisoft'))
                                  <li>
                                    <div id="opcGliSoft" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/certificadoSoft'" href="#" style="cursor: pointer;">
                                      <span>Certificados Software</span>
                                    </div>
                                  </li>
                                  @endif
                              </ul>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas',
                                                                                                            'ver_seccion_formulas','ver_seccion_juegos','ver_seccion_glisoft',
                                                                                                            'ver_seccion_glihard','ver_seccion_sectores','ver_seccion_importaciones',
                                                                                                            'ver_seccion_relevamientos','ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                            'ver_seccion_beneficios','ver_planilla_layout_total','ver_planilla_layout_parcial',
                                                                                                            'ver_seccion_gestionar_movimientos','ver_seccion_relevamientos_movimientos',
                                                                                                            'ver_seccion_eventualidades','ver_seccion_eventualidades_MTM',
                                                                                                            'ver_seccion_estestadoparque','ver_seccion_estestadorelevamientos',
                                                                                                            'ver_seccion_informecontable','ver_seccion_informesector']))
                        <div class="separadoresMenu">AUDITORÍA</div>
                        <li>
                            <div id="barraJuegos" class="opcionesHover" data-target="#juegos" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('maquinas','iconoMaquinas')
                                </span>
                                <span>Juegos</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="juegos">
                              @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_estestadoparque','ver_seccion_estestadorelevamientos',
                              'ver_seccion_informecontable','ver_seccion_informesector']))
                              <li>
                                <div class="opcionesHover" data-target="#informesMTM" data-toggle="collapse" href="#">
                                  <span class="flechita">
                                    <i class="fa fa-angle-right"></i>
                                  </span>
                                  <span>Informes</span>
                                </div>

                                <!-- TERCER NIVEL -->
                                <ul class="subMenu2 collapse" id="informesMTM">
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estestadoparque'))
                                    <li>
                                      <div id="opcInformeEstadoParque" class="opcionesHover" 
                                      @if(false)
                                      onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeEstadoParque'" href="#" style="cursor: pointer;"
                                      @else
                                      style="color: grey;"
                                      @endif
                                      >
                                        <span>Plataforma</span>
                                      </div>
                                    </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_informecontable'))
                                    <li>
                                      <div id="opcInformesContableMTM" class="opcionesHover" 
                                      @if(false)
                                      onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeContableMTM'" href="#" style="cursor: pointer;"
                                      @else
                                      style="color: grey;"
                                      @endif
                                      >
                                        <span>Juegos</span>
                                      </div>
                                    </li>
                                  @endif
                                </ul>
                              </li>
                              @endif

                              <!-- Procedimientos -->
                              @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_importaciones','ver_seccion_relevamientos',
                                                                                                                  'ver_seccion_relevamientos_progresivos','ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                                  'ver_seccion_beneficios','ver_planilla_layout_total',
                                                                                                                  'ver_planilla_layout_parcial','ver_seccion_prueba_juegos',
                                                                                                                  'ver_seccion_prueba_progresivos','ver_seccion_gestionar_movimientos',
                                                                                                                  'ver_seccion_relevamientos_movimientos','ver_seccion_eventualidades',
                                                                                                                  'ver_seccion_eventualidades_MTM']))
                              <li>
                                <div id="gestionarProcedimientos" class="opcionesHover" data-target="#procedimientos" data-toggle="collapse" href="#">
                                  <span class="flechita">
                                    <i class="fa fa-angle-right"></i>
                                  </span>
                                  <span>Procedimientos</span>
                                </div>
                                <!-- TERCER NIVEL -->
                                @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_relevamientos_progresivos',
                                                                                                                    'ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                                    'ver_seccion_beneficios']))
                               <ul class="subMenu2 collapse" id="procedimientos">
                                   <li>
                                     <div id="gestionarContadores" class="opcionesHover" data-target="#contadores" data-toggle="collapse" href="#">
                                       <span class="flechita">
                                         <i class="fa fa-angle-right"></i>
                                       </span>
                                       <span>Producidos</span>
                                     </div>

                                     <!-- CUARTO NIVEL -->
                                     <ul class="subMenu3 collapse" id="contadores">
                                       @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_importaciones'))
                                         <li>
                                           <div id="opcImportaciones" class="opcionesHover" 
                                           onclick="window.location = window.location.protocol + '//' + window.location.host + '/importaciones'" href="#" style="cursor: pointer;"
                                           >
                                             <span>Importaciones</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_producidos'))
                                         <li>
                                           <div id="opcProducidos" class="opcionesHover" 
                                           onclick="window.location = window.location.protocol + '//' + window.location.host + '/producidos'" href="#" style="cursor: pointer;"
                                           >
                                             <span>Producidos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_beneficios'))
                                         <li>
                                           <div id="opcBeneficios" class="opcionesHover" 
                                           onclick="window.location = window.location.protocol + '//' + window.location.host + '/beneficios'" href="#" style="cursor: pointer;"
                                           >
                                             <span>Beneficios</span>
                                           </div>
                                         </li>
                                         @endif
                                     </ul>
                                   </li>
                               </ul>
                               @endif
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_ae_alta']))
                        <div class="separadoresMenu">AUTOEXCLUSIÓN (FÍSICO)</div>
                        <li>
                          <div id="" class="opcionesHover"  href="">
                            <a 
                            href="{{'//'.$_SERVER['SERVER_NAME'].':8000/autoexclusion'}}"
                            target="_blank">
                            <span class="flechita">
                                <i class="fa fa-angle-right"></i>
                              </span>
                              <span class="icono" style="padding-bottom: 50px;">
                                @svg('usuario','iconoUsuarios')
                              </span>
                              <span>AUTOEXCLUSIÓN</span>
                            </a>
                          </div>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['estadisticas_generales','estadisticas_por_casino','estadisticas_interanuales',
                                                                                                            'informes_mtm','informes_bingos','informes_mesas']))
                        <div class="separadoresMenu">ESTADÍSTICAS</div>
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['estadisticas_generales','estadisticas_por_casino','estadisticas_interanuales']))
                        <li>
                            <div id="barraEstadisticas" class="opcionesHover" data-target="#tablero" data-toggle="collapse" href="#">
                              <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('tablero_control','iconoTableroControl')
                                </span>
                                <span>Tablero</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="tablero">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_generales'))
                              <li>
                                <div id="opcEstadisticasGenerales" class="opcionesHover" 
                                @if(false)
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasGenerales'" href="#" style="cursor: pointer;"
                                @else
                                style="color: grey;"
                                @endif
                                >
                                  <span>Generales</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_por_casino'))
                              <li>
                                <div id="opcEstadisticasPorCasino" class="opcionesHover" 
                                @if(false)
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasPorCasino'" href="#" style="cursor: pointer;"
                                @else
                                style="color: grey;"
                                @endif
                                >
                                  <span>Por Plataforma</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_interanuales'))
                              <li>
                                <div id="opcEstadisticasInteranuales" class="opcionesHover"
                                @if(false) 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/interanuales'" href="#" style="cursor: pointer;"
                                @else
                                style="color: grey;"
                                @endif
                                >
                                  <span>Interanuales</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'informes_mtm'))
                        <li>
                            <div id="barraInformes" class="opcionesHover" data-target="#informes" data-toggle="collapse" href="#">
                              <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('informes','iconoInformes')
                                </span>
                                <span>Informes</span>
                            </div>
                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="informes">
                              <li>
                                <div id="opcInformesJuegos" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesJuegos'" href="#" style="cursor: pointer;"
                                >
                                  <span>Juegos</span>
                                </div>
                              </li>
                            </ul>
                        </li>
                        @endif
                        @endif
                    </ul>

                </div>
                <div class="bottomMenu"></div>
              </div> <!-- contenedorMenu -->
          <!--  </div>  scrollMenu -->
        </aside>

        <!-- Vista de secciones -->
        <main class="contenedorVistaPrincipal">

          <section>
              <div class="container-fluid">
                @section('contenidoVista')
                @show

              </div>

          </section>
        </main>
              <!-- DESDE ACA -->

        <!-- NOTIFICACIÓN DE ÉXITO -->
          <!--  (*) Para que la animación solo MUESTRE (fije) el mensaje, se agrega la clase 'fijarMensaje' a #mensajeExito-->
          <!--  (*) Para que la animación MUESTRE Y OCULTE el mensaje, se quita la clase 'fijarMensaje' a #mensajeExito-->
          <!-- (**) si se quiere mostrar los botones de ACEPTAR o SALIR, se agrega la clase 'mostrarBotones' a #mensajeExito -->
          <!-- (**) para no mostrarlos, se quita la clase 'mostrarBotones' a #mensajeExito -->

        <div id="mensajeExito" class="" hidden>
            <div class="cabeceraMensaje">
              <!-- <i class="fa fa-times" style=""></i> -->
              <button type="button" class="close" style="font-size:40px;position:relative;top:10px;right:20px;"><span aria-hidden="true">×</span></button>
            </div>
            <div class="iconoMensaje">
              <img src="/img/logos/check.png" alt="imagen_check" >
            </div>
            <div class="textoMensaje" >
                <h3>ÉXITO</h3>
                <p>El CASINO fue creado con éxito.</p>
            </div>
            <div class="botonesMensaje">
                <button class="btn btn-success confirmar" type="button" name="button">ACEPTAR</button>
                <button class="btn btn-default salir" type="button" name="button">SALIR</button>
            </div>
        </div>

        <!-- Modal Error -->
        <div id="mensajeError"  hidden>
            <div class="cabeceraMensaje"></div>
            <div class="iconoMensaje">
              <img src="/img/logos/error.png" alt="imagen_error" >
            </div>
            <div class="textoMensaje" >
                <h3>ERROR</h3>
                <p>No es posible realizar la acción</p>
            </div>

        </div>

        <!-- Modal ayuda -->
        <div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #1976D2;">
                     <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     @section('tituloDeAyuda')
                     @show
                    </div>
                    <div  id="colapsado" class="collapse in">
                    <div class="modal-body modalCuerpo">
                              <div class="row">
                                @section('contenidoAyuda')
                                @show
                              </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                    </div>
                  </div>
                </div>
              </div>
        </div>
        <!-- HASTA ACA -->
    </div>


    <!-- jQuery -->
    <script src="/js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="/js/bootstrap.js"></script>

    <!-- JavaScript ajaxError -->
    <script src="/js/ajaxError.js"></script>

    <!-- JavaScript personalizado -->
    <script src="/js/barraNavegacion.js"></script>

    <!-- JavaScript de tarjetas animadas -->
    <script src="/js/anime.min.js"></script>
    <script src="/js/main.js"></script>

    <!-- TableSorter -->
    <script type="text/javascript" src="/js/jquery.tablesorter.js"></script>
    <script type="text/javascript" src="/js/iconosTableSorter.js"></script>

    <!-- Collapse JS | Controla el menú -->
    <script type="text/javascript" src="/js/collapse.js"></script>

    <!-- librerias de animate -->
    <script src="/js/createjs-2015.11.26.min.js"></script>
    <script src="/js/Animacion_logo2.js?1517927954849"></script>

    <script src="/js/perfect-scrollbar.js" charset="utf-8"></script>

    <script type="text/javascript">

        $(document).on('show.bs.collapse','.subMenu1',function(){
            $('.subMenu1').not($(this)).collapse('hide');
        });
        $(document).on('show.bs.collapse','.subMenu2',function(){
            $('.subMenu2').not($(this)).collapse('hide');
        });
        $(document).on('show.bs.collapse','.subMenu3',function(){
            $('.subMenu3').not($(this)).collapse('hide');
        });

        var ps = new PerfectScrollbar('.opcionesMenu');
    </script>

    @section('scripts')
    @show

  </body>
</html>
