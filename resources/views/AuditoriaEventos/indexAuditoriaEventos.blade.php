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
                                <h4 class="txtNuevo">IMPORTAR UN ARCHIVO</h4>
                            </center>
                        </div>
                    </div>
                </div>
            </a>
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
                    <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                        data-target="#colapsado" style="position:relative; right:20px; top:5px">
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
                                        class="custom-file-input" type="file" accept=".csv" style="display:none;" />
                                    <button type="button" id="adjuntoEventosBtn" class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="adjuntoEventosName" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarAdjuntoEventos" type="button" style="display: none;"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoEventos">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                                <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoVacio">No se ha
                                    seleccionado ningún arhcivos</span>
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
