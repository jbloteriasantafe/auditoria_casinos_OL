$(document).ready(function() {
    $('#barraMesas').attr('aria-expanded','true');
    $('#mesasPanio').removeClass();
    $('#mesasPanio').addClass('subMenu1 collapse in');
    $('.tituloSeccionPantalla').text('Gestionar Cierres y Aperturas');
    $('#opcAperturas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#opcAperturas').addClass('opcionesSeleccionado');

    $('#tipoArchivo').val('1');
    $('#selectCas').val('0');
    $('#casinoApertura').val('0');
    $('#selectJuego').val('0');
    $('#filtroMesa').val('');
    $('#B_fecha_filtro').val('');
    $('#B_fecha_cie').val('');
    $('#B_fecha_apert').val('');

    $('#mensajeExito').hide();
    $('#mensajeError').hide();

    $("#tablaCyA").tablesorter({
        headers: {
          3: {sorter:false}
        }

    });


  $(function(){
    $('#dtpFechaApert').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalCargaApertura'),
      });
  });

  $(function(){
    $('#dtpfechaCierre').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalCargaCierre'),
      });
  });
  $(function(){
    $('#dtpFecha').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
      });

        $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);
  });

  $('#modalCargaApertura #agregarMesa').click(clickAgregarMesa);
  $('#modalCargaCierre #agregarMesaCierre').click(clickAgregarMesaCierre);


}); //fin document ready

//BUSCAR BUSCAR BUSCA buscar

$('#btn-buscarCyA').click(function(e,pagina,page_size,columna,orden){

  e.preventDefault();


  $('#cuerpoTablaCyA tr').remove();

  var fila = $(document.createElement('tr'));


    if($('#tipoArchivo').val()==2){ //elige ver CIERRES
      //Fix error cuando librería saca los selectores
      if(isNaN($('#herramientasPaginacion').getPageSize())){
        var size = 10; // por defecto
      }else {
        var size = $('#herramientasPaginacion').getPageSize();
      }

      var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
      // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('cierre'),orden: $('#tablaResultados .activa').attr('estado')} ;

      if(sort_by == null){ // limpio las columnas
        $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
      }
      $('#tablaInicial').text('CIERRES');
          var formData= {
            fecha: $('#B_fecha_filtro').val(),
            nro_mesa: $('#filtroMesa').val(),
            id_juego:$('#selectJuego').val(),
            id_casino: $('#selectCas').val(),
            page: page_number,
            sort_by: sort_by,
            page_size: page_size,
          }

          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
              }
          });

          $.ajax({
              type: 'POST',
              url: 'cierres/filtrosCierres',
              data: formData,
              dataType: 'json',

              success: function (data){
                $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.cierre.total,clickIndiceMov);
                $('#tablaResultados tbody tr').remove();
                $('#tablaResultados').find('#estado_ocultar').hide();
                for (var i = 0; i < data.cierre.data.length; i++) {

                    var fila=  generarFilaCierres(data.cierre.data[i]);
                    $('#cuerpoTablaCyA').append(fila);
                }
                $('#herramientasPaginacion').generarIndices(page_number,page_size,data.cierre.total,clickIndiceMov);
              },
              error: function(data){
              },
          })
        }
  else
  {
    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }
    else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('apertura'),orden: $('#tablaResultados .activa').attr('estado')} ;

    if(sort_by == null){ // limpio las columnas
      $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

      $('#tablaInicial').text('APERTURAS');

        var formData = {
          fecha: $('#B_fecha_filtro').val(),
          nro_mesa: $('#filtroMesa').val(),
          id_juego:$('#selectJuego').val(),
          id_casino: $('#selectCas').val(),
          page: page_number,
          sort_by: sort_by,
          page_size: page_size,
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'aperturas/filtrosAperturas',
            data: formData,
            dataType: 'json',

            success: function (data){
              $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.apertura.total,clickIndiceMov);

              $('#tablaResultados tbody tr').remove();

              $('#tablaResultados').find('#estado_ocultar').show();

              for (var i = 0; i < data.apertura.data.length; i++) {
                  var fila=generarFilaAperturas(data.apertura.data[i]);
                  $('#cuerpoTablaCyA').append(fila);
              }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.apertura.total,clickIndiceMov);

            },
            error: function(data){
            },
        })
      }
});

//APERTURAS APERTURAS APERTURAS APERTURAS Aperturas

$("#modalCargaCierre").on('hidden.bs.modal', function () {
    $('#btn-buscarCyA').trigger('click');
  });

$("#modalCargaApertura").on('hidden.bs.modal', function () {
    $('#btn-buscarCyA').trigger('click');
  });

$(document).on('click','#tablaResultados thead tr th',function(e){

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
    clickIndiceMov(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
  });


function clickIndiceMov(e,pageNumber,tam){

    if(e != null){
      e.preventDefault();
    }

    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaResultados .activa').attr('value');
    var orden = $('#tablaResultados .activa').attr('estado');
    $('#btn-buscarCyA').trigger('click',[pageNumber,tam,columna,orden]);
}


$('#btn-generar-rel').on('click', function(e){

  e.preventDefault();


  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  var formData = {
    // id_ape: 1,
    // cantidad_maquinas: $('#cantidad_maquinas').val(),
    // cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
  }

  $.ajax({
      type: "POST",
      url: 'aperturas/generarRelevamiento',
      data: formData,
      dataType: 'json',

       beforeSend: function(data){

         $('#modalRelevamiento').modal('show');
         $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();

      },
      success: function (data) {

          // $('#btn-buscar').click();
           $('#modalRelevamiento').modal('hide');

          var iframe;
          iframe = document.getElementById("download-container");
          if (iframe === null){
              iframe = document.createElement('iframe');
              iframe.id = "download-container";
              iframe.style.visibility = 'hidden';
              document.body.appendChild(iframe);
          }

          iframe.src = data.url_zip;
          console.log('7777',iframe);
      },
      error: function (data) {

         $('#modalRelevamiento').modal('hide');

      }
  });

});

$('#btn-cargar-apertura').on('click', function(e){
  $('#mensajeExitoCargaAp').hide();
  e.preventDefault();
  limpiarCargaApertura();
  $('#tablaMesasApert tbody tr').remove();

  $('#B_fecha_apert').val("").prop('disabled',false);

  ocultarErrorValidacion($('#B_fecha_apert'));
  ocultarErrorValidacion($('#horarioAp'));
  ocultarErrorValidacion($('#casinoApertura'));

  $('#mensajeErrorCargaAp').hide();
  $('#casinoApertura').val("0");

  $('.detallesCargaAp').hide();
  $('#btn-finalizar-apertura').hide();

  $('#modalCargaApertura').modal('show');

})

$(document).on('change','#casinoApertura',function(){

  limpiarCargaApertura();
  $('#tablaMesasApert tbody tr').remove();

  $('#columnaDetalle').hide();
  var fecha=$('#B_fecha_apert').val();
  var id_casino=$('#casinoApertura').val();
  $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino,'mesas','id_mesa_de_panio','nro_mesa',1,true);
  $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
});

