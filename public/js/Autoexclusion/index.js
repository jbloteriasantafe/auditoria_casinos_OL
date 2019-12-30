$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Alta de autoexcluidos');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');

  $('#dtpFechaNacimiento').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaAutoexclusionEstado').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaVencimiento').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaFinalizacion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaCierreDefinitivo').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#btn-buscar').trigger('click');

});

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if (isNaN($('#herramientasPaginacion').getPageSize())) {
        var size = 10; // por defecto
    } else {
        var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaAutoexcluidos .activa').attr('value'), orden: $('#tablaAutoexcluidos .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaAutoexcluidos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        estado: $('#buscadorEstado').val(),
        casino: $('#buscadorCasino').val(),
        fecha_autoexclusion: $('#buscadorFechaAutoexclusion').val(),
        fecha_vencimiento: $('#buscadorFechaVencimiento').val(),
        fecha_finalizacion: $('#buscadorFechaFinalizacion').val(),
        fecha_cierre_definitivo: $('#buscadorFechaCierreDefinitivo').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/autoexclusion/buscarAutoexcluidos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);

            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaAutoexcluidos tbody').append(generarFilaTabla(resultados.data[i]));
            }

            $('#herramientasPaginacion')
                .generarIndices(page_number, page_size, resultados.total, clickIndice);
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
});

//Paginacion
$(document).on('click', '#tablaAutoexcluidos thead tr th[value]', function(e) {
    $('#tablaAutoexcluidos th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i')
            .removeClass().addClass('fa fa-sort-desc')
            .parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort-asc')
                .parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort')
                .parent().attr('estado', '');
        }
    }
    $('#tablaAutoexcluidos th:not(.activa) i')
        .removeClass().addClass('fa fa-sort')
        .parent().attr('estado', '');
    clickIndice(e,
        $('#herramientasPaginacion').getCurrentPage(),
        $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaAutoexcluidos .activa').attr('value');
    var orden = $('#tablaAutoexcluidos .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(unAutoexcluido) {
    console.log(unAutoexcluido);
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.dni').text(unAutoexcluido.nro_dni);
    fila.find('.apellido').text(unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres);
    fila.find('.estado').text(unAutoexcluido.descripcion);
    fila.find('.fecha_ae').text(unAutoexcluido.fecha_ae);
    fila.find('button').each(function(idx, c) { $(c).val(unAutoexcluido.id_autoexcluido); });
    let ver_mas = fila.find('.info').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VER MÁS', 'data-delay': '{"show":"300", "hide":"100"}' });

    fila.css('display', 'flow-root');

    return fila;
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

//Opacidad del modal al minimizar
$('#btn-minimizarMaquinas').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
    }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});



$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');

});

//Botón agregar nuevo AE
$('#btn-agregar-ae').click(function(e){
    e.preventDefault();

  //vuelvo a step1
  $('.page').removeClass('active');
  $('.step1').addClass('active');

  //limpio el form
  $('#frmAgregarAE :input').val('');

  //oculto el botón anterior
  $("#btn-prev").hide();

  //título y color header
  $('.modal-title').text('| AGREGAR AE');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  //oculto botón guardar y muestro botón siguiente en caso que se encuentre oculto
  $("#btn-guardar").hide();
  $("#btn-next").show();

  //cargar select provincia
  cargarProvincias();

  //Restablezco los valores ininiciales de step
  $('.step').removeClass('finish');
  $('.step').removeClass('actived');
  $('#one').addClass('actived');

  //muestra modal
  $('#modalAgregarAE').modal('show');
});


//función para autocompletar el input de provincia
function cargarProvincias(){
    var options = {
    url: "/js/Autoexclusion/provincias.json",
    listLocation: "provincias",
    getValue: "nombre",

    list: {
    	match: {
    		enabled: true
    	},
      onChooseEvent: function() {
			     cargarLocalidades();
           cargarLocalidadesVinculo();
		       }
    }
  };

  $("#nombre_provincia").easyAutocomplete(options);
  $("#nombre_provincia_vinculo").easyAutocomplete(options);
};

//función para autocompletar el input de localidad de los datos personales
function cargarLocalidades(){
  var nombre_provincia =  $("#nombre_provincia").val();
  var localidades = [];

  $.getJSON('/js/Autoexclusion/localidades.json', function(data) {
         $.each(data.localidades, function(key, value) {
           //si pertenece a la provincia seleccionada, la agrego
           if(value.provincia.nombre === nombre_provincia){
             var localidad = {
               id: value.id,
               nombre: value.nombre,
             }
             localidades.push(localidad);
           }
         }); // close each()
        }); // close getJSON()

  var options = {
    data: localidades,

  	getValue: "nombre",
      	list: {
      		match: {
      			enabled: true
      		}
      	}
  };

  $("#nombre_localidad").easyAutocomplete(options);
};

