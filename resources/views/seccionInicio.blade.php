@extends('includes.dashboard')
@section('headerLogo')
    <span class="etiquetaLogoInicio">@svg('home', 'iconoHome')</span>
@endsection
@section('contenidoVista')

@section('estilos')
    <style media="screen">
        .seccionVisitada {
            text-align: center;
            height: 22.47vh;
        }

        .seccionVisitada a {
            text-decoration: none;
        }

        .seccionVisitada:hover i {
            opacity: 1;
        }

        .seccionVisitada:hover .icon {
            transform: scale(1.3);
            top: 0px;
        }

        .seccionVisitada i {
            color: #aaa;
            display: block;
            opacity: 0;
        }

        .seccionVisitada .icon {
            stroke: #aaa;
            position: relative;
            top: -1.68vh;
        }

        .seccionVisitada h6 {
            font-family: Roboto-Condensed;
            font-size: 2.0225vh;
        }

        /* ICONOS */
        .seccionVisitada .iconoMaquinas {
            width: 4.166vw;
            height: 8.989vh;
        }

        .seccionVisitada .iconoUsuarios {
            width: 2.708vw;
            height: 5.843vh;
            margin: 1.573vh 0vw 1.573vh 0vw;
        }

        .seccionVisitada .iconoExpedientes {
            width: 3.229vw;
            height: 6.966vh;
            margin: 1.011vh 0vw 1.011vh 0vw;
        }

        .titulo_ala_highchart {
            color: #333333;
            font-size: 18px;
            font-weight: bold;
            fill: #333333;
            font-family: Roboto-Regular;
        }

        .texto_ala_celda {
            font-size: 14px;
            font-weight: bold;
            font-family: Roboto-Regular;
            color: #aaa !important;
        }

        .celda {
            width: 14.2857%;
            text-align: center;
            margin: 0px;
            padding: 0px;
            display: inline-block;
            float: left;
        }
    </style>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
?>

@section('contenidoVista')

    <div class="row">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>ÚLTIMAS SECCIONES VISITADAS</h4>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                @foreach ($ultimas_visitadas as $visitada)
                                    <div class="col-md-3 seccionVisitada">
                                        @continue(empty($visitada->ruta))
                                        <a href="{{ $visitada->ruta }}">
                                            <i class="fa fa-share fa-2x"></i>
                                            {{-- El switch en laravel no es break por default, queda mas simple con elseif --}}
                                            @if ($visitada->ruta == 'casinos')
                                                @svg('casinos', 'iconoCasinosModif')
                                            @elseif($visitada->ruta == 'usuarios')
                                                @svg('usuario', 'iconoUsuarios')
                                            @elseif($visitada->ruta == 'roles')
                                                @svg('usuario', 'iconoUsuarios')
                                            @elseif($visitada->ruta == 'configCuenta')
                                                @svg('usuario', 'iconoUsuarios')
                                            @elseif($visitada->ruta == 'logActividades')
                                                @svg('usuario', 'iconoUsuarios')
                                            @elseif($visitada->ruta == 'expedientes')
                                                @svg('expedientes', 'iconoExpedientes')
                                            @elseif($visitada->ruta == 'resoluciones')
                                                @svg('expedientes', 'iconoExpedientes')
                                            @elseif($visitada->ruta == 'disposiciones')
                                                @svg('expedientes', 'iconoExpedientes')
                                            @elseif($visitada->ruta == 'informePlataforma')
                                                @svg('informes', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'informeContableJuego')
                                                @svg('informes', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'informeEstadoJuegos')
                                                @svg('informes', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'informeEstadoJugadores')
                                                @svg('informes', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'juegos')
                                                @svg('maquinas', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'certificadoSoft')
                                                @svg('maquinas', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'importaciones')
                                                @svg('maquinas', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'producidos')
                                                @svg('maquinas', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'beneficios')
                                                @svg('maquinas', 'iconoMaquinas')
                                            @elseif($visitada->ruta == 'estadisticasGenerales')
                                                @svg('tablero_modif', 'iconoTableroModif')
                                            @elseif($visitada->ruta == 'estadisticasPorCasino')
                                                @svg('tablero_modif', 'iconoTableroModif')
                                            @elseif($visitada->ruta == 'interanuales')
                                                @svg('tablero_control', 'iconoTableroModif')
                                            @elseif($visitada->ruta == 'informesJuegos')
                                                @svg('informes', 'iconoInformesModif')
                                            @else
                                                <!-- AGREGAR LA RUTA ARRIBA -->
                                                @svg('casinos', 'iconoCasinosModif')
                                            @endif
                                        </a>
                                        <h6>{{ $visitada->seccion }}</h6>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div> <!-- panel -->
                </div>
            </div>
            @if ($usuario->tienePermiso('estadisticas_generales'))
                @section('iniciopanel')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <div class="row">
                                        <br>
                                    @endsection
                                    @section('finpanel')
                                        <br>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endsection

                @yield('iniciopanel')
                <div id="divBeneficiosAnuales" class="col-md-6"></div>
                <div id="divJugadoresAnuales" class="col-md-6"></div>
                @yield('finpanel')

                @yield('iniciopanel')
                <div class="row">
                    <div id="divBeneficiosMensuales">
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div id="divJugadoresMensuales">
                    </div>
                </div>
                @yield('finpanel')



                @yield('iniciopanel')
                <div class="row">
                    <div id="divPdevAUnAnio">
                    </div>
                </div>
                @yield('finpanel')

                @yield('iniciopanel')
                <div class="row">
                    <div id="divHoldAUnAnio">
                    </div>
                </div>
                <div class="row">
                    <div id="divHoldNeto">
                    </div>
                </div>
                @yield('finpanel')

                @yield('iniciopanel')
                <div class="row">
                    <div id="divARPU">
                    </div>
                </div>
                @yield('finpanel')

                @yield('iniciopanel')
                <div class="row">
                    <div id="divDistribucionJugadoresProvincias" class="col-md-6">
                    </div>
                    <div id="divDistribucionJugadoresDepartamentos" class="col-md-6">
                    </div>
                </div>
                @yield('finpanel')

                @yield('iniciopanel')
                <div class="row">
                    <div id="divCalendarioActividadesCompletadas">
                    </div>
                </div>
                @yield('finpanel')

                <div id="moldeMes" style="width: 25%;border-top: 1px solid #ddd;border-right: 1px solid #ddd;" hidden>
                    <div class="mesTitulo texto_ala_celda celda" style="width: 100%;">
                        MES AÑO
                    </div>
                </div>
                <datalist 
                  id="estadosDias"
                  data-fecha-minima="{{ $estadosDias['fecha_minima']->format('Y-m-d') }}"
                  data-fecha-maxima="{{ $estadosDias['fecha_maxima']->format('Y-m-d') }}"
                  data-tbls="{{ json_encode($estadosDias['tbls']) }}"
                >
                  @foreach ($estadosDias['estadosDias'] as $d => $e)
                  <option 
                    data-fecha="{{ $d }}" 
                    data-detalle="{{ json_encode($e->detalle) }}" 
                    data-porcentaje="{{ $e->porcentaje }}"
                  >
                    {{ $e->importados }}/{{ $e->posibles }}
                  </option>
                  @endforeach
                </datalist>
            @endif
        </div>
    </div>
    <meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| AYUDA INICIO</h3>
@endsection
@section('contenidoAyuda')
    <div class="col-md-12">
        <h5>Tarjetas de Inicio</h5>
        <p>En esta sección se podrá ver una ayuda rápida al calendario con actividades previstas y cargadas en el sistema,
            incluyendo feriados y días no hábiles.
            Además de la situación actual del clima en Santa Fe y los últimos accesos a los cuales cada usuario visitó por
            última vez.</p>
    </div>
@endsection

@section('scripts')
    <script src="js/highcharts_11_3_0/highcharts.js"></script>
    <script src="js/highcharts_11_3_0/highcharts-more.js"></script>
    <script src="js/highcharts_11_3_0/highcharts-3d.js"></script>
    <script src="js/highcharts_11_3_0/exporting.js"></script>
    <script src="js/highcharts_11_3_0/export-data.js"></script>
    <script src="js/highcharts_11_3_0/accessibility.js"></script>
    <script src="js/seccionInicio.js?9"></script>
@endsection