//presiona el botón dentro del modal de carga que confirma el casino
$('#confirmar').on('click',function(e){

  e.preventDefault();

    $('#btn-guardar-apertura').hide();
    if($('#casinoApertura').val() != 0 && $('#casinoApertura').val() != 4 && $('#B_fecha_apert').val().length !=0){

      $('.detallesCargaAp').show();

      var fecha = $('#B_fecha_apert').val();
      var id_casino=$('#casinoApertura').val();

      $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino,'mesas','id_mesa_de_panio','nro_mesa',1,true);

      $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#B_fecha_apert').prop('disabled', true);


      $.get('usuarios/quienSoy',function(data){
        $('#cargador').val(data.usuario.nombre);
        $('#cargador').attr('data-cargador',data.usuario.id_usuario);
      })
      }
      else{
        if($('#casinoApertura').val() == 0 ){
          mostrarErrorValidacion($('#casinoApertura'),'Campo Obligatorio',false);
        }
        if($('#B_fecha_apert').val().length == 0  ){
          mostrarErrorValidacion($('#B_fecha_apert'),'Campo Obligatorio',false);
        }
      }
})

$(document).on('change','.inputApe',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

        if(num != '' && num != 0)
        {   var cantidad=num;
            $(this).attr('data-ingresado',cantidad);
            var valor=$(this).attr('data-valor');

            var subtotal=0;
            subtotal = Number($('#totalApertura').val());
            subtotal += Number(valor * cantidad);

            $('#totalApertura').val(subtotal);
        }

        if (num ==''|| num ==0) {
          var cantidad=0;
          var subtotal=0;

          $(this).attr('data-ingresado',cantidad);

          subtotal = Number($('#totalApertura').val());
          subtotal -= Number(($(this).attr('data-ingresado')) * ($(this).attr('data-valor')));

          $('#totalApertura').val(subtotal);
        }
  }
  else{
        if(num !='' && num !=0)
        {
            var cantidad=num;
            var valor=$(this).attr('data-valor');
            var subtotal=0;

            subtotal = Number($('#totalApertura').val());
            subtotal -= Number(valor * $(this).attr('data-ingresado'));//resto antes de perderlo
            $('#totalApertura').val(subtotal);

            var total=0;
            total = Number($('#totalApertura').val());
            total += Number(valor * cantidad);//valor nuevo

            $('#totalApertura').val(total);

            $(this).attr('data-ingresado',cantidad);
        }
        if (num ==''|| num ==0) {
          var cantidad=0;
          var valor=$(this).attr('data-valor');
          var subtotal=0;

          subtotal = Number($('#totalApertura').val());
          subtotal -= Number($(this).attr('data-ingresado') * valor );

          $('#totalApertura').val(subtotal);
          $(this).attr('data-ingresado',cantidad);

        }
    }
});


$(document).on('click', '.btn_ver_mesa', function(e){
  e.preventDefault();

  $('#mensajeExitoCargaAp').hide();
  $('#mensajeErrorCargaAp').hide();

  //setea moneda en pesos
  $("input[name='monedaApertura'][value='1']").prop('checked', true);
  limpiarCargaApertura();

  $('#bodyMesas tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-cargado') == true){

    $('#btn-guardar-apertura').hide();
  }
  else{
  $('#tablaCargaApertura tbody tr').remove();
  $('#totalApertura').val('');
  $('#btn-guardar-apertura').show();
  $('#btn-guardar-apertura').prop('disabled',false);

  $('#columnaDetalle').show();
  var id_mesa=$(this).attr('data-id');
  $('#id_mesa_ap').val(id_mesa);

  $.get('mesas/detalleMesa/' + id_mesa, function(data){

    $('#moneda').val(data.moneda.descripcion);

    for (var i = 0; i < data.fichas.length; i++) {

      var fila= $('#filaFichasClon').clone();
      fila.removeAttr('id');
      fila.attr('id', data.fichas[i].id_ficha);
      fila.find('.fichaVal').val(data.fichas[i].valor_ficha).attr('id',data.fichas[i].id_ficha);
      fila.find('.inputApe').attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0);
      fila.css('display', 'block');
      $('#tablaCargaApertura #bodyCApertura').append(fila);
     }
  })
  }
})

//presiona el tachito dentro del listado de mesas, la borra de la lista
$(document).on('click', '.btn_borrar_mesa', function(e){
  e.preventDefault();

  $(this).parent().parent().remove();

  limpiarCargaApertura();
  limpiarCargaCierre();

  $('#columnaDetalle').hide();
  $('#columnaDetalleCie').hide();

  var tbody = $("#listaMesasCierres tbody");

  //si queda vacia la tabla, la oculta.
  if (tbody.children().length == 0) {

    console.log('andaaa');
    $('.listMes').hide();
  }
});


