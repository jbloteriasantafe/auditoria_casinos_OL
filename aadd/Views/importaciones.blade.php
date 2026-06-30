@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
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
    </div>
    
    <div class="col-md-3">
        <div class="panel panel-default" style="border-left: 4px solid #2e7d32;">
            <div class="panel-heading">
                <h4><i class="fa fa-info-circle" style="color: #2e7d32;"></i> Ayuda</h4>
            </div>
            <div class="panel-body">
                <p>En esta sección puedes subir archivos en formato <strong>CSV</strong> y <strong>Excel</strong>.</p>
                <p>El sistema se encargará de guardarlos físicamente en el servidor de forma segura.</p>
            </div>
        </div>
    </div>
</div>

@include('aadd::Modals.modalImportadorAadd')
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var archivosSeleccionados = [];

    // Cuando se seleccionan archivos
    $('#archivoModal').on('change', function(e) {
        archivosSeleccionados = Array.from(e.target.files);
        var tbody = $('#tbodyArchivosPreview');
        tbody.empty();

        if(archivosSeleccionados.length > 0) {
            $('#listaArchivosPreview').show();
            
            var opcionesPlataforma = `
                <option value="" disabled selected>Seleccione...</option>
                <option value="BPLAY">BPLAY</option>
                <option value="CITYCENTER">CITYCENTER</option>
                <option value="JUGADON">JUGADON</option>
                <option value="BETWARRIOR">BETWARRIOR</option>
                <option value="BETSON">BETSON</option>
                <option value="BET365">BET365</option>
            `;

            archivosSeleccionados.forEach(function(archivo, index) {
                var tr = $('<tr>').attr('data-index', index);
                
                var tdNombre = $('<td>').text(archivo.name);
                
                var selectPlataforma = $('<select>').addClass('form-control select-plataforma').prop('required', true).html(opcionesPlataforma);
                var tdPlataforma = $('<td>').append(selectPlataforma);
                
                var tdEstado = $('<td>').addClass('td-estado').html('<span class="text-muted"><i class="fa fa-clock-o"></i> Pendiente</span>');

                tr.append(tdNombre).append(tdPlataforma).append(tdEstado);
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

        // Validar que todos tengan plataforma seleccionada
        var faltaPlataforma = false;
        $('#tbodyArchivosPreview tr').each(function() {
            var val = $(this).find('.select-plataforma').val();
            if(!val) {
                faltaPlataforma = true;
            }
        });

        if (faltaPlataforma) {
            $('#errorAjaxForm').text('Debe seleccionar la plataforma para todos los archivos.').show();
            return;
        }

        var formData = new FormData();
        // Agregar token CSRF
        formData.append('_token', $('input[name="_token"]').val());

        // Recorrer filas para empaquetar archivo original + plataforma
        $('#tbodyArchivosPreview tr').each(function() {
            var index = $(this).attr('data-index');
            var plataforma = $(this).find('.select-plataforma').val();
            var archivo = archivosSeleccionados[index];
            
            formData.append('archivos[]', archivo);
            formData.append('plataformas[]', plataforma);
        });

        $('#errorAjaxForm').hide().text('');
        $('#mensajeExito').hide().text('');
        
        // Deshabilitar UI
        $(this).prop('disabled', true).text('IMPORTANDO...');
        $('.select-plataforma').prop('disabled', true);
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
        $('#archivoModal').prop('disabled', false);
        archivosSeleccionados = [];
    });
});
</script>
@endsection