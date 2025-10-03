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

        .d-flex {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .icon-button {
            padding-top: 2px;
            padding-bottom: 2px;
            padding-left: 5px;
            padding-right: 5px;
            margin-bottom: 5px;
        }

        .lista-juegos {
            padding: 10px;
            position: absolute;
            top: 100%;
            left: 1.5em;
            width: 95%;
            border: 1px solid #ccc;
            background: white;
            z-index: 10;
            border-radius: 5px;
            display: none;
        }

        .resultados-busqueda {
            height: 300px;
            max-height: 350px;
            overflow-y: auto;
            margin-top: 5px;
        }

        .nombre-juego {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .list-item {
            padding: 5px 20px;
            border-bottom: 1px solid #eee;
        }

        .list-item:hover {
            background-color: #f0f0f0;
        }

        .list-selected-item {
            padding: 5px 20px;
            border-bottom: 1px solid #eee;
        }

        .list-selected-item:hover {
            background-color: #f0f0f0;
        }

        .lista-juegos-seleccionados {
            max-height: 300px;
            overflow-y: auto;
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

    {{-- ! MODAL GESTIÓN INFORME TÉCNICO --}}
    <div class="modal fade" id="modalInformeTecnico" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                    <button type="button" class="close" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </button>
                    <button id="btn-minimizar" type="button" class="close" data-toggle="collapse"
                        data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px">
                        <i class="fa fa-minus"></i>
                    </button>
                    <h3 class="modal-title" id="myModalLabel">| GESTIÓN INFORME TÉCNICO </h3>
                </div>
                <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                        <form class="row" id="formulario">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5>Agregar juegos</h5>
                                    <div id="select-juegos" class="form-control d-flex">
                                        <p class="juego-seleccionado">Seleccione un juego</p>
                                        <div class="icon-button">
                                            <i class="fa fa-angle-down icon"></i>
                                        </div>
                                        <div class="lista-juegos">
                                            <input id="buscador-juegos" type="text" class="form-control"
                                                placeholder="Buscar juego..." />
                                            <div class="resultados-busqueda">
                                                @foreach ($juegos as $juego)
                                                    <div class="list-item">
                                                        <p class="nombre-juego">{{ $juego->nombre_juego }}</p>
                                                        <div>
                                                            <small>ID: <b>{{ $juego->id_juego }}</b></small> |
                                                            <small>Porcentaje de devolución:
                                                                <b>{{ $juego->porcentaje_devolucion }}%</b></small> |
                                                            <small>Movil: <b>{{ $juego->movil }}</b></small> |
                                                            <small>Escritorio: <b>{{ $juego->escritorio }}</b></small>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <h5>JUEGOS SELECCIONADOS</h5>
                                <div class="lista-juegos-seleccionados">
                                    <!-- Aquí se agregarán los juegos seleccionados -->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer" style="padding-top: 7px;">
                    <button id="btn-guardar-informe" type="button" value="add"></button>
                    <button id="btn-cancelar-informe" type="button" class="btn btn-default" id="btn-salir"
                        data-dismiss="modal" aria-label="Close">CANCELAR</button>
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