//dentro del modal de carga apertura, presiona el botón guardar:
$('#btn-guardar-apertura').on('click', function(e){

  e.preventDefault();

  $(this).prop('disabled','true');

  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  $('#recalcularApert').trigger('click');


    var id_mesa =$('#modalCargaApertura #id_mesa_ap').val();
    var fichas=[];
    var moneda= $('input[name=monedaApertura]:checked').val();
    var f= $('#bodyCApertura > tr');
    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        cantidad_ficha: $(this).find('.inputApe').val()
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_cargador: $('#cargador').attr('data-cargador'),
        id_casino: $('#casinoApertura').val(),
        hora: $('#horarioAp').val(),
        fecha: $('#B_fecha_apert').val(),
        id_fiscalizador:$('#fiscalizApertura').obtenerElementoSeleccionado(),
        id_mesa_de_panio:id_mesa,
        total_pesos_fichas_a: $('#totalApertura').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'aperturas/guardarApertura',
          data: formData,
          dataType: 'json',

          success: function (data){
            $('#columnaDetalle').hide();
            $('#btn-guardar-apertura').hide();
            $('#bodyMesas').find('#' + id_mesa).attr('data-cargado',true);
            $('#bodyMesas').find('#' + id_mesa).find('.btn_borrar_mesa').parent().remove();
            $('#bodyMesas').find('#' + id_mesa).find('.btn_ver_mesa').prop('disabled', true);
            $('#bodyMesas').find('#' + id_mesa).append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50')));
            $('#mensajeExitoCargaAp').show();
            $('#btn-guardar-apertura').hide();
            $('#btn-finalizar-apertura').show();
          },
          error: function(data){
            $('#mensajeError h3').text('ERROR');
            console.log('ddd',data);
            var response = data.responseJSON;

            if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_apert'),response.fecha[0],false);
            }
            if(typeof response.hora !== 'undefined'){
              mostrarErrorValidacion($('#horarioAp'),response.hora[0],false);
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              $('#mensajeErrorCargaAp').show();
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#mensajeErrorCargaAp').show();
            }
            if(typeof response.id_moneda !== 'undefined'){
              $('#mensajeErrorCargaAp').show();
            }
            $('#btn-guardar-apertura').prop('disabled',false);

          },
      })

});


//btn finalizar dentro del modal carga apertura
$('#btn-finalizar-apertura').on('click', function(){

    $('#modalCargaApertura').modal('hide');
    $('#mensajeExito h3').text('ÉXITO');
    $('#mensajeExito p').text('Las Aperturas cargadas han sido guardadas correctamente');
    $('#mensajeExito').show();
})


//CIERRES CIERRES CIERRES CIERRES Cierres

$('#btn-cargar-cierre').on('click', function(e){

  e.preventDefault();

  limpiarCargaCierre();
  ocultarErrorValidacion($('#horario_ini_c'));
  ocultarErrorValidacion($('#juegoCierre'));
  ocultarErrorValidacion($('#horarioCie'));
  ocultarErrorValidacion($('#B_fecha_cie'));
  ocultarErrorValidacion($('#totalAnticipoCierre'));
  ocultarErrorValidacion($('#casinoCierre'));

  $('#B_fecha_cie').val('');

  $('#mensajeCargaConError').hide();
  $('#mensajeFichasError2').hide();
  $('#mensajeErrorMoneda').hide();

  $('#casinoCierre').val("0");
  $('.desplegable').hide();

  $('#btn-guardar-cierre').hide();
  $('#btn-finalizar-cierre').hide();

  $('#modalCargaCierre').modal('show');

})

//por si luego de confirmar cambia de nuevo el casino
$(document).on('change','#casinoCierre',function(){

  var id_casino=$('#casinoCierre').val();
  $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);
  $('#fiscalizadorCierre').val('');
  $('#tablaCargaCierreF tbody tr').remove();
  $('#horario_ini_c').val("");
  $('#horarioCie').val("");
  $('#totalCierre').val("");
  $('#total_anticipos_c').val("");
  $('columnaDetalleCie').hide();
});

$('#confirmarCierre').on('click',function(e){

  e.preventDefault();

    if($('#casinoCierre').val() != 0  && $('#B_fecha_cie').val().length != 0 ){

      $('.desplegable').show(); //agregar mesa + fiscalizador
      var id_casino=$('#casinoCierre').val();

      // $('#fiscalizadorCierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      // $('#fiscalizadorCierre').setearElementoSeleccionado(0,"");

      $('.listMes').hide();
      $('#listaMesasCierres tbody tr').remove();
      $('#columnaDetalleCie').hide();
      $('#mensajeExitoCargaCie').hide();

      $.get('usuarios/quienSoy',function(data){
        $('#fiscalizadorCierre').val(data.usuario.nombre);
        $('#fiscalizadorCierre').attr('data-cargador',data.usuario.id_usuario);
      })
    }
  else{
    if($('#casinoCierre').val() == 0 ){
      mostrarErrorValidacion($('#casinoCierre'),'Campo Obligatorio',false);
    }
    if($('#B_fecha_cie').val().length == 0  ){
      mostrarErrorValidacion($('#B_fecha_cie'),'Campo Obligatorio',false);
    }
  }

})

$(document).on('click', '.cargarDatos', function(e){
  e.preventDefault();

  var id_casino=$('#casinoCierre').val();

  $('#mensajeExitoCargaAp').hide();
  $('#mensajeErrorCargaAp').hide();
  $('#mensajeExitoCargaCie').hide();
  limpiarCargaCierre();


  $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);

  $('#btn-guardar-cierre').show();
  $("input[name='moneda'][value='1']").prop('checked', true);

  $('#modalCargaCierre #id_mesa_panio').val($(this).attr('data-id'));


  //$('#hor_cierre').datepicker('1-2-3');
  //$('#horario_ini_c').datepicker('1-2-3');

  $('#listaMesasCierres tbody tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-cargado') == true){

    $('#btn-guardar-cierre').hide();
  }
  else{
  //$('#listaMesasCierres tbody tr').remove();
  $('#btn-guardar-cierre').show();
  $('#btn-guardar-cierre').prop('disabled',false);
  }
  $('#columnaDetalleCie').show();

  var id_mesa=$(this).attr('data-id');
  $('#id_mesa_ap').val(id_mesa);

  $.get('mesas/detalleMesa/' + id_mesa, function(data){

    //$('#moneda').val(data.moneda.descripcion);
    for (var i = 0; i < data.fichas.length; i++) {


      var fila= $('#clonCierre').clone();
      fila.removeAttr('id');
      fila.attr('id', data.fichas[i].id_ficha);
      fila.find('.fichaValCC').val(data.fichas[i].valor_ficha).attr('id',data.fichas[i].id_ficha);
      fila.find('.inputCie').attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0);
      fila.css('display', 'block');
      $('#bodyFichasCierre').append(fila);
     }

  })

});


