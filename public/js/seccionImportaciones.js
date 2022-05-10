$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Importaciones');
  $('#fecha_busqueda,#mesInfoImportacion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  });

  $('#fechaProducido').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fechaBeneficio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'mm/yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date());

  if($('#plataforma_busqueda option').length == 2 ){
    $('#plataforma_busqueda option:eq(1)').prop('selected', true);
  }

  $('#tipo_archivo').change();
  //Paginar
  $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
  $('#plataformaInfoImportacion').val(1);
  $('#monedaInfoImportacion').val(1);
  $('#plataformaInfoImportacion').change();
});


$('#plataformaInfoImportacion').change(function() {
    $('#monedaInfoImportacion').change();
});

$('#mesInfoImportacion').on("change.datetimepicker",function(){
  $('#monedaInfoImportacion').change();
});

$('#monedaInfoImportacion').change(function() {
    const id_plataforma = $('#plataformaInfoImportacion').val();
    const id_moneda = $(this).val();
    const fecha_sort = $('#infoImportaciones .activa').attr('estado');
    cargarTablasImportaciones(id_plataforma, id_moneda, fecha_sort);
});

function limpiarBodysImportaciones() {
    $('#infoImportaciones tbody tr').not('#moldeFilaImportacion').remove();
    $('#infoImportaciones tbody').hide();
}

function cargarTablasImportaciones(plataforma, moneda, fecha_sort) {
    const fecha = $('#mes_info_hidden').val();
    const url = fecha.size == 0? '/' : ('/' + fecha);
    $.get('importaciones/' + plataforma + url + '/' + (fecha_sort? fecha_sort : ''), function(data) {
        const tablaBody = $('#infoImportaciones tbody');

        console.log("Plataforma: ", plataforma);

        limpiarBodysImportaciones();

        for (let i = 0; i < data.arreglo.length; i++) {
          const moldeFilaImportacion = $('#moldeFilaImportacion').clone();
          moldeFilaImportacion.removeAttr('id');
          moldeFilaImportacion.find('.fecha').text(convertirDate(data.arreglo[i].fecha));
          moldeFilaImportacion.find('.producido_juegos').addClass(data.arreglo[i].producido[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.producido_jugadores').addClass(data.arreglo[i].prod_jug[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.beneficio_juegos').addClass(data.arreglo[i].beneficio[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.producido_poker').addClass(data.arreglo[i].prod_poker[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.beneficio_poker').addClass(data.arreglo[i].benef_poker[moneda]? 'true' : 'false');
          tablaBody.append(moldeFilaImportacion);
          moldeFilaImportacion.show();
        }

        tablaBody.show();
    });

    $('#moldeFilaImportacion').hide();
}

function obtenerFechaString(dateFecha, conDia) {
    var arrayFecha = dateFecha.split('/');
    console.log(arrayFecha);
    var meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

    if (conDia) {
      return arrayFecha[0] + ' ' +  meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
    }
    else return meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
}

//Opacidad del modal al minimizar
$('#btn-minimizar,#btn-minimizarProducidos,#btn-minimizarBeneficios').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).on('click','.planilla', function(){
  //Mostrar el título correspondiente
  $('#modalPlanilla h3.modal-title').text('VISTA PREVIA '+$('#tipo_archivo option:selected').text());

  //Limpiar el modal
  $('#modalPlanilla #fecha').val('');
  $('#modalPlanilla #plataforma').val('');
  $('#modalPlanilla #tipo_moneda').val('');
  $('#modalPlanilla').attr('data-id',$(this).val());
  $('#modalPlanilla').attr('data-page','0');
  $('#modalPlanilla').attr('data-size',30);
  const tipo_importacion = $('#tipo_archivo').val();
  const head = $('#tablaVistaPrevia thead');
  head.empty();
  $('#tablaVistaPrevia tbody tr').remove();
  head.append(clonarFilaHeader(tipo_importacion));
  actualizarPreview(tipo_importacion,$(this).val(),0,30);

  //Mostrar el modal de la vista previa
  $('#modalPlanilla').modal('show');
});

function actualizarPreview(tipo_importacion,id,page,size){
  $('#prevPreview').attr('disabled',page == 0);
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importaciones/previewImportacion',
    data: {tipo_importacion: tipo_importacion,id: id,page: page,size: size},
    dataType: 'json',
    success: function (data) {
      $('#previewPage').text(page+1);
      const totales = Math.ceil(data.cant_detalles/size);
      $('#previewTotal').text(totales);
      $('#nextPreview').attr('disabled',(page+1) >= totales);
      $('#modalPlanilla #fecha').val(convertirDate(data.fecha));
      $('#modalPlanilla #plataforma').val(data.plataforma.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      $('#tablaVistaPrevia tbody tr').remove();
      for (var i = 0; i < data.detalles.length; i++) {
        agregarFilaDetalle(tipo_importacion,data.detalles[i]);
      }
    },
    error: function (data) {
      console.log(data);
    }
  });
}

