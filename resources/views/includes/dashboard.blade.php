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
        justify-content: space-between;
        padding: 0px;
        margin: 0px;
        font-family: Roboto-Regular;
      }
      #barraMenuPrincipal .card {
        height: inherit;
        flex: 1;
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
        border-right:  1px solid rgba(255,255,255,0.15);
        border-bottom: 1px solid rgba(255,255,255,0.15);
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
      #botonMenuDesplegable {
        color: #fff;
        background-color: rgb(38, 50, 56);
        border-color: rgb(0,0,0,0.5);
      }
      #menuDesplegable {
        background-color: #263238;
        color: #fff;
        font-family: Roboto-Regular;
        font-size: 1.25em;
      }
      #menuDesplegable ul {
        box-shadow: inset 0 0 0 100vw rgba(255,255,255,0.04);
        padding-left:  0px;
        margin-left:   10px;
        border-left:   3px solid #185891db;
        border-right:  3px solid #00000000;
        border-top:    2px solid #00000000;
        border-bottom: 2px solid #00000000;
        list-style-type: none;
      }
      #menuDesplegable li {
        text-align: left;
        padding: 2px;
      }
      #menuDesplegable .enlace {
        text-decoration: none;
        color: rgba(190,190,255,0.85);
        border-bottom: 1px solid rgba(255,255,255,0.15);
        text-align: center;
      }
      #menuDesplegable .enlace > a, #menuDesplegable .menu_con_opciones > span, #menuDesplegable .menu_con_opciones_desplegado > span,#menuDesplegable .desactivado > span{
        display: block;
        width: 100%;
      }
      #menuDesplegable .desactivado {
        text-align: center;
        color: rgba(255,255,255,0.7);
        background: repeating-linear-gradient(45deg,
          rgba(  0,  0,  0,0.05),
          rgba(255,255,255,0.05) 5px,
          rgba(  0,  0,  0,0.05) 5px,
          rgba(255,255,255,0.05) 5px
        );
      }
      #menuDesplegable .menu_con_opciones{
        background-color: #0e5e5661;
        text-align: center;
      }
      #menuDesplegable .menu_con_opciones > ul{
        display: none;
      }
      #menuDesplegable .opcion_actual{
        color: white;
        background: rgb(61, 106, 41);
      }
      #menuDesplegable a {
        color: white;
        text-decoration: none;
      }
      #menuDesplegable .menu_con_opciones > span {
        font-weight: bold;
      }
      #menuDesplegable .desactivado > span {
        font-style: italic;
      }
      #menuDesplegable .enlace > a:hover, #menuDesplegable .menu_con_opciones > span:hover, #menuDesplegable .menu_con_opciones_desplegado > span:hover,#menuDesplegable .desactivado > span:hover{
        cursor: pointer;
        background-color: rgba(19, 24, 54,0.2);
      }
      #menuDesplegable .menu_con_opciones_desplegado > span{
        border-bottom: 2px solid rgb(61, 106, 41);
      }
      #menuDesplegable > .menu_con_opciones_desplegado{/*Lo deshabilito para el primer nivel*/
        border-bottom: unset;
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
                    'algun_permiso' => ['ver_seccion_usuarios'],
                  ],
                  'Roles y permisos' => [
                    'link' => '/roles',
                    'algun_permiso' => ['ver_seccion_roles_permisos'],
                  ],
                  'Log de actividades' => [
                    'link' => '/logActividades',
                    'algun_permiso' => ['ver_seccion_logs_actividades'],
                  ],
                ]
              ],
              'Expedientes' => [
                'hijos' => [
                  'Gestionar expedientes' => [
                    'link' => '/expedientes',
                    'algun_permiso' => ['ver_seccion_expedientes'],
                  ],
                  'Resoluciones' => [
                    'link' => '/resoluciones',
                    'algun_permiso' => ['ver_seccion_resoluciones'],
                  ],
                  'Notas' => [
                    'link' => '/notas',
                    'algun_permiso' => [],//@TODO: Sin permiso O_o ??
                  ],
                  'Disposiciones' => [
                    'link' => '/disposiciones',
                    'algun_permiso' => ['ver_seccion_disposiciones'],
                  ],
                ]
              ],
              'Juegos' => [
                'hijos' => [
                  'Juegos' => [
                    'link' => '/juegos',
                    'algun_permiso' => ['ver_seccion_juegos'],
                  ],
                  'Certificados Software' => [
                    'link' => '/certificadoSoft',
                    'algun_permiso' => ['ver_seccion_glisoft'],
                  ],
                ]
              ],
              'Autoexclusión' => [
                'link' => 'http://'.$_SERVER['REMOTE_ADDR'].':8000/autoexclusion',
                'link_style' => 'color: #aaf;text-decoration: underline;',
                'algun_permiso' => ['ver_seccion_ae_alta'],
              ]
            ];
            $auditoria_hijos = [
              'Importación Diaria' => [
                'link' => '/importaciones',
                'algun_permiso' => ['ver_seccion_importaciones'],
              ],
              'Validación' => [
                'hijos' => [
                  'Producidos' => [
                    'link' => '/producidos',
                    'algun_permiso' => ['ver_seccion_producidos'],
                  ],
                  'Beneficios' => [
                    'link' => '/beneficios',
                    'algun_permiso' => ['ver_seccion_beneficios'],
                  ],
                ]
              ],
              'Informes Auditoria' => [
                'hijos' => [
                  'Plataforma' => [
                    'link' => '/informePlataforma',
                    'algun_permiso' => ['ver_seccion_estestadoparque'],
                  ],
                  'Juegos/Jugadores' => [
                    'link' => '/informeContableJuego',
                    'algun_permiso' => ['ver_seccion_informecontable'],
                  ],
                ]
              ]
            ];
            $estadisticas_hijos = [
              'Informes' => [
                'hijos' => [
                  'Juegos' => [
                    'link' => '/informesJuegos',
                    'algun_permiso' => ['informes_mtm'],
                  ],
                  'Generales' => [
                    'link' => '/informesGenerales',
                    'algun_permiso' => ['informes_mtm'],
                  ],
                ]
              ],
              'Tablero' => [
                'hijos' => [
                  'Generales' => [
                    //'link' => '/estadisticasGenerales',
                    'link_style' => 'color: grey;',
                    'algun_permiso' => ['estadisticas_generales'],
                  ],
                  'Por Plataforma' => [
                    //'link' => '/estadisticasPorCasino',
                    'link_style' => 'color: grey;',
                    'algun_permiso' => ['estadisticas_por_casino'],
                  ],
                  'Interanuales' => [
                    //'link' => '/interanuales',
                    'link_style' => 'color: grey;',
                    'algun_permiso' => ['estadisticas_interanuales'],
                  ],
                ]
              ]
            ];
            $opciones = [
              'Plataformas' => [//ver_seccion_casinos
                //'link' => '/casinos',
                'link_style' => 'color: grey;',
                'algun_permiso' => ['ver_seccion_casinos'],
              ],
              'Gestion' => [
                'hijos' => $gestion_hijos,
              ],
              'Auditoria' => [
                'hijos' => $auditoria_hijos,
              ],
              'Estadisticas' => [
                'hijos' => $estadisticas_hijos,
              ],
            ];
            //Copia los permisos necesarios de los hijos a los padres, simplifica el array de $opciones bastante. Solo es necesario indicar la opcion
            $promover_permisos = function($k,&$opciones) use (&$promover_permisos){
              $opciones['algun_permiso'] = $opciones['algun_permiso'] ?? [];//Lo inicializo si no tiene
              $hijos = &$opciones['hijos'];
              if(!is_null($hijos)) foreach($hijos as $op => &$h){
                $h = $promover_permisos($op,$h);
                $opciones['algun_permiso'] = array_merge($opciones['algun_permiso'],$h['algun_permiso']);
              }
              return $opciones;
            };
            {
              $aux = ['hijos' => $opciones];
              $opciones = $promover_permisos('',$aux)['hijos'];
            }
            $ac = AuthenticationController::getInstancia();
            $parseOpcion = function($opciones,$primer_nivel = false) use (&$parseOpcion,$ac,$id_usuario){
              $lista = "";
              foreach($opciones as $op => $datos){
                $permisos    = $datos['algun_permiso'] ?? [];
                if(count($permisos) != 0 && !$ac->usuarioTieneAlgunPermiso($id_usuario,$permisos)) continue;
                
                $divli_style = $datos['divli_style'] ?? '';
                $link_style  = $datos['link_style']  ?? '';
                $link        = $datos['link']        ?? '#';
                
                //Reemplazar las strings por algun templateado/view??
                //https://www.w3schools.com/Bootstrap/tryit.asp?filename=trybs_ref_js_dropdown_multilevel_css&stacked=h
                if(count($datos['hijos'] ?? []) == 0){
                  $open  = "<li style='$divli_style'><a tabindex='-1' href='$link' style='$link_style;'>";
                  $close = '</a></li>';
                  if($primer_nivel){
                    $open = "<div class='card' style='$divli_style'><a tabindex='-1' href='$link' style='$link_style'>";
                    $close = '</a></div>';
                  }
                  $lista .= "$open $op $close";
                }
                else if ($primer_nivel){
                  $submenu = $parseOpcion($datos['hijos']);
                  $lista .= "<div class='card dropdown' style='$divli_style'>
                    <a class='dropdown-toggle' data-toggle='dropdown' style='$link_style'>$op</a>
                    <ul class='dropdown-menu'>
                    $submenu
                    </ul>
                  </div>";
                }
                else {
                  $submenu = $parseOpcion($datos['hijos']);
                  $lista .= "<li class='dropdown-submenu' style='$divli_style'>
                    <a class='desplegar-menu' tabindex='-1' href='#' style='$link_style'>$op</a>
                    <ul class='dropdown-menu'>
                    $submenu
                    </ul>
                  </li>";
                }
              }
              return $lista;
            };
            $parseOpcionDesplegable = function($opciones) use (&$parseOpcionDesplegable,$ac,$id_usuario){
              $lista = "";
              foreach($opciones as $op => $datos){
                $permisos    = $datos['algun_permiso'] ?? [];
                if(count($permisos) != 0 && !$ac->usuarioTieneAlgunPermiso($id_usuario,$permisos)) continue;
                if(isset($datos['link'])){
                  $link  = $datos['link'];
                  $lista.= "<li class='enlace'><a href='$link'>$op</a></li>";
                }
                else if(!isset($datos['hijos']) || count($datos['hijos']) == 0){
                  $lista.= "<li class='desactivado'><span>$op</span></li>";
                }
                else{
                  $lista.="<li class='menu_con_opciones'><span>$op</span>";
                  $lista.="<ul>";
                  $lista.=$parseOpcionDesplegable($datos['hijos']);
                  $lista.="</ul>";
                  $lista.="</li>";
                }
              }
              return $lista;
            };
            ?>
            <ul id="barraMenuPrincipal">
              <div class="card" style="width: 8vw; flex: unset;">
                <?php $fondoOL = '/img/tarjetas/banner_OL'.(rand(0,1) + 1).'.jpg'; ?>
                <a tabindex="-1" href="/inicio">
                  <span><img src="/img/logos/logo_nuevo2_bn.png" style="width: 8vw;"></span>
                </a>
              </div>
              <div class="card" style="width: 8vw; flex: unset;">
                <a tabindex="-1" href="/configCuenta">
                  <?php
                  $img_user = UsuarioController::getInstancia()->tieneImagen() ? '/usuarios/imagen' : '/img/img_user.jpg';
                  ?>
                  <span>
                    <img src='{{$img_user}}' class='img-circle' style="width: 2vw;">
                  </span>
                  {{$usuario['usuario']->nombre}} 
                  {{'@'.$usuario['usuario']->user_name}}
                </a>
              </div>
              <div id="btn-ayuda" class="card" style="background-color: rgb(61, 106, 41);">
                @section('headerLogo')
                @show
                <span class="tituloSeccionPantalla" style="text-align: center;">---</span>
              </div>
              {!! $parseOpcion($opciones ?? [],true) !!}
              <div class="card dropdown" style="width: 5%;flex: unset;"  onclick="markNotificationAsRead('{{count($usuario['usuario']->unreadNotifications)}}')">
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
              <div class="card" style="width:5%;flex: unset;">
                <a id="ticket" tabindex="-1" href="#">
                  <span><i id="ticket" class="far fa-envelope"></i></span>
                </a>
              </div>
              @endif
              <div class="card" style="width:5%;flex: unset;">
                <a id="calendario" tabindex="-1" href="/calendario_eventos">
                  <span><i  class="far fa-fw fa-calendar-alt"></i></span>
                </a>
              </div>
              <div class="card" style="width:5%;flex: unset;">
                <a class="etiquetaLogoSalida"  tabindex="-1" href="#">
                  <span><img src="/img/logos/salida.png"></span>
                </a>
              </div>
            </ul>
          </nav>
        </header>
        <div style="width:100%;position: absolute;z-index: 3;">
          <aside id="menuDesplegable" style="height: 100vh;width: 15%;float: left;overflow-y: scroll;" hidden>
            <ul class="menu_con_opciones_desplegado" style="margin-top: 5%;">
            {!! $parseOpcionDesplegable($opciones ?? []) !!}
            </ul>
          </aside>
          <div style="float: left;">
            <button id="botonMenuDesplegable" type="button" class="btn" 
              data-toggle="#menuDesplegable,#oscurecerContenido,#botonDerecha,#botonIzquierda" 
              style="z-index: 4;position: absolute;">
              <i id="botonDerecha" class="fa fa-fw fa-solid fa-arrow-right"></i>
              <i id="botonIzquierda" class="fa fa-fw fa-solid fa-arrow-left" style="display: none;"></i>
            </button>
          </div>
          <div id="oscurecerContenido" style="position:absolute;z-index: 3;height: 100%;left: 15%;width: 100%;float:left;background: rgba(0,0,0,0.2);" hidden>
            &nbsp;
          </div>
        </div>
        <?php $menu_costado = false; ?>
        @if($menu_costado)
        <!-- Menú lateral -->
        <aside>
              <div class="contenedorMenu">
                 <div class="opcionesMenu">
                    <!-- PRIMER NIVEL -->
                    <ul>                        
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
                        @endif
                    </ul>
                </div>
                <div class="bottomMenu"></div>
              </div> <!-- contenedorMenu -->
          <!--  </div>  scrollMenu -->
        </aside>
        @endif

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

        @if($menu_costado)
        var ps = new PerfectScrollbar('.opcionesMenu');
        @endif

        $(document).ready(function(){
          $('#barraMenuPrincipal a.desplegar-menu').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            const submenu = $(this).next('ul');
            $(this).closest('ul.dropdown-menu')//voy para el menu de arriba
            .find('ul.dropdown-menu').not(submenu).hide();//escondo todos los submenues menos el propio
            submenu.toggle();//Toggleo el submenu
          });
          $(document).on('hidden.bs.dropdown','.dropdown',function(e){
            //Escondo todos los submenues cuando se esconde un menu de 1er nivel
            $(this).find('li.dropdown-submenu').find('ul.dropdown-menu').hide();
          });
          $(document).on('click','#menuDesplegable .menu_con_opciones > span,#menuDesplegable .menu_con_opciones_desplegado > span',function(e){
            if($(this).parent().hasClass('menu_con_opciones_desplegado')){//Si esta desplegado solo escondo todo lo por debajo
              //Submenues
              $(this).parent().find('.menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
              //Padre
              $(this).parent().removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
              return;
            }
            //Si hizo click en otro menu, escondo todo y desplego el arbol hasta ahi
            //Escondo todo
            $('#menuDesplegable .menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
            //Abro todos los padres
            $(this).parents('.menu_con_opciones').removeClass('menu_con_opciones').addClass('menu_con_opciones_desplegado');
          });
          $('#botonMenuDesplegable').click(function(e){
            //Busco la opcion basado en la URL y la diferencio
            const opcion_actual = $('#menuDesplegable a').filter(function(){
              return $(this).attr('href') == ("/"+window.location.pathname.split("/")[1]);
            });
            //Lo marco como que es la opción actual mostrandose
            opcion_actual.parent().toggleClass('opcion_actual');
            //Desplego la opcion
            opcion_actual.closest('.menu_con_opciones').children('span').click();
            //Muestro el menu
            $($(this).attr('data-toggle')).toggle();
          });
        });
    </script>

    <script src="/js/modalTicket.js" charset="utf-8"></script>

    @section('scripts')
    @show

  </body>
</html>