//función para autocompletar el input de localidad del contacto
function cargarLocalidadesVinculo(){
  var nombre_provincia =  $("#nombre_provincia_vinculo").val();
  var localidades = [];

  $.getJSON('/js/Autoexclusion/localidades.json', function(data) {
         $.each(data.localidades, function(key, value) {
           //si pertenece a la provincia seleccionada, la agrego
           if(value.provincia.nombre === nombre_provincia){
             var localidad = {
               id: value.id,
               nombre: value.nombre,
             }
             localidades.push(localidad);
           }
         }); // close each()
        }); // close getJSON()

  var options = {
    data: localidades,

  	getValue: "nombre",
      	list: {
      		match: {
      			enabled: true
      		}
      	}
  };

  $("#nombre_localidad_vinculo").easyAutocomplete(options);
};

//función para actualizar fechas
$( "#fecha_autoexlusion" ).change(function() {
  var fecha_autoexlusion = new Date( $( "#fecha_autoexlusion" ).val() );

  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1)


  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() + 360);
  $( "#fecha_cierre_definitivo" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 180);
  $( "#fecha_vencimiento_periodo" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 30);
  $( "#fecha_renovacion" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());

});

$("#btn-prev").on("click", function(){
    //si no es el primero, tengo anterior
    if($(".page.active").index() > 0){
        //cambio form active
        $(".page.active").removeClass("active").prev().addClass("active");
        //cambio step active
        $(".step.actived").removeClass("actived").removeClass("finish").prev().addClass("actived");
        //oculto botón anterior
        $("#btn-prev").hide();
      }

    //si le dio al boton de anterior y la pagina activa (despues de darle al boton)
    //es del step #2 (index1) o step #3 (index2), muestro los botones de ANTERIOR
    //y SIGUIENTE, y oculto el de ENVIAR
    if($(".page.active").index() == 1 || $(".page.active").index() == 2) {
        $("#btn-prev").show();
        $("#btn-next").show();
        $("#btn-guardar").hide();
    }

});

