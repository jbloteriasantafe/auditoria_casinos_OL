//Opacidad del modal al minimizar
$('#btn-minimizarProducidos').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarBeneficios').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Importaciones');
  $('#opcImportaciones').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');

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

  setearValueFecha();
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
          moldeFilaImportacion.find('.producido').addClass(data.arreglo[i].producido[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.producido_jugadores').addClass(data.arreglo[i].prod_jug[moneda]? 'true' : 'false');
          moldeFilaImportacion.find('.beneficio').addClass(data.arreglo[i].beneficio[moneda]? 'true' : 'false');
          tablaBody.append(moldeFilaImportacion);
          moldeFilaImportacion.show();
        }

        tablaBody.show();
    });

    $('#moldeFilaImportacion').hide();
}


function setearValueFecha() {
  var tipo_archivo = $('#tipo_archivo').val();

  switch (tipo_archivo) {
    case '2':
      $('#tablaImportaciones #tipo_fecha').attr('value',"producido.fecha");
      break
    case '3':
      $('#tablaImportaciones #tipo_fecha').attr('value',"beneficio_mensual.fecha");
      break;
  }
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
$('#btn-minimizar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).on('click','.planilla', function(){
  const tipo_importacion = $('#tipo_archivo').val();
  const titulo = {2:'VISTA PREVIA PRODUCIDO',3:'VISTA PREVIA BENEFICIO'}[tipo_importacion];
  //Mostrar el título correspondiente
  $('#modalPlanilla h3.modal-title').text(titulo);

  //Limpiar el modal
  $('#modalPlanilla #fecha').val('');
  $('#modalPlanilla #plataforma').val('');
  $('#modalPlanilla #tipo_moneda').val('');
  $('#modalPlanilla').attr('data-id',$(this).val());
  $('#modalPlanilla').attr('data-page','0');
  $('#modalPlanilla').attr('data-size',30);
  $('#modalPlanilla').attr('data-tipo',tipo_importacion);
  const head = $('#tablaVistaPrevia thead tr');
  head.children().remove();
  $('#tablaVistaPrevia tbody tr').remove();
  if(tipo_importacion == "PRODUCIDO"){
    head.append($('<th>').addClass('col-xs-1').append('JUEGO'));
    head.append($('<th>').addClass('col-xs-1').append('CATEGORIA'));
    head.append($('<th>').addClass('col-xs-1').append('JUGADORES'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO'));
    actualizarPreviewProducidos($(this).val(),0,30);
  }
  else if(tipo_importacion == "PRODJUG"){
    head.append($('<th>').addClass('col-xs-2').append('JUGADOR'));
    head.append($('<th>').addClass('col-xs-1').append('JUEGOS'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('APUESTA'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('PREMIO'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO (Ef)'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO (Bo)'));
    head.append($('<th>').addClass('col-xs-1').append('BENEFICIO'));
    actualizarPreviewProducidosJugadores($(this).val(),0,30);
  }
  else if(tipo_importacion == "BENEFICIO"){
    head.append($('<th>').addClass('col-xs-1').append('FECHA'));
    head.append($('<th>').addClass('col-xs-1').append('JUGADORES'));
    head.append($('<th>').addClass('col-xs-2').append('DEPOSITOS'));
    head.append($('<th>').addClass('col-xs-2').append('RETIROS'));
    head.append($('<th>').addClass('col-xs-2').append('APUESTA'));
    head.append($('<th>').addClass('col-xs-2').append('PREMIO'));
    head.append($('<th>').addClass('col-xs-2').append('BENEFICIO'));
    actualizarPreviewBeneficios($(this).val(),0,30)
  }

  //Mostrar el modal de la vista previa
  $('#modalPlanilla').modal('show');
});

function actualizarPreviewBeneficios(id_beneficio_mensual,page,size){
  $('#prevPreview').attr('disabled',page == 0);
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: 'POST',
    url: 'importaciones/previewBeneficio',
    data: {id_beneficio_mensual: id_beneficio_mensual,page: page,size: size},
    dataType: 'json',
    success: function (data) {
      $('#previewPage').text(page+1);
      const totales = Math.ceil(data.cant_detalles/size);
      $('#previewTotal').text(totales);
      $('#nextPreview').attr('disabled',(page+1) >= totales);
      $('#modalPlanilla #fecha').val(convertirDate(data.beneficio_mensual.fecha));
      $('#modalPlanilla #plataforma').val(data.plataforma.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      $('#tablaVistaPrevia tbody tr').remove();
      for (var i = 0; i < data.beneficios.length; i++) {
        agregarFilaDetalleBeneficio(data.beneficios[i]);
      }
    },
    error: function (data) {
      console.log(data);
    }
  });
}

function actualizarPreviewProducidos(id_producido,page,size){
  $('#prevPreview').attr('disabled',page == 0);
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: 'POST',
    url: 'importaciones/previewProducido',
    data: {id_producido: id_producido,page: page,size: size},
    dataType: 'json',
    success: function (data) {
      $('#previewPage').text(page+1);
      const totales = Math.ceil(data.cant_detalles/size);
      $('#previewTotal').text(totales);
      $('#nextPreview').attr('disabled',(page+1) >= totales);
      $('#modalPlanilla #fecha').val(convertirDate(data.producido.fecha));
      $('#modalPlanilla #plataforma').val(data.plataforma.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      $('#tablaVistaPrevia tbody tr').remove();
      for (var i = 0; i < data.detalles_producido.length; i++) {
        agregarFilaDetalleProducido(data.detalles_producido[i]);
      }
    },
    error: function (data) {
      console.log(data);
    }
  });
}

function actualizarPreviewProducidosJugadores(id_producido_jugadores,page,size){
  $('#prevPreview').attr('disabled',page == 0);
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: 'POST',
    url: 'importaciones/previewProducidoJugadores',
    data: {id_producido_jugadores: id_producido_jugadores,page: page,size: size},
    dataType: 'json',
    success: function (data) {
      $('#previewPage').text(page+1);
      const totales = Math.ceil(data.cant_detalles/size);
      $('#previewTotal').text(totales);
      $('#nextPreview').attr('disabled',(page+1) >= totales);
      $('#modalPlanilla #fecha').val(convertirDate(data.producido_jugadores.fecha));
      $('#modalPlanilla #plataforma').val(data.plataforma.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      $('#tablaVistaPrevia tbody tr').remove();
      for (var i = 0; i < data.detalles_producido_jugadores.length; i++) {
        agregarFilaDetalleProducidoJugadores(data.detalles_producido_jugadores[i]);
      }
    },
    error: function (data) {
      console.log(data);
    }
  });
}


$('#prevPreview').click(function(){
  const id = parseInt($('#modalPlanilla').attr('data-id'));
  let page = parseInt($('#modalPlanilla').attr('data-page'));
  page = Math.max(page-1,0)
  $('#modalPlanilla').attr('data-page',page);
  const size = parseInt($('#modalPlanilla').attr('data-size'));
  const tipo = $('#modalPlanilla').attr('data-tipo');
  if(tipo == "PRODUCIDO") actualizarPreviewProducidos(id,page,size)
  if(tipo == "PRODJUG") actualizarPreviewProducidosJugadores(id,page,size)
  else if(tipo == "BENEFICIO") actualizarPreviewBeneficios(id,page,size);
});

$('#nextPreview').click(function(){
  const id = parseInt($('#modalPlanilla').attr('data-id'));
  let page = parseInt($('#modalPlanilla').attr('data-page'));
  page++;
  $('#modalPlanilla').attr('data-page',page);
  const size = parseInt($('#modalPlanilla').attr('data-size'));
  const tipo = $('#modalPlanilla').attr('data-tipo');
  if(tipo == "PRODUCIDO") actualizarPreviewProducidos(id,page,size)
  else if(tipo == "PRODJUG") actualizarPreviewProducidosJugadores(id,page,size)
  else if(tipo == "BENEFICIO") actualizarPreviewBeneficios(id,page,size);
});

$('#tipo_archivo').change(function(){$('#btn-buscarImportaciones').click();});
$('#plataforma_busqueda').change(function(){$('#btn-buscarImportaciones').click();});
$('#fecha_busqueda_input').change(function(){$('#btn-buscarImportaciones').click();});
$('#moneda_busqueda').change(function(){$('#btn-buscarImportaciones').click();});

$(document).on('click','.borrar',function(){
  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val($(this).val()).attr('data-tipo',$('#tipo_archivo').val());
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ $('#tipo_archivo option:selected').text() + '?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  var id_importacion = $(this).val();
  var tipo_archivo = $(this).attr('data-tipo');
  console.log('Borrar ' + tipo_archivo + ': ' + id_importacion);

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  let url = 'importaciones';
  switch(tipo_archivo){
    case 'PRODUCIDO':
      url += "/eliminarProducido/" + id_importacion;
      break;
    case 'PRODJUG':
      url += "/eliminarProducidoJugadores/" + id_importacion;
      break;
    case 'BENEFICIO':
      url += "/eliminarBeneficioMensual/" + id_importacion;
      break;
    default:
      return;
  }

  $.ajax({
      type: "DELETE",
      url: url,
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
function agregarFilaDetalleProducido(detprod) {
  var fila = $('<tr>');
  fila.append($('<td>').addClass('col-xs-1').append(detprod.cod_juego));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.categoria));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.jugadores));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.apuesta_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.apuesta_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.apuesta));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.premio_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.premio_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.premio));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.beneficio_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.beneficio_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprod.beneficio));
  $('#tablaVistaPrevia tbody').append(fila);
}
function agregarFilaDetalleProducidoJugadores(detprodjug) {
  var fila = $('<tr>');
  fila.append($('<td>').addClass('col-xs-2').append(detprodjug.jugador));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.juegos));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.apuesta_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.apuesta_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.apuesta));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.premio_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.premio_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.premio));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.beneficio_efectivo));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.beneficio_bono));
  fila.append($('<td>').addClass('col-xs-1').append(detprodjug.beneficio));
  $('#tablaVistaPrevia tbody').append(fila);
}

