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

    <link rel="icon" type="image/png" sizes="32x32" href="/img/logos/favicon.ico">
    <title>CASOnline - Lotería de Santa Fe</title>

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

    <style>
      #barraMenuPrincipal {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
      }
      #barraMenuPrincipal .card {
        height: inherit;
        display: flex;/*Esto es para que si hay varias entradas en el card se organizen una despues de la otra (icono de ayuda)*/
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        align-content: center;
      }
      #barraMenuPrincipal a,#btn-ayuda{
        color: white;
        background-color: rgb(38, 50, 56);
        border-right: 1px solid rgb(73, 102, 121);
        border-bottom: 1px solid rgb(73, 102, 121);
        text-decoration: none;
        width: 100%;/*Estos hacen que ocupen todo el div*/
        height: 100%;
        display: flex;/*Estos centran verticalmente*/
        flex-direction: column;
        justify-content: center;
        text-align: center;
      }
      #barraMenuPrincipal div:hover,#barraMenuPrincipal a:hover,
      #barraMenuPrincipal div:focus,#barraMenuPrincipal a:focus {
        background-color: #384382 !important;
        cursor: pointer;
      }
      #barraMenuPrincipal img {
        width: 1em;
      }
      #barraMenuPrincipal svg {
        max-height: 2em;
        fill: white;
        stroke: white;
      }
      #barraMenuPrincipal .dropdown-submenu {
        position: relative;
      }
      #barraMenuPrincipal .dropdown-menu {
        padding: 0px;
        margin: 0px;
        border: 0px;
        width: 100%;
      }
      #barraMenuPrincipal .dropdown-submenu .dropdown-menu {
        top: 10%;
        left: 100%;
      }
    </style>

    @section('estilos')
    @show

  </head>
  <body>

    <!-- Contenedor de toda la página -->
    <div class="contenedor">
        <!-- Barra superior  -->
        <header>
          <nav>
            <?php
            $gestion_hijos = [
              'Usuarios' => [
                'hijos' => [
                  'Gestionar usuarios' => [
                    'link' => '/usuarios',
                  ],
                  'Roles y permisos' => [
                    'link' => '/roles',
                  ],
                  'Log de actividades' => [
                    'link' => '/logActividades',
                  ],
                ]
              ],
              'Expedientes' => [
                'hijos' => [
                  'Gestionar expedientes' => [
                    'link' => '/expedientes',
                  ],
                  'Resoluciones' => [
                    'link' => '/resoluciones',
                  ],
                  'Notas' => [
                    'link' => '/notas',
                  ],
                  'Disposiciones' => [
                    'link' => '/disposiciones',
                  ],
                ]
              ],
              'Juegos' => [
                'hijos' => [
                  'Juegos' => [
                    'link' => '/juegos',
                  ],
                  'Certificados Software' => [
                    'link' => '/certificadoSoft',
                  ],
                ]
              ],
              'Autoexclusión' => [
                'link' => 'http://'.$_SERVER['REMOTE_ADDR'].':8000/autoexclusion',
                'style' => 'color: #aaf;text-decoration: underline;'
              ]
            ];
            $auditoria_hijos = [
              'Importación Diaria' => [
                'link' => '/importaciones',
              ],
              'Validación' => [
                'hijos' => [
                  'Producidos' => [
                    'link' => '/producidos',
                  ],
                  'Beneficios' => [
                    'link' => '/beneficios',
                  ],
                ]
              ],
              'Informes Auditoria' => [
                'hijos' => [
                  'Plataforma' => [
                    'link' => '/informePlataforma',
                  ],
                  'Juegos/Jugadores' => [
                    'link' => '/informeContableJuego',
                  ],
                ]
              ]
            ];
            $estadisticas_hijos = [
              'Informes' => [
                'hijos' => [
                  'Juegos' => [
                    'link' => '/informesJuegos',
                  ],
                  'Generales' => [
                    'link' => '/informesGenerales',
                  ],
                ]
              ],
              'Tablero' => [
                'hijos' => [
                  'Generales' => [
                    'deshabilitado' => true,
                    'link' => '/estadisticasGenerales',
                  ],
                  'Por Plataforma' => [
                    'deshabilitado' => true,
                    'link' => '/estadisticasPorCasino',
                  ],
                  'Interanuales' => [
                    'deshabilitado' => true,
                    'link' => '/interanuales',
                  ],
                ]
              ]
            ];
            $opciones = [
              'Plataformas' => [
                'deshabilitado' => true,
                'link' => '/casinos',
                'style' => 'width: 12%',
              ],
              'Gestion' => [
                'hijos' => $gestion_hijos,
                'style' => 'width: 12%',
              ],
              'Auditoria' => [
                'hijos' => $auditoria_hijos,
                'style' => 'width: 12%',
              ],
              'Estadisticas' => [
                'hijos' => $estadisticas_hijos,
                'style' => 'width: 12%',
              ],
            ];
            //https://www.w3schools.com/Bootstrap/tryit.asp?filename=trybs_ref_js_dropdown_multilevel_css&stacked=h
            $parseOpcion = function($opciones,$primer_nivel = false) use (&$parseOpcion){
              $lista = "";
              foreach($opciones as $op => $datos){//Reemplazar las 3 (o 4) opciones por algun templateado/view??
                $style = $datos['style'] ?? '';
                $link = ($datos['deshabilitado'] ?? false)? '#' : ($datos['link'] ?? '#');
                if(count($datos['hijos'] ?? []) == 0){
                  $color = $link == '#' ? 'color: grey' : '';
                  $open  = "<li><a tabindex='-1' href='$link' style='$color;$style;'>";
                  $close = '</a></li>';
                  if($primer_nivel){
                    $open = "<div class='card' style='$style'><a tabindex='-1' href='$link' style='$color;'>";
                    $close = '</a></div>';
                  }
                  $lista .= "$open $op $close";
                }
                else if ($primer_nivel){
                  $submenu = $parseOpcion($datos['hijos']);
                  $lista .= "<div class='card dropdown' style='$style'>
                    <a class='dropdown-toggle' data-toggle='dropdown'>$op</a>
                    <ul class='dropdown-menu'>
                    $submenu
                    </ul>
                  </div>";
                }
                else {
                  $submenu = $parseOpcion($datos['hijos']);
                  $lista .= "<li class='dropdown-submenu'>
                    <a class='desplegar-menu' tabindex='-1' href='#' style='$style'>$op</a>
                    <ul class='dropdown-menu'>
                    $submenu
                    </ul>
                  </li>";
                }
              }
              return $lista;
            }
            ?>
            <ul id="barraMenuPrincipal">
              <div class="card">
                <?php $fondoOL = '/img/tarjetas/banner_OL'.(rand(0,1) + 1).'.jpg'; ?>
                <a tabindex="-1" href="/inicio"><!--style="background-image: url({{$fondoOL}});background-size: cover;"-->
                  <span><img src="/img/logos/logo_nuevo2_bn.png" style="width: 10em;"></span>
                </a>
              </div>
              <div class="card">
                <a tabindex="-1" href="/configCuenta">
                  <?php
                  $img_user = UsuarioController::getInstancia()->tieneImagen() ? '/usuarios/imagen' : '/img/img_user.jpg';
                  ?>
                  <span>
                    <img src='{{$img_user}}' class='img-circle' style="width: 2.5em;">
                  </span>
                  {{$usuario['usuario']->nombre}} 
                  {{'@'.$usuario['usuario']->user_name}}
                </a>
              </div>
              <div id="btn-ayuda" class="card" style="width:9%;background-color: rgb(61, 106, 41);">
                @section('headerLogo')
                @show
                <span class="tituloSeccionPantalla" style="text-align: center;">---</span>
              </div>
              {!! $parseOpcion($opciones ?? [],true) !!}
              <div class="card dropdown" style="width: 5%;"  onclick="markNotificationAsRead('{{count($usuario['usuario']->unreadNotifications)}}')">
                <a class="dropdown-toggle" type="button" data-toggle="dropdown">
                  <span>
                    <i  class="far fa-bell"></i>
                    <span class="badge" style="background: white;color: black;text-align: center;">{{count($usuario['usuario']->unreadNotifications)}}</span>
                  </span>
                </a>
                <ul class="dropdown-menu" style="max-height: 300px; overflow-y:auto; width:350px;">
                  @forelse ($usuario['usuario']->unreadNotifications as $notif)
                  <div style="background: #E6E6E6;">
                      @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                  </div>
                  @empty
                    @forelse($usuario['usuario']->lastNotifications() as $notif)
                      @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                    @empty
                    <a href="#" style="display: inline-block;width: 100%;">No hay nuevas Notificaciones</a>
                    @endforelse
                  @endforelse
                  </ul>
              </div>
              @if($usuario['usuario']->es_superusuario || $usuario['usuario']->es_auditor)
              <div class="card" style="width:3%;">
                <a id="ticket" tabindex="-1" href="#">
                  <span><i id="ticket" class="far fa-envelope"></i></span>
                </a>
              </div>
              @endif
              <div class="card" style="width:3%;">
                <a id="calendario" tabindex="-1" href="/calendario_eventos">
                  <span><i  class="far fa-fw fa-calendar-alt"></i></span>
                </a>
              </div>
              <div class="card" style="width:3%;">
                <a class="etiquetaLogoSalida"  tabindex="-1" href="#">
                  <span><img src="/img/logos/salida.png"></span>
                </a>
              </div>
            </ul>
          </nav>
        </header>

        <!-- Menú lateral -->
        <aside>
              <div class="contenedorMenu">
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

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_usuarios','ver_seccion_roles_permisos','ver_seccion_logs_actividades']))
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

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_juegos','ver_seccion_glisoft']))
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
                        
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_ae_alta']))
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

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,[
                        'ver_seccion_importaciones','ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estestadoparque','ver_seccion_informecontable'
                         ]))
                        <div class="separadoresMenu">AUDITORÍA</div>
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_importaciones'))
                          <li>
                            <div id="opcImportaciones" class="opcionesHover" 
                            onclick="window.location = window.location.protocol + '//' + window.location.host + '/importaciones'" href="#" style="cursor: pointer;"
                            >
                              <span class="flechita"><i class="fa fa-angle-right"></i></span>
                              <span class="icono" style="padding-bottom: 56px;">
                                @svg('expedientes','iconoExpedientes')
                              </span>
                              <span>Importacion Diaria</span>
                            </div>
                          </li>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_producidos','ver_seccion_beneficios']))
                          <li>
                            <div class="opcionesHover" data-target="#validacion" data-toggle="collapse" href="#">
                              <span class="flechita"><i class="fa fa-angle-right"></i></span>
                              <span class="icono" style="color: #868b90;font-size: 160%;">
                                <i class="fa fa-check-square"></i>
                              </span>
                              <span>Validación</span>
                            </div>
                          </li>
                          <ul class="subMenu2 collapse" id="validacion">
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
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_estestadoparque','ver_seccion_informecontable']))
                          <li>
                            <div class="opcionesHover" data-target="#informesAuditoria" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('informes','iconoInformes')
                                </span>
                                <span>Informes Auditoria</span>
                            </div>
                            <ul class="subMenu2 collapse" id="informesAuditoria">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estestadoparque'))
                              <li>
                                <div id="opcInformePlataforma" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/informePlataforma'" href="#" style="cursor: pointer;"
                                >
                                  <span>Plataforma</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_informecontable'))
                              <li>
                                <div id="opcInformesContableJuego" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeContableJuego'" href="#" style="cursor: pointer;"
                                >
                                  <span>Juegos/Jugadores</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                          </li>
                          @endif
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['estadisticas_generales','estadisticas_por_casino','estadisticas_interanuales',
                                                                                                            'informes_mtm','informes_bingos','informes_mesas']))
                        <div class="separadoresMenu">ESTADÍSTICAS</div>
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
                              <li>
                                <div id="opcInformesGenerales" class="opcionesHover" 
                                onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesGenerales'" href="#" style="cursor: pointer;"
                                >
                                  <span>Generales</span>
                                </div>
                              </li>
                            </ul>
                        </li>
                        @endif
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

        @if($usuario['usuario']->es_superusuario || $usuario['usuario']->es_auditor)
        <div id="modalTicket" class="modal fade in" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header" style="font-family: Robot-Black;background-color: #6dc7be;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <i class="fa fa-times"></i>
                </button>
                <h3 class="modal-title">Crear ticket</h3>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-12">
                    <input class="form-control ticket-asunto" placeholder="Asunto"/>
                  </div>
                </div>
                <br>
                <div class="row">
                  <div class="col-md-12">
                    <textarea class="form-control ticket-mensaje" placeholder="Mensaje"></textarea>
                  </div>
                </div>
                <br>
                <div class="row">
                  <h5>Adjunto</h5>
                  <input type="file" class="form-control-file ticket-adjunto" multiple/>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary ticket-enviar">Enviar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              </div>
            </div>
          </div>
        </div>
        @endif
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

        $(document).ready(function(){
          $('a.desplegar-menu').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            $(this).next('ul').toggle();
          });
          function clearMenus() {//basado en el archivo bootstrap.js
            $('#barraMenuPrincipal').find('.dropdown-backdrop').remove()
            $('#barraMenuPrincipal').find('[data-toggle="dropdown"]').each(function () {
              var $this         = $(this)
              var $parent       = $this.parent();
              var relatedTarget = { relatedTarget: this }

              if (!$parent.hasClass('open')) return

              const event = $.Event('hide.bs.dropdown', relatedTarget);
              $parent.trigger(event);

              if (event.isDefaultPrevented()) return

              $this.attr('aria-expanded', 'false')
              const event2 = $.Event('hidden.bs.dropdown', relatedTarget);
              $parent.removeClass('open').trigger(event2);
            });
          };
          $('#barraMenuPrincipal').focusout(function(e){
            $(this).find('.dropdown-submenu').find('.dropdown-menu').hide();
            clearMenus();
          });
        });
    </script>

    <script src="/js/modalTicket.js" charset="utf-8"></script>

    @section('scripts')
    @show

  </body>
</html>