//botón siguiente modal agregar ae
$("#btn-next").on("click", function(){
  var step = $(".page.active").index() + 1;
  var valid = 1;

  //verificacion de inputs validos step #1
  if(step == 1) {
    if (isNaN($('#nro_dni').val()) && $('#nro_dni').val() != '') {
      mostrarErrorValidacion($('#nro_dni') , 'El número de DNI debe ser un dato de tipo numérico' , false);
      valid = 0;
    }

    //si existe dni, precargo el formulario con los datos
    $.get('/autoexclusion/existeAutoexcluido/' + $('#nro_dni').val(), function (data) {

      if (data != -1) {
        //precargo el step de datos personales y de contacto
        $('#apellido').val(data.autoexcluido.apellido);
        $('#nombres').val(data.autoexcluido.nombres);
        $('#fecha_nacimiento').val(data.autoexcluido.fecha_nacimiento);
        $('#id_sexo').val(data.autoexcluido.id_sexo);
        $('#id_estado_civil').val(data.autoexcluido.id_estado_civil);
        $('#domicilio').val(data.autoexcluido.domicilio);
        $('#nro_domicilio').val(data.autoexcluido.nro_domicilio);
        $('#nombre_provincia').val(data.autoexcluido.nombre_provincia);
        $('#nombre_localidad').val(data.autoexcluido.nombre_localidad);
        $('#telefono').val(data.autoexcluido.telefono);
        $('#correo').val(data.autoexcluido.correo);
        $('#id_ocupacion').val(data.autoexcluido.id_ocupacion);
        $('#id_capacitacion').val(data.autoexcluido.id_capacitacion);

        if (data.datos_contacto != null) {
          $('#nombre_apellido').val(data.datos_contacto.nombre_apellido);
          $('#domicilio_vinculo').val(data.datos_contacto.domicilio);
          $('#nombre_provincia_vinculo').val(data.datos_contacto.nombre_provincia);
          $('#nombre_localidad_vinculo').val(data.datos_contacto.nombre_localidad);
          $('#telefono_vinculo').val(data.datos_contacto.telefono);
          $('#vinculo').val(data.datos_contacto.vinculo);
        }

        //precargo el step del estado
        $('#id_casino').val(data.estado.id_casino);
        $('#id_estado').val(data.estado.id_nombre_estado);
        $('#fecha_autoexlusion').val(data.estado.fecha_ae);
        $('#fecha_vencimiento_periodo').val(data.estado.fecha_vencimiento);
        $('#fecha_renovacion').val(data.estado.fecha_renovacion);
        $('#fecha_cierre_definitivo').val(data.estado.fecha_cierre_ae);

        //precargo el step de la encuesta
        if (data.encuesta != null) {
          $('#juego_preferido').val(data.encuesta.id_juego_preferido);
          $('#id_frecuencia_asistencia').val(data.encuesta.id_frecuencia_asistencia);
          $('#veces').val(data.encuesta.veces);
          $('#tiempo_jugado').val(data.encuesta.tiempo_jugado);
          $('#socio_club_jugadores').val(data.encuesta.club_jugadores);
          $('#juego_responsable').val(data.encuesta.juego_responsable);
          $('#autocontrol_juego').val(data.encuesta.autocontrol_juego);
          $('#como_asiste').val(data.encuesta.como_asiste);
          $('#recibir_informacion').val(data.encuesta.recibir_informacion);
          $('#medio_recepcion').val(data.encuesta.medio_recibir_informacion);
          $('#observaciones').val(data.encuesta.observacion);
        }

        //cambio el estado del boton guardar a "existente", para editar y no agregar un AE nuevo
        $('#btn-guardar').val('existente');
      }

    });
  }
  //verificacion de inputs validos step #2
  else if(step == 2) {
    if (!/^[a-zA-Z\s]+$/.test($('#apellido').val())) {
      mostrarErrorValidacion($('#apellido') , 'El apellido no puede contener números' , false);
      valid = 0;
    }

    if (!/^[a-zA-Z\s]+$/.test($('#nombres').val())) {
      mostrarErrorValidacion($('#nombre') , 'Los nombres no puede contener números' , false);
      valid = 0;
    }

    if (isNaN($('#nro_domicilio').val()) && $('#nro_domicilio').val() != '') {
      mostrarErrorValidacion($('#nro_domicilio') , 'El número de domicilio debe ser un dato de tipo numérico' , false);
      valid = 0;
    }

    if (isNaN($('#telefono').val()) && $('#telefono').val() != '') {
      mostrarErrorValidacion($('#telefono') , 'El teléfono debe ser un dato de tipo numérico' , false);
      valid = 0;
    }

    if (!$('#correo').val().includes("@")) {
      mostrarErrorValidacion($('#correo') , 'El email ingresado debe ser valido' , false);
      valid = 0;
    }
  }
  //verificacion de inputs validos step #3
  else if(step == 3) {

  }


    //verifico que los input del step no esten en blanco.
    $( ".step"+step+" :input" ).each(function(){
      if( $(this).val() == '' && step<4){
          mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
          valid = 0;
      }
    });

    //verifico que el step sea valido para avanzar
    if(valid == 1 && step<4){
      //si no es el último step, tengo siguiente
      if($(".page.active").index() < $(".page").length-1){
          //cambio form active
          $(".page.active").removeClass("active").next().addClass("active");
          //cambio step active
          $(".step.actived").removeClass("actived").addClass("finish").next().addClass("actived");
          //muestro botón anterior
          $("#btn-prev").show();
      }
    }

    //si llegue al final, muestro botón de guardar y oculto el de siguiente
    if( $(".page.active").index() == $(".page").length-1 ){
      $("#btn-guardar").show();
      $("#btn-next").hide();
     }
});


//botón guardar ae
$('#btn-guardar').click(function (e) {
    let hay_datos_contacto = ($('#nombre_apellido').val()!='' || $('#domicilio_vinculo').val()!='' || $('#nombre_localidad_vinculo').val()!='' ||
                              $('#nombre_provincia_vinculo').val()!='' || $('#telefono_vinculo').val()!='' || $('#vinculo').val()!='') ?
                              1 : 0;

    let hay_encuesta = ($('#juego_preferido').val()!='' || $('#id_frecuencia_asistencia').val()!='' || $('#veces').val()!='' ||
                        $('#tiempo_jugado').val()!='' || $('#socio_club_jugadores').val()!='' || $('#juego_responsable').val()!='' ||
                        $('#autocontrol_juego').val()!='' || $('#recibir_informacion').val()!='' || $('#medio_recepcion').val()!='' ||
                        $('#observaciones').val()!='' || $('#como_asiste').val()!='') ?
                        1 : 0;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    //guardo los datos personales
    var ae_datos =  {
      nro_dni: $('#nro_dni').val(),
      apellido: $('#apellido').val(),
      nombres: $('#nombres').val(),
      fecha_nacimiento: $('#fecha_nacimiento').val(),
      id_sexo: $('#id_sexo').val(),
      id_estado_civil: $('#id_estado_civil').val(),
      domicilio: $('#domicilio').val(),
      nro_domicilio: $('#nro_domicilio').val(),
      nombre_localidad: $('#nombre_localidad').val(),
      nombre_provincia: $('#nombre_provincia').val(),
      telefono: $('#telefono').val(),
      correo: $('#correo').val(),
      id_ocupacion: $('#id_ocupacion').val(),
      id_capacitacion: $('#id_capacitacion').val(),
    }

    //guardo los datos de contacto
    var ae_datos_contacto =  {
      hay_datos_contacto: hay_datos_contacto,
      nombre_apellido: $('#nombre_apellido').val(),
      domicilio_vinculo: $('#domicilio_vinculo').val(),
      nombre_localidad_vinculo: $('#nombre_localidad_vinculo').val(),
      nombre_provincia_vinculo: $('#nombre_provincia_vinculo').val(),
      telefono_vinculo: $('#telefono_vinculo').val(),
      vinculo: $('#vinculo').val(),
    }

    //guardo los datos de estado+fecha
    var ae_estado = {
      id_casino: $('#id_casino').val(),
      id_nombre_estado: $('#id_estado').val(),
      fecha_autoexlusion: $('#fecha_autoexlusion').val(),
      fecha_vencimiento_periodo: $('#fecha_vencimiento_periodo').val(),
      fecha_renovacion: $('#fecha_renovacion').val(),
      fecha_cierre_definitivo: $('#fecha_cierre_definitivo').val(),
    }

    //guardo los datos de importación
    var ae_importacion = {
      foto1: $('#foto1')[0],

    }

    //guardo los datos de la encuesta
    var ae_encuesta = {
        hay_encuesta: hay_encuesta,
        juego_preferido: $('#juego_preferido').val(),
        id_frecuencia_asistencia: $('#id_frecuencia_asistencia').val(),
        veces: $('#veces').val(),
        tiempo_jugado: $('#tiempo_jugado').val(),
        socio_club_jugadores: $('#socio_club_jugadores').val(),
        juego_responsable: $('#juego_responsable').val(),
        autocontrol_juego: $('#autocontrol_juego').val(),
        recibir_informacion: $('#recibir_informacion').val(),
        medio_recepcion: $('#medio_recepcion').val(),
        observaciones: $('#observaciones').val(),
        como_asiste: $('#como_asiste').val(),
    }
    //datos para enviar
    var formData = {
      datos_personales: ae_datos,
      datos_contacto: ae_datos_contacto,
      ae_estado: ae_estado,
      ae_encuesta: ae_encuesta
    }

    //url de destino, dependiendo si se esta creando o modificando una sesión
    let url;
    //dependiendo el valor del botón guarda o edita
    let state = $('#btn-guardar').val();
    if( state == 'nuevo'){
      url =  'autoexclusion/agregarAE/1';
    }else{
      url = 'autoexclusion/agregarAE/0';
      //se agrega id_ae si se esta modificando
      var formData = {
        datos_personales: ae_datos,
        datos_contacto: ae_datos_contacto,
        ae_estado: ae_estado,
        ae_importacion: ae_importacion,
        ae_encuesta: ae_encuesta,
        // id_ae: $('#id_ae').val(),
      }
    }

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (state == "nuevo"){//si se esta creando guarda en tabla
              $('#mensajeExito P').text('La autoexclusión fue GUARDADA correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              // $('#cuerpoTabla').append(generarFilaTabla(data.sesion,data.estado,data.casino,data.nombre_inicio,data.nombre_fin,'guardar'));
            }else{ //Si está modificando
              $('#mensajeExito p').text('La autoexclusión fue EDITADA correctamente.');
              $('#mensajeExito div').css('background-color','#FFB74D');
            }
            // $('#frmFormula').trigger("reset");
            $('#modalAgregarAE').modal('hide');
            //hago un trigger al boton buscar asi actualiza la tabla sin recargar la pagina
            $('#btn-buscar').trigger('click');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);
        }
    });
});

//boton ver mas:
$(document).on('click', '#btnVerMas', function(e){
  e.preventDefault();

  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #fff');

  var id_autoexcluido = $(this).val();
  $('#modalVerMas input:checked').prop('checked' ,false);

  $.get('/autoexclusion/buscarAutoexcluido/' + id_autoexcluido, function (data) {
    console.log(data);

    $('#infoApellido').val(data.autoexcluido.apellido);
    $('#infoNombres').val(data.autoexcluido.nombres);
    $('#infoFechaNacimiento').val(data.autoexcluido.fecha_nacimiento);
    $('#infoDni').val(data.autoexcluido.nro_dni);
    $('#infoSexo').val(data.autoexcluido.id_sexo);
    $('#infoEstadoCivil').val(data.autoexcluido.id_estado_civil);
    $('#infoDomicilio').val(data.autoexcluido.domicilio);
    $('#infoNroDomicilio').val(data.autoexcluido.nro_domicilio);
    $('#infoProvincia').val(data.autoexcluido.nombre_provincia);
    $('#infoLocalidad').val(data.autoexcluido.nombre_localidad);
    $('#infoTelefono').val(data.autoexcluido.telefono);
    $('#infoEmail').val(data.autoexcluido.correo);
    $('#infoOcupacion').val(data.autoexcluido.id_ocupacion);
    $('#infoCapacitacion').val(data.autoexcluido.id_capacitacion);

    if (data.datos_contacto != null) {
      $('#infoNombreApellidoVinculo').val(data.datos_contacto.nombre_apellido);
      $('#infoDomiclioVinculo').val(data.datos_contacto.domicilio);
      $('#infoProvinciaVinculo').val(data.datos_contacto.nombre_provincia);
      $('#infoLocalidadVinculo').val(data.datos_contacto.nombre_localidad);
      $('#infoTelefonoVinculo').val(data.datos_contacto.telefono);
      $('#infoVinculo').val(data.datos_contacto.vinculo);
    }
    else {
      $('#infoDatosContacto').remove();

      $('#infoNombreApellidoVinculo').val('Información no ingresada');
      $('#infoDomiclioVinculo').val('Información no ingresada');
      $('#infoProvinciaVinculo').val('Información no ingresada');
      $('#infoLocalidadVinculo').val('Información no ingresada');
      $('#infoTelefonoVinculo').val('Información no ingresada');
      $('#infoVinculo').val('Información no ingresada');
    }

    $('#infoCasino').val(data.estado.id_casino);
    $('#infoEstado').val(data.estado.id_nombre_estado);
    $('#infoFechaAutoexclusion').val(data.estado.fecha_ae);
    $('#infoFechaVencimiento').val(data.estado.fecha_vencimiento);
    $('#infoFechaRenovacion').val(data.estado.fecha_renovacion);
    $('#infoFechaCierreDefinitivo').val(data.estado.fecha_cierre_ae);

    if (data.encuesta != null) {
      $('#infoJuegoPreferido').val(data.encuesta.id_juego_preferido);
      $('#infoFrecuenciaAsistencia').val(data.encuesta.id_frecuencia_asistencia);
      $('#infoVeces').val(data.encuesta.veces);
      $('#infoTiempoJugado').val(data.encuesta.tiempo_jugado);
      $('#infoSocioClubJugadores').val(data.encuesta.club_jugadores);
      $('#infoJuegoResponsable').val(data.encuesta.juego_responsable);
      $('#infoAutocontrol').val(data.encuesta.autocontrol_juego);
      $('#infoComoAsiste').val(data.encuesta.como_asiste);
      $('#infoRecibirInformacion').val(data.encuesta.recibir_informacion);
      $('#infoMedioRecepcion').val(data.encuesta.medio_recibir_informacion);
      $('#infoObservaciones').val(data.encuesta.observacion);
    }
    else {
      $('#infoJuegoPreferido').val('Información no ingresada');
      $('#infoFrecuenciaAsistencia').val('Información no ingresada');
      $('#infoVeces').val('Información no ingresada');
      $('#infoTiempoJugado').val('Información no ingresada');
      $('#infoSocioClubJugadores').val('Información no ingresada');
      $('#infoJuegoResponsable').val('Información no ingresada');
      $('#infoAutocontrol').val('Información no ingresada');
      $('#infoComoAsiste').val('Información no ingresada');
      $('#infoRecibirInformacion').val('Información no ingresada');
      $('#infoMedioRecepcion').val('Información no ingresada');
      $('#infoObservaciones').val('Información no ingresada');
    }

    $('#modalVerMas').modal('show');
  });

  //SALIR DEL MODAL VER MAS
  $('#btn-salir').click(function() {
      $('#modalVerMas').modal('hide');
  });

});
