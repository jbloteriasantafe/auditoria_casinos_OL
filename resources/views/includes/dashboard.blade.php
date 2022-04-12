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
            $get_svg = function($nombre){
              return file_get_contents(resource_path()."/assets/svg/$nombre.svg");
            };
            $gestion_hijos = [
              'Usuarios' => [
                'icono' => $get_svg('usuario'),
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
                'icono' => $get_svg('expedientes'),
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
                'icono' => $get_svg('maquinas'),
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
                'icono' => $get_svg('usuario'),
                'link' => 'http://'.($_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR']).':8000/autoexclusion',
                'link_style' => 'color: #aaf;text-decoration: underline;',
                'algun_permiso' => ['ver_seccion_ae_alta'],
              ]
            ];
            $auditoria_hijos = [
              'Importación Diaria' => [
                'icono' => $get_svg('expedientes'),
                'link' => '/importaciones',
                'algun_permiso' => ['ver_seccion_importaciones'],
              ],
              'Validación' => [
                'icono' => '<i class="fa fa-check-square"></i>',
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
                'icono' => $get_svg('informes'),
                'hijos' => [
                  'Plataforma' => [
                    'link' => '/informePlataforma',
                    'algun_permiso' => ['ver_seccion_estestadoparque'],
                  ],
                  '<span>Juegos/Jugadores</span><small>[contable]</small>' => [
                    'link' => '/informeContableJuego',
                    'algun_permiso' => ['ver_seccion_informecontable'],
                  ],
                  '<span>Jugadores</span><small>[estado]</small>' => [
                    'link' => '/informeEstadoJuegosJugadores',
                    'algun_permiso' => ['ver_seccion_informecontable'],//@TODO: crear un permiso especializado
                  ]
                ]
              ]
            ];
            $estadisticas_hijos = [
              'Informes' => [
                'icono' => $get_svg('informes'),
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
                'icono' => $get_svg('tablero_control'),
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
            //Ademas pone los valores por defecto de cada campo, simplificando el codigo de los componentes
            {
              $promover_permisos_y_asignar_defaults = function($k,&$opciones) use (&$promover_permisos_y_asignar_defaults){
                $opciones['algun_permiso'] = $opciones['algun_permiso'] ?? [];
                $opciones['divli_style']   = $opciones['divli_style']   ?? '';
                $opciones['link_style']    = $opciones['link_style']    ?? '';
                $opciones['link']          = $opciones['link']          ?? '#';
                $opciones['icono']         = $opciones['icono']         ?? '';
                $opciones['hijos']         = $opciones['hijos']         ?? [];
                $hijos = &$opciones['hijos'];
                if(!is_null($hijos)) foreach($hijos as $op => &$h){
                  $h = $promover_permisos_y_asignar_defaults($op,$h);
                  $opciones['algun_permiso'] = array_merge($opciones['algun_permiso'],$h['algun_permiso']);
                }
                return $opciones;
              };
              $aux = ['hijos' => $opciones];
              $opciones = $promover_permisos_y_asignar_defaults('',$aux)['hijos'];
            }

            {
              $ac = AuthenticationController::getInstancia();//Saco las opciones segun los permisos que tenga
              $filtrar_permisos = function($k,&$opciones) use (&$filtrar_permisos,$ac,$id_usuario){
                $nuevos_hijos = [];
                $hijos = &$opciones['hijos'];
                if(!is_null($hijos)) foreach($hijos as $op => $datos){
                  $permisos = $datos['algun_permiso'] ?? [];
                  if(count($permisos) != 0 && !$ac->usuarioTieneAlgunPermiso($id_usuario,$permisos)) continue;
                  $nuevos_hijos[$op] = $filtrar_permisos($op,$datos);
                }
                $opciones['hijos'] = $nuevos_hijos;
                return $opciones;
              };
              $aux = ['hijos' => $opciones];
              $opciones = $filtrar_permisos('',$aux)['hijos'];
            }
            ?>
          </nav>
          @component('includes.barraMenuPrincipal',[
            'usuario' => UsuarioController::getInstancia()->quienSoy()['usuario'],
            'tiene_imagen' => UsuarioController::getInstancia()->tieneImagen(),
            'opciones' => $opciones ?? [],
          ])
          @endcomponent
        </header>
        @php
          //Fisico
          /*
          $casinos_ids = $usuario['usuario']->casinos->map(function($c){return $c->id_casino;})->toArray();
          $cas_random = $casinos_ids[array_rand($casinos_ids,1)];
          $tarjetas = [1 => '/img/tarjetas/banner_MEL.jpg',2 => '/img/tarjetas/banner_CSF.jpg',3 => '/img/tarjetas/banner_ROS.jpg'];
          $tarjeta = $tarjetas[$cas_random] ?? null;
          $tarjeta_css = $tarjeta? "background-image: url($tarjeta);height: 13vh;background-size: contain;background-repeat: space" : null;
          */
          //Online
          $tarjeta_css = null;
        @endphp
        @component('includes.menuDesplegable',[
          'tarjeta_css' => $tarjeta_css,
          'opciones' => $opciones ?? [],
        ])
        @endcomponent

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
    @section('scripts')
    @show
  </body>
</html>