$(document).on('change','.inputCie',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if(num !== '' && num !== 0) //si se ingreso un valor diferente de 0
    {   var cantidad=num;
        $(this).attr('data-ingresado',cantidad);
        var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalCierre').val());
        subtotal += Number(cantidad);
        $('#totalCierre').val(subtotal);}

    if (num == '' || num == 0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalCierre').val());
      subtotal -= Number($(this).attr('data-ingresado') );
      $('#totalCierre').val(subtotal);
      $(this).attr('data-ingresado',cantidad);

    }
  }
  else{
    if(num !== '' && num !== 0){ //si se ingreso un valor diferente de 0
        var cantidad=num;
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalCierre').val());
          if(($(this).attr('data-ingresado')) !== 0){
            subtotal-=Number($(this).attr('data-ingresado'));}
        $('#totalCierre').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalCierre').val());
        total += Number(cantidad);

        $('#totalCierre').val(total);}

    if (num=='' || num==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalCierre').val());
          subtotal -= Number($(this).attr('data-ingresado') );
          $('#totalCierre').val(subtotal);

          $(this).attr('data-ingresado',cantidad);

    }
  }
})



//dentro del modal de carga de cierre, presiona el botón guardar
$('#btn-guardar-cierre').on('click', function(e){

  e.preventDefault();

  $(this).prop('disabled',true);

  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  $('#recalcular').trigger('click');

    var fichas=[];
    var id_mesa=$('#id_mesa_panio').val();
    var moneda= $('input[name=moneda]:checked').val();
    var f= $('#bodyFichasCierre > tr');
    $.each(f, function(index, value){
      var valor={
        id_ficha: $(this).find('.fichaValCC').attr('id'),
        monto_ficha: $(this).find('.inputCie').attr('data-ingresado')
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);
      }
      else{
        fichas=null;
      }

    })

      var formData= {
        fecha: $('#B_fecha_cie').val(),
        hora_inicio: $('#horario_ini_c').val(),
        hora_fin:$('#horarioCie').val(),
        id_fiscalizador: $('#fiscalizadorCierre').attr('data-cargador'),
        id_casino: $('#casinoCierre').val(),
        id_juego_mesa: $('#juegoCierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_c:$('#totalCierre').val(),
        total_anticipos_c:$('#totalAnticipoCierre').val(),
        id_mesa_de_panio:id_mesa,
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'cierres/guardar',
          data: formData,
          dataType: 'json',

          success: function (data){

            limpiarCargaCierre();

            $('#listaMesasCierres tbody').find('#' + id_mesa).attr('data-cargado',true);
            $('#listaMesasCierres tbody').find('#' + id_mesa).find('.btn_borrar_mesa').parent().remove();
            $('#listaMesasCierres tbody').find('#' + id_mesa).find('.cargarDatos').prop('disabled', true);
            $('#listaMesasCierres tbody').find('#' + id_mesa).append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50')));

            $('#columnaDetalleCie').hide();
            $('#mensajeCargaConError').hide();
            $('#mensajeFichasError2').hide();
            $('#mensajeErrorMoneda').hide();
            $('#btn-guardar-cierre').hide();
            $('#mensajeExitoCargaCie').show();
            $('#btn-finalizar-cierre').show();

          },
          error: function(data){

            var response = data.responseJSON;

            if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_cie'),response.fecha[0],false);
            }

            if(typeof response.fichas !== 'undefined'){
              $('#mensajeFichasError2').show();

            }
            if(typeof response.id_juego_mesa !== 'undefined' || typeof response.id_mesa_de_panio !== 'undefined' || typeof response.id_fiscalizador !== 'undefined'){
              $('#mensajeCargaConError').show();
              $('#mensajeCargaConError').focus();
            }

            if(typeof response.id_moneda !== 'undefined'){
              $('#mensajeErrorMoneda').show();
            }

            $('#btn-guardar-cierre').prop('disabled',false);



          },
      })

});

//btn finalizar dentro del modal de carga de cierre
$('#btn-finalizar-cierre').on('click', function(){

  $('#modalCargaCierre').modal('hide');
  $('#mensajeExito h3').text('EXITO');
  $('#mensajeExito p').text('Los Cierres cargados han sido guardados correctamente.');
  $('#mensajeExito').show();

})

$(document).on('click', '.infoCyA', function(e) {

  e.preventDefault();

  //veo el data-tipo para ver si se trata de una apertura o de un cierres
  //y hago el get que corresponda
  var tipo=$(this).attr('data-tipo');

  if(tipo=='apertura'){

    $('#bodyFichasDetApert tr').remove();
    var id_apertura=$(this).val();

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){
      console.log('pp',data.detalles);
      $('#modalDetalleApertura').modal('show');

      $('.mesa_det_apertura').text(data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.fecha_det_apertura').text(data.apertura.fecha);
      $('.juego_det_apertura').text(data.juego.nombre_juego);
      $('.hora_apertura_det').text(data.apertura.hora_format);
      $('.cargador_det_apertura').text(data.cargador.nombre);
      $('.fisca_det_apertura').text(data.fiscalizador.nombre);
      $('#totalAperturaDet').val(data.apertura.total_pesos_fichas_a);

      if(data.cargador!=null){
      $('.cargador_det_apertura').text(data.cargador.nombre);}

      for (var i = 0; i < data.detalles.length; i++) {

        var fila = $(document.createElement('tr'));

            fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detalles[i].valor_ficha)))

              if(data.detalles[i].cantidad_ficha != null){
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detalles[i].cantidad_ficha)));}
              else{
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(0)));
                }


        $('#bodyFichasDetApert').append(fila);
      }

    })
  }

  if(tipo=='cierre'){

    //limpiar porquerias
    $('#datosCierreFichas tr').remove();
    $('#datosCierreFichasApertura tr').remove();

    var id_cierre= $(this).val();

    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      $('#modalDetalleCierre').modal('show');

      $('.mesa_det_cierre').text(data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.fecha_detalle_cierre').text(data.cierre.fecha);
      $('.juego_det_cierre').text(data.nombre_juego);
      $('.cargador_det_cierre').text(data.cargador.nombre);

      if(data.cierre.hora_fin != null){
        $('.hora_cierre_det').text(data.cierre.hora_fin_format);
      }
      else{
        $('.hora_cierre_det').text(' - ');

      }
      if(data.cierre.hora_inicio != null){
        $('.inicio_cierre_det').text(data.cierre.hora_inicio_format);
      }
      else{
        $('.inicio_cierre_det').text(' - ');

      }

      //creo la tabla de fichas de cierres
      for (var i = 0; i < data.detallesC.length; i++) {
        var fila = $(document.createElement('tr'));

            fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detallesC[i].valor_ficha)))
            if(data.detallesC[i].monto_ficha != null){
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detallesC[i].monto_ficha)));}
            else{
              fila.append($('<td>')
              .addClass('col-xs-6')
              .append($('<h8>')
              .text(0)));
            }

        $('#datosCierreFichas').append(fila);
      }

      $('#total_detalle').val(data.cierre.total_pesos_fichas_c);
      if(data.cierre.total_anticipos_c != null){
        $('#anticipos_detalle').val(data.cierre.total_anticipos_c);
      }
      else{
        $('#anticipos_detalle').val(' - ');
      }

       for (var i = 0; i < data.detalleAP.length; i++) {
         var fila2 = $(document.createElement('tr'));

             fila2.append($('<td>')
                 .addClass('col-xs-6')
                 .append($('<h8>')
                 .text(data.detalleAP[i].valor_ficha).css('align','center')))
                 if(data.detalleAP[i].monto_ficha != null){
                 fila2.append($('<td>')
                 .addClass('col-xs-6')
                 .append($('<h8>')
                 .text(data.detalleAP[i].monto_ficha).css('align','center')));}
                 else{
                   fila2.append($('<td>')
                   .addClass('col-xs-6')
                   .append($('<h8>')
                   .text('0').css('align','center')));
                 }

         $('#datosCierreFichasApertura').append(fila2);
       }

        $('#totalA_det_cierre').val(data.apertura.total_pesos_fichas_a)
    })
  }
});

