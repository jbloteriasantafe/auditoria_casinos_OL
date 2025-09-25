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
    {{-- ! FILTRO DE NOTAS --}}
    <div class="row">
        <div class="col-md-12">
            <div id="contenedorFiltros" class="panel panel-default">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                    <h4>Filtros de Búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>NRO. DE NOTA</h5>
                                <input class="form-control" id="buscarNroNota" value="" />
                            </div>
                            <div class="col-md-4">
                                <h5>NOMBRE DEL EVENTO</h5>
                                <input class="form-control" id="buscarNombreEvento" value="" />
                            </div>
                            <div class="col-md-4">
                                <h5>CASINO</h5>
                                <select class="form-control" id="buscarNombreCasino" value="">
                                    <option value="" selected disabled>-- Selecciones un casino si lo desea --
                                    </option>
                                    <option value="">AMBOS
                                    </option>
                                    @foreach ($casinos as $casino)
                                        <option value="{{ $casino['id_casino'] }}">{{ $casino['casino'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <center>
                                <button id="btn-buscar" class="btn btn-infoBuscar" type="button"><i
                                        class="fa fa-fw fa-search"></i> BUSCAR</button>
                            </center>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ! TABLA DE NOTAS --}}
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>LISTADO DE NOTAS</h4>
                </div>
                <div class="panel-body">
                    <div class="tabla-scroll">
                        <table id="tablaNotas" class="table">
                            <thead>
                                <tr>
                                    <!-- <i class="fa fa-sort"></i> -->
                                    <th class="col-sm-1 text-center" value="numero_nota" estado=""
                                        title="Número de nota">NRO. DE NOTA</th>
                                    <th class="col-sm-1 text-center" value="nombre_evento" estado=""
                                        title="Nombre de evento">NOMBRE EVENTO
                                    </th>{{-- ! TODO LO QUE DICE NOTAS EN EL CODIGO ESTA COMO PAUTAS --}}
                                    <th class="col-sm-1 text-center" value="adjunto_pautas" estado=""
                                        title="Adjunto pautas">ADJ. NOTAS
                                    </th>
                                    <th class="col-sm-1 text-center" value="adjunto_diseño" estado=""
                                        title="Adjunto diseño">ADJ. DISEÑO
                                    </th>
                                    <th class="col-sm-1 text-center" value="adjunto_basesycond" estado=""
                                        title="Adjunto bases y condiciones">ADJ. BASES
                                        Y
                                        CONDICIONES
                                    </th>
                                    <th class="col-sm-1 text-center" value="adjunto_informe_tecnico"
                                        title="Adjunto informe técnico">ADJ. INFORME TÉCNICO</th>
                                    <th class="col-sm-1 text-center" value="estado" title="Estado de la nota">ESTADO</th>
                                    <th class="col-sm-1 text-center" value="notas_relacionadas" title="Notas relacionadas">
                                        NOTAS RELACIONADAS</th>
                                    <th class="col-sm-1 text-center" value="acciones_nota" title="Acciones">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla">
                                <tr class="filaTabla" style="display: none">
                                    <td class="col-sm-1 text-center numero_nota"></td>
                                    <td class="col-sm-1 text-center nombre_evento"></td>
                                    <td class="col-sm-1 text-center adjunto_pautas"></td>
                                    <td class="col-sm-1 text-center adjunto_disenio"></td>
                                    <td class="col-sm-1 text-center adjunto_basesycond"></td>
                                    <td class="col-sm-1 text-center adjunto_informe_tecnico"></td>
                                    <td class="col-sm-1 text-center estado"></td>
                                    <td class="col-sm-1 text-center notas_relacionadas"></td>
                                    <td class="col-sm-1 text-center acciones_nota"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| INFORMES TÉCNICOS</h3>
@endsection

@section('contenidoAyuda')
    <div class="col-md-12">
        <h5>Informes técnicos</h5>
        <p>
            Este modulo permite agregar juegos y generar informes técnicos a las notas subidas por los casinos
        </p>
    </div>
@endsection

@section('scripts')
    <script src="/js/NotasCasino/indexInformesTecnicos.js" type="text/javascript"></script>
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
