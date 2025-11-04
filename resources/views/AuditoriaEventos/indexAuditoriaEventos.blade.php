@extends('includes.dashboard')
@section('headerLogo')
    <span class="etiquetaLogoInformes">@svg('informes', 'iconoInformes')</span>
@endsection

@section('estilos')
    <link rel="stylesheet" href="/css/paginacion.css">
    <link rel="stylesheet" href="/css/lista-datos.css">
    <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
    <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/css/animacionCarga.css">
    <style>
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            display: none;
        }

        .input-error {
            border: 1px solid #e74c3c;
            background-color: #fdecea;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .contenedorVistaPrincipal {
            position: relative;
            height: auto;
            /* deja que crezca según el contenido */
            overflow-y: visible;
            /* elimina el scroll del contenedor */
        }

        .tabla-scroll {
            max-height: 500px;
            /* ajusta el alto máximo según necesites */
            overflow-y: auto;
            overflow-x: auto;
            margin-top: 2rem;
        }

        /* Mantener cabecera fija si se quiere */
        #tablaNotas thead th {
            position: sticky;
            top: 0;
            background: #f8f8f8;
            z-index: 2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            text-align: center;
        }

        #tablaNotas thead th[title] {
            cursor: help;
        }

        /* Estilo general de celdas */
        #tablaNotas td,
        #tablaNotas th {
            max-width: 140px;
            /* ancho máximo de cada celda */
            max-height: 40px;
            /* alto máximo de cada celda */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            text-align: center;
        }

        /* Tooltip usando el atributo title */
        #tablaNotas td[title] {
            cursor: help;
        }
    </style>
@endsection

@section('contenidoVista')
    <div class="row">
        <div class="col-xl-12 col-md-12">
            <a href="" id="btn-importar-evento" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center>
                        <img class="imgNuevo" src="/img/logos/CSV_white.png">
                    </center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                            <center>
                                <h5 class="txtLogo">+</h5>
                                <h4 class="txtNuevo">IMPORTAR EVENTOS</h4>
                            </center>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="row contenedorVistaPrincipal">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">LISTA DE EVENTOS IMPORTADOS</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="filtroCasino">CASINO</label>
                            <select id="filtroCasino" class="form-control" placeholder="SELECCIONAR CASINO">
                                <option value="">TODOS</option>
                                @foreach ($casinos as $casino)
                                    <option value="{{ $casino['id'] }}">{{ $casino['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroFecha">FECHA DE CARGA</label>
                            <select id="filtroFecha" class="form-control">
                                @foreach ($fechas_array as $fecha)
                                    <option value="{{ $fecha }}">{{ Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="tabla-scroll">
                        <table id="tablaNotas" class="table">
                            <thead>
                                <tr>
                                    <th class="col-sm-1 text-center" value="numero_nota" estado=""
                                        title="Número de nota">NRO. DE NOTA</th>
                                    <th class="col-sm-1 text-center" value="casino_origen" estado=""
                                        title="Casino de origen">ORIGEN
                                    </th>
                                    <th class="col-sm-1 text-center" value="nombre_evento" estado=""
                                        title="Nombre evento">NOMBRE EVENTO
                                    </th>
                                    <th class="col-sm-1 text-center" value="fecha_inicio_evento"
                                        title="Fecha de inicio del evento">FECHA INICIO EVENTO</th>
                                    <th class="col-sm-1 text-center" value="fecha_finalizacion_evento"
                                        title="Fecha de finalización del evento">FECHA FINALIZACIÓN
                                        EVENTO</th>
                                    <th class="col-sm-1 text-center" value="fecha_carga" title="Fecha de carga">FECHA CARGA
                                    </th>
                                    <th class="col-sm-1 text-center" value="estado" title="Estado del evento">ESTADO</th>
                                    <th class="col-sm-1 text-center" value="url_promo" title="URL de la promoción">
                                        URL PROMO</th>
                                    <th class="col-sm-1 text-center" value="valido" title="Valido">VÁLIDO</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla">
                                <tr class="filaTabla" style="display: none">
                                    <td class="col-sm-1 text-center numero_nota"></td>
                                    <td class="col-sm-1 text-center casino_origen"></td>
                                    <td class="col-sm-1 text-center nombre_evento"></td>
                                    <td class="col-sm-1 text-center fecha_inicio_evento"></td>
                                    <td class="col-sm-1 text-center fecha_finalizacion_evento"></td>
                                    <td class="col-sm-1 text-center fecha_carga"></td>
                                    <td class="col-sm-1 text-center estado"></td>
                                    <td class="col-sm-1 text-center url_promo"></td>
                                    <td class="col-sm-1 text-center valido"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                </div>
            </div>
        </div>
        {{-- ! MODAL DE IMPORTACION DE EVENTOS --}}
        <div class="modal fade" id="modalImporteEventos" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                        <button type="button" class="close" data-dismiss="modal">
                            <i class="fa fa-times"></i>
                        </button>
                        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse"
                            data-minimizar="true" data-target="#colapsado"
                            style="position:relative; right:20px; top:5px">
                            <i class="fa fa-minus"></i>
                        </button>
                        <h3 class="modal-title" id="myModalLabel">| IMPORTAR EVENTOS </h3>
                    </div>
                    <div id="colapsado" class="collapse in">
                        <div class="modal-body">
                            <form class="row" id="formularioImportacionEventos">
                                {{-- ! IMPORTAR EVENTOS --}}
                                <div class="col-lg-12">
                                    <h5>IMPORTAR EVENTOS</h5>
                                    <div class="custom-file">
                                        <input id="adjuntoEventos" name="adjuntoEventos" data-borrado="false"
                                            class="custom-file-input" type="file" accept=".csv"
                                            style="display:none;" />
                                        <button type="button" id="adjuntoEventosBtn" class="btn btn-primary">Seleccionar
                                            archivo</button>
                                        <span id="adjuntoEventosName" class="ms-2">Ningún archivo seleccionado</span>
                                        <button id="eliminarAdjuntoEventos" type="button" style="display: none;"
                                            class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                    </div>
                                    <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoEventos">El
                                        archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                        MB.</span>
                                    <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoVacio">No
                                        se
                                        ha
                                        seleccionado ningún archivo</span>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding-top: 7px;">
                        <button id="btn-guardar-evento" type="button" value="add"></button>
                        <button id="btn-cancelar-evento" type="button" class="btn btn-default" id="btn-salir"
                            data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('tituloDeAyuda')
        <h3 class="modal-title2" style="color: #fff;">| IMPORTADOR Y COMPARADOR EVENTOS</h3>
    @endsection

    @section('contenidoAyuda')
        <div class="col-md-12">
            <h5>Importador y Comparador Eventos</h5>
            <p>
                Este modulo permite importar los eventos activos de los casinos y luego compararlos con los eventos activos
                cargados en nuestra base de datos.
            </p>
        </div>
    @endsection

    @section('scripts')
        <script src="/js/AuditoriaEventos/indexAuditoriaEventos.js" charset="utf-8"></script>
        <script src="/js/paginacion.js" charset="utf-8"></script>
        <script src="/js/lista-datos.js" type="text/javascript"></script>
        <!-- Custom input Bootstrap -->
        <script src="/js/fileinput.min.js" type="text/javascript"></script>
        <script src="/js/locales/es.js" type="text/javascript"></script>
        <script src="/themes/explorer/theme.js" type="text/javascript"></script>
        <!-- DateTimePicker JavaScript -->
        <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
        <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
    @endsection
