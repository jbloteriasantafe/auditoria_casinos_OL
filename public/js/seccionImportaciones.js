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
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');

  $('.tituloSeccionPantalla').text('Importaciones');
  $('#opcImportaciones').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');

  //Habilitar o no la fecha según el plataforma
  $('#mensajeInformacion').hide();

  $('#fecha_busqueda').datetimepicker({
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

  $('#mesInfoImportacion').datetimepicker({
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

  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date());

  if($('#plataforma_busqueda option').length == 2 ){
    $('#plataforma_busqueda option:eq(1)').prop('selected', true);
  }

  setearValueFecha();
  //Paginar
    $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

  id_plataforma = 1;
  id_tipo_moneda = 1;

  $('#plataformaInfoImportacion').val(id_plataforma);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  $('#plataformaInfoImportacion').change();
});


$('#plataformaInfoImportacion').change(function() {
    var id_plataforma = $(this).val();
    var id_moneda = $('#monedaInfoImportacion').val();
    const fecha_sort = $('#infoImportaciones .activa').attr('estado');
    //Si el plataforma elegido no es Rosario, entonces ocultar el select de monedas
      //Para Santa Fe y Melincué mandar moneda PESOS por defecto
      //Para Rosario mirar que moneda está seleccionada
    if (id_plataforma != '3') {
        $('#monedaInfoImportacion').hide();

        cargarTablasImportaciones(id_plataforma, '1',fecha_sort); //El 1 es PESOS
    }else {
        $('#monedaInfoImportacion').show();
        console.log("Plataforma: ", id_plataforma);
        console.log("Moneda: ", id_moneda);
        $('#monedaInfoImportacion').change();
    }
});

$('#monedaInfoImportacion').change(function() {
    var id_moneda = $(this).val();
    const fecha_sort = $('#infoImportaciones .activa').attr('estado');

    if (id_moneda == 1) $('.tablaBody').removeClass('dolares').addClass('pesos');
    else $('.tablaBody').removeClass('pesos').addClass('dolares');

    //Esto pasa siempre en Rosario, el único plataforma que tiene dolar
    cargarTablasImportaciones('3', id_moneda, fecha_sort);
});

$('#mesInfoImportacion').on("change.datetimepicker",function(){
  var id_plataforma = $('#plataformaInfoImportacion').val();
  var id_moneda = $('#monedaInfoImportacion').val();
  const fecha_sort = $('#infoImportaciones .activa').attr('estado');

  if(id_plataforma != '3'){
    cargarTablasImportaciones(id_plataforma, '1', fecha_sort); //El 1 es PESOS
  }
  else{
    cargarTablasImportaciones(id_plataforma,id_moneda, fecha_sort);
  }
})

function limpiarBodysImportaciones() {
    $('.tablaBody tr').not('#moldeFilaImportacion').remove();
    $('.tablaBody').hide();
}

function cargarTablasImportaciones(plataforma, moneda, fecha_sort) {
    const fecha = $('#mes_info_hidden').val();
    const url = fecha.size == 0? '/' : ('/' + fecha);
    $.get('importaciones/' + plataforma + url + '/' + (fecha_sort? fecha_sort : ''), function(data) {
        var tablaBody;

        console.log("Plataforma: ", plataforma);

        limpiarBodysImportaciones();

        switch (plataforma) {
          case '1':
            tablaBody = $('#bodyMelincue');
            break;
          case '2':
            tablaBody = $('#bodySantaFe');
            break;
          case '3':
            tablaBody = $('#bodyRosario');
            break;
        }

        for (var i = 0; i < data.arreglo.length; i++) {

          var moldeFilaImportacion = $('#moldeFilaImportacion').clone();
          moldeFilaImportacion.removeAttr('id');
          moldeFilaImportacion.find('.fecha').text(convertirDate(data.arreglo[i].fecha));
          var filaProducido = moldeFilaImportacion.find('.producido');
          var filaBeneficio = moldeFilaImportacion.find('.beneficio');
          if (moneda == '1') {
            console.log('PESOS');
            data.arreglo[i].producido.pesos == true ? filaProducido.addClass('true') : filaProducido.addClass('false');
            data.arreglo[i].beneficio.pesos == true ? filaBeneficio.addClass('true') : filaBeneficio.addClass('false');
          }
          else {
            console.log('DOLAR');
            data.arreglo[i].producido.dolares == true ? filaProducido.addClass('true') : filaProducido.addClass('false');
            data.arreglo[i].beneficio.dolares == true ? filaBeneficio.addClass('true') : filaBeneficio.addClass('false');
          }

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
      $('#tablaImportaciones #tipo_fecha').attr('value',"beneficio.fecha");
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
  if(tipo_importacion == 2){
    head.append($('<th>').addClass('col-xs-2').append('JUEGO'));
    head.append($('<th>').addClass('col-xs-1').append('CATEGORIA'));
    head.append($('<th>').addClass('col-xs-1').append('JUGADORES'));
    head.append($('<th>').addClass('col-xs-1').append('TotalWagerCash'));
    head.append($('<th>').addClass('col-xs-1').append('TotalWagerBonus'));
    head.append($('<th>').addClass('col-xs-1').append('TotalWager'));
    head.append($('<th>').addClass('col-xs-1').append('GrossRevenueCash'));
    head.append($('<th>').addClass('col-xs-1').append('GrossRevenueBonus'));
    head.append($('<th>').addClass('col-xs-1').append('GrossRevenue'));
    head.append($('<th>').addClass('col-xs-2').append('VALOR'));
  }
  else if(tipo_importacion == 3){
    //@TODO: Implementar
  }
  $('#tablaVistaPrevia tbody tr').remove();

  //Comprobar el tipo de importacion. BENEFICIO tiene una ruta diferente a PRODUCIDO
  if (tipo_importacion == 3) {
    actualizarPreviewBeneficios($(this).val(),0,30)
  }else if (tipo_importacion == 2) {
    actualizarPreviewProducidos($(this).val(),0,30);
  }

  //Mostrar el modal de la vista previa
  $('#modalPlanilla').modal('show');
});

function actualizarPreviewBeneficios(id_beneficio,page,size){
  /*//@TODO IMPLEMENTAR CUANDO SE HAGA BENMEFICIOS
      //el request contiene mes anio id_tipo_moneda id_plataforma
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      var formData = {
          mes: $(this).attr('data-mes'),
          anio: $(this).attr('data-anio'),
          id_tipo_moneda: $(this).attr('data-moneda'),
          id_plataforma: $(this).attr('data-plataforma'),
      }

      $.ajax({
          type: 'POST',
          url: 'importaciones/previewBeneficios',
          data: formData,
          dataType: 'json',
          success: function (data) {
            console.log(data);

            $('#modalPlanilla #fecha').val(convertirDate(data.beneficios[0].fecha).substring(3,11));
            $('#modalPlanilla #plataforma').val(data.plataforma.nombre);
            $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('FECHA')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COININ')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COINOUT')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('VALOR')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('% DEVOLUCION')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROMEDIO')));

            for (var i = 0; i < data.beneficios.length; i++) {
                agregarFilaDetalleBeneficio(data.beneficios[i]);
            }
          },
          error: function (data) {
            console.log(data);
          }
      });*/
  return;
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
    url: 'importaciones/previewProducidos',
    data: {id_producido: id_producido,page: page,size: size},
    dataType: 'json',
    success: function (data) {//@TODO: AGREGAR PAGINADO
      $('#previewPage').text(page);
      const totales = Math.ceil(data.cant_detalles/size);
      $('#previewTotal').text(totales);
      $('#nextPreview').attr('disabled',page >= totales);
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

$('#prevPreview').click(function(){
  const id = parseInt($('#modalPlanilla').attr('data-id'));
  let page = parseInt($('#modalPlanilla').attr('data-page'));
  page = Math.max(page-1,0)
  $('#modalPlanilla').attr('data-page',page);
  const size = parseInt($('#modalPlanilla').attr('data-size'));
  const tipo = parseInt($('#modalPlanilla').attr('data-tipo'));
  if(tipo == 2) actualizarPreviewProducidos(id,page,size)
  else if(tipo == 3) actualizarPreviewBeneficios(id,page,size);
});

$('#nextPreview').click(function(){
  const id = parseInt($('#modalPlanilla').attr('data-id'));
  let page = parseInt($('#modalPlanilla').attr('data-page'));
  page++;
  $('#modalPlanilla').attr('data-page',page);
  const size = parseInt($('#modalPlanilla').attr('data-size'));
  const tipo = parseInt($('#modalPlanilla').attr('data-tipo'));
  console.log('Pagina',page);
  if(tipo == 2) actualizarPreviewProducidos(id,page,size)
  else if(tipo == 3) actualizarPreviewBeneficios(id,page,size);
});

$(document).on('click','.borrar',function(){
  const id_importacion = $(this).val();
  //Mirar en la tabla los tipos de archivos listados (2:producidos;3:beneficios).
  const tipo_archivo = $('#tipo_archivo').val();
  const nombre_tipo_archivo = {2: 'PRODUCIDO',3: 'BENEFICIO'}[tipo_archivo];

  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val(id_importacion).attr('data-tipo',tipo_archivo);
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ nombre_tipo_archivo + '?');
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

  var url;

  switch(tipo_archivo){
    case '2':
      url = "producidos/eliminarProducido/" + id_importacion;
      break;
    case '3':
      url = "beneficios/eliminarBeneficio/" + id_importacion;
      break;
  }

  $.ajax({
      type: "DELETE",
      url: url,
      success: function (data) {
        //Remueve de la tabla
        console.log();
        // $('#' + tipo_archivo + id_importacion).remove();
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalEliminar').modal('hide');

      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });
});

/*********************** PRODUCIDOS *********************************/
function agregarFilaDetalleProducido(producido) {
  var fila = $('<tr>');
  fila.append($('<td>').addClass('col-xs-2').text(producido.cod_juego));
  fila.append($('<td>').addClass('col-xs-1').append(producido.categoria));
  fila.append($('<td>').addClass('col-xs-1').append(producido.jugadores));
  fila.append($('<td>').addClass('col-xs-1').append(producido.TotalWagerCash));
  fila.append($('<td>').addClass('col-xs-1').append(producido.TotalWagerBonus));
  fila.append($('<td>').addClass('col-xs-1').append(producido.TotalWager));
  fila.append($('<td>').addClass('col-xs-1').append(producido.GrossRevenueCash));
  fila.append($('<td>').addClass('col-xs-1').append(producido.GrossRevenueBonus));
  fila.append($('<td>').addClass('col-xs-1').append(producido.GrossRevenue));
  fila.append($('<td>').addClass('col-xs-2').append(producido.valor));
  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-importarProducidos').click(function(e){
  e.preventDefault();
  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();
  $('#fechaProducido').data('datetimepicker').reset();
  $('#plataformaProducido').val("");
  $('#monedaProducido').val("");
  $('#datosProducido').hide();

  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #datosProducido').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();

  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionProducidos').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
});

$('#btn-guardarProducido').on('click',function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarProducido';

  var formData = new FormData();

  const id_plataforma = $('#plataformaProducido').val();
  const fecha = $('#fechaProducido_hidden').val();
  const id_tipo_moneda = $('#monedaProducido').val();
  formData.append('id_plataforma', id_plataforma);
  formData.append('fecha', fecha);
  formData.append('id_tipo_moneda', id_tipo_moneda);

  $('#plataformaInfoImportacion').val(id_plataforma);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  const date =$('#fechaProducido').data('datetimepicker').getDate();
  $('#mesInfoImportacion').data('datetimepicker').setDate(date);
  $('#plataformaInfoImportacion').change();
  
  //Si subió archivo lo guarda
  if($('#modalImportacionProducidos #archivo').attr('data-borrado') == 'false' && $('#modalImportacionProducidos #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionProducidos #archivo')[0].files[0]);
  }


    $.ajax({
        type: "POST",
        url: url,
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

function habilitarInputProducido(){
  //Inicializa el fileinput para cargar los CSV
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
}

function toIso(f){
  //input fecha tipo 1/11/2020 06:00:00
  aux = f.split(' ');
  f = aux[0].split('/');
  return f[2]+'-'+f[1]+'-'+(f[0].length==1? '0'+f[0] : f[0])+'T'+aux[1];
}

function procesarDatosProducidos(e) {
    const csv = e.target.result;
    //Limpio retorno de carro y saco las lineas sin nada.
    const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(s => s.length > 0);
    if(allTextLines.length > 0){
      const columnas = allTextLines[0].split(',');
      if (columnas.length == 10) {
        //Si tiene filas, extraigo la fecha
        if (allTextLines.length > 1) {
          //Se obtiene la fecha del CSV para mostrarlo
          const date = allTextLines[1].split(',')[0];
          $('#fechaProducido').data('datetimepicker').setDate(new Date(toIso(date)));
          $('#fechaProducido input').attr('disabled',true);
          $('#fechaProducido span').hide()
        }
        else{
          $('#fechaProducido input').attr('disabled',false);
          $('#fechaProducido span').show()
        }
        $('#monedaProducido').val(1).attr('disabled',true);
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
    id_tipo_moneda = 0;
    $('#modalImportacionProducidos #archivo').attr('data-borrado','true');
    $('#modalImportacionProducidos #archivo')[0].files[0] = null;
    $('#modalImportacionProducidos #mensajeInvalido').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileselect', function(event) {
    $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
    // Se lee el archivo guardado en el input de tipo 'file'.
    // se valida la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionProducidos #archivo')[0].files[0]);
    reader.onload = procesarDatosProducidos;
});

$('#btn-reintentarProducido').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #datosProducido').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();
  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
});

/*********************** BENEFICIOS *********************************/
function agregarFilaDetalleBeneficio(beneficio){
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-2').text(convertirDate(beneficio.fecha)));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinin));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinout));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.valor));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.porcentaje_devolucion));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.promedio_por_maquina));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('#modalAyuda .modal-title').text('| IMPORTACIONES');
  $('#modalAyuda .modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

$('#btn-importarBeneficios').click(function(e){
  e.preventDefault();
  //Mostrar: rowArchivo
  $('#modalImportacionBeneficios #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionBeneficios #rowMoneda').hide();
  $('#modalImportacionBeneficios #mensajeError').hide();
  $('#modalImportacionBeneficios #mensajeInvalido').hide();
  $('#modalImportacionBeneficios #mensajeInformacion').hide();
  $('#modalImportacionBeneficios #iconoCarga').hide();

  habilitarInputBeneficio();
  $('#modalImportacionBeneficios').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionBeneficios').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarBeneficio').hide();
});

$('#btn-guardarBeneficio').on('click', function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarBeneficio';

  var formData = new FormData();


  formData.append('id_plataforma', 3);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);

  $('#plataformaInfoImportacion').val(3);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  {
    const aux = fecha_date.split('/');
    $('#mesInfoImportacion').data('datetimepicker').setDate(new Date(aux[2]+'/'+aux[1]+'/'+aux[0]));
  }
  $('#plataformaInfoImportacion').change();

  //Si subió archivo lo guarda
  if($('#modalImportacionBeneficios #archivo').attr('data-borrado') == 'false' && $('#modalImportacionBeneficios #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionBeneficios #archivo')[0].files[0]);
  }


  $.ajax({
      type: "POST",
      url: url,
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
        $('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del BENEFICIO fueron importados');

        $('#mensajeExito').show();
      },
      error: function (data) {
        //Mostrar: mensajeError
        $('#modalImportacionBeneficios #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionBeneficios #rowArchivo').hide();
        $('#modalImportacionBeneficios #rowFecha').hide();
        $('#modalImportacionBeneficios #mensajeInvalido').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();
        $('#modalImportacionBeneficios #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});

function habilitarInputBeneficio(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
  $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');
  $("#modalImportacionBeneficios #archivo").fileinput('destroy').fileinput({
      language: 'es',
    //       showPreview: false,
          // allowedFileExtensions: ["csv", "txt"],
    //       elErrorContainer: "#alertaArchivo"
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
}

function procesarDatosBeneficios(e) {
    var csv = e.target.result;
    var allTextLines = csv.split('\n');

    console.log(allTextLines.length);

    if (allTextLines.length > 4) {
        console.log('ASD');
        var data = allTextLines[4].split(';');

        var tarr = [];

        for (var j=0; j<data.length; j++) {
              tarr.push(data[j]);
        }

        console.log(tarr);
        if (tarr.length == COL_BEN_ROS) {
            console.log('Está bien');
            id_plataforma = 3;
            //Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)
            $('#modalImportacionBeneficios #rowMoneda').show();
            $('#modalImportacionBeneficios #rowMoneda select').val(0);
            $('#modalImportacionBeneficios #mensajeInvalido').hide();

            //Info plataforma
            $('#modalImportacionBeneficios #informacionPlataforma').text('PLATAFORMA ROSARIO');
            //Info fecha
            fecha_date = tarr[0];

            $('#modalImportacionBeneficios #informacionFecha').text(obtenerFechaString(fecha_date, false));
        }
        else {
            $('#modalImportacionBeneficios #rowMoneda').hide();
            $('#modalImportacionBeneficios #mensajeInformacion').hide();

            $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
            $('#modalImportacionBeneficios #mensajeInvalido').show();

            $('#modalImportacionBeneficios #iconoCarga').hide();
            //Ocultar botón de subida
            $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
        }

    } else {

        $('#modalImportacionBeneficios #rowMoneda').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();

        $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
        $('#modalImportacionBeneficios #mensajeInvalido').show();

        $('#modalImportacionBeneficios #iconoCarga').hide();
        //Ocultar botón de subida
        $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
    }


}

$('#modalImportacionBeneficios #rowMoneda select').change(function(e) {
  console.log('CAMBIÓ');

  //Si se elige una moneda
  if ($(this).val() != 0) {
    id_tipo_moneda = $(this).val();

    $('#modalImportacionBeneficios #informacionMoneda').text($(this).find('option:selected').text());
    $('#modalImportacionBeneficios #iconoMoneda').show();
    $('#modalImportacionBeneficios #informacionMoneda').show();
    //Mostrar el mensaje de información
    $('#modalImportacionBeneficios #mensajeInformacion').show();
    //Mostrar botón SUBIR
    $('#btn-guardarBeneficio').show();
  } else {
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#btn-guardarBeneficio').hide();
  }

});

//Eventos de la librería del input
$('#modalImportacionBeneficios #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionBeneficios #rowMoneda').hide();
   $('#modalImportacionBeneficios #mensajeInformacion').hide();
   $('#modalImportacionBeneficios #mensajeInvalido').show();
   $('#modalImportacionBeneficios #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarBeneficio').hide();

});

$('#modalImportacionBeneficios #archivo').on('fileclear', function(event) {
    id_tipo_moneda = 0;
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','true');
    $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#modalImportacionBeneficios #mensajeInvalido').hide();
    $('#modalImportacionBeneficios #rowMoneda').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarBeneficio').hide();
});

$('#modalImportacionBeneficios #archivo').on('fileselect', function(event) {
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué plataforma pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionBeneficios #archivo')[0].files[0]);
    reader.onload = procesarDatosBeneficios;
});

/*****************PAGINACION******************/

function agregarFilasImportaciones(data, id) {
  var fila = $('<tr>');

  var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

  //Si es beneficio no se muestra el dia y se agregan los 'datas'
  if (id == null) {
    fila.append($('<td>').addClass('col-xs-3').text("-"));
    fila.append($('<td>').addClass('col-xs-3').text(meses[data.mes - 1] + ' ' + data.anio));
    fila.append($('<td>').addClass('col-xs-2').text(data.plataforma));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla')
                                              .attr('data-mes', data.mes)
                                              .attr('data-anio', data.anio)
                                              .attr('data-plataforma', data.id_plataforma)
                                              .attr('data-moneda', data.id_tipo_moneda)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))
                         )
                         .append($('<button>').addClass('btn btn-danger borrar').val(id)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }
  else {
    var archivo = typeof data.fecha_archivo == "undefined" ? "-" : convertirDate(data.fecha_archivo);
    fila.append($('<td>').addClass('col-xs-3').text(archivo));
    fila.append($('<td>').addClass('col-xs-3').text(convertirDate(data.fecha)));
    fila.append($('<td>').addClass('col-xs-2').text(data.plataforma));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla').val(id)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))

                         )
                         .append($('<button>').addClass('btn btn-danger borrar').val(id)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }


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

  var formData = {
    fecha: $('#fecha_busqueda_hidden').val(),
    plataformas: $('#plataforma_busqueda').val(),
    tipo_moneda: $('#moneda_busqueda').val(),
    seleccion: $('#tipo_archivo').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log('FormData de buscar: ', formData);

  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();
      $('#tablaImportaciones').attr('data-tipo', formData.seleccion);
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      for (var i = 0; i < resultados.data.length; i++) {
        agregarFilasImportaciones(resultados.data[i],resultados.data[i].id_producido);
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