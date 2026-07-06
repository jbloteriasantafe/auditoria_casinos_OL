@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 15px rgba(80, 78, 118, 0.05) !important; border: 1px solid #C4C3E3 !important; background-color: #ffffff; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; background-color: #ffffff !important; border-bottom: 1px solid #C4C3E3 !important; color: #504E76 !important; }
    .btn { border-radius: 8px !important; font-weight: 500; transition: all 0.2s ease; box-shadow: none !important; }
    .btn-primary { background-color: #504E76 !important; color: #ffffff !important; border: none !important; }
    .btn-primary:hover { background-color: #3b3958 !important; color: #ffffff !important; }
    .btn-default { background-color: #C4C3E3 !important; color: #504E76 !important; border: none !important; }
    .btn-default:hover { background-color: #b0afce !important; }
    .alert { border-radius: 8px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.02); border: none !important; }
    .well { border-radius: 12px !important; box-shadow: 0 2px 10px rgba(80, 78, 118, 0.05) !important; border: 1px solid #C4C3E3 !important; background-color: #fcfbf5 !important; }
    .modal-content { border-radius: 12px !important; overflow: hidden; border: none !important; box-shadow: 0 10px 30px rgba(80, 78, 118, 0.15) !important; }
    .table > thead > tr > th { color: #333 !important; font-weight: 600; }
    .table > tbody > tr > td { color: #222 !important; font-weight: 500; }
    .container-fluid { padding-right: 20px !important; padding-left: 20px !important; }

</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #ffffff; border-bottom: 1px solid #C4C3E3;">
                <h3 class="panel-title" style="font-weight: bold; color: #504E76;"><i class="fa fa-upload"></i> Importación</h3>
            </div>
            <div class="panel-body text-center" style="padding: 10px; display: flex; flex-direction: column; align-items: center; gap: 40px;">
        <!-- Contenedores de alerta para cuando se cierre el modal -->
        <div id="mensajeExito" class="alert alert-success" style="display: none; text-align: left;"></div>
        <div id="mensajeError" class="alert alert-danger" style="display: none; text-align: left;"></div>

        <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#modalImportadorAadd" style="box-shadow: 0 4px 10px rgba(80, 78, 118, 0.2) !important;">
            <i class="fa fa-upload"></i> Importación beneficio diario
        </button>
        
        @include('aadd::Componentes.navegacion-chips', [
            'pantalla' => 'importaciones',
            'containerStyle' => 'display: flex; justify-content: center; gap: 10px; width: 100%;',
            'chipStyle' => 'margin: 0;'
        ])
    </div>
        </div>
        @include('aadd::Componentes.tablaEstadoMensual')
    </div>
</div>

@include('aadd::Modals.modalImportadorAadd')

@include('aadd::Modals.modalDetallesArchivos')
@endsection

@section('contenidoAyuda')
<div class="col-md-12">
    <h5>Importador de Archivos de Beneficio Diario</h5>
    <p>Esta interfaz permite gestionar y visualizar la importación de archivos de las plataformas.</p>
    <ul>
        <li><strong>Importación:</strong> Utilice el botón "Importación beneficio diario" para abrir el modal de carga. Allí podrá seleccionar múltiples archivos, indicando para cada uno la plataforma y fecha correspondiente antes de importarlos.</li>
        <li><strong>Visualización de Estado:</strong> En la tabla inferior se muestra el estado de las importaciones realizadas a nivel mensual, organizado por día y plataforma.</li>
        <li><strong>Detalles por Día:</strong> Puede interactuar con la tabla de estado para ver un listado detallado de los archivos que ya fueron procesados y cargados para una fecha y plataforma específica.</li>
    </ul>
</div>
@endsection

@section('scripts')
@include('aadd::Componentes.scripts-paginacion')
<script>
    window.plataformas_disponibles = {!! json_encode($plataformas ?? []) !!};

    $(document).ready(function() {
    var plataformas = window.plataformas_disponibles || [];
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

            var hoy = new Date().toISOString().split('T')[0];

            archivosSeleccionados.forEach(function(archivo, index) {
                var tr = $('<tr>').attr('data-index', index);
                
                var tdNombre = $('<td>').text(archivo.name);
                
                var selectPlataforma = $('<select>').addClass('form-control select-plataforma').prop('required', true).html(opcionesPlataforma);
                var tdPlataforma = $('<td>').append(selectPlataforma);
                
                var inputFecha = $('<input>').attr('type', 'date').addClass('form-control input-fecha').val(hoy).prop('required', true);
                var tdFecha = $('<td>').append(inputFecha);
                
                var tdEstado = $('<td>').addClass('td-estado').html('<span class="text-muted"><i class="fa fa-clock-o"></i> Pendiente</span>');
                
                var btnEliminar = $('<button>').addClass('btn btn-danger btn-xs btn-eliminar-archivo').attr('title', 'Eliminar').html('<i class="fa fa-trash"></i>');
                var tdAccion = $('<td>').append(btnEliminar);

                tr.append(tdNombre).append(tdPlataforma).append(tdFecha).append(tdEstado).append(tdAccion);
                tbody.append(tr);
            });
        } else {
            $('#listaArchivosPreview').hide();
        }
    });

    // Evento para eliminar archivos de la lista antes de subir
    $('#tbodyArchivosPreview').on('click', '.btn-eliminar-archivo', function(e) {
        e.preventDefault();
        var tr = $(this).closest('tr');
        var index = tr.attr('data-index');
        
        // Marcar como nulo en el array
        archivosSeleccionados[index] = null;
        
        // Eliminar la fila
        tr.remove();
        
        // Si no quedan filas, esconder tabla
        if ($('#tbodyArchivosPreview tr').length === 0) {
            $('#listaArchivosPreview').hide();
            $('#archivoModal').val(''); // Limpiar el input para permitir elegir los mismos archivos otra vez
            archivosSeleccionados = [];
        }
    });

    $('#btn-importar-ajax').click(function(e) {
        e.preventDefault();
        
        var tieneArchivos = false;
        $('#tbodyArchivosPreview tr').each(function() {
            var index = $(this).attr('data-index');
            if (archivosSeleccionados[index]) {
                tieneArchivos = true;
            }
        });

        if (!tieneArchivos) {
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
        formData.append('_token', $('meta[name="_token"]').attr('content') || $('input[name="_token"]').val());

        // Recorrer filas para empaquetar archivo original + plataforma + fecha
        $('#tbodyArchivosPreview tr').each(function() {
            var index = $(this).attr('data-index');
            var archivo = archivosSeleccionados[index];
            if (!archivo) return; // Si es null (eliminado), ignorar
            
            var plataforma = $(this).find('.select-plataforma').val();
            var fecha = $(this).find('.input-fecha').val();
            
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
    
    // Inicializar paginación al cargar la vista
    if (typeof paginarTabla === 'function') {
        paginarTabla('#contenedor-tabla-mensual table', 30);
    }
});

window.actualizarTablaMensual = function(mes) {
    if (!mes) return;
    $.ajax({
        url: '/aadd/importaciones',
        type: 'GET',
        data: { mes: mes },
        success: function(response) {
            $('#contenedor-tabla-mensual').replaceWith(response);
            if (typeof paginarTabla === 'function') {
                paginarTabla('#contenedor-tabla-mensual table', 30);
            }
        },
        error: function(err) {
            console.error('Error al actualizar la tabla mensual', err);
        }
    });
};

window.verDetallesArchivos = function(fecha, plataforma) {
    $('#modalDetallesArchivosLabel').html('<i class="fa fa-list"></i> Archivos Cargados - ' + plataforma + ' (' + fecha + ')');
    $('#listaArchivosDetalle').empty().hide();
    $('#mensajeSinArchivos').hide();
    $('#cargandoDetalles').show();
    $('#modalDetallesArchivos').modal('show');

    $.ajax({
        url: '/aadd/detalles-archivos',
        type: 'GET',
        data: { fecha: fecha, plataforma: plataforma },
        success: function(response) {
            $('#cargandoDetalles').hide();
            if (response.archivos && response.archivos.length > 0) {
                var ul = $('#listaArchivosDetalle');
                response.archivos.forEach(function(archivo) {
                    var tipoStr = archivo.tipo_archivo ? archivo.tipo_archivo.replace(/_/g, ' ') : 'Desconocido';
                    ul.append('<li class="list-group-item"><i class="fa fa-file-text-o text-info"></i> <strong>' + tipoStr + '</strong>: ' + archivo.nombre + '</li>');
                });
                ul.show();
            } else {
                $('#mensajeSinArchivos').show();
            }
        },
        error: function(err) {
            $('#cargandoDetalles').hide();
            $('#mensajeSinArchivos').text('Error al buscar los archivos.').show();
        }
    });
};
</script>
@endsection