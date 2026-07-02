@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.05) !important; border: none !important; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; }
    .btn { border-radius: 8px !important; }
    .table > thead > tr > th { text-align: center; vertical-align: middle; background-color: #f9f9f9; color: #333; font-weight: bold; border-bottom: 2px solid #ddd; }
    .table > tbody > tr > td { text-align: center; vertical-align: middle; }
    .container-fluid { padding-right: 20px !important; padding-left: 20px !important; }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Beneficio Diario</h4>
            </div>
            <div class="panel-body">
                <form id="filtroBeneficioDiario" class="form-inline" style="margin-bottom: 20px;">
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
                $('#contenedor-tabla-beneficio').html(response);
                $('#contenedor-tabla-beneficio').css('opacity', '1');
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
});
</script>
@endsection
