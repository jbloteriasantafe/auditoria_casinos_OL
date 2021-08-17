$(document).ready(function(){
  $('#barraJuegos').attr('aria-expanded','true');
  $('#juegos').removeClass();
  $('#juegos').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');
  
  $('.tituloSeccionPantalla').text('Beneficios');
  $('#opcBeneficios').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcBeneficios').addClass('opcionesSeleccionado');

  $('#mensajeExito').hide();

  $('#dtpFechaDesde').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    showClear: true,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3
  });

  $('#dtpFechaHasta').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3
  });

  $('#btn-buscar').trigger('click');
});

//Pop up de informacion de los iconos de acciones
$(document).on('mouseenter','.popInfo',function(e){
    $(this).popover('show');
});

//Popup para ajustara las diferencias de los beneficios
$(document).on('click','.pop',function(e){
    e.preventDefault();
    $(this).popover('show');
});

$(document).on('click','.cancelarAjuste',function(e){
  $('.pop').popover('hide');
});

//Filtro de búsqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ?  
    {columna: columna,orden: orden} : {columna: $('#tablaBeneficios .activa').attr('value'),orden: $('#tablaBeneficios .activa').attr('estado')} ;

  if(sort_by == null){ // limpio las columnas
    $('#tablaBeneficios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
      id_plataforma: $('#selectPlataformas').val(),
      fecha_desde: $('#fecha_desde').val(),
      fecha_hasta: $('#fecha_hasta').val(),
      id_tipo_moneda: $('#selectTipoMoneda').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'beneficios/buscarBeneficios',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTablaResultados tr').remove();
      for (let i = 0; i < resultados.data.length; i++) {
        const filaBeneficio = generarFilaTabla(resultados.data[i]);
        $('#cuerpoTablaResultados').append(filaBeneficio);
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
        console.log('Error:', data);
    }
  });
});

$(document).on('click','.ver',function(e){
  e.preventDefault();
  mostrarBeneficioMensual($(this).val(),'ver');;
});

$(document).on('click','.validar',function(e){
  e.preventDefault();
  mostrarBeneficioMensual($(this).val(),'validar');
});

function mostrarBeneficioMensual(id_beneficio_mensual,modo){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const fila = $('#beneficio'+id_beneficio_mensual);
  $('#plataformaModal').val(fila.find('.plataforma').text());
  $('#tipoMonedaModal').val(fila.find('.moneda').text());
  $('#anioModal').val(fila.find('.anio').text());
  $('#mesModal').val(fila.find('.mes').text());

  $.ajax({
    type: 'GET',
    url: 'beneficios/obtenerBeneficios/'+id_beneficio_mensual,
    dataType: 'json',
    success: function (data) {
      $('#tablaModal #cuerpoTabla tr').remove();
      for (let i = 0; i < data.length; i++) {
        const filaBeneficio = generarFilaModal(data[i]);
        $('#tablaModal #cuerpoTabla').append(filaBeneficio)
      }
      $('#textoExito').text('');
      if(modo == 'validar'){
        $('#modalBeneficioMensual .modal-title').text('VALIDAR BENEFICIOS');
        $('#modalBeneficioMensual .modal-header').css('background-color','#FFB74D');
        $('#btn-validar-si').hide();
        $('#btn-validar').show();
        $('#btn-validar').val(id_beneficio_mensual);
        $('#btn-validar-si').val(id_beneficio_mensual);
      }
      else{
        $('#modalBeneficioMensual .modal-title').text('BENEFICIOS');
        $('#modalBeneficioMensual .modal-header').css('background-color','#4FC3F7');
        $('#modalBeneficioMensual textarea').attr('disabled',true);
        $('#modalBeneficioMensual #cuerpoTabla button').not('.ver-producido').attr('disabled',true);
        $('#btn-validar-si').hide();
        $('#btn-validar').hide();
      }

      $('#modalBeneficioMensual').modal('show');
    },
    error: function (data) {
        console.log('Error:', data);
    }
  });
}



$(document).on('click','#tablaBeneficios thead tr th[value]',function(e){
  $('#tablaBeneficios th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaBeneficios th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

/***** FUNCIONES *****/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaBeneficios .activa').attr('value');
  const orden = $('#tablaBeneficios .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarContentAjuste(ajuste,id_beneficio){
  const formulario =  `<div align="right">
                        <input class="form-control valorAjuste" type="text" value="${ajuste}" placeholder="JACKPOT">
                        <br>
                        <button id="${id_beneficio}" class="btn btn-successAceptar ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>
                        <button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>
                      </div>`;
  return formulario;
}

function generarBotonAjuste(diferencia,id_beneficio){
  return $('<button>')
  .addClass('btn btn-success pop boton_ajuste')
  .attr('tabindex', 0)
  .attr('data-trigger','manual')
  .attr('data-toggle','popover')
  .attr('data-html','true')
  .attr('title','AJUSTE')
  .attr('data-content',generarContentAjuste(diferencia,id_beneficio))
  .attr('disabled',(diferencia == 0))
  .append($('<i>').addClass('fa fa-fw fa-wrench'));
}

//Generar las filas de los beneficios por cada día del mes para el modal
function generarFilaModal(beneficio){
  const fila = $('<tr>');
  fila.attr('id','id'+beneficio.id_beneficio)
    .append($('<td>').text(beneficio.fecha).addClass('fecha'))
    .append($('<td>').text(beneficio.beneficio_calculado).addClass('calculado'))
    .append($('<td>').text(beneficio.beneficio).addClass('importado'))
    .append($('<td>').text(beneficio.ajuste).addClass('ajuste'))
    .append($('<td>').text(beneficio.diferencia).addClass('diferencia'))
    .append($('<td>').append(generarBotonAjuste(beneficio.diferencia,beneficio.id_beneficio)))
    .append($('<td>').append($('<textarea>').addClass('form-control').css('resize','vertical').text(beneficio.observacion)))
    .append($('<td>').append($('<button>')
      .append($('<i>')
        .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-search')
      )
      .addClass('btn').addClass('btn-info').addClass('ver-producido')
      .attr('data-idProducido',beneficio.id_producido)
      .attr('disabled',!beneficio.id_producido)
      .attr('title','DETALLES PRODUCIDO')
    ));

    return fila;
}

$(document).on('click','.ver-producido',function(e){
  e.preventDefault();
  id_producido=$(this).attr('data-idProducido')
  console.log(id_producido);
  window.open('producidos/generarPlanilla/' + id_producido,'_blank');
});

//Generar las filas para la tabla de los beneficios mensuales
function generarFilaTabla(beneficio){
  const fila = $('<tr>');
  const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  fila.attr('id','beneficio' + beneficio.id_beneficio_mensual)
  .append($('<td>').addClass('col-xs-2 plataforma').text(beneficio.plataforma))
  .append($('<td>').addClass('col-xs-2 mes').text(meses[beneficio.mes - 1]))
  .append($('<td>').addClass('col-xs-1 anio').text(beneficio.anio))
  .append($('<td>').addClass('col-xs-2 moneda').text(beneficio.tipo_moneda))
  .append($('<td>').addClass('col-xs-3 diferencias').text(beneficio.diferencias_mes))

  const acciones = $('<td>');
  acciones.append($('<button>').addClass('btn btn-success ver')
    .attr('title','VER').append($('<i>').addClass('fa fa-fw fa-search-plus'))
  );
  if(beneficio.validado == 0){
    acciones.append($('<button>').addClass('btn btn-success validar')
      .attr('title','VALIDAR').append($('<i>').addClass('fa fa-fw fa-check'))
    );
  }
  acciones.append($('<button>').addClass('btn btn-info planilla')
    .attr('title','IMPRIMIR').append($('<i>').addClass('fa fa-fw fa-print'))
  );
  acciones.append($('<button>').addClass('btn btn-info informe_completo')
    .attr('title','INFORME COMPLETO').append('<div style="color: black;font-size: 95%">.csv</div>')
  );
  fila.append(acciones);
  fila.find('button').val(beneficio.id_beneficio_mensual);
  return fila;
}

$(document).on('click','.ajustar',function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  const formData = {
    id_beneficio: $(this).attr('id'),
    valor: $(this).parent().find('.valorAjuste').val().replace(/,/g,"."),
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/ajustarBeneficio',
      data: formData,
      dataType: 'json',
      success: function (data) {
        const fila = $('#id' + formData.id_beneficio);
        fila.find('.ajuste').text(data.ajuste.toFixed(2));
        const dif = data.diferencia.toFixed(2);
        fila.find('.diferencia').text(dif);
        fila.find('.boton_ajuste').popover('destroy');
        fila.find('.boton_ajuste').parent().empty().append(generarBotonAjuste(data.diferencia,formData.id_beneficio));
      },
      error: function (data) {
        console.log('Error:', data);
      }
    });
});

function mensajeError(errores) {
  $('#mensajeError .textoMensaje').empty();
  for (let i = 0; i < errores.length; i++) {
      $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
  }
  $('#mensajeError').hide();
  setTimeout(function() {
      $('#mensajeError').show();
  }, 250);
}

function validarBeneficios(id_beneficio_mensual,validar_beneficios_sin_producidos){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  let beneficios = [];
  $('#cuerpoTabla tr').each(function(){
    const b = {
      id_beneficio: $(this).attr('id').substring(2),
      observacion: $(this).find('textarea').val(),
    };
    beneficios.push(b);
  });
  $('#textoExito').text('');

  const formData = {
    id_beneficio_mensual: id_beneficio_mensual,
    beneficios: beneficios,
    validar_beneficios_sin_producidos: validar_beneficios_sin_producidos
  };

  ocultarErrorValidacion($('#modalBeneficioMensual textarea'));
  $.ajax({
    type: 'POST',
    url: 'beneficios/validarBeneficios',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#tablaModal #cuerpoTabla tr').remove();
      $('#modalBeneficioMensual').modal('hide');
      $('#mensajeExito').hide();
      $('#mensajeExito h3').text('BENEFICIOS validados');
      $('#mensajeExito p').text('Los beneficios fueron validados correctamente');
      $('#mensajeExito div').css('background-color','#6dc7be');
      $('#mensajeExito').show();
      $('#btn-buscar').trigger('click');
    },
    error: function (data) {
      console.log('Error:', data);
      $('#textoExito').text('');
      const keys = Object.keys(data.responseJSON);
      console.log(data.responseJSON);
      for(const kidx in keys){
        const k = keys[kidx];
        if(k == 'id_beneficio_mensual') continue;
        const error = data.responseJSON[k].join('\n');
        mostrarErrorValidacion($('#id'+k+' textarea'),error,false);
      }
      if(data.responseJSON.id_beneficio_mensual !== undefined){
        mensajeError(data.responseJSON.id_beneficio_mensual);
      }
      $('#btn-validar-si').show();
    }
  });
}

$(document).on('click','#btn-validar',function(e){
  e.preventDefault();
  validarBeneficios($(this).val(),1);
});

$(document).on('click','#btn-validar-si',function(e){
  e.preventDefault();
  validarBeneficios($(this).val(),0);
});

$(document).on('click','.planilla',function(){
  window.open('beneficios/generarPlanilla/' + $(this).val(),'_blank');
});

$(document).on('click','.informe_completo',function(){
  window.open('beneficios/informeCompleto/' + $(this).val(),'_blank');
});

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

$('#btn-cotizacion').on('click', function(e){
  e.preventDefault();
  //limpio modal
  $('#labelCotizacion').html("");
  $('#labelCotizacion').attr("data-fecha","");
  $('#valorCotizacion').val("");
  //inicio calendario
  $('#calendarioInicioBeneficio').fullCalendar({  // assign calendar
    locale: 'es',
    backgroundColor: "#f00",
    eventTextColor:'yellow',
    editable: false,
    selectable: true,
    allDaySlot: false,
    selectAllow:false,

    customButtons: {
      nextCustom: {
        text: 'Siguiente',
        click: function() {
          cambioMes('next');
        }
      },
      prevCustom: {
        text: 'Anterior',
        click: function() {
          cambioMes('prev');
        }
      },
    },
    header: {
      left: 'prev,next',
      center: 'title',
      right: 'month',
    },
    events: function(start, end, timezone, callback) {
      $.ajax({
        url: 'cotizacion/obtenerCotizaciones/'+ start.format('YYYY-MM'),
        type:"GET",
        success: function(doc) {
          let events = [];
          $(doc).each(function() {
            let numero=""+$(this).attr('valor');
            events.push({
              title:"" + numero.replace(".", ","),
              start: $(this).attr('fecha')
            });
          });
          callback(events);
        }
      });
    },
    dayClick: function(date) {
      $('#labelCotizacion').html('Guardar cotización para el día '+ '<u>'  +date.format('DD/M/YYYY') + '</u>' );
      $('#labelCotizacion').attr("data-fecha",date.format('YYYY-MM-DD'));
      $('#valorCotizacion').val("");
      $('#valorCotizacion').focus();
    },
  });

  $('#modal-cotizacion').modal('show')

});

// guardar nueva cotizacion y recargar calendario
$('#guardarCotizacion').on('click',function(){
  const fecha = $('#labelCotizacion').attr('data-fecha');
  const valor = $('#valorCotizacion').val();
  const formData = {
    fecha: fecha,
    valor: valor,
  }
  $.ajax({
    type: 'POST',
    url: 'cotizacion/guardarCotizacion',
    data: formData,
    success: function (data) {
     $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
      //limpio modal
      $('#labelCotizacion').html("");
      $('#labelCotizacion').attr("data-fecha","");
      $('#valorCotizacion').val("");
    }
  });
});

function cambioMes(s){
  $('#calendarioInicioBeneficio').fullCalendar(s);
  $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
};