function reiniciarModalImportarProducido(){
  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();
  $('#fechaProducido').data('datetimepicker').reset();
  $('#plataformaProducido').val("");
  $('#monedaProducido').val("");
  $('#datosProducido').hide(); 
  $('#modalImportacionProducidos #rowArchivo').show();
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #datosProducido').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();
  $('#modalImportacionProducidos #archivo')[0].files[0] = null;
  $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
  $("#modalImportacionProducidos #archivo").fileinput('destroy').fileinput({
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
  $('#modalImportacionProducidos .modal-title').text("| IMPORTAR PRODUCIDO");
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
  $('#mensajeExito').hide();
  $('#modalImportacionProducidos').data('modo','juegos').modal('show');
});

$('#btn-importarProducidosJugadores').click(function(e){
  e.preventDefault();
  $('#modalImportacionProducidos .modal-title').text("| IMPORTAR PRODUCIDO JUEGOS");
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
  $('#mensajeExito').hide();
  $('#modalImportacionProducidos').data('modo','jugadores').modal('show');
});

$('#btn-guardarProducido').on('click',function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

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
  if($('#modalImportacionProducidos #archivo').attr('data-borrado') == 'false' && $('#modalImportacionProducidos #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionProducidos #archivo')[0].files[0]);
  }

  const modo = $('#modalImportacionProducidos').data('modo') == "jugadores" ? "Jugadores" : "";
  $.ajax({
      type: "POST",
      url: 'importaciones/importarProducido' + modo,
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionProducidos').find('.modal-footer').children().hide();
        $('#modalImportacionProducidos').find('.modal-body').children().hide();
        $('#modalImportacionProducidos').find('.modal-body').children('#iconoCarga').show();
      },
      complete: function(data){
        console.log('Terminó');
      },
      success: function (data) {
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
        $('#modalImportacionProducidos').modal('hide');
        limpiarBodysImportaciones();
        $('#plataformaInfoImportacion').change();
        $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN PRODUCIDO');
        text = data.cantidad_registros + ' registro(s) del PRODUCIDO fueron importados'
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
        $('#modalImportacionProducidos #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionProducidos #rowArchivo').hide();
        $('#modalImportacionProducidos #mensajeInvalido').hide();
        $('#modalImportacionProducidos #datosProducido').hide();
        $('#modalImportacionProducidos #iconoCarga').hide();
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
  const COLUMNASPROD = $('#modalImportacionProducidos').data('modo') == "jugadores"? 9 : 10;
  if(allTextLines.length > 0){
    const columnas = allTextLines[0].split(',');
    if (columnas.length == COLUMNASPROD) {
      //Si tiene filas, extraigo la fecha
      if (allTextLines.length > 1) {
        //Se obtiene la fecha del CSV para mostrarlo
        const date = allTextLines[1].split(',')[0].replaceAll('"','');//Saco las comillas
        $('#fechaProducido').data('datetimepicker').setDate(new Date(toIso(date)));
        $('#fechaProducido input').attr('disabled',true);
        $('#fechaProducido span').hide()
      }
      else{
        $('#fechaProducido input').attr('disabled',false);
        $('#fechaProducido span').show()
      }
      $('#monedaProducido').val(1);
      $('#modalImportacionProducidos #mensajeInvalido').hide();
      //Mostrar botón SUBIR
      $('#btn-guardarProducido').show();
      $('#datosProducido').show();
      return;
    }
  }
  //Si no retorno arriba quiere decir que no era valido
  $('#modalImportacionProducidos #mensajeInvalido p').text('El archivo no contiene producidos');
  $('#modalImportacionProducidos #mensajeInvalido').show();
  $('#modalImportacionProducidos #iconoCarga').hide();
  //Ocultar botón de subida
  $('#btn-guardarProducido').hide();
}

//Eventos de la librería del input
$('#modalImportacionProducidos #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionProducidos #datosProducido').hide();
   $('#modalImportacionProducidos #mensajeInvalido').show();
   $('#modalImportacionProducidos #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileclear', function(event) {
  reiniciarModalImportarProducido();
});

$('#modalImportacionProducidos #archivo').on('fileselect', function(event) {
  reiniciarModalImportarProducido();
  $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
  // Se lee el archivo guardado en el input de tipo 'file'.
  // se valida la importación.
  let reader = new FileReader();
  reader.onload = procesarDatosProducidos;
  reader.readAsText($('#modalImportacionProducidos #archivo')[0].files[0]);
});

$('#btn-reintentarProducido').click(function(e) {
  e.preventDefault();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
  reiniciarModalImportarProducido();
});

/*********************** BENEFICIOS *********************************/
function agregarFilaDetalleBeneficio(beneficio){
  const fila = $('<tr>');
  fila.append($('<td>').addClass('col-xs-1').append(beneficio.fecha));
  fila.append($('<td>').addClass('col-xs-1').append(beneficio.jugadores));
  fila.append($('<td>').addClass('col-xs-2').append(beneficio.depositos));
  fila.append($('<td>').addClass('col-xs-2').append(beneficio.retiros));
  fila.append($('<td>').addClass('col-xs-2').append(beneficio.apuesta));
  fila.append($('<td>').addClass('col-xs-2').append(beneficio.premio));
  fila.append($('<td>').addClass('col-xs-2').append(beneficio.beneficio));
  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('#modalAyuda .modal-title').text('| IMPORTACIONES');
  $('#modalAyuda .modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

function reiniciarModalImportarBeneficios(){
  $('#modalImportacionBeneficios #rowArchivo').show();
  $('#fechaBeneficio').data('datetimepicker').reset();
  $('#plataformaBeneficio').val("");
  $('#monedaBeneficio').val("");
  $('#datosBeneficio').hide();
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionBeneficios #mensajeError').hide();
  $('#modalImportacionBeneficios #mensajeInvalido').hide();
  $('#modalImportacionBeneficios #iconoCarga').hide();
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
  $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');
  $("#modalImportacionBeneficios #archivo").fileinput('destroy').fileinput({
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
  $('#modalImportacionBeneficios').find('.modal-footer').children().show();
  reiniciarModalImportarBeneficios();
  $('#modalImportacionBeneficios').modal('show');
});

$('#btn-guardarBeneficio').on('click', function(e){
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

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
  if($('#modalImportacionBeneficios #archivo').attr('data-borrado') == 'false' && $('#modalImportacionBeneficios #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionBeneficios #archivo')[0].files[0]);
  }

  $.ajax({
      type: "POST",
      url: 'importaciones/importarBeneficio',
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionBeneficios').find('.modal-footer').children().hide();
        $('#modalImportacionBeneficios').find('.modal-body').children().hide();
        $('#modalImportacionBeneficios').find('.modal-body').children('#iconoCarga').show();
      },
      complete: function(data){
        console.log('Terminó');
      },
      success: function (data) {
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
        $('#modalImportacionBeneficios').modal('hide');
        limpiarBodysImportaciones();
        $('#plataformaInfoImportacion').change();
        $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN BENEFICIO');
        $('#mensajeExito p').text(data.dias + ' registro(s) del BENEFICIO fueron importados');
        $('#mensajeExito').show();
      },
      error: function (data) {
        //Mostrar: mensajeError
        $('#modalImportacionBeneficios #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionBeneficios #rowArchivo').hide();
        $('#modalImportacionBeneficios #datosBeneficio').hide();
        $('#modalImportacionBeneficios #mensajeInvalido').hide();
        $('#modalImportacionBeneficios #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});


function procesarDatosBeneficios(e) {
  const csv = e.target.result;
  //Limpio retorno de carro y saco las lineas sin nada.
  const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(s => s.length > 0);
  if(allTextLines.length > 0){
    const columnas = allTextLines[0].split(',');
    if (columnas.length == 16) {
      //Si tiene filas, extraigo la fecha y moneda
      if (allTextLines.length > 2) {//2 porque tiene la cabezera y el total si o si (supongo)
        const date = allTextLines[1].split(',')[1].replaceAll('"','');//Saco las comillas
        $('#fechaBeneficio').data('datetimepicker').setDate(new Date(toIso(date)));
        $('#fechaBeneficio input').attr('disabled',true);
        $('#fechaBeneficio span').hide();
        const moneda = allTextLines[1].split(',')[2];
        $(`#monedaBeneficio option:contains(${moneda})`).prop('selected',true)
        $('#monedaBeneficio').attr('disabled',true);
      }
      else{
        $('#fechaBeneficio input').attr('disabled',false);
        $('#fechaBeneficio span').show();
        $('#monedaBeneficio').attr('disabled',false);
      }
      $('#modalImportacionBeneficios #mensajeInvalido').hide();
      //Mostrar botón SUBIR
      $('#btn-guardarBeneficio').show();
      $('#datosBeneficio').show();
      return;
    }
  }
  //Si no retorno arriba quiere decir que no era valido
  $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
  $('#modalImportacionBeneficios #mensajeInvalido').show();
  $('#modalImportacionBeneficios #iconoCarga').hide();
  //Ocultar botón de subida
  $('#btn-guardarBeneficio').hide();
}

//Eventos de la librería del input
$('#modalImportacionBeneficios #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacionBeneficios #datosProducido').hide();
  $('#modalImportacionBeneficios #mensajeInvalido').show();
  $('#modalImportacionBeneficios #mensajeInvalido p').text(msg);
  //Ocultar botón SUBIR
  $('#btn-guardarBeneficio').hide();
});

$('#modalImportacionBeneficios #archivo').on('fileclear', function(event) {
  reiniciarModalImportarBeneficios();
});

$('#modalImportacionBeneficios #archivo').on('fileselect', function(event) {
  reiniciarModalImportarBeneficios();
  $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');
  // Se lee el archivo guardado en el input de tipo 'file'.
  // se valida la importación.
  var reader = new FileReader();
  reader.readAsText($('#modalImportacionBeneficios #archivo')[0].files[0]);
  reader.onload = procesarDatosBeneficios;
});

$('#btn-reintentarBeneficio').click(function(e) {
  e.preventDefault();
  $('#modalImportacionBeneficios').find('.modal-footer').children().show();
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
    setearValueFecha();
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
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaImportaciones .activa').attr('value'),orden: $('#tablaImportaciones .activa').attr('estado')} ;
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
      $('#tablaImportaciones').attr('data-tipo', formData.seleccion);
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      for (let i = 0; i < resultados.data.length; i++) {
        agregarFilasImportaciones(resultados.data[i]);
      }
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