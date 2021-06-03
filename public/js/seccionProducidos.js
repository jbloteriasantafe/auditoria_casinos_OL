$(document).ready(function(){
  $('#barraJuegos').attr('aria-expanded','true');
  $('#juegos').removeClass();
  $('#juegos').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Producidos');
  $('#opcProducidos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcProducidos').addClass('opcionesSeleccionado');

  $('#dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#dtpFechaFin').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  
  $('#btn-buscar').trigger('click');
});

$('#btn-buscar').on('click' , function(e,pagina,page_size,columna,orden) {
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
    {columna: columna,orden: orden} : 
    {columna: $('#tablaImportacionesProducidos .activa').attr('value'),orden: $('#tablaImportacionesProducidos .activa').attr('estado')};

  if(sort_by == null){ // limpio las columnas
    $('#tablaImportacionesProducidos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  
  var busqueda = {
    id_plataforma : $('#selectPlataforma').val(),
    id_tipo_moneda : $('#selectMoneda').val(),
    fecha_inicio : $('#fecha_inicio').val(),
    fecha_fin : $('#fecha_fin').val() ,
    correcto : $('#selectCorrecto').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: 'GET',
      url: 'producidos/buscarProducidos',
      data: busqueda,
      dataType: 'json',
      success: function (data) {
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.total,clickIndice);
        $('#tablaImportacionesProducidos tbody').empty();
        for (var i = 0; i < data.data.length; i++) {
          agregarFilaTabla(data.data[i]);
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.total,clickIndice);
      },
      error: function (data) {
        console.log('ERROR');
        console.log(data);
      },
  });
})


$(document).on('click','#tablaImportacionesProducidos thead tr th[value]',function(e){
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
  $('#tablaImportacionesProducidos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

/***** FUNCIONES *****/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaImportacionesProducidos .activa').attr('value');
  const orden = $('#tablaImportacionesProducidos .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}


//mostrar popover
$(document).on('mouseenter','.popInfo',function(e){
    $(this).popover('show');
});

function mostrarDetalles(plataforma,fecha,moneda,detalles,tipo){
  $('#columnaDetalle').hide();
  $('#mensajeExito').modal('hide');
  $('#modalCargaProducidos .mensajeSalida span').hide();
  $('#descripcion_validacion').text(plataforma+' - '+fecha+' - $'+moneda);
  $('#cuerpoTabla').empty();

  let diferencias = 0;
  for (let i = 0; i < detalles.length; i++) {
    const d = detalles[i];
    const fila = generarFilaDetalle(d,tipo);
    diferencias+=d.diferencia;
    $('#cuerpoTabla').append(fila);
    $('#btn-salir-validado').hide();
    $('#btn-salir').show();
  }
  $('#detalles_con_diferencias').text(diferencias);
  $('#verSoloDiferencias').prop('checked',true).change();
  $('#modalCargaProducidos').modal('show');
}

//Lupa de mostrar el producido
$(document).on('click','.carga',function(e){
  e.preventDefault();
  const id_producido = $(this).val();
  const tr = $(this).closest('tr');
  const moneda = tr.find('.tipo_moneda').text();
  const fecha = tr.find('.fecha_producido').text();
  const plataforma = tr.find('.plataforma').text();
  $('#modalCargaProducidos .modal-title').text('Producidos');
  $.get('producidos/detallesProducido/' + id_producido, function(data){
    mostrarDetalles(plataforma,fecha,moneda,data.detalles,'juegos');
  });
});

$(document).on('click','.carga_jugadores',function(e){
  e.preventDefault();
  const id_producido_jugadores = $(this).val();
  const tr = $(this).closest('tr');
  const moneda = tr.find('.tipo_moneda').text();
  const fecha = tr.find('.fecha_producido').text();
  const plataforma = tr.find('.plataforma').text();
  $('#modalCargaProducidos .modal-title').text('Producidos de jugadores');
  $.get('producidos/detallesProducidoJugadores/' + id_producido_jugadores, function(data){
    mostrarDetalles(plataforma,fecha,moneda,data.detalles,'jugadores');
  });
});

$('#btn-salir-validado').on('click', function(e){
    $('#modalCargaProducidos').modal('hide');
    $('#btn-buscar').trigger('click');
})

$(document).on('click','.infoDetalle',function(e){//PRESIONA UN OJITO
  e.preventDefault();
  $('#cuerpoTabla tr .botones').css('background-color','#FFFFFF');
  $(this).parent().css('background-color', '#FFCC80');
  $('#modalCargaProducidos .mensajeFin').hide();
  const id_detalle = $(this).val();
  const tipo = $(this).data('tipo');
  let url = 'producidos/';
  if(tipo == 'juegos'){
    url += 'datosDetalle/';
    $('.datosJuego').show();
    $('.datosJugadores').hide();
  } 
  else if(tipo == 'jugadores'){
    url += 'datosDetalleJugadores/';
    $('.datosJuego').hide();
    $('.datosJugadores').show();
  }
  else{
    console.log('Tipo de detalle invalido '+id_detalle+' '+tipo);
    return;
  }
  $.get(url + id_detalle, function(data){
    const d = data.detalle;
    const diff = data.diferencias;
    $('#btn-guardar').data('id-detalle', id_detalle);
    $('#btn-finalizar').data('id',id_detalle);
    $('#columnaDetalle').show();
    const validar = function(diferencia){
      return diferencia? 'rgb(227,140,140)' : 'rgb(140,227,184)';
    };
    const pdev = function(a,p){
      return a == 0.0? '' : (100*p/a).toFixed(2);
    }
    $('#apuesta_efectivo').val(d.apuesta_efectivo);
    $('#apuesta_bono').val(d.apuesta_bono);
    $('#apuesta').val(d.apuesta).css('background-color',validar(diff.apuesta));
    $('#premio_efectivo').val(d.premio_efectivo);
    $('#efectivo_pdev').text(pdev(d.apuesta_efectivo,d.premio_efectivo));
    $('#premio_bono').val(d.premio_bono);
    $('#bono_pdev').text(pdev(d.apuesta_bono,d.premio_bono));
    $('#premio').val(d.premio).css('background-color',validar(diff.premio));
    $('#total_pdev').text(pdev(d.apuesta,d.premio));
    $('#beneficio_efectivo').val(d.beneficio_efectivo).css('background-color',validar(diff.beneficio_efectivo));
    $('#beneficio_bono').val(d.beneficio_bono).css('background-color',validar(diff.beneficio_bono));
    $('#beneficio').val(d.beneficio).css('background-color',validar(diff.beneficio));
    $('#categoria').val(d.categoria).css('background-color',validar(diff.categoria));
    const agrupados = d.jugadores? d.jugadores : 0 + d.juegos? d.juegos : 0;
    $('#agrupados').val(agrupados);
    const j = data.juego;
    const en_bd = j != null;
    const v_en_bd = validar(!en_bd);
    $('#en_bd').val('NO').css('background-color',v_en_bd);
    $('#nombre_juego').val('-');
    $('#categoria_juego').val('-');
    $('#moneda_juego').val('-').css('background-color','');
    $('#devolucion_juego').val('-');
    if(en_bd){
      $('#en_bd').val('SI')
      $('#nombre_juego').val(j.nombre_juego);
      $('#categoria').css('background-color',validar(diff.categoria));
      $('#categoria_juego').val(data.categoria);
      $('#moneda_juego').val($(`#selectMoneda option[value=${j.id_tipo_moneda}]`).text()).css('background-color',validar(diff.moneda));
      $('#devolucion_juego').val(j.porcentaje_devolucion);
    }
  });
}); 


function generarFilaDetalle(d,tipo){
  var fila=$('#filaClon').clone();
  fila.removeAttr('id');
  fila.find('.codigo').text(d.codigo);
  fila.find('button').val(d.id_detalle).data('tipo',tipo);
  if(d.diferencia) fila.find('i').removeClass('fa-eye').addClass('fa-exclamation');
  fila.css('display', 'block');
  return  fila;
}


//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
  window.open('producidos/generarPlanilla/' + $(this).val(),'_blank');
});
$(document).on('click','.planilla_jugadores',function(){
  window.open('producidos/generarPlanillaJugadores/' + $(this).val(),'_blank');
});

//función para generar el listado inicial
function agregarFilaTabla(producido){
  const clearNull = function(x){ x? x : 0.00; }
  const plat = $(`#selectPlataforma option[value=${producido.id_plataforma}]`).text();
  const moneda = $(`#selectMoneda option[value=${producido.id_tipo_moneda}]`).text();
  const tr = $('#moldeFilaTabla').clone().removeAttr('id');
  tr.find('.plataforma').text(plat);
  tr.find('.fecha_producido').text(producido.fecha);
  tr.find('.tipo_moneda').text(moneda);
  tr.find('.producido').text(producido.beneficio);
  tr.find('.producido_jugadores').text(producido.beneficio_jugadores? producido.beneficio_jugadores : "----")
  .css('color',producido.beneficio == producido.beneficio_jugadores? 'rgb(75, 230, 75)' : 'rgb(180, 75, 75)');
  tr.find('button').val(producido.id_producido);

  const warning = $('<i class="fa fa-fw fa-exclamation">').attr('style','color: #FFB74D !important;');
  if(producido.diferencias) tr.find('.carga i').after(warning.clone());

  if(producido.id_producido_jugadores) tr.find('.planilla_jugadores,.carga_jugadores').val(producido.id_producido_jugadores)
  else tr.find('.planilla_jugadores,.carga_jugadores').remove();

  
  $('#tablaImportacionesProducidos tbody').append(tr);
}


$(document).on('click', '#tablaImportacionesProducidos thead tr th[value]', function(e) {
  $('#tablaImportacionesProducidos th').removeClass('activa');
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
  $('#tablaImportacionesProducidos th:not(.activa) i')
      .removeClass().addClass('fa fa-sort')
      .parent().attr('estado', '');
  
  $('#btn-buscar').click();
});

$('#verSoloDiferencias').change(function(e){
  $('#cuerpoTabla i.fa-eye').closest('tr').toggle(!$(this).prop('checked'));
})