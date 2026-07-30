@extends('includes.dashboard')

@section('headerLogo')
    <span class="etiquetaLogoCasinos">@svg('casinos', 'iconoCasinos')</span>
@endsection

@section('contenidoVista')
    <style>
        /* Google Fonts y tipografía premium */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');

        .aadd-container {
            font-family: 'Inter', sans-serif;
            color: #504E76;
        }

        .panel-heading {
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            background-color: #ffffff !important;
            border-bottom: 1px solid #C4C3E3 !important;
            color: #4a4f87 !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        .panel-heading h4 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #4a4f87;
        }

        .btn {
            border-radius: 8px !important;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            box-shadow: none !important;
        }

        .btn-primary {
            background-color: #4a4f87 !important;
            color: #ffffff !important;
            border: none !important;
        }

        .btn-primary:hover {
            background-color: #3b3f6f !important;
            color: #ffffff !important;
        }

        .btn-default {
            background-color: #C4C3E3 !important;
            color: #4a4f87 !important;
            border: none !important;
        }

        .btn-default:hover {
            background-color: #b0afce !important;
            color: #4a4f87 !important;
        }

        .panel-acumulado {
            background: linear-gradient(135deg, #f8fafd 0%, #edf2f7 100%) !important;
            border: solid #C4C3E3 !important;
            box-shadow: 0 10px 25px rgba(80, 78, 118, 0.05) !important;
            border-radius: 16px !important;
            height: 100%;
        }

        .panel-acumulado .panel-heading {
            background: transparent !important;
            border-bottom: 1px solid #C4C3E3 !important;
            padding: 20px !important;
            border-radius: 16px 16px 0 0 !important;
        }

        .panel-acumulado .panel-body {
            background: transparent !important;
            padding: 20px !important;
        }

        .panel-acumulado h5 {
            color: #4a4f87 !important;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem !important;
            margin: 0 !important;
            font-weight: 700 !important;
            letter-spacing: 0.3px;
        }

        .panel-acumulado small {
            color: #8c8b9f !important;
            font-size: 0.85rem !important;
        }

        .panel-acumulado strong {
            color: #4a4f87 !important;
            font-size: 1.3rem !important;
        }

        .panel-acumulado span {
            color: #8c8b9f !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.5px;
        }

        /* Filter Bar */
        .filtro-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(80, 78, 118, 0.05);
            border: 1px solid #C4C3E3;
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
            margin-top: 25px;
        }

        .filtro-container .form-group {
            margin: 0 !important;
            display: flex;
            flex-direction: column;
        }

        .filtro-container label {
            font-size: 0.75rem;
            color: #8c8b9f;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .filtro-container .form-control {
            border-radius: 8px;
            border: 1px solid #C4C3E3;
            box-shadow: none;
            padding: 8px 12px;
            height: 40px;
        }

        .filtro-container .form-control:focus {
            border-color: #4a4f87;
            box-shadow: 0 0 0 2px rgba(74, 79, 135, 0.2);
        }

        .filtro-container .btn-default {
            border-radius: 8px !important;
            border: 1px solid #C4C3E3 !important;
            color: #4a4f87 !important;
            padding: 8px 16px;
            background-color: #ffffff !important;
            height: 40px;
            font-weight: 500;
        }

        .filtro-container .btn-default:hover {
            background-color: #b0afce !important;
        }

        /* Export Bar */
        .export-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            background: linear-gradient(to right, #f8fafc, #ffffff);
            border: 1px solid #C4C3E3;
            border-radius: 12px;
            padding: 15px 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        .export-bar span {
            color: #475569;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .export-bar .fa-info-circle {
            color: #3b82f6;
            font-size: 1.2rem;
        }

        .export-bar .btn-primary {
            background: linear-gradient(135deg, #4a4f87 0%, #3b3f6f 100%) !important;
            border-radius: 8px !important;
            padding: 10px 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: none;
            box-shadow: 0 4px 10px rgba(74, 79, 135, 0.3) !important;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-bar .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 79, 135, 0.4) !important;
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading" style="margin-bottom: 20px">
                    <h4>Comparación de beneficios por plataforma</h4>
                </div>
                <div class="panel-body">
                    @include('aadd::Componentes.navegacion-chips', ['pantalla' => 'datos-rapidos'])

                    <form id="filtroDatosRapidos" class="filtro-container">
                        <div class="form-group">
                            <label for="fecha_desde">Desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                                value="{{ $fechaDesde }}">
                        </div>
                        <div class="form-group">
                            <label for="fecha_hasta">Hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                value="{{ $fechaHasta }}">
                        </div>
                        <div class="form-group" style="margin-left: auto;">
                            <label>Plataformas Visibles</label>
                            <div class="dropdown">
                                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="true">
                                    <i class="fa fa-columns" style="margin-right: 5px; color: #8c8b9f;"></i> Personalizar
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" id="platform-filter-menu"
                                    style="padding: 10px; min-width: 220px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #edf2f7;">
                                    @foreach ($plataformas as $plat)
                                        <li><label
                                                style="font-weight: 500; display: block; padding: 4px 8px; cursor: pointer; color: #334155;"><input
                                                    type="checkbox" name="plataformas[]" value="{{ $plat }}"
                                                    data-plat="{{ $plat }}" checked style="margin-right: 8px;">
                                                {{ $plat }}</label></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </form>

                    <div class="row" style="margin-bottom: 20px; display: flex; flex-wrap: wrap;">
                        <!-- Acumulado del Rango -->
                        <div class="col-md-6" style="margin-bottom: 10px;">
                            <div class="panel panel-default panel-acumulado">
                                <div class="panel-heading" style="border-radius: 12px 12px 0 0; padding: 15px;">
                                    <h5 style="margin: 0; color: #4A4F87; font-weight: bold;"><i
                                            class="fa fa-calendar-check-o"></i> Acumulado del Rango (GGR)</h5>
                                    <small class="text-muted" style="color: #4A4F87 !important;">Suma del rango
                                        seleccionado</small>
                                </div>
                                <div class="panel-body" style="padding: 15px;">
                                    <div class="row" id="acumulados-rango-container">
                                        @foreach ($plataformas as $plat)
                                            <div class="col-xs-4" style="margin-bottom: 10px;">
                                                <span
                                                    style="font-size: 0.8em; color: #4A4F87; text-transform: uppercase;">{{ $plat }}</span><br>
                                                <strong style="font-size: 1.1em; color: #4A4F87;"
                                                    id="rango-{{ $plat }}">
                                                    $ {{ number_format($acumuladosRango[$plat] ?? 0, 2, ',', '.') }}
                                                </strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acumulado Mensual -->
                        <div class="col-md-6" style="margin-bottom: 10px;">
                            <div class="panel panel-default panel-acumulado"
                                style="border: 1px solid #C4C3E3; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05); border-radius: 12px; height: 100%;">
                                <div class="panel-heading" style="border-radius: 12px 12px 0 0; padding: 15px;">
                                    <div class="pull-right"
                                        style="display: flex; align-items: center; gap: 10px; margin-top: -5px;">
                                        <span id="estado-mes-badge" class="badge"
                                            style="background-color: #FEB737; display: none;">Mes Concluido</span>
                                        <input type="month" id="selector_mes" class="form-control input-sm"
                                            style="width: auto; display: inline-block; border-radius: 6px;">
                                    </div>
                                    <h5 style="margin: 0; color: #4A4F87; font-weight: bold;"><i
                                            class="fa fa-users"></i> Acumulado Mensual (Jugadores Activos)</h5>
                                    <small class="text-muted" style="color: #4A4F87 !important;">Del archivo mensual de
                                        cada plataforma, sin sumar días</small>
                                </div>
                                <div class="panel-body" style="padding: 15px; position: relative;">
                                    <div class="row" id="acumulados-mes-container">
                                        <div class="col-md-12 text-center" id="mensaje-seleccione-mes"
                                            style="padding: 20px; color: #4A4F87">
                                            Seleccione un mes para ver el acumulado.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="contenedor-tabla-datos">
                        @include('aadd::Componentes.tablaDatosRapidos')
                    </div>

                    <!-- Fila de Exportadores dentro de la Card -->
                    <div class="export-bar" style="margin-top: 25px; margin-bottom: 10px;">
                        <span>
                            <i class="fa fa-info-circle"></i>
                            Se imprimirán los datos del rango de fechas y las plataformas seleccionadas arriba.
                        </span>
                        <div style="display: flex; gap: 10px;">
                            <a href="#" id="btn-exportar-excel" class="btn btn-primary"
                                style="border-radius: 8px !important; padding: 10px 24px; font-weight: 600; letter-spacing: 0.5px; border: none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3) !important; transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; gap: 8px; background-color: #10b981;">
                                <i class="fa fa-file-excel-o"></i> Exportar Excel
                            </a>
                            <a href="#" id="btn-exportar-pdf" class="btn btn-primary">
                                <i class="fa fa-file-pdf-o"></i> Exportar reporte PDF
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <meta name="_token" content="{!! csrf_token() !!}" />
    @endsection

    @section('tituloDeAyuda')
        <h3 class="modal-title" style="color: #fff;">| AYUDA COMPARACIÓN DE BENEFICIOS</h3>
    @endsection

    @section('contenidoAyuda')
        <div class="col-md-12">
            <h5>Comparación de beneficios por plataforma</h5>
            <p>Vista consolidada de todas las plataformas a la vez: una fila por día, y para cada plataforma sus jugadores
                activos y su GGR de ese día. Sirve para comparar plataformas entre sí sin tener que ir cambiando el
                selector.</p>
            <p>El <strong>Acumulado Mensual</strong> muestra los jugadores activos que informa el archivo mensual de cada
                plataforma. <strong>No es la suma de los días</strong>: un mismo jugador aparece en el diario de cada día
                que jugó, así que sumarlos multiplicaría el total. Si una plataforma no tiene archivo mensual importado
                para el mes elegido se muestra <em>sin datos</em>, que no es lo mismo que cero jugadores.</p>
        </div>
    @endsection

    @section('scripts')
        @include('aadd::Componentes.scripts-paginacion')
        <script>
            $(document).ready(function() {

                // Muestra u oculta las columnas de cada plataforma segun el dropdown.
                // Filtra por clase y no por indice porque la cabecera tiene dos niveles
                // y celdas con colspan/rowspan.
                function aplicarFiltroPlataformas() {
                    $('#platform-filter-menu input[type="checkbox"]').each(function() {
                        var plat = $(this).data('plat');
                        var visible = $(this).is(':checked');
                        $('#contenedor-tabla-datos .col-plat-' + plat).toggle(visible);
                    });
                }

                var xhrActualizar = null;

                function actualizarTabla() {
                    if (xhrActualizar) {
                        xhrActualizar.abort();
                    }

                    var formData = $('#filtroDatosRapidos').serialize();
                    $('#contenedor-tabla-datos').css('opacity', '0.5');

                    xhrActualizar = $.ajax({
                        url: '/aadd/datos-rapidos/tabla',
                        type: 'GET',
                        data: formData,
                        success: function(response) {
                            $('#contenedor-tabla-datos').html(response.html);
                            $('#contenedor-tabla-datos').css('opacity', '1');

                            if (response.acumulados_rango) {
                                $.each(response.acumulados_rango, function(plat, valor) {
                                    $('#rango-' + plat).text('$ ' + valor.toLocaleString('es-AR', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }));
                                });
                            }

                            paginarTabla('#contenedor-tabla-datos table', 30);
                            aplicarFiltroPlataformas();
                        },
                        error: function(err) {
                            if (err.statusText !== 'abort') {
                                console.error('Error al actualizar la tabla', err);
                                $('#contenedor-tabla-datos').css('opacity', '1');
                            }
                        },
                        complete: function() {
                            xhrActualizar = null;
                        }
                    });
                }

                var timerFiltro = null;
                $('#fecha_desde, #fecha_hasta').on('change input', function() {
                    clearTimeout(timerFiltro);

                    timerFiltro = setTimeout(function() {
                        var desde = $('#fecha_desde').val();
                        var hasta = $('#fecha_hasta').val();

                        // Si alguna fecha esta vacia, dejamos que el backend use sus defaults
                        if (!desde || !hasta) {
                            actualizarTabla();
                            return;
                        }

                        var dateRegex = /^[2][0-9]{3}-[0-9]{2}-[0-9]{2}$/;
                        if (dateRegex.test(desde) && dateRegex.test(hasta)) {
                            actualizarTabla();
                        }
                    }, 500);
                });

                $('#selector_mes').on('change', function() {
                    var mes = $(this).val();
                    if (!mes) return;

                    $('#acumulados-mes-container').html(
                        '<div class="col-md-12 text-center"><i class="fa fa-spinner fa-spin text-primary"></i></div>'
                    );
                    $('#estado-mes-badge').hide();

                    $.ajax({
                        url: '/aadd/datos-rapidos/acumulado-mensual',
                        type: 'GET',
                        data: {
                            mes: mes
                        },
                        success: function(response) {
                            var html = '';
                            $.each(response.acumulados, function(plat, valor) {
                                // null = la plataforma no tiene archivo mensual importado,
                                // que no es lo mismo que haber tenido cero jugadores
                                var contenido = (valor === null) ?
                                    '<span style="font-size: 0.95em; color: #b9b8c8;">sin datos</span>' :
                                    '<strong style="font-size: 1.1em; color: #4A4F87;">' +
                                    valor.toLocaleString('es-AR') + '</strong>';
                                html +=
                                    '<div class="col-xs-4" style="margin-bottom: 10px;">' +
                                    '<span style="font-size: 0.8em; color: #4A4F87; text-transform: uppercase;">' +
                                    plat + '</span><br>' + contenido +
                                    '</div>';
                            });
                            $('#acumulados-mes-container').html(html);

                            if (response.estado === 'concluido') {
                                $('#estado-mes-badge').text('Mes Concluido').css('background-color',
                                    '#d1dfa4ff').show();
                            } else if (response.estado === 'proximo') {
                                $('#estado-mes-badge').text('Mes Próximo').css('background-color',
                                    '#f7f4d0ff').show();
                            } else {
                                $('#estado-mes-badge').text('Mes en Curso').css('background-color',
                                    '#abc2eeff').show();
                            }
                        },
                        error: function() {
                            $('#acumulados-mes-container').html(
                                '<div class="col-md-12 text-center text-danger">Error al cargar datos.</div>'
                            );
                        }
                    });
                });

                // Inicializar el selector de mes con el mes actual y disparar la carga
                var hoy = new Date();
                var mesActualStr = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2);
                $('#selector_mes').val(mesActualStr).trigger('change');

                // Evitar que el dropdown se cierre al clickear un checkbox
                $('#platform-filter-menu').on('click', function(e) {
                    e.stopPropagation();
                });

                $('#platform-filter-menu input[type="checkbox"]').on('change', function() {
                    aplicarFiltroPlataformas();
                });

                // Inicializar paginación y filtro al cargar la página por primera vez
                paginarTabla('#contenedor-tabla-datos table', 30);
                aplicarFiltroPlataformas();

                $('#btn-exportar-pdf').on('click', function(e) {
                    e.preventDefault();
                    var formData = $('#filtroDatosRapidos').serialize();
                    window.open('/aadd/informes/pdf/datos-rapidos?' + formData, '_blank');
                });

                $('#btn-exportar-excel').on('click', function(e) {
                    e.preventDefault();
                    var formData = $('#filtroDatosRapidos').serialize();
                    window.open('/aadd/informes/excel/datos-rapidos?' + formData, '_blank');
                });
            });
        </script>
    @endsection
