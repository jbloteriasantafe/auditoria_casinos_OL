@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.05) !important; border: none !important; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; }
    .btn { border-radius: 8px !important; }
    .alert { border-radius: 8px !important; }
    .well { border-radius: 12px !important; }
    .modal-content { border-radius: 12px !important; overflow: hidden; }
</style>
<div class="row">
    <div class="col-md-9">
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                <h3 class="panel-title" style="font-weight: bold; color: #333;"><i class="fa fa-upload"></i> Importación</h3>
            </div>
            <div class="panel-body text-center" style="padding: 40px;">
                <!-- Contenedores de alerta para cuando se cierre el modal -->
                <div id="mensajeExito" class="alert alert-success" style="display: none; text-align: left;"></div>
                <div id="mensajeError" class="alert alert-danger" style="display: none; text-align: left;"></div>

                <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#modalImportadorAadd" style="background-color: #2e7d32; border-color: #1b5e20;">
                    <i class="fa fa-upload"></i> Importación beneficio diario
                </button>
                <br><br><br>
                <a href="/aadd" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver al panel principal</a>
            </div>
        </div>

        @include('aadd::Componentes.tablaEstadoMensual')
    </div>
    
</div>

@include('aadd::Modals.modalImportadorAadd')
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var plataformas = {!! json_encode($plataformas) !!};
    var archivosSeleccionados = [];

    // Cuando se seleccionan archivos
    $('#archivoModal').on('change', function(e) {
        archivosSeleccionados = Array.from(e.target.files);
        var tbody = $('#tbodyArchivosPreview');
        tbody.empty();

        if(archivosSeleccionados.length > 0) {
            $('#listaArchivosPreview').show();
            
            var opcionesPlataforma = '<option value="" disabled selected>Seleccione...</option>';
            plataformas.forEach(function(platNombre) {
                opcionesPlataforma += '<option value="' + platNombre + '">' + platNombre + '</option>';
            });

            var hoy = "{{ date('Y-m-d') }}";

            archivosSeleccionados.forEach(function(archivo, index) {
                var tr = $('<tr>').attr('data-index', index);
                
                var tdNombre = $('<td>').text(archivo.name);
                
                var selectPlataforma = $('<select>').addClass('form-control select-plataforma').prop('required', true).html(opcionesPlataforma);
                var tdPlataforma = $('<td>').append(selectPlataforma);
                
                var inputFecha = $('<input>').attr('type', 'date').addClass('form-control input-fecha').val(hoy).prop('required', true);
                var tdFecha = $('<td>').append(inputFecha);
                
                var tdEstado = $('<td>').addClass('td-estado').html('<span class="text-muted"><i class="fa fa-clock-o"></i> Pendiente</span>');

                tr.append(tdNombre).append(tdPlataforma).append(tdFecha).append(tdEstado);
                tbody.append(tr);
            });
        } else {
            $('#listaArchivosPreview').hide();
        }
    });

    $('#btn-importar-ajax').click(function(e) {
        e.preventDefault();
        
        if (archivosSeleccionados.length === 0) {
            $('#errorAjaxForm').text('Debe seleccionar al menos un archivo.').show();
            return;
        }

        // Validar que todos tengan plataforma y fecha seleccionada
        var faltaDatos = false;
        $('#tbodyArchivosPreview tr').each(function() {
            var plat = $(this).find('.select-plataforma').val();
            var fecha = $(this).find('.input-fecha').val();
            if(!plat || !fecha) {
                faltaDatos = true;
            }
        });

        if (faltaDatos) {
            $('#errorAjaxForm').text('Debe seleccionar la plataforma y la fecha para todos los archivos.').show();
            return;
        }

        var formData = new FormData();
        // Agregar token CSRF
        formData.append('_token', $('input[name="_token"]').val());

        // Recorrer filas para empaquetar archivo original + plataforma + fecha
        $('#tbodyArchivosPreview tr').each(function() {
            var index = $(this).attr('data-index');
            var plataforma = $(this).find('.select-plataforma').val();
            var fecha = $(this).find('.input-fecha').val();
            var archivo = archivosSeleccionados[index];
            
            formData.append('archivos[]', archivo);
            formData.append('plataformas[]', plataforma);
            formData.append('fechas[]', fecha);
        });

        $('#errorAjaxForm').hide().text('');
        $('#mensajeExito').hide().text('');
        
        // Deshabilitar UI
        $(this).prop('disabled', true).text('IMPORTANDO...');
        $('.select-plataforma').prop('disabled', true);
        $('.input-fecha').prop('disabled', true);
        $('#archivoModal').prop('disabled', true);
        
        // Limpiar estados previos
        $('.td-estado').html('<span class="text-info"><i class="fa fa-spinner fa-spin"></i> Subiendo...</span>');

        $.ajax({
            type: 'POST',
            url: '/aadd/importar',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $('#btn-importar-ajax').prop('disabled', false).text('IMPORTAR');
                
                // La respuesta debe ser un Array de ArchivoImportadoDTO
                if (Array.isArray(response)) {
                    // Actualizar el estado de cada fila según el DTO
                    $('#tbodyArchivosPreview tr').each(function() {
                        var index = $(this).attr('data-index');
                        var archivoOriginal = archivosSeleccionados[index];
                        
                        // Buscar el DTO correspondiente por nombre_archivo
                        var dto = response.find(item => item.nombre_archivo === archivoOriginal.name);
                        
                        if (dto) {
                            var icon = dto.estado === 'exito' ? '<i class="fa fa-check text-success"></i> ' : '<i class="fa fa-times text-danger"></i> ';
                            var color = dto.estado === 'exito' ? 'text-success' : 'text-danger';
                            var msg = dto.mensaje ? dto.mensaje : (dto.estado === 'exito' ? 'Ok' : 'Error');
                            $(this).find('.td-estado').html('<span class="' + color + '" title="' + msg + '">' + icon + msg + '</span>');
                        }
                    });
                }
            },
            error: function(data) {
                $('#btn-importar-ajax').prop('disabled', false).text('IMPORTAR');
                $('.select-plataforma').prop('disabled', false);
                $('.input-fecha').prop('disabled', false);
                $('#archivoModal').prop('disabled', false);
                $('.td-estado').html('<span class="text-danger"><i class="fa fa-times"></i> Falló</span>');
                
                var response = data.responseJSON;
                if(response && typeof response.archivos !== 'undefined') {
                    $('#errorAjaxForm').text(response.archivos[0]).show();
                } else if(response && typeof response.message !== 'undefined') {
                    $('#errorAjaxForm').text(response.message).show();
                } else {
                    $('#errorAjaxForm').text('Error general del servidor al subir los archivos.').show();
                }
            }
        });
    });
    
    // Limpiar el modal cada vez que se abre
    $('#modalImportadorAadd').on('show.bs.modal', function () {
        $('#errorAjaxForm').hide().text('');
        $('#mensajeExito').hide().text('');
        $('#formImportadorAadd')[0].reset();
        $('#tbodyArchivosPreview').empty();
        $('#listaArchivosPreview').hide();
        $('#btn-importar-ajax').prop('disabled', false).text('IMPORTAR');
        $('.select-plataforma').prop('disabled', false);
        $('.input-fecha').prop('disabled', false);
        $('#archivoModal').prop('disabled', false);
        archivosSeleccionados = [];
    });
});

window.actualizarTablaMensual = function(mes) {
    if (!mes) return;
    $.ajax({
        url: '/aadd/importaciones',
        type: 'GET',
        data: { mes: mes },
        success: function(response) {
            $('#contenedor-tabla-mensual').replaceWith(response);
        },
        error: function(err) {
            console.error('Error al actualizar la tabla mensual', err);
        }
    });
};
</script>
@endsection