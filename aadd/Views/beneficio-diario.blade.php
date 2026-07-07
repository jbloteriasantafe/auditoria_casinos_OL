@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 15px rgba(80, 78, 118, 0.05) !important; border: 1px solid #C4C3E3 !important; background-color: #ffffff; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; background-color: #ffffff !important; border-bottom: 1px solid #C4C3E3 !important; color: #292639 !important; }
    .btn { border-radius: 8px !important; font-weight: 500; transition: all 0.2s ease; box-shadow: none !important; }
    .btn-primary { background-color: #292639 !important; color: #ffffff !important; border: none !important; }
    .btn-primary:hover { background-color: #292639 !important; color: #ffffff !important; }
    .panel-acumulado { background-color: #C6DA81!important; border-color: #efedf8ff !important; }
    .panel-acumulado .panel-heading { background-color: #C6DA81!important; border-bottom: 1px solid rgba(255,255,255,0.3) !important; }
    .panel-acumulado .panel-body { background-color: #C6DA81!important; }
    .table { border: none !important; }
    .table > thead > tr > th { text-align: center; vertical-align: middle; background-color: #f8fafd; color: #333; font-weight: 600; border-bottom: 2px solid #edf2f7; text-transform: uppercase; font-size: 0.9em; letter-spacing: 0.5px; }
    .table > tbody > tr > td { text-align: center; vertical-align: middle; border-top: 1px solid #f8f9fa; color: #222; font-weight: 500; }
    .table-striped > tbody > tr:nth-of-type(odd) { background-color: #fcfcfc; }
    .container-fluid { padding-right: 20px !important; padding-left: 20px !important; }

</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading" style="margin-bottom: 20px">
                <h4>Beneficio Diario</h4>
            </div>
            <div class="panel-body">
                @include('aadd::Componentes.navegacion-chips', ['pantalla' => 'beneficio-diario'])

                <form id="filtroBeneficioDiario" class="form-inline" style="margin-bottom: 20px; margin-top: 20px;">
                    <div class="form-group">
                        <label for="fecha_desde">Desde:</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}">
                    </div>
                    <div class="form-group" style="margin-left: 10px;">
                        <label for="fecha_hasta">Hasta:</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}">
                    </div>
                    <div class="form-group" style="margin-left: 20px;">
                        <label for="plataforma">Plataforma:</label>
                        <select class="form-control" id="plataforma" name="plataforma">
                            @foreach($plataformas as $plat)
                                <option value="{{ $plat }}" {{ $plataformaSeleccionada == $plat ? 'selected' : '' }}>{{ $plat }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="row" style="margin-bottom: 20px; display: flex; flex-wrap: wrap;">
                    <!-- Acumulado del Rango -->
                    <div class="col-md-6" style="margin-bottom: 10px;">
                        <div class="panel panel-default panel-acumulado" style="border: 1px solid #C4C3E3; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05); border-radius: 12px; height: 100%;">
                            <div class="panel-heading" style="border-radius: 12px 12px 0 0; padding: 15px;">
                                <h5 style="margin: 0; color: #4A4F87; font-weight: bold;"><i class="fa fa-calendar-check-o"></i> Acumulado del Rango (GGR)</h5>
                                <small class="text-muted" style="color: #4A4F87 !important;">Suma del rango seleccionado</small>
                            </div>
                            <div class="panel-body" style="padding: 15px;">
                                <div class="row" id="acumulados-rango-container">
                                    @foreach($plataformas as $plat)
                                        <div class="col-xs-4" style="margin-bottom: 10px;">
                                            <span style="font-size: 0.8em; color: #4A4F87; text-transform: uppercase;">{{ $plat }}</span><br>
                                            <strong style="font-size: 1.1em; color: #4A4F87;" id="rango-{{ $plat }}">
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
                        <div class="panel panel-default panel-acumulado" style="border: 1px solid #C4C3E3; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05); border-radius: 12px; height: 100%;">
                            <div class="panel-heading" style="border-radius: 12px 12px 0 0; padding: 15px;">
                                <div class="pull-right" style="display: flex; align-items: center; gap: 10px; margin-top: -5px;">
                                    <span id="estado-mes-badge" class="badge" style="background-color: #FEB737; display: none;">Mes Concluido</span>
                                    <input type="month" id="selector_mes" class="form-control input-sm" style="width: auto; display: inline-block; border-radius: 6px;">
                                </div>
                                <h5 style="margin: 0; color: #4A4F87; font-weight: bold;"><i class="fa fa-calendar"></i> Acumulado Mensual (GGR)</h5>
                                <small class="text-muted" style="color: #4A4F87 !important;">Total del mes seleccionado</small>
                            </div>
                            <div class="panel-body" style="padding: 15px; position: relative;">
                                <div class="row" id="acumulados-mes-container">
                                    <div class="col-md-12 text-center" id="mensaje-seleccione-mes" style="padding: 20px; color: #4A4F87">
                                        Seleccione un mes para ver el acumulado.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="contenedor-tabla-beneficio">
                    @include('aadd::Componentes.tablaBeneficioDiario')
                </div>

            </div>
        </div>
    </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA BENEFICIO DIARIO</h3>
@endsection

@section('contenidoAyuda')
<div class="col-md-12">
    <h5>Beneficio Diario</h5>
    <p>Esta sección muestra los reportes diarios de beneficios, incluyendo jugadores activos, apuestas, turnover, payout y GGR.</p>
</div>
@endsection

@section('scripts')
@include('aadd::Componentes.scripts-paginacion')
<script>
$(document).ready(function() {

    function actualizarTabla() {
        var formData = $('#filtroBeneficioDiario').serialize();
        
        // Opcional: mostrar un indicador de carga en la tabla
        $('#contenedor-tabla-beneficio').css('opacity', '0.5');

        $.ajax({
            url: '/aadd/beneficio-diario',
            type: 'GET',
            data: formData,
            success: function(response) {
                // Ahora el controlador devuelve un JSON con html y acumulados_rango
                $('#contenedor-tabla-beneficio').html(response.html);
                $('#contenedor-tabla-beneficio').css('opacity', '1');
                
                // Actualizar las tarjetas de acumulado de rango
                if (response.acumulados_rango) {
                    $.each(response.acumulados_rango, function(plat, valor) {
                        $('#rango-' + plat).text('$ ' + valor.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    });
                }
                
                paginarTabla('#contenedor-tabla-beneficio table', 30);
            },
            error: function(err) {
                console.error('Error al actualizar la tabla', err);
                $('#contenedor-tabla-beneficio').css('opacity', '1');
            }
        });
    }

    $('#fecha_desde, #fecha_hasta, #plataforma').on('change', function() {
        actualizarTabla();
    });

    $('#selector_mes').on('change', function() {
        var mes = $(this).val();
        if(!mes) return;
        
        $('#acumulados-mes-container').html('<div class="col-md-12 text-center"><i class="fa fa-spinner fa-spin text-primary"></i></div>');
        $('#estado-mes-badge').hide();

        $.ajax({
            url: '/aadd/acumulado-mensual',
            type: 'GET',
            data: { mes: mes },
            success: function(response) {
                var html = '';
                $.each(response.acumulados, function(plat, valor) {
                    var valorFormatted = valor.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    html += '<div class="col-xs-4" style="margin-bottom: 10px;">' +
                                '<span style="font-size: 0.8em; color: #4A4F87; text-transform: uppercase;">' + plat + '</span><br>' +
                                '<strong style="font-size: 1.1em; color: #4A4F87;">$ ' + valorFormatted + '</strong>' +
                            '</div>';
                });
                $('#acumulados-mes-container').html(html);
                
                if(response.concluido) {
                    $('#estado-mes-badge').text('Mes Concluido').css('background-color', '#A3B565').show();
                } else {
                    $('#estado-mes-badge').text('Mes en Curso').css('background-color', '#F4CE45').show();
                }
            },
            error: function() {
                $('#acumulados-mes-container').html('<div class="col-md-12 text-center text-danger">Error al cargar datos.</div>');
            }
        });
    });
    
    // Inicializar el selector de mes con el mes actual y disparar la carga
    var hoy = new Date();
    var mesActualStr = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2);
    $('#selector_mes').val(mesActualStr).trigger('change');
    
    // Inicializar paginación al cargar la página por primera vez
    paginarTabla('#contenedor-tabla-beneficio table', 30);
});
</script>
@endsection