$('#prevPreview,#nextPreview').click(function(){
  const id = parseInt($('#modalPlanilla').attr('data-id'));
  let page = parseInt($('#modalPlanilla').attr('data-page'));

  if($(this).attr('id') == 'prevPreview'){ page  = Math.max(page-1,0); }
  else if($(this).attr('id') == 'nextPreview'){ page++; }

  $('#modalPlanilla').attr('data-page',page);
  const size = parseInt($('#modalPlanilla').attr('data-size'));
  const tipo = $('#tipo_archivo').val();
  actualizarPreview(tipo,id,page,size);
});

$('#tipo_archivo').change(function(){$('#btn-buscarImportaciones').click();});
$('#plataforma_busqueda').change(function(){$('#btn-buscarImportaciones').click();});
$('#fecha_busqueda_input').change(function(){$('#btn-buscarImportaciones').click();});
$('#moneda_busqueda').change(function(){$('#btn-buscarImportaciones').click();});

$(document).on('click','.borrar',function(){
  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  const id = $(this).val();
  const tipo = $(this).attr('data-tipo');
  $('#btn-eliminarModal').val(id).attr('data-tipo',tipo);
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ $('#tipo_archivo option').filter(function(){return $(this).val() == tipo}).text() + '?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })

  const camelCase = $(this).attr('data-tipo').val().toLowerCase().replace(/([-_][a-z])/g, function(group){//producido_juegos -> producidoJuegos
    return group.toUpperCase().replace('-', '').replace('_', ''); 
  });
  const id_importacion = $(this).val();
  $.ajax({
    type: "DELETE",
    url: 'importaciones/'+camelCase[0].toUpperCase()+camelCase.slice(1)+'/'+id_importacion,
    success: function (data) {
      $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
      $('#monedaInfoImportacion').change();
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

/*********************** PRODUCIDOS *********************************/
function clonarFila(modo,detalle){
  const fila = $(`.moldeDiario.${modo}`).clone().removeClass('moldeDiario');
  fila.find('td').each(function(){
    const attr = detalle[$(this).attr('data-atributo')];
    $(this).text(attr).attr('title',attr);
  });
  return fila;
}
function clonarFilaHeader(modo){
  const fila = $(`.moldeDiarioHeader.${modo}`).clone().removeClass('moldeDiarioHeader');
  return fila;
}
function agregarFilaDetalle(modo,detalle){
  $('#tablaVistaPrevia tbody').append(clonarFila(modo,detalle));
}

function reiniciarModalImportarProducido(){
  //Mostrar: rowArchivo
  $('#modalImportacionDiario #rowArchivo').show();
  $('#fechaProducido').data('datetimepicker').reset();
  $('#plataformaProducido').val("");
  $('#monedaProducido').val("");
  $('#datosProducido').hide(); 
  $('#modalImportacionDiario #rowArchivo').show();
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionDiario #mensajeError').hide();
  $('#modalImportacionDiario #mensajeInvalido').hide();
  $('#modalImportacionDiario #datosProducido').hide();
  $('#modalImportacionDiario #iconoCarga').hide();
  $('#modalImportacionDiario #archivo')[0].files[0] = null;
  $('#modalImportacionDiario #archivo').attr('data-borrado','false');
  $("#modalImportacionDiario #archivo").fileinput('destroy').fileinput({
      language: 'es',
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
  });
  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
}

$('#btn-importarProducidos').click(function(e){
  e.preventDefault();
  $('#modalImportacionDiario .modal-title').text("| IMPORTAR PRODUCIDO");
  $('#modalImportacionDiario').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
  $('#mensajeExito').hide();
  $('#modalImportacionDiario').data('modo','producido_juegos').modal('show');
});

$('#btn-importarProducidosJugadores').click(function(e){
  e.preventDefault();
  $('#modalImportacionDiario .modal-title').text("| IMPORTAR PRODUCIDO JUGADORES");
  $('#modalImportacionDiario').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
  $('#mensajeExito').hide();
  $('#modalImportacionDiario').data('modo','producido_jugadores').modal('show');
});

$('#btn-importarProducidosPoker').click(function(e){
  e.preventDefault();
  $('#modalImportacionDiario .modal-title').text("| IMPORTAR PRODUCIDO POKER");
  $('#modalImportacionDiario').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
  $('#mensajeExito').hide();
  $('#modalImportacionDiario').data('modo','producido_poker').modal('show');
});

$('#btn-guardarProducido').on('click',function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  let formData = new FormData();
  const id_plataforma = $('#plataformaProducido').val();
  const fecha = $('#fechaProducido_hidden').val();
  const id_tipo_moneda = $('#monedaProducido').val();
  formData.append('id_plataforma', id_plataforma);
  formData.append('fecha', fecha);
  formData.append('id_tipo_moneda', id_tipo_moneda);

  $('#plataformaInfoImportacion').val(id_plataforma);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  const date = $('#fechaProducido').data('datetimepicker').getDate();
  $('#mesInfoImportacion').data('datetimepicker').setDate(date);
  $('#plataformaInfoImportacion').change();
  
  //Si subió archivo lo guarda
  if($('#modalImportacionDiario #archivo').attr('data-borrado') == 'false' && $('#modalImportacionDiario #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionDiario #archivo')[0].files[0]);
  }

  const url_modo = {
    'producido_juegos'    : 'importaciones/importarProducido',
    'producido_jugadores' : 'importaciones/importarProducidoJugadores',
    'producido_poker'     : 'importaciones/importarProducidoPoker',
  };
  const modo = $('#modalImportacionDiario').data('modo');
  if(!(modo in url_modo)) return;
  $.ajax({
      type: "POST",
      url: url_modo[modo],
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionDiario').find('.modal-footer').children().hide();
        $('#modalImportacionDiario').find('.modal-body').children().hide();
        $('#modalImportacionDiario').find('.modal-body').children('#iconoCarga').show();
      },
      success: function (data) {
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
        $('#modalImportacionDiario').modal('hide');
        limpiarBodysImportaciones();
        $('#plataformaInfoImportacion').change();
        $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN');
        text = data.cantidad_registros + ' registro(s) fueron importados'
        if(data.juegos_multiples_reportes > 0) text += '<br>' + data.juegos_multiples_reportes + ' juego(s) reportaron multiples veces';
        if(data.jugadores_multiples_reportes > 0) text += '<br>' + data.jugadores_multiples_reportes + ' jugador(es) reportaron multiples veces';
        $('#mensajeExito p').html(text);
        $('#mensajeExito').show();
      },
      error: function (data) {
        //alerta de error si el archivo ya se encuentra cargado y validado.
        var response = JSON.parse(data.responseText);
        if(response.producido_validado !== 'undefined'){
          $('#mensajeError h6').text('El Producido para esa fecha ya está validado y no se puede reimportar.')
        }
        //Mostrar: mensajeError
        $('#modalImportacionDiario #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionDiario #rowArchivo').hide();
        $('#modalImportacionDiario #mensajeInvalido').hide();
        $('#modalImportacionDiario #datosProducido').hide();
        $('#modalImportacionDiario #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});

function toIso(f){
  //input fecha tipo 1/11/2020 06:00:00
  aux = f.split(' ');
  f = aux[0].split('/');
  const mm = (f[1].length==1? '0'+f[1]: f[1]);
  const dd = (f[0].length==1? '0'+f[0]: f[0]);
  return f[2]+'-'+mm+'-'+dd+'T'+aux[1];
}

function procesarDatosProducidos(e) {
  const csv = e.target.result;
  //Limpio retorno de carro y saco las lineas sin nada.
  const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(s => s.length > 0);
  const columnas_modos = {//@TODO: Unificar beneficio
    'producido_juegos' : 10, 'producido_jugadores' : 9, 'producido_poker' : 10,
  }
  const modo = $('#modalImportacionDiario').data('modo');
  const fail = function(){
    //Si no retorno arriba quiere decir que no era valido
    $('#modalImportacionDiario #mensajeInvalido p').text('El archivo no contiene producidos');
    $('#modalImportacionDiario #mensajeInvalido').show();
    $('#modalImportacionDiario #iconoCarga').hide();
    //Ocultar botón de subida
    $('#btn-guardarProducido').hide();
  };

  if(allTextLines.length <= 0 ||!(modo in columnas_modos)){
    return fail();
  }
  const columnas = columnas_modos[modo];
  if(allTextLines[0].split(',').length != columnas){
    return fail();
  }

  if(allTextLines.length == 1){
    $('#fechaProducido input').attr('disabled',false);
    $('#fechaProducido span').show()
  }
  else if (['producido_juegos','producido_jugadores','producido_poker'].includes(modo)) {//Si tiene filas, extraigo la fecha
    const date = allTextLines[1].split(',')[0].replaceAll('"','');//Saco las comillas
    $('#fechaProducido').data('datetimepicker').setDate(new Date(toIso(date)));
    $('#fechaProducido input').attr('disabled',true);
    $('#fechaProducido span').hide()
  }
  else{
    return fail();
  }
  $('#monedaProducido').val(1);
  $('#modalImportacionDiario #mensajeInvalido').hide();
  //Mostrar botón SUBIR
  $('#btn-guardarProducido').show();
  $('#datosProducido').show();
}

//Eventos de la librería del input
$('#modalImportacionDiario #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionDiario #datosProducido').hide();
   $('#modalImportacionDiario #mensajeInvalido').show();
   $('#modalImportacionDiario #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarProducido').hide();
});

$('#modalImportacionDiario #archivo').on('fileclear', function(event) {
  reiniciarModalImportarProducido();
});

$('#modalImportacionDiario #archivo').on('fileselect', function(event) {
  reiniciarModalImportarProducido();
  $('#modalImportacionDiario #archivo').attr('data-borrado','false');
  // Se lee el archivo guardado en el input de tipo 'file'.
  // se valida la importación.
  let reader = new FileReader();
  reader.onload = procesarDatosProducidos;
  reader.readAsText($('#modalImportacionDiario #archivo')[0].files[0]);
});

$('#btn-reintentarProducido').click(function(e) {
  e.preventDefault();
  $('#modalImportacionDiario').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
});

/*********************** BENEFICIOS *********************************/

function reiniciarModalImportarBeneficios(){
  $('#modalImportacionMensual #rowArchivo').show();
  $('#fechaBeneficio').data('datetimepicker').reset();
  $('#plataformaBeneficio').val("");
  $('#monedaBeneficio').val("");
  $('#datosBeneficio').hide();
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionMensual #mensajeError').hide();
  $('#modalImportacionMensual #mensajeInvalido').hide();
  $('#modalImportacionMensual #iconoCarga').hide();
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionMensual #archivo')[0].files[0] = null;
  $('#modalImportacionMensual #archivo').attr('data-borrado','false');
  $("#modalImportacionMensual #archivo").fileinput('destroy').fileinput({
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
  });
  $('#btn-guardarBeneficio').hide();
}

$('#btn-importarBeneficios').click(function(e){
  e.preventDefault();
  $('#modalImportacionMensual').find('.modal-footer').children().show();
  reiniciarModalImportarBeneficios();
  $('#modalImportacionMensual .modal-title').text("| IMPORTAR BENEFICIO");
  $('#modalImportacionMensual').data('modo','beneficio_juegos').modal('show');
  $('#modalImportacionMensual').modal('show');
});

$('#btn-importarBeneficiosPoker').click(function(e){
  e.preventDefault();
  $('#modalImportacionMensual').find('.modal-footer').children().show();
  reiniciarModalImportarBeneficios();
  $('#modalImportacionMensual .modal-title').text("| IMPORTAR BENEFICIO POKER");
  $('#modalImportacionMensual').data('modo','beneficio_poker').modal('show');
  $('#modalImportacionMensual').modal('show');
});

$('#btn-guardarBeneficio').on('click', function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  let formData = new FormData();
  const id_plataforma = $('#plataformaBeneficio').val();
  const fecha = $('#fechaBeneficio_hidden').val();
  const id_tipo_moneda = $('#monedaBeneficio').val();
  formData.append('id_plataforma', id_plataforma);
  formData.append('fecha', fecha);
  formData.append('id_tipo_moneda', id_tipo_moneda);

  $('#plataformaInfoImportacion').val(id_plataforma);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  const date = $('#fechaBeneficio').data('datetimepicker').getDate();
  $('#mesInfoImportacion').data('datetimepicker').setDate(date);
  $('#plataformaInfoImportacion').change();

  //Si subió archivo lo guarda
  if($('#modalImportacionMensual #archivo').attr('data-borrado') == 'false' && $('#modalImportacionMensual #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionMensual #archivo')[0].files[0]);
  }

  const urls = {'beneficio_juegos' : 'importaciones/importarBeneficio','beneficio_poker' : 'importaciones/importarBeneficioPoker'};
  const modo = $('#modalImportacionMensual').data('modo');
  if(!(modo in urls)) return;

  $.ajax({
    type: "POST",
    url: urls[modo],
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      console.log('Empezó');
      $('#modalImportacionMensual').find('.modal-footer').children().hide();
      $('#modalImportacionMensual').find('.modal-body').children().hide();
      $('#modalImportacionMensual').find('.modal-body').children('#iconoCarga').show();
    },
    complete: function(data){
      console.log('Terminó');
    },
    success: function (data) {
      $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
      $('#modalImportacionMensual').modal('hide');
      limpiarBodysImportaciones();
      $('#plataformaInfoImportacion').change();
      $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN BENEFICIO');
      $('#mensajeExito p').text(data.dias + ' registro(s) del BENEFICIO fueron importados');
      $('#mensajeExito').show();
    },
    error: function (data) {
      //Mostrar: mensajeError
      $('#modalImportacionMensual #mensajeError').show();
      //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
      $('#modalImportacionMensual #rowArchivo').hide();
      $('#modalImportacionMensual #datosBeneficio').hide();
      $('#modalImportacionMensual #mensajeInvalido').hide();
      $('#modalImportacionMensual #iconoCarga').hide();
      console.log('ERROR!');
      console.log(data);
    }
  });
});

function procesarDatosProducidos(e) {
  $('#monedaProducido').val(1);
  $('#modalImportacionDiario #mensajeInvalido').hide();
  //Mostrar botón SUBIR
  $('#btn-guardarProducido').show();
  $('#datosProducido').show();
}

function procesarDatosBeneficios(e) {
  const csv = e.target.result;
  //Limpio retorno de carro y saco las lineas sin nada.
  const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(s => s.length > 0);
  const columnas_modos = {
    'beneficio_juegos' : 16, 'beneficio_poker' : 13,
  }
  const modo = $('#modalImportacionMensual').data('modo');
  const fail = function(){
    //Si no retorno arriba quiere decir que no era valido
    $('#modalImportacionMensual #mensajeInvalido p').text('El archivo no contiene beneficios');
    $('#modalImportacionMensual #mensajeInvalido').show();
    $('#modalImportacionMensual #iconoCarga').hide();
    //Ocultar botón de subida
    $('#btn-guardarBeneficio').hide();
  };
  if(allTextLines.length <= 0 ||!(modo in columnas_modos)){
    return fail();
  }
  const columnas = columnas_modos[modo];
  if(allTextLines[0].split(',').length != columnas){
    return fail();
  }
  if(allTextLines.length == 2){
    $('#fechaBeneficio input').attr('disabled',false);
    $('#fechaBeneficio span').show();
    $('#monedaBeneficio').attr('disabled',false);
  }
  else if (allTextLines.length > 2) {//Si tiene filas, extraigo la fecha
    const date = allTextLines[1].split(',')[1].replaceAll('"','');//Saco las comillas
    $('#fechaBeneficio').data('datetimepicker').setDate(new Date(toIso(date)));
    $('#fechaBeneficio input').attr('disabled',true);
    $('#fechaBeneficio span').hide();
    const moneda = allTextLines[1].split(',')[2];
    $(`#monedaBeneficio option:contains(${moneda})`).prop('selected',true)
    $('#monedaBeneficio').attr('disabled',true);
  }
  else{
    return fail();
  }
  $('#modalImportacionMensual #mensajeInvalido').hide();
  //Mostrar botón SUBIR
  $('#btn-guardarBeneficio').show();
  $('#datosBeneficio').show();
}

//Eventos de la librería del input
$('#modalImportacionMensual #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacionMensual #datosProducido').hide();
  $('#modalImportacionMensual #mensajeInvalido').show();
  $('#modalImportacionMensual #mensajeInvalido p').text(msg);
  //Ocultar botón SUBIR
  $('#btn-guardarBeneficio').hide();
});

$('#modalImportacionMensual #archivo').on('fileclear', function(event) {
  reiniciarModalImportarBeneficios();
});

$('#modalImportacionMensual #archivo').on('fileselect', function(event) {
  reiniciarModalImportarBeneficios();
  $('#modalImportacionMensual #archivo').attr('data-borrado','false');
  // Se lee el archivo guardado en el input de tipo 'file'.
  // se valida la importación.
  var reader = new FileReader();
  reader.readAsText($('#modalImportacionMensual #archivo')[0].files[0]);
  reader.onload = procesarDatosBeneficios;
});

$('#btn-reintentarBeneficio').click(function(e) {
  e.preventDefault();
  $('#modalImportacionMensual').find('.modal-footer').children().show();
  reiniciarModalImportarBeneficios();
  $('#mensajeExito').hide();
});

/*****************PAGINACION******************/

function agregarFilasImportaciones(data) {
  var fila = $('<tr>');
  fila.append($('<td>').addClass('col-xs-3').text("-"));
  fila.append($('<td>').addClass('col-xs-3').text(convertirDate(data.fecha)));
  fila.append($('<td>').addClass('col-xs-2').text(data.plataforma));
  fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
  fila.append($('<td>').addClass('col-xs-2')
    .append($('<button>').addClass('btn btn-info planilla').val(data.id)
      .append($('<i>').addClass('far fa-fw fa-file-alt'))
    )
    .append($('<button>').addClass('btn btn-danger borrar').val(data.id)
      .append($('<i>').addClass('fa fa-fw fa-trash-alt'))
    )
  );
  $('#tablaImportaciones tbody').append(fila);
}

//Detectar el cambio de TIPO DE ARCHIVO
$('#tipo_archivo').on('change',function(){
  var tipo_archivo = $('#tipo_archivo').val();

  switch (tipo_archivo) {
    case '2':
      $('#tablaImportaciones #tipo_fecha').attr('value',"producido.fecha");
      break
    case '3':
      $('#tablaImportaciones #tipo_fecha').attr('value',"beneficio_mensual.fecha");
      break;
  }
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaImportaciones .activa').attr('value');
  var orden = $('#tablaImportaciones .activa').attr('estado');
  $('#btn-buscarImportaciones').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaImportaciones thead tr th[value]',function(e){
  $('#tablaImportaciones th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaImportaciones th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$('#btn-buscarImportaciones').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })

  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaImportaciones .activa').attr('value'),orden: $('#tablaImportaciones .activa').attr('estado')} ;
  if(sort_by == null){ //limpio las columnas
    $('#tablaImportaciones th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    fecha: $('#fecha_busqueda_hidden').val(),
    id_plataforma: $('#plataforma_busqueda').val(),
    id_tipo_moneda: $('#moneda_busqueda').val(),
    tipo_archivo: $('#tipo_archivo').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };

  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      for (let i = 0; i < resultados.data.length; i++) {
        agregarFilasImportaciones(resultados.data[i]);
      }
      $('#tablaImportaciones tbody button').attr('data-tipo',formData.tipo_archivo);
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

$(document).on('click', '#infoImportaciones thead tr th[value]', function(e) {
  $('#infoImportaciones th').removeClass('activa');
  if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
      $(e.currentTarget).children('i')
          .removeClass('fa-sort').addClass('fa fa-sort-desc')
          .parent().addClass('activa').attr('estado', 'desc');
  } else {
      if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
          $(e.currentTarget).children('i')
              .removeClass('fa-sort-desc').addClass('fa fa-sort-asc')
              .parent().addClass('activa').attr('estado', 'asc');
      } else {
          $(e.currentTarget).children('i')
              .removeClass('fa-sort-asc').addClass('fa fa-sort')
              .parent().attr('estado', '');
      }
  }
  $('#infoImportaciones th:not(.activa) i')
      .removeClass().addClass('fa fa-sort')
      .parent().attr('estado', '');
  
  $('#plataformaInfoImportacion').change();
});