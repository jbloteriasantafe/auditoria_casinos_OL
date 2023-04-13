$(document).ready(function(){
  const ddmmyyhhiiss_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    startView: 2,
    ignoreReadonly: true,
    format: 'yyyy-mm-dd hh:ii:ss',
    minView: 0,
  };
  const ddmmyy_dtp = {
    ...ddmmyyhhiiss_dtp,
    format: 'yyyy-mm-dd',
    minView: 2,
  };

  $('#dtpFechaSistema').datetimepicker(ddmmyyhhiiss_dtp);
  $('#dtpFechaImportacionEstados').datetimepicker(ddmmyy_dtp);
  $('.tituloSeccionPantalla').text('Estado de Juegos');
  $('#btn-buscar').trigger('click');
});

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden,async=true) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  let size = 10;
  //Fix error cuando librería saca los selectores
  if (!isNaN($('#herramientasPaginacion').getPageSize())) {
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaJuegos .activa').attr('value'), orden: $('#tablaJuegos .activa').attr('estado') };
  if (sort_by == null) { // limpio las columnas
    $('#tablaJuegos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
  }

  const formData = {
    plataforma:  $('#buscadorPlataforma').val(),
    codigo:      $('#buscadorCodigo').val(),
    nombre:      $('#buscadorNombre').val(),
    categoria:   $('#buscadorCategoria').val(),
    tecnologia:  $('#buscadorTecnologia').val(),
    estado:      $('#buscadorEstado').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };

  $.ajax({
    type: 'GET',
    url: 'informeEstadoJuegos/buscarJuegos',
    data: formData,
    async: async,
    dataType: 'json',
    success: function(resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);

      $('#tablaJuegos tbody').empty();
      for (let i = 0; i < resultados.data.length; i++) {
        const fila = $('#moldeTablaJuegos').clone().removeAttr('id');
        $('#tablaJuegos tbody').append(llenarFila(fila,resultados.data[i]));
      }

      $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);
    },
    error: function(data) {
      console.log('Error:', data);
    }
  });
});

//Paginacion
$(document).on('click', '#tablaJuegos thead tr th[value]', function(e) {
  $('#tablaJuegos th').removeClass('activa');
  if ($(this).children('i').hasClass('fa-sort')) {
    $(this).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(this).children('i').hasClass('fa-sort-down')) {
      $(this).children('i').removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(this).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaJuegos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam,async = true) {
  if (e != null) {
      e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaJuegos .activa').attr('value');
  const orden = $('#tablaJuegos .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden,async]);
}

function llenarFila(fila,jugador){
  const convertir_fecha = function(fecha){
    if(fecha == null || fecha.length == 0) return '-';
    yyyymmdd = fecha.split('-');
    return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
  }
  console.log(jugador);
  fila.find('.fecha_importacion').text(convertir_fecha(jugador.fecha_importacion)).attr('title',jugador.fecha_importacion);
  fila.find('.plataforma').text(jugador.plataforma);
  fila.find('.codigo').text(jugador.codigo).attr('title',jugador.codigo);
  fila.find('.nombre').text(jugador.nombre).attr('title',jugador.nombre);
  fila.find('.categoria').text(jugador.categoria).attr('title',jugador.categoria);
  fila.find('.tecnologia').text(jugador.tecnologia).attr('title',jugador.tecnologia);
  fila.find('.estado').text(jugador.estado).attr('title',jugador.estado);
  fila.find('button').val(jugador.id_estado_juego_importado);
  return fila;
}

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

function obtenerVal(obj){
  if(obj.is('[rango]')){
    const filtroD = $(obj.attr('data-busq')+'D');
    const filtroH = $(obj.attr('data-busq')+'H');
    const rango = filtroD.val() +' - '+ filtroH.val();
    return rango.trim() == '-'? '' : rango;
  }
  else if(obj.is('[fecha]')){
    const filtroD = $(obj.attr('data-busq')+'D').find('input').first();
    const filtroH = $(obj.attr('data-busq')+'H').find('input').first();
    const rango = filtroD.val() +' - '+ filtroH.val();
    return rango.trim() == '-'? '' : rango;
  }
  else{
    const filtro = $(obj.attr('data-busq'));
    if(filtro.is('select')){
      let valor = filtro.val();
      if(obj.is('[data-busq-attr]')){
        const opcion = filtro.find('option:selected');
        const atributo = obj.attr('data-busq-attr');
        if(opcion.is(`[${atributo}]`)){
          valor = opcion.attr(atributo);
        }
      }
      return valor;
    }
    else if(filtro.is('input')){
      return filtro.val();
    }
  }
}

function mostrarHistorial(id_estado_juego_importado,pagina){
  $('#modalHistorial').find('.prevPreview,.nextPreview').val(id_estado_juego_importado);

  const sort_by =  { columna: $('#modalHistorial .cuerpo .activa').attr('value'), orden: $('#modalHistorial .cuerpo .activa').attr('estado') };
  sort_by.columna = sort_by.columna ?? 'fecha_importacion';
  sort_by.orden   = sort_by.orden ?? 'desc';

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const formData = {
    id_estado_juego_importado: id_estado_juego_importado,
    page: pagina,
    page_size: 30,
    sort_by: sort_by,
  };
  $.ajax({
    type: "GET",
    url: '/informeEstadoJuegos/historial',
    data: formData,
    success: function (data) {
      $('#modalHistorial .cuerpo  tbody').empty();
      data.data.forEach((jugador,idx) => {
        const fila = $('#moldeCuerpoHistorial').clone().removeAttr('id');
        $('#modalHistorial .cuerpo tbody').append(llenarFila(fila,jugador));
      });
      const pages = Math.ceil(data.total / formData.page_size);
      $('#modalHistorial').find('.previewPage').val(pagina).data('old_val',pagina);
      $('#modalHistorial').find('.previewTotal').val(pages);
      $('#modalHistorial').find('.prevPreview').attr('disabled',pagina <= 1);
      $('#modalHistorial').find('.nextPreview').attr('disabled',pagina >= pages);
      $('#modalHistorial').modal('show');
    },
    error: function (data) {
      console.log(data);
    }
  });
}

$(document).on('click','.historia',function(){
  mostrarHistorial($(this).val(),1);
});

$(document).on('click','.prevPreview,.nextPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).closest('.paginado').find('.previewPage').val());
  const next = p + ($(this).hasClass('nextPreview')? 1 : -1);
  mostrarHistorial($(this).val(),next);
});

$(document).on('focusin','.previewPage',function(e){
  $(this).data('old_val',$(this).val());
});

$(document).on('change','.previewPage',function(e){
  const old   = parseInt($(this).data('old_val'));
  const val   = parseInt($(this).val());
  const total = parseInt($(this).parent().find('.previewTotal').val());
  if(val > total || val <= 0){
    $(this).val(old);
    return;
  }
  mostrarHistorial($('#modalHistorial').find('.prevPreview').val(),val);
});


$(document).on('click', '#modalHistorial .cuerpo tr th[value]', function(e) {
  $('#tablaJuegos th').removeClass('activa');
  if ($(this).children('i').hasClass('fa-sort')) {
    $(this).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(this).children('i').hasClass('fa-sort-down')) {
      $(this).children('i').removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(this).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaJuegos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  mostrarHistorial($('#modalHistorial').find('.prevPreview').val(),$('#modalHistorial').find('.previewPage').val());
});

$('#btn-informe-diferencias').click(function(e){
  e.preventDefault();
  reiniciarModalVerificarEstados();
  $('#modalVerificarEstados').modal('show');
});

$('#btn-verificarEstados').click(function(){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  let progress = 0;
  $('#animacionGenerando').show();
  const loading = setInterval(function(){
    const message = ['―','/','|','\\'];
    $('#animacionGenerando').text(message[progress]);
    progress = (progress + 1)%4;
  },100);

  const formData = new FormData();
  formData.append("id_plataforma",$('#plataformaVerificarEstado').val());
  formData.append("fecha_sistema",$('#fechaSistema').val());
  formData.append("cambio_fecha_sistema",$('#fechaSistema').data("cambio_fecha_sistema")? 1 : 0);
  formData.append("fecha_importacion",$('#fechaImportacionEstados').val());

  $.ajax({
    type: "POST",
    url: "/informeEstadoJuegos/generarDiferenciasEstadosJuegos",
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    responseType: "blob",
    success: function (data) {//https://stackoverflow.com/questions/2805330/opening-pdf-string-in-new-window-with-javascript
      clearInterval(loading);
      $('#animacionGenerando').empty().append('&nbsp;').hide();
      const byteCharacters = atob(data);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const file = new Blob([byteArray], { type: 'application/pdf;base64' });
      const fileURL = window.URL.createObjectURL(file);
      $('#resultado_diferencias').attr('href',fileURL);
      const codigo_plat = $('#plataformaVerificarEstado option').filter(function(){
        return $(this).val() == formData.get("id_plataforma");
      }).attr('data-codigo');
      const fechaSistema = formData.get("fecha_sistema").slice(0,10).split("-").map(function(val,idx){
        return idx == 0? val.slice(2) : val;
      }).join("") + formData.get("fecha_sistema").slice(11).split(":").join("");
      const fechaImportacion = formData.get("fecha_importacion").split("-").map(function(val,idx){
        return idx == 0? val.slice(2) : val;
      }).join("");
      $('#resultado_diferencias').attr('download',`Diferencias-Estados-${codigo_plat}-${fechaSistema}-${fechaImportacion}.pdf`);
      $('#resultado_diferencias').show();
      $('#resultado_diferencias_span').click();//El evento click sobre el <a> no hace nada
    },
    error: function (data) {
      console.log(data);
      clearInterval(loading);
      $('#animacionGenerando').text('ERROR');
      mensajeError((data?.responseJSON?.errors ?? {}).errores);
    }
  });
});

function reiniciarModalVerificarEstados(){
  $('#plataformaVerificarEstado').val("");
  $('#animacionGenerando').empty().append('&nbsp;').hide();
  $('#resultado_diferencias').attr('href','#').removeAttr('download').hide();
  const fecha = new Date();
  $('#dtpFechaSistema').data('datetimepicker').setDate(fecha);
  $('#fechaSistema').data('cambio_fecha_sistema',false);
  $('#dtpFechaImportacionEstados').data('datetimepicker').setDate(fecha);
}

$('#fechaSistema').change(function(){
  $(this).data('cambio_fecha_sistema',true);
});
