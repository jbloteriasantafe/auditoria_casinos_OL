//Resaltar la sección en el menú del costado
$(document).ready(function() {
  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar expedientes');
  $('#opcGestionarExpedientes').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcGestionarExpedientes').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');

  limpiarModal();

  $('#navConfig').click();
  $('#error_nav_config').hide();
  $('#error_nav_notas').hide();

  //DTP filtros
  $('#B_dtpFechaInicio span:first').click();

  $('#B_dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3,
    container: $('main section'),
  });
});

/* PESTAÑAS */

// Motrar la pestaña activa (se agrega el subrayado).
$('.navModal a').click(function(e){
    e.preventDefault();
    $('.navModal a').removeClass();
    $(this).addClass('navModalActivo');
});

//Cambiar a la sección de Configuración.
$('#navConfig').click(function(){
  $('.seccion').hide();
  $('#secConfig').show();
});

//Cambiar a la sección de Notas nuevas.
$('#navNotas').click(function(){
  $('.seccion').hide();
  $('#secNotas').show();
});

/////////////////////////////////// NOTAS ////////////////////////////////////

$(document).on('change','.plataformasExp', function() {
    var plataformas_seleccionadas = $('.plataformasExp:checked');
    // Si hay 0 plataformas seleccionados: limpiar las secciones de notas y mostrar mensajes.
    if (plataformas_seleccionadas.length <= 0) {
        limpiarSeccionNotas();
        $('.mensajeNotas').show();
        $('.formularioNotas').hide();
    //Si hay un SOLO UNA seleccionado: habilitar las dos pestañas
    } else {
        habilitarNotasNuevas();
    }
});

function limpiarSeccionNotas() {
  $('.notaNueva').not('#moldeNotaNueva').remove(); //Eliminar las filas de notas
}

function habilitarNotasNuevas() {
  $('#secNotas .mensajeNotas').hide();
  $('#secNotas .formularioNotas').show();
}

function mostrarMovimientosDisponibles(cantidadMovimientos) {
  if (cantidadMovimientos == 1) $('#cantidadMovimientos').text('1 Movimiento disponible');
  else $('#cantidadMovimientos').text(cantidadMovimientos + ' Movimientos disponibles');
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

//Quitar eventos de la tecla Enter y guardar
$('#collapseFiltros').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13 && $('#modalExpediente').is(':visible')) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

//DATETIMEPICKER de las fechas
function habilitarDTP() {
  //Resetear DTP (Click en la cruz)
  $('#dtpFechaInicio span:first').click();
  $('#dtpFechaPase span:first').click();

  $('#dtpFechaPase').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    container: $('#modalExpediente'),
  });

  $('#dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    container: $('#modalExpediente'),
  });
}

//Agregar nueva disposicion en el modal
$('#btn-agregarDisposicion').click(function(){
  var moldeDisposicion = $('#moldeDisposicion').clone();
  moldeDisposicion.removeAttr('id');
  moldeDisposicion.find('#tiposMovimientosDisp').prop("disabled", false);
  moldeDisposicion.show();
  $('#columnaDisposicion').append(moldeDisposicion);
});

// Agregar resolucion
$('#btn-agregarResolucion').on("click",function(e){
  var fila = $('<tr>').attr("id-resolucion",-1);
  nro_res=$('#nro_resolucion').val();
  anio_res=$('#nro_resolucion_anio').val();

  if (nro_res!="" && anio_res!=""){
    fila.append($('<td>').text(nro_res));
    fila.append($('<td>').text(anio_res));
    var boton = $('<button>').addClass('btn btn-danger borrarResolucion')
                           .css('margin-left','10px')
                           .append($('<i>').addClass('fa fa-fw fa-trash'));
    fila.append($('<td>').append(boton));
    $('#tablaResolucion').append(fila);
    $('#nro_resolucion').val("");
    $('#nro_resolucion_anio').val("");
  }
});

$(document).on('click','.borrarResolucion',function(){
  $(this).parent().parent().remove();
});


$(document).on('click','.borrarDisposicion',function(){
  $(this).parent().parent().remove();
});

//Contador global que sirve para generar el id de cada DTP de notas nuevas
var nro_nota = 0;
$('#btn-notaNueva').click(function(e){
    nro_nota = nro_nota + 1;                                                    //Se incrementa en 1, para que cada DTP tenga un ID diferente

    e.preventDefault();
    var clonNota = $('#moldeNotaNueva').clone();
    clonNota.removeAttr('id');
    clonNota.show();

    clonNota.find('.dtpFechaNota').attr('data-link-field', nro_nota + '_fecha');
    clonNota.find('.fecha_notaNueva').attr('id', nro_nota + '_fecha');

    clonNota.find('.dtpFechaNota').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd MM yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
    });

    $('#moldeNotaNueva').before(clonNota);
});

$('#modalExpediente').on('click','.borrarNota', function(){
    $(this).parent().parent().remove();
});

$('#modalExpediente').on('click','.borrarNotaMov', function(){
    var id_movimiento = $(this).attr('id');
    $('#movimientosDisponibles option[value="'+ id_movimiento +'"]').show();    //Mostrar el movimiento borrado nuevamente en el selector
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| GESTIONAR EXPEDIENTES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(e){
    e.preventDefault();

    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();
    $('#dispoCarg').hide();
    //Ocultar errores
    $('#error_nav_config').hide();
    $('#error_nav_notas').hide();

    habilitarDTP();

    nro_nota = 0; //Reiniciar el contador de notas

    $('#navMov').parent().show();
    $('#navConfig').click(); //Empezar por la sección de configuración
    $('.formularioNotas').hide(); //Ocultar los formularios de notas
    $('.notasCreadas').hide(); //Ocultar las notas creadas (es del modal modificar expediente)
    $('.plataformasExp').prop('checked',false);
    $('.mensajeExito').show();
    $('.mensajeNotas').show();


    limpiarModal();
    $('#concepto').val(' ');
    $('#tema').val(' ');

    habilitarControles(true);
    $('.modal-title').text('NUEVO EXPEDIENTE');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('#btn-guardar').val("nuevo");
    $('#btn-cancelar').text('CANCELAR');
    $('#asociar').show();

    $('#tiposMovimientosDisp option').remove();

    $('#modalExpediente').modal('show');
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
  $('#mensajeExito').hide();
  $('#modalExpediente').find('.modal-footer').children().show();
  $('#modalExpediente').find('.modal-body').children().show();
  $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

  limpiarModal();
  //Ocultar errores
  $('#error_nav_config').hide();
  $('#error_nav_notas').hide();

  $('.modal-title').text('| VER EXPEDIENTE');
  $('.modal-header').attr('style','background: #4FC3F7');
  $('#btn-cancelar').text('SALIR');
  $('#navConfig').click(); //Empezar por la sección de configuración
  var id_expediente = $(this).val();

  $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
    mostrarExpediente(data.expediente,data.plataformas,data.resolucion,data.disposiciones,data.notas,false);
    habilitarControles(false);

    //Deshabilitar sección de 'notas & movimientos'
    $('#navMov').parent().hide();
    $('.notasNuevas').hide();
    $('#modalExpediente').modal('show');
  });
});

$(document).on('click','.modificar',function(){
    $('#mensajeExito').hide();
    $('#tablaDispoCreadas tbody tr').not('#moldeDispoCargada').remove();
    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

    $('.plataformasExp').prop('checked',false).prop('disabled',false);
    limpiarModal();
    habilitarDTP();
    $('.modal-title').text('| MODIFICAR EXPEDIENTE');
    $('.modal-header').attr('style','background: #FFB74D');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');
    $('#btn-cancelar').text('CANCELAR');
    $('#asociar').hide();

    $('#navMov').parent().show();
    $('#navConfig').click(); //Empezar por la sección de configuración

    //Ocultar errores
    $('#error_nav_config').hide();
    $('#error_nav_notas').hide();

    var id_expediente = $(this).val();
    $('#modalExpediente #id_expediente').val(id_expediente);

    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
        mostrarExpediente(data.expediente,data.plataformas,data.resolucion,data.disposiciones,data.notas,true);
        habilitarControles(true);
        $('#btn-guardar').val("modificar");
        $('#modalExpediente').modal('show');
        $('[rel=tooltip]').tooltip('disable');
    });

});

