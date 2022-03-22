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
    <link rel="stylesheet" href="/web-fonts-with-css/css/fontawesome-all.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">
    <link rel="stylesheet" href="/css/menuHeader_y_Desplegable.css?2">

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
                'link' => 'http://'.($_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR']).':8000/autoexclusion',
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
                  '<span>Juegos/Jugadores</span><small>[contable]</small>' => [
                    'link' => '/informeContableJuego',
                    'algun_permiso' => ['ver_seccion_informecontable'],
                  ],
                  '<span>Juegos/Jugadores</span><small>[estado]</small>' => [
                    'link' => '/informeEstadoJuegosJugadores',
                    'algun_permiso' => ['ver_seccion_informecontable'],//@TODO: crear un permiso especializado
                  ]
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
                    $open  = "<div class='card' style='$divli_style'><a tabindex='-1' href='$link' style='$link_style'>";
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
                <a class="dropdown-toggle no_abrir_en_mouseenter" type="button" data-toggle="dropdown">
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
    <script type="text/javascript" src="/js/modalTicket.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/menuHeader_y_Desplegable.js?3" charset="utf-8"></script>
    @section('scripts')
    @show
  </body>
</html>
