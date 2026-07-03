@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important; border: 1px solid #f0f0f0 !important; background-color: #ffffff; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; background-color: #fafbfc !important; border-bottom: 1px solid #f0f0f0 !important; color: #555 !important; }
    .btn { border-radius: 8px !important; font-weight: 500; transition: all 0.2s ease; box-shadow: none !important; }
    .btn-primary { background-color: #e3f2fd !important; color: #1565c0 !important; border: none !important; }
    .btn-primary:hover { background-color: #bbdefb !important; color: #0d47a1 !important; }
    .table { border: none !important; }
    .table > thead > tr > th { text-align: center; vertical-align: middle; background-color: #f8fafd; color: #333; font-weight: 600; border-bottom: 2px solid #edf2f7; text-transform: uppercase; font-size: 0.9em; letter-spacing: 0.5px; }
    .table > tbody > tr > td { text-align: center; vertical-align: middle; border-top: 1px solid #f8f9fa; color: #222; font-weight: 500; }
    .table-striped > tbody > tr:nth-of-type(odd) { background-color: #fcfcfc; }
    .container-fluid { padding-right: 20px !important; padding-left: 20px !important; }
    .chip { display: inline-flex; align-items: center; padding: 8px 16px; margin-right: 12px; margin-bottom: 10px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 50px; color: #495057; font-weight: 500; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.04); }
    .chip:hover { background-color: #e3f2fd; border-color: #90caf9; color: #1565c0; text-decoration: none; box-shadow: 0 4px 8px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .chip i { margin-right: 8px; font-size: 1.1em; color: #64b5f6; transition: color 0.2s ease; }
    .chip:hover i { color: #1565c0; }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Beneficio Diario</h4>
            </div>
            <div class="panel-body">
                <div style="margin-bottom: 20px;">
                    <a href="/aadd" class="chip">
                        <i class="fa fa-arrow-left"></i> Menú Principal
                    </a>
                    <a href="/aadd/importaciones" class="chip">
                        <i class="fa fa-upload"></i> Importador de Archivos
                    </a>
                </div>

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
    function paginarTabla(tableSelector, filasPorPagina) {
        var $table = $(tableSelector);
        // Evitar duplicar controles si ya existe
        $table.next('.paginador-custom').remove();
        
        var $tbody = $table.find('tbody');
        var $tr = $tbody.find('tr:not(.no-paginar)'); // se puede marcar filas especiales si hace falta
        var totalFilas = $tr.length;
        var totalPaginas = Math.ceil(totalFilas / filasPorPagina);
        
        if (totalPaginas <= 1) return;
        
        var $paginador = $('<div class="paginador-custom text-center" style="margin-top: 15px;"></div>');
        for (var i = 1; i <= totalPaginas; i++) {
            var $btn = $('<button class="btn btn-default btn-sm" style="margin: 0 2px;">' + i + '</button>');
            $btn.data('page', i);
            $paginador.append($btn);
        }
        $table.after($paginador);
        
        function mostrarPagina(page) {
            $paginador.find('button').removeClass('btn-primary').addClass('btn-default');
            $paginador.find('button').filter(function() { return $(this).data('page') === page; }).removeClass('btn-default').addClass('btn-primary');
            
            var inicio = (page - 1) * filasPorPagina;
            var fin = inicio + filasPorPagina;
            
            $tr.hide();
            $tr.slice(inicio, fin).show();
        }
        
        $paginador.on('click', 'button', function(e) {
            e.preventDefault();
            mostrarPagina($(this).data('page'));
        });
        
        mostrarPagina(1);
    }

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
    
    // Inicializar paginación al cargar la página por primera vez
    paginarTabla('#contenedor-tabla-beneficio table', 30);
});
</script>
@endsection