$(document).on('click', '.modificarCyA', function(e) {

  e.preventDefault();
  //verifico que tipo de archivo es: cierre o aperturas
  //en base a eso hago diferentes gets y uso diferentes modales.
  var tipo= $(this).attr('data-tipo');
  $('#modificar_apertura').hide();

  //APERTURA
  if(tipo =='apertura'){

    ocultarErrorValidacion($('#hs_apertura'));
    ocultarErrorValidacion($('#car_apertura'));
    ocultarErrorValidacion($('#fis_apertura'));
    $('#modificarFichasAp tr').remove();
    $('#errorModificar2').hide();


    var id_apertura=$(this).val();
    //guardo el id para hacer el guardar despues
    $('#modalModificarApertura #id_apertura').val(id_apertura);

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){

      var id_casino = data.casino.id_casino;
      $('.f_apertura').val(data.apertura.fecha);
      $('#fis_apertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_apertura').setearElementoSeleccionado(data.fiscalizador.id_usuario, data.fiscalizador.nombre);
      $('.car_apertura').val(data.cargador.nombre);
      $('.cas_apertura').val( data.casino.nombre);
      $('#hs_apertura').val(data.apertura.hora_format);
      $('.j_apertura').val(data.juego.nombre_juego);
      $('.nro_apertura').val(data.mesa.nro_mesa);
      $("input[name='monedaModApe'][value='"+data.moneda.id_moneda+"']").prop('checked', true);


      for (var i = 0; i < data.detalles.length; i++) {
        var fila = $(document.createElement('tr'));

        fila.attr('id', data.detalles[i].id_ficha)
            .append($('<td>')
            .addClass('col-md-3').addClass('fichaVal').attr('id',data.detalles[i].id_ficha)
            .append($('<input>').prop('readonly','true')
            .val(data.detalles[i].valor_ficha).css('text-align','center')))

            if(data.detalles[i].cantidad_ficha != null){

              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modApertura').attr('id', 'input').val(data.detalles[i].cantidad_ficha).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', data.detalles[i].cantidad_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modApertura').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', 0)))
            }


        $('#modificarFichasAp').append(fila);
      }
      var total = 0;

      $.each($('#modificarFichasAp tr'), function(index, value){

        var valor = $(this).find('.modApertura').attr('data-valor');
        var ingresado = $(this).find('.modApertura').attr('data-ingresado');

        total += Number(valor * ingresado);

        console.log('si', total);

        $('#totalModifApe').val(total);
      })
      $('#modificar_apertura').show();

      $('#modalModificarApertura').modal('show');

    })
  }

  //CIERRE
  if(tipo=='cierre'){

    ocultarErrorValidacion($('#hs_cierre_cierre'));
    ocultarErrorValidacion($('#hs_inicio_cierre'));
    ocultarErrorValidacion($('#totalAnticipoModif'));
    ocultarErrorValidacion($('#fis_cierre'));
    $('#errorModificarCierre2').hide();
    $('#errorModificarCierre').hide();

    $('#modificarFichasCie tr').remove();
    var id_cierre= $(this).val();
    $('#modalModificarCierre #id_cierre').val(id_cierre);


    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      var id_casino = data.casino.id_casino;

      $("input[name='monedaModCie'][value='"+data.moneda.id_moneda+"']").prop('checked', true);

      $('.f_cierre').val(data.cierre.fecha);
      $('#fis_cierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_cierre').setearElementoSeleccionado(data.cargador.id_usuario, data.cargador.nombre);
      $('.cas_cierre').val( data.casino.nombre);
      $('#hs_cierre_cierre').val(data.cierre.hora_fin_format);
      $('#hs_inicio_cierre').val(data.cierre.hora_inicio_format);
      $('.j_cierre').val(data.nombre_juego);
      $('.nro_cierre').val(data.mesa.nro_mesa);
      $('#totalAnticipoModif').val(data.cierre.total_anticipos_c);
      $('#totalModifCie').val(data.cierre.total_pesos_fichas_c);

      for (var i = 0; i < data.detallesC.length; i++) {
        var fila = $(document.createElement('tr'));

        fila.attr('id', data.detallesC[i].id_ficha)
            .append($('<td>')
            .addClass('col-md-3').addClass('fichaVal').attr('id',data.detallesC[i].id_ficha)
            .append($('<input>').prop('readonly','true')
            .val(data.detallesC[i].valor_ficha).css('text-align','center')))

            if(data.detallesC[i].monto_ficha != null){

              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modCierre').attr('id', 'input').val(data.detallesC[i].monto_ficha).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', data.detallesC[i].monto_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modCierre').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', 0)))
            }


        $('#modificarFichasCie').append(fila);
      }
      var total = 0;
      $('#modificarFichasCie tr').each(function(){

        var ingresado = $(this).find('.modCierre').attr('data-ingresado');

        total += Number(ingresado);


        $('#totalModifCie').val(total);
      })

      $('#modalModificarCierre').modal('show');

      })

  }
});

//detecta modificaciones en los inputs de modificacion de apertura
$(document).on('change','.modApertura',function(){

  var num= Numeros($(this).val());

    if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado
      if(num !='' && num!=0)
      {   var cantidad=num;
          $(this).attr('data-ingresado',cantidad);
          var valor=$(this).attr('data-valor');

          var subtotal=0;
          subtotal = Number($('#totalModifApe').val());
          subtotal += Number(valor * cantidad);

          $('#totalModifApe').val(subtotal);
      }

      if (num==''|| num==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado'));

        $('#totalModifApe').val(subtotal);
        $(this).attr('data-ingresado',cantidad);

      }
    }
    else{
      if(num!='' && num!=0)
      {   var cantidad=num;
          var valor=$(this).attr('data-valor');
          var ingresado=$(this).attr('data-ingresado');
          var subtotal = 0;

          subtotal = Number($('#totalModifApe').val());
          subtotal -= Number(valor * ingresado);//resto antes de perderlo
          $('#totalModifApe').val(subtotal);

          $(this).attr('data-ingresado',cantidad);

          var total=0;
          total = Number($('#totalModifApe').val());
          total += Number(valor * cantidad);//valor nuevo

          $('#totalModifApe').val(total);
      }
      if (num=='' || num==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado')*($(this).attr('data-valor')));

        $('#totalModifApe').val(subtotal);
        $(this).attr('data-ingresado',cantidad);

      }
    }
})


//Guardar El modificar apertura
$('#modificar_apertura').on('click', function(e){
  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];
    var f= $('#modificarFichasAp > tr');
    var moneda= $('input[name=monedaModApe]:checked').val();


    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        cantidad_ficha: $(this).find('.modApertura').val()
      }
      if(valor.cantidad_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_apertura:$('#modalModificarApertura #id_apertura').val(),
        hora: $('#hs_apertura').val(),
        id_fiscalizador:$('#fis_apertura').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifApe').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'aperturas/modificarApertura',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificarApertura').modal('hide');
             $('#mensajeExito h3').text('ÉXITO');
             $('#mensajeExito p').text('Apertura guardada correctamente');
             $('#mensajeExito').show();
          },
          error: function(data){

            var response = data.responseJSON.errors;

            if(typeof response.hora !== 'undefined'){
              mostrarErrorValidacion($('#hs_apertura'),response.hora[0],false);
            }
            if(typeof response.id_moneda !== 'undefined'){
              $('#errorModificar2').show();
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              mostrarErrorValidacion($('#fis_apertura'),response.id_fiscalizador[0],false);
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#errorModificar').show();
            }
            if(typeof response.fichas !== 'undefined'){
              $('#errorModificar').show();
            }

          },
      })

});

//MODIFICAR CIERRE
//modifica el monto de alguna ficha
$(document).on('change','.modCierre',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if(num!=null && num!=0) //si se ingreso un valor diferente de 0
    {   var cantidad=num;
        $(this).attr('data-ingresado',cantidad);
        //var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalModifCie').val());
        subtotal += Number(cantidad);
        $('#totalModifCie').val(subtotal);}

    if (num==null || num==0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalModifCie').val());
      subtotal -= Number($(this).attr('data-ingresado') );

      $('#totalModifCie').val(subtotal);
      $(this).attr('data-ingresado',cantidad);

    }
  }
  else{
    if(num!=null && num!=0){ //si se ingreso un valor diferente de 0
        var cantidad=num;
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalModifCie').val());
        subtotal-=Number($(this).attr('data-ingresado'));
        $('#totalModifCie').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalModifCie').val());
        total += Number(cantidad);

        $('#totalModifCie').val(total);}

    if (num=='' || num==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalModifCie').val());
          subtotal -= Number($(this).attr('data-ingresado') );

          $('#totalModifCie').val(subtotal);
          $(this).attr('data-ingresado',cantidad);

    }

  }

})


//Guardar El modificarC
$('#modificar_cierre').on('click', function(e){
  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];
    var moneda= $('input[name=monedaModCie]:checked').val();
    var f= $('#modificarFichasCie > tr');

    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        monto_ficha: $(this).find('.modCierre').val()
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_cierre_mesa:$('#modalModificarCierre #id_cierre').val(),
        hora_inicio: $('#hs_inicio_cierre').val(),
        hora_fin: $('#hs_cierre_cierre').val(),
        id_fiscalizador:$('#fis_cierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifCie').val(),
        total_anticipos_c: $('#totalAnticipoModif').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'cierres/modificarCierre',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificarCierre').modal('hide');
             $('#mensajeExito h3').text('ÉXITO');
             $('#mensajeExito p').text('Cierre guardado correctamente');
             $('#mensajeExito').show();
          },
          error: function(data){

            var response = data.responseJSON.errors;


            if(typeof response.id_fiscalizador !== 'undefined'){
              mostrarErrorValidacion($('#fis_cierre'),response.id_fiscalizador[0],false);
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#errorModificarCierre').show();
            }
            if(typeof response.id_moneda !== 'undefined'){
              $('#errorModificarCierre2').show();
            }
            if(typeof response.fichas !== 'undefined'){
              $('#errorModificarCierre').show();
            }

          },
      })

});


//botón validar dentro del listado de aperturas
$(document).on('click', '.validarCyA', function(e) {
  e.preventDefault();
  $('#mensajeErrorValApertura').hide();

  $('#mensajeExito').hide();

  limpiarModalValidar();

  var id_apertura=$(this).val();
  $('#validar').val(id_apertura);
  $('#validar').hide();
  $('#div_cierre').hide();
   $('#obsValidacion').val(''),

  $.get('aperturas/obtenerApValidar/' + id_apertura , function(data){

    $('.nro_validar').text(data.mesa.nro_mesa);
    $('.fechaAp_validar_aper').text(data.apertura.fecha);
    $('.j_validar').text(data.juego.nombre_juego);
    $('.cas_validar').text(data.casino.nombre);

    $('.hs_validar_aper').text(data.apertura.hora);
    $('.fis_validar_aper').text(data.fiscalizador.nombre);
    $('.car_validar_aper').text(data.cargador.nombre);
    $('.tipo_validar_aper').text(data.tipo_mesa.descripcion);
    $('.mon_validar_aper').text(data.moneda.descripcion);
    $('.mon_validar_aper').val(data.moneda.id_moneda);
    $('#total_aper_validar').val(data.apertura.total_pesos_fichas_a);

    for (var i = 0; i < data.fechas_cierres.length; i++) {
      $('#fechaCierreVal')
      .append($('<option>')
              .val(data.fechas_cierres[i].id_cierre_mesa)
              .text(data.fechas_cierres[i].fecha + ' -- '+data.fechas_cierres[i].hora_inicio
                    +' a '+ data.fechas_cierres[i].hora_fin
                    +' -- '+data.fechas_cierres[i].siglas
                  )
              )
    }

  })

  $('#modalValidarApertura').modal('show');

});

//comparar, busca el cierre que se desea comparar
$(document).on('click','.comparar',function(){

  if($('#fechaCierreVal').val() != 0){
    $('#tablaValidar tbody tr').remove();

    $('#validar').show();
    var moneda=$('.mon_validar_aper').val();
    var apertura=$('#validar').val();
    var cierre=$('#fechaCierreVal').val();
    //{id_apertura}/{id_cierre}/{id_moneda}
    $.get('compararCierre/' + apertura + '/' + cierre + '/' + moneda, function(data){

      $('#div_cierre').show();

      // //datos cierre
      if(data.cierre == null){
        $('.hs_inicio_validar').text('-');
        $('.hs_cierre_validar').text('-');
        $('.f_validar').text('-');
        $('#anticipos_validar').val('-');
        $('#total_cierre_validar').val('-');
      }else {
        $('.hs_inicio_validar').text(data.cierre.hora_inicio);
        $('.hs_cierre_validar').text(data.cierre.hora_fin);
        $('.f_validar').text(data.cierre.fecha);
        $('#anticipos_validar').val(data.cierre.total_anticipos_c);
        $('#total_cierre_validar').val(data.cierre.total_pesos_fichas_c);
      }

      if(data.detalles_join.length > 0){

        for (var i = 0; i < data.detalles_join.length; i++) {

          var fila= $(document.createElement('tr'));

          fila.attr('id', data.detalles_join[i].id_ficha);


          //pregunto si hay detalle_cierre cargado
          if(data.detalles_join[i].id_detalle_cierre != null && data.detalles_join[i].monto_ficha!= null){
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_id_ficha').addClass('cierre').text(data.detalles_join[i].valor_ficha).css('font-weight','bold'))
                  .append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_cierre').addClass('cierre').text(data.detalles_join[i].monto_ficha).css('font-weight','bold'));

            }else{
                fila.append($('<td>')
                    .addClass('col-xs-3').addClass('v_id_ficha').addClass('cierre').text(data.detalles_join[i].valor_ficha).css('font-weight','bold'))
                    .append($('<td>')
                    .addClass('col-xs-3').addClass('v_monto_cierre').addClass('cierre').text('0').css('font-weight','bold'))

              }

        //  pregunto si hay apertura cargada
            if(data.detalles_join[i].id_detalle_apertura != null && data.detalles_join[i].monto_ficha_apertura != null){

              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_apertura').text(data.detalles_join[i].monto_ficha_apertura).css('font-weight','bold'))

            }
            else {
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_apertura').text('0').css('font-weight','bold').prop('readonly',true))

            }

            //agrego icono comparando valores
            var monto_apertura = fila.find('.v_monto_apertura').text();
            var monto_cierre = fila.find('.v_monto_cierre').text();

            if(monto_cierre == monto_apertura){
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('.iconoValidacion')
                  .append($('<i>').addClass('fa fa-fw fa-check').css('color', '#66BB6A')));
            }else {
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('.iconoValidacion')
                  .append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F')));
            }

            $('#tablaValidar #validarFichas').append(fila);
          }
        }
    })
  }


});
//cuando cambia la fecha
$(document).on('change', '#fechaCierreVal', function(e) {

  e.preventDefault();

  var t=$('#fechaCierreVal').val();

  if(t==0){
    $('#validar').hide();
  }

  $('#tablaValidar tbody tr').remove();
  $('#div_cierre').hide();
  $('#anticipos_validar').val('-');
  $('#total_cierre_validar').val('-');

});

//botón validar dentro del modal
$(document).on('click', '#validar', function(e) {
  e.preventDefault();

  var id_apertura = $(this).val();

    var formData= {
      id_cierre:$('#fechaCierreVal').val(),
      id_apertura:id_apertura,
      observaciones: $('#obsValidacion').val(),
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'aperturas/validarApertura',
        data: formData,
        dataType: 'json',

        success: function (data){

          $('#modalValidarApertura').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Apertura Validada correctamente. ');
          $('#mensajeExito').show();
          $('#btn-buscarCyA').trigger('click');
        },
        error: function(data){

           var response = data.responseJSON.errors;

           if(typeof response.id_cierre !== 'undefined'){
             $('#mensajeErrorValApertura').show();
           }
          // if(typeof response.hora_fin !== 'undefined'){
          //   mostrarErrorValidacion($('#hs_cierre_cierre'),response.hora_fin[0],false);
          // }
        },
    })

});


//si es superusuario puede eliminarCyA
$(document).on('click','.eliminarCyA',function(e){

  var tipo = $(this).attr('data-tipo');
   var id=$(this).val();

  $('#cuerpoTablaCyA').find('#' + id).remove();

  if(tipo=='apertura'){
     $.get('aperturas/bajaApertura/' + id, function(data){
       $('#mensajeExito h3').text('ÉXITO');
       $('#mensajeExito p').text(' ');
       $('#mensajeExito').show();
     })
  }

  if(tipo=='cierre'){
       $.get('cierres/bajaCierre/' + id, function(data){
         $('#mensajeExito h3').text('ÉXITO');
         $('#mensajeExito p').text(' ');
          $('#mensajeExito').show();
       })
}
});

//dentro del modal de cargar cierres, para agregar la mesa al listado
function clickAgregarMesaCierre(e) {
  var id_mesa_panio = $('#inputMesaCierre').attr('data-elemento-seleccionado');


     $.get('http://' + window.location.host +"/mesas/detalleMesa/" + id_mesa_panio, function(data) {

       var fila= $(document.createElement('tr'));
       fila.attr('id', data.mesa.id_mesa_de_panio)
           .append($('<td>')
           .addClass('col-xs-4')
           .text(data.mesa.nro_mesa).css('border-right','2px solid #ccc')
         )
         .append($('<td>')
         .addClass('col-xs-2')
         .append($('<span>').text(' '))
         .append($('<button>')
         .addClass('cargarDatos').attr('data-id',data.mesa.id_mesa_de_panio).attr('data-cargado',false)
             .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-eye')
           )))
           .append($('<td>')
           .addClass('col-xs-2')
           .append($('<span>').text(' '))
           .append($('<button>')
           .addClass('btn_borrar_mesa').append($('<i>')
           .addClass('fas').addClass('fa-fw').addClass('fa-trash')
             )))

      $('#inputMesaCierre').setearElementoSeleccionado(0 , "");
      $('#listaMesasCierres tbody').append(fila);
      $('.listMes').show();


    });

}


//dentro del modal de cargar aperturas, para agregar la mesa al listado
function clickAgregarMesa(e) {
  var id_mesa_panio = $('#inputMesaApertura').attr('data-elemento-seleccionado');


     $.get('http://' + window.location.host +"/mesas/detalleMesa/" + id_mesa_panio, function(data) {

       var fila= $(document.createElement('tr'));
       fila.attr('id', data.mesa.id_mesa_de_panio)
           .append($('<td>')
           .addClass('col-xs-4')
           .text(data.mesa.nro_mesa).css('border-right','2px solid #ccc')
         ).append($('<td>')
         .addClass('col-xs-4')
         .text(data.juego.nombre_juego))
         .append($('<td>')
         .addClass('col-xs-2')
         .append($('<span>').text(' '))
         .append($('<button>')
         .addClass('btn_ver_mesa').attr('data-id',data.mesa.id_mesa_de_panio).attr('data-cargado',false)
             .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-eye')
           )))
           .append($('<td>')
           .addClass('col-xs-2')
           .append($('<span>').text(' '))
           .append($('<button>')
           .addClass('btn_borrar_mesa').append($('<i>')
           .addClass('fas').addClass('fa-fw').addClass('fa-trash')
             )))

         $('#bodyMesas').append(fila);
      $('#inputMesaApertura').setearElementoSeleccionado(0 , "");


    });

}

//fc que generan la fila del listado principal:
function generarFilaAperturas(data){
    if(data.hora != null){
      var piecesi = data.hora.split(':')
      var houri, minutei;

      if(piecesi.length === 3) {
        houri = piecesi[0];
        minutei = piecesi[1];
      }
    }else{
      houri = '-';
      minutei = '-';
    }

    var fila = $('#moldeFilaCyA').clone();
    fila.removeAttr('id');
    fila.attr('id', data.id_apertura_mesa);

    fila.find('.L_fecha').text(data.fecha);
    fila.find('.L_juego').text(data.nombre_juego);
    fila.find('.L_mesa').text(data.nro_mesa);
    fila.find('.L_hora').text( houri +':'+minutei);
    fila.find('.L_moneda').text(data.siglas_moneda);
    fila.find('.L_casino').text(data.nombre);
    if(data.id_estado_cierre == 3){
      fila.find('.L_estado').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('text-align','center'));
    }else{
        fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('text-align','center'));
    }


    fila.find('.infoCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.modificarCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.validarCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_apertura_mesa);
    if(data.id_estado_cierre == 3){
      fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    }
    fila.css('display', '');

    return fila;

}


function generarFilaCierres(data){

    if(data.hora_inicio != null){
      var piecesi = data.hora_inicio.split(':')
      var houri, minutei;

      if(piecesi.length === 3) {
        houri = piecesi[0];
        minutei = piecesi[1];
      }
        if (data.hora_fin != null) {
          var piecesf= data.hora_fin.split(':')
          var hourf, minutef;

          if(piecesf.length === 3) {
            hourf = piecesf[0];
            minutef = piecesf[1];
          }

        } else {
            hourf = '-';
            minutef = '-';
        }

    }else{
      houri = '-';
      minutei = '-';
    }

    var fila = $('#moldeFilaCyA').clone();
    fila.removeAttr('id');
    fila.attr('id', data.id_cierre_mesa);

    fila.find('.L_fecha').text(data.fecha);
    fila.find('.L_juego').text(data.nombre_juego);
    fila.find('.L_mesa').text(data.nro_mesa);
    fila.find('.L_hora').text( houri +':'+minutei + '-'+ hourf +':'+minutef);
    fila.find('.L_moneda').text(data.siglas_moneda);
    fila.find('.L_casino').text(data.nombre);

    fila.find('.L_estado').hide();



    //attr=data-tipo sirve para luego determinar qué get o post realizar
    //cuando se presionan, ya que se usa un mismo molde

    fila.find('.infoCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);
    fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);

    fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);
    fila.css('display', '');

    if(data.id_estado_cierre == 3){
      fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    }
  return fila;

}
function limpiarCargaCierre(){

  $('#juegoCierre').setearElementoSeleccionado('0',"");
  $('#totalCierre').val('');
  $('#totalAnticipoCierre').val('');
  $('#bodyFichasCierre tr').remove();
  $('#horarioCie').val('');
  $('#horario_ini_c').val('');
  $('#id_mesa_panio').val('');

}

function limpiarCargaApertura(){

  $('#id_mesa_ap').setearElementoSeleccionado('0',"");
  $('#totalApertura').val('');
  $('#horarioAp').val('');
  $('#fiscalizApertura').setearElementoSeleccionado(0,"");
  $('#cargador').val('');
  $('#tablaCargaApertura tbody tr').remove();
  $('#mensajeExitoCargaAp').hide();

}

function limpiarModalValidar(){
  //$('#validarFichas tr').not('moldeValidar').remove();
  $('.nro_validar').text(' ');
  $('.j_validar_aper').text(' ');
  $('.j_validar').text(' ');
  $('.cas_validar').text(' ');
  $('.hs_inicio_validar').text(' ');
  $('.hs_cierre_validar').text(' ');
  $('.f_validar').text(' ');
  $('.hs_validar_aper').text(' ');
  $('.fis_validar_aper').text(' ');
  $('.car_validar_aper').text(' ');
  $('.tipo_validar_aper').text(' ');
  $('.mon_validar_aper').text(' ');
  $('#total_cierre_validar').val('');
  $('#total_aper_validar').val('');
  $('#anticipos_validar').val('');
  $('#fechaCierreVal option').not('.defecto').remove();
  $('#tablaValidar tbody tr').remove();

}

function Numeros(string){//Solo numeros
    var out = '';
    var filtro = '1234567890.,';//Caracteres validos

    //Recorrer el texto y verificar si el caracter se encuentra en la lista de validos
    for (var i=0; i<string.length; i++)
       if (filtro.indexOf(string.charAt(i)) != -1)
             //Se añaden a la salida los caracteres validos
	     out += string.charAt(i);

    //Retornar valor filtrado
    return out;
}