$(document).on('click','.eliminar',function(){
    //Cambiar colores modal
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').removeAttr('style');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_expediente = $(this).val();
    $('#btn-eliminarModal').val(id_expediente);
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })
    var id_expediente = $(this).val();

    $.ajax({
        type: "DELETE",
        url: "expedientes/eliminarExpediente/" + id_expediente,
        success: function (data) {
          //Remueve de la tabla
          $('#expediente' + id_expediente).remove();
          $("#tablaExpedientes").trigger("update");
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

function obtenerNotasNuevas() {
  var notas_nuevas = [];
  var mov = null;
  $.each($('.notaNueva').not('#moldeNotaNueva'), function (index, value) {
    if($(this).find('.tiposMovimientos').val() != 0){
      mov = $(this).find('.tiposMovimientos').val();
    }else{
      mov = null;
    }
      var nota = {
        fecha: $(this).find('.fecha_notaNueva').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_tipo_movimiento: mov,
      }

      notas_nuevas.push(nota);
  });

  return notas_nuevas;
}

//Cuando aprieta guardar en el modal de Nuevo/Modificar expediente
$('#btn-guardar').click(function (e) {
    $('#mensajeExito').hide();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var fecha_pase = $('#fecha_pase').val();
    var fecha_iniciacion = $('#fecha_inicio').val();
    var resolucion = obtenerResoluciones();
    var disposiciones = [];

    $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
        var disposicion = {
          nro_disposicion: $(this).find('.nro_disposicion').val(),
          nro_disposicion_anio: $(this).find('.nro_disposicion_anio').val(),
          descripcion: $(this).find('#descripcion_disposicion').val(),
          id_tipo_movimiento: $(this).find('#tiposMovimientosDisp').val(),
        }
        disposiciones.push(disposicion);
    });
    var dispo_cargadas = [];
    var tabla = $('#tablaDispoCreadas tbody > tr').not('#moldeDispoCargada');

    $.each(tabla, function(index, value){
        var id_disposicion= $(this).attr('id');
        dispo_cargadas.push(id_disposicion);
    });

    var notas = obtenerNotasNuevas();
    var tablaNotas=[];
    var tabla= $('#tablaNotasCreadas tbody > tr').not('#moldeFilaNota');

    $.each(tabla, function(index, value){
      tablaNotas.push(value.id);
    });

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = ((state == "modificar") ? 'expedientes/modificarExpediente':'expedientes/guardarExpediente');
    var formData = {
      id_expediente: $('#id_expediente').val(),
      nro_exp_org: $('#nro_exp_org').val(),
      nro_exp_interno: $('#nro_exp_interno').val(),
      nro_exp_control: $('#nro_exp_control').val(),
      plataformas: $('.plataformasExp:checked').map(function(idx,obj){return obj.id;}),
      fecha_pase: fecha_pase,
      fecha_iniciacion: fecha_iniciacion,
      remitente: $('#remitente').val(),
      concepto: $('#concepto').val(),
      iniciador: $('#iniciador').val(),
      tema: $('#tema').val(),
      ubicacion_fisica: $('#ubicacion').val(),
      destino: $('#destino').val(),
      nro_cuerpos: $('#nro_cuerpos').val(),
      nro_folios: $('#nro_folios').val(),
      anexo: $('#anexo').val(),
      resolucion: resolucion,
      disposiciones: disposiciones,
      notas: notas,
      tablaNotas: tablaNotas,
      dispo_cargadas: dispo_cargadas
    }

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalExpediente').find('.modal-footer').children().hide();
          $('#modalExpediente').find('.modal-body').children().hide();
          $('#modalExpediente').find('.modal-body').children('#iconoCarga').show();
        },
        success: function (data) {
            $('#btn-buscar').trigger('click');

            if (state == "nuevo"){ //Si está agregando agrega una fila con el nuevo expediente
              $('#mensajeExito h3').text('Creación Exitosa');
              $('#mensajeExito p').text('El expediente fue creado con éxito');
              $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
            }else{ //Si está modificando reemplaza la fila con el expediente modificado
              $('#mensajeExito h3').text('Modificación Exitosa');
              $('#mensajeExito p').text('El expediente fue modificado con éxito');
              $('#mensajeExito .cabeceraMensaje').addClass('modificar');
            }

            $('#modalExpediente').modal('hide');
            $('#mensajeExito').show();

        },
        error: function (data) {
            console.log('Error:', data);

            $('#modalExpediente').find('.modal-footer').children().show();
            $('#modalExpediente').find('.modal-body').children().show();
            $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

            var response = JSON.parse(data.responseText);

            //Si hay algun campo vacio en nro_exp
            var nro_exp_org_vacio = typeof response.nro_exp_org != "undefined";
            var nro_exp_interno_vacio = typeof response.nro_exp_interno != "undefined";
            var nro_exp_control_vacio = typeof response.nro_exp_control != "undefined";

            //Ocultar errores
            $('#error_nav_config').hide();
            $('#error_nav_notas').hide();

            //////////////////////////  ALERTAS DE CONFIGURACIÓN /////////////////////////

            if(typeof response.plataformas !== 'undefined'){
              mostrarErrorValidacion($('#contenedorPlataformas'),"Debe seleccionar al menos una plataforma",true);
            }

            if (nro_exp_org_vacio || nro_exp_interno_vacio || nro_exp_control_vacio) {
                if(nro_exp_org_vacio) mostrarErrorValidacion($('#nro_exp_org'),response.nro_exp_org[0],false);
                if(nro_exp_interno_vacio) mostrarErrorValidacion($('#nro_exp_interno'),response.nro_exp_interno[0],false);
                if(nro_exp_control_vacio) mostrarErrorValidacion($('#nro_exp_control'),response.nro_exp_control[0],false);
                $('#error_nav_config').show();
            }

            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.fecha_iniciacion != "undefined") {
              mostrarErrorValidacion($('#dtpFechaInicio input'),response.fecha_iniciacion[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.fecha_pase != "undefined") {
              mostrarErrorValidacion($('#dtpFechaPase input'),response.fecha_pase[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.destino != "undefined") {
              mostrarErrorValidacion($('#destino'),response.destino[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.ubicacion_fisica != "undefined") {
              mostrarErrorValidacion($('#ubicacion'),response.ubicacion_fisica[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.iniciador != "undefined") {
              mostrarErrorValidacion($('#iniciador'),response.iniciador[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.remitente != "undefined") {
              mostrarErrorValidacion($('#remitente'),response.remitente[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.concepto != "undefined") {
              mostrarErrorValidacion($('#concepto'),response.concepto[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.tema != "undefined") {
              mostrarErrorValidacion($('#tema'),response.tema[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_folios != "undefined") {
              mostrarErrorValidacion($('#nro_folios'),response.nro_folios[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.anexo != "undefined") {
              mostrarErrorValidacion($('#anexo'),response.anexo[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response["resolucion.nro_resolucion"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion'),response['resolucion.nro_resolucion'][0],false);
              $('#error_nav_config').show();
            }
            if (typeof response["resolucion.nro_resolucion_anio"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion_anio'),response['resolucion.nro_resolucion_anio'][0],false);
              $('#error_nav_config').show();
            }

            var i=0;
            $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
              if(typeof response['disposiciones.'+ i +'.nro_disposicion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion'),response['disposiciones.'+ i +'.nro_disposicion'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.nro_disposicion_anio'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion_anio'),response['disposiciones.'+ i +'.nro_disposicion_anio'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.descripcion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('#descripcion_disposicion'),response['disposiciones.'+ i +'.descripcion'][0],false);
                $('#error_nav_config').show();
              }

              i++;
            })

            //////////////////////////  ALERTAS DE NOTAS /////////////////////////
            var i = 0;
            $('.notaNueva').not('#moldeNotaNueva').each(function(){
                if(typeof response['notas.'+ i +'.fecha'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.dtpFechaNota input'),response['notas.'+ i +'.fecha'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.identificacion'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.identificacion'),response['notas.'+ i +'.identificacion'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.detalle'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.detalleNota'),response['notas.'+ i +'.detalle'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.id_tipo_movimiento'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.tiposMovimientos'),response['notas.'+ i +'.id_tipo_movimiento'][0],false);
                  $('#error_nav_notas').show();
                }
                i++;
            });
        }
    });
});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    id_plataforma: $('#B_plataforma').val(),
    fecha_inicio: $('#fecha_inicio1').val(),
    ubicacion_fisica: $('#B_ubicacion').val(),
    remitente: $('#B_remitente').val(),
    concepto: $('#B_concepto').val(),
    tema: $('#B_tema').val(),
    destino: $('#B_destino').val(),
    nota: $('#B_nota').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  $.ajax({
      type: 'POST',
      url: 'expedientes/buscarExpedientes',
      data: formData,
      dataType: 'json',
      success: function (data) {
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.expedientes.total,clickIndice);
        $('#cuerpoTabla tr').remove();

        for(var i = 0; i < data.expedientes.data.length; i++) {
          generarFilaTabla(data.expedientes.data[i]);
        }

        $('[data-toggle="tooltip"]').tooltip();

        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.expedientes.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$(document).on('click','.borrarNotaCargada',function(e){
  $(this).parent().parent().remove();
});


function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(expediente){
      var fila = $(document.createElement('tr'));
      expediente.ubicacion_fisica != null ? ubicacion=expediente.ubicacion_fisica : ubicacion='-' ;
      expediente.fecha_iniciacion != null ? fecha= convertirDate(expediente.fecha_iniciacion) : fecha='-' ;

      fila.attr('id','expediente' + expediente.id_expediente)
          .append($('<td>')
              .addClass('col-xs-3')
              .text(expediente.nro_exp_org + '-' + expediente.nro_exp_interno + '-' + expediente.nro_exp_control)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .text(fecha)
          )

          .append($('<td>')
              .addClass('col-xs-3')
              .text(expediente.nombre)
          )

          .append($('<td>')
              .addClass('col-xs-3')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                  )
                  .addClass('btn').addClass('btn-warning').addClass('modificar')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                  )
                  .addClass('btn').addClass('btn-danger').addClass('eliminar')
                  .attr('value',expediente.id_expediente)
              )
          )
        $('#cuerpoTabla').append(fila);
}

function habilitarControles(valor){
  $('#nro_exp_org').prop('readonly',!valor);
  $('#nro_exp_interno').prop('readonly',!valor);
  $('#nro_exp_control').prop('readonly',!valor);
  $('.plataformasExp').prop('disabled',!valor);
  $('#dtpFechaPase input').prop('readonly',!valor);
  $('#dtpFechaInicio input').prop('readonly',!valor);
  $('#destino').prop('readonly',!valor);
  $('#ubicacion').prop('readonly',!valor);
  $('#iniciador').prop('readonly',!valor);
  $('#remitente').prop('readonly',!valor);
  $('#concepto').prop('readonly',!valor);
  $('#tema').prop('readonly',!valor);
  $('#nro_cuerpos').prop('readonly',!valor);
  $('#nro_folios').prop('readonly',!valor);
  $('#anexo').prop('readonly',!valor);
  $('#nro_resolucion').prop('readonly',!valor);
  $('#nro_resolucion_anio').prop('readonly',!valor);

  $('#columnaDisposicion .Disposicion').each(function(){
    $(this).find('#nro_disposicion').prop('readonly',!valor);
    $(this).find('#nro_disposicion_anio').prop('readonly',!valor);
  });

  if(valor){// nuevo y modificar
    $('#btn-agregarDisposicion').show();
    $('#btn-guardar').prop('disabled',false).show();
    $('#btn-guardar').css('display','inline-block');
  }
  else{// ver detalle
    $('#btn-agregarDisposicion').hide();
    $('#btn-guardar').prop('disabled',true).hide();
    $('#btn-guardar').css('display','none');
  }
}

function limpiarModal(){
  lista_tipos_movimientos= [];
  $('#frmExpediente').trigger('reset');
  $('#modalExpediente input').val('');
  $('#id_expediente').val(0);
  $('#columnaDisposicion .disposicion').not('#moldeDisposicion').remove();
  $('.filaNota').not('#moldeFilaNota').remove(); //Eliminar todas las notas creadas
  $('.notaNueva').not('#moldeNotaNueva').remove(); //Eliminar las filas de notas nuevas
  $('.notaMov').not('#moldeNotaMov').remove(); //Eliminar las filas de notas con movimientos existentes
  //limipar tabla de resoluciones
  $('#tablaResolucion tbody').empty();
  limpiarAlertas();
}

function limpiarAlertas(){
  ocultarErrorValidacion($('#nro_exp_org'));
  ocultarErrorValidacion($('#nro_exp_interno'));
  ocultarErrorValidacion($('#nro_exp_control'));
  ocultarErrorValidacion($('#contenedorPlataformas'));
  ocultarErrorValidacion($('#dtpFechaPase input'));
  ocultarErrorValidacion($('#dtpFechaInicio input'));
  ocultarErrorValidacion($('#destino'));
  ocultarErrorValidacion($('#ubicacion'));
  ocultarErrorValidacion($('#iniciador'));
  ocultarErrorValidacion($('#remitente'));
  ocultarErrorValidacion($('#concepto'));
  ocultarErrorValidacion($('#tema'));
  ocultarErrorValidacion($('#nro_cuerpos'));
  ocultarErrorValidacion($('#nro_folios'));
  ocultarErrorValidacion($('#anexo'));
  ocultarErrorValidacion($('#nro_resolucion'));
  ocultarErrorValidacion($('#nro_resolucion_anio'));
  $('#columna .Disposicion').each(function(){
    $(this).find('#nro_disposicion').removeClass('alerta');
    $(this).find('#nro_disposicion_anio').removeClass('alerta');
  });
  $('.alertaTabla').remove();
}

function mostrarExpediente(expediente,plataformas,resolucion,disposiciones,notas,editable){
  $('#nro_exp_org').val(expediente.nro_exp_org);
  $('#nro_exp_control').val(expediente.nro_exp_control);
  $('#nro_exp_interno').val(expediente.nro_exp_interno);

  for (var i = 0; i < plataformas.length; i++) {
    $('#'+ plataformas[i].id_plataforma).prop('checked',true).prop('disabled',true);
  }

  if (plataformas.length > 0) $('.plataformasExp').change();

  if(expediente.fecha_pase != null){
    // var fecha_pase = expediente.fecha_pase.split('-');
    $('#dtpFechaPase input').val(convertirDate(expediente.fecha_pase));
    $('#fecha_pase').val(expediente.fecha_pase);
  }
  if(expediente.fecha_iniciacion != null){
    $('#dtpFechaInicio input').val(convertirDate(expediente.fecha_iniciacion));
    $('#fecha_inicio').val(expediente.fecha_iniciacion);
  }
  $('#destino').val(expediente.destino);
  $('#ubicacion').val(expediente.ubicacion_fisica);
  $('#iniciador').val(expediente.iniciador);
  $('#remitente').val(expediente.remitente);
  $('#concepto').val(expediente.concepto);
  $('#tema').val(expediente.tema);
  $('#nro_cuerpos').val(expediente.nro_cuerpos);
  $('#nro_folios').val(expediente.nro_folios);
  $('#anexo').val(expediente.anexo);

  if(resolucion != null){
    $('#nro_resolucion').val(resolucion.nro_resolucion);
    $('#nro_resolucion_anio').val(resolucion.nro_resolucion_anio);
  }
  resolucion.forEach(res => {
    var fila = $('<tr>').attr("id-resolucion",res.id_resolucion);
    fila.append($('<td>').text(res.nro_resolucion));
    fila.append($('<td>').text(res.nro_resolucion_anio));
    var boton = $('<button>').addClass('btn btn-danger borrarResolucion')
                           .css('margin-left','10px')
                           .append($('<i>').addClass('fa fa-fw fa-trash'));
    fila.append($('<td>').append(boton));
    $('#tablaResolucion').append(fila);

  });

  if(disposiciones.length != 0){
    for(var index=0; index<disposiciones.length; index++){
      agregarDisposicion(disposiciones[index],editable);
    }
  }

  var i = 0;
  for (i; i < notas.length; i++) {
    agregarNota(notas[i],false);
  }
  //Si hay notas mostrarlas
  $('.notasCreadas').toggle(i > 0);
}

function agregarNota(nota) {
  var fila = $('#moldeFilaNota').clone();
  fila.show();
  fila.removeAttr('moldeFilaNota');
  fila.attr('id',nota.id_nota);
  fila.find('.borrarNotaCargada').attr('id',nota.id_nota);
  fila.find('.identificacion').text(nota.identificacion);
  fila.find('.fecha').text(convertirDate(nota.fecha));
  fila.find('.detalle').text(nota.detalle);
  $('#tablaNotasCreadas tbody').append(fila);
}

function agregarDisposicion(disposicion, editable){
  $('#dispoCarg').toggle(editable);
  if(!editable){
    const moldeDisposicion = $('#moldeDisposicion').clone();
    moldeDisposicion.removeAttr('id');
    moldeDisposicion.attr('id',disposicion.id_disposicion);
    moldeDisposicion.find('.nro_disposicion').val(disposicion.nro_disposicion).prop('readonly',true);
    moldeDisposicion.find('.nro_disposicion_anio').val(disposicion.nro_disposicion_anio).prop('readonly',true);
    moldeDisposicion.find('#descripcion_disposicion').val(disposicion.descripcion).prop('readonly',true);
    moldeDisposicion.find('.borrarDisposicion').hide();
    moldeDisposicion.show();
    if(disposicion.id_nota != null){
      moldeDisposicion.find('#tiposMovimientosDisp').val(disposicion.id_estado_juego);
    }else {
      moldeDisposicion.find('#tiposMovimientosDisp').hide();
    }
    $('#columnaDisposicion').append(moldeDisposicion);
  }
  else{
    const moldeDisposicion = $('#moldeDispoCargada').clone();
    moldeDisposicion.removeAttr('id');
    moldeDisposicion.attr('id', disposicion.id_disposicion);
    moldeDisposicion.find('.nro_dCreada').text(disposicion.nro_disposicion);
    moldeDisposicion.find('.anio_dCreada').text(disposicion.nro_disposicion_anio);
    if(disposicion.descripcion != null){
      moldeDisposicion.find('.desc_dCreada').text(disposicion.descripcion);}
    else {
      moldeDisposicion.find('.desc_dCreada').text("Sin Descripción");
    }
    if(disposicion.descripcion_movimiento != null){
      moldeDisposicion.find('.mov_dCreada').text(disposicion.nombre_estado);
    }
    else{
      moldeDisposicion.find('.mov_dCreada').text(" -- ");
    }
    moldeDisposicion.find('.borrarDispoCargada').val(disposicion.id_disposicion);
    moldeDisposicion.show();
    $('#tablaDispoCreadas tbody').append(moldeDisposicion);
  }
}
$(document).on('click','.borrarDispoCargada', function(){
  $(this).parent().parent().remove();
})

function obtenerResoluciones(){
  var resoluciones=[];
  $.each($('#tablaResolucion tbody tr') , function(indexMayor){
    var res={
      id_resolucion:$(this).attr("id-resolucion"),
      nro_resolucion:$(this).find('td:eq(0)').text(),
      nro_resolucion_anio: $(this).find('td:eq(1)').text(),
    }
    resoluciones.push(res);
  });
  return resoluciones;
};
