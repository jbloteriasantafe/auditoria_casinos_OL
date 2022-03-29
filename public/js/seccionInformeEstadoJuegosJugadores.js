$(document).ready(function(){
  const ddmmyy_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  };

  $('#dtpFechaAutoexclusionD').datetimepicker(ddmmyy_dtp);
  $('#dtpFechaAutoexclusionH').datetimepicker(ddmmyy_dtp);
  $('#dtpFechaAltaD').datetimepicker(ddmmyy_dtp);
  $('#dtpFechaAltaH').datetimepicker(ddmmyy_dtp);
  $('#dtpFechaUltimoMovimientoD').datetimepicker(ddmmyy_dtp);
  $('#dtpFechaUltimoMovimientoH').datetimepicker(ddmmyy_dtp);
  $('#fechaImportacion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    startView: 2,
    minView: 2,
    pickerPosition: "top-right",
  });
  $('.tituloSeccionPantalla').text('Estado de Jugadores');
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
  const sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaJugadores .activa').attr('value'), orden: $('#tablaJugadores .activa').attr('estado') };
  if (sort_by == null) { // limpio las columnas
    $('#tablaJugadores th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
  }

  const iso = function(dtp){
    //getDate me retorna hoy si esta vacio, lo tengo que verificar
    if(dtp.find('input').val().length == 0) return "";
    const date = dtp.data("datetimepicker").getDate();
    const y = date.getFullYear();
    const m = date.getMonth()+1;
    const d = date.getDate();
    return y + (m<10?'-0':'-') + m + (d<10?'-0':'-') + d;
  }

  const formData = {
    plataforma:  $('#buscadorPlataforma').val(),
    codigo:      $('#buscadorCodigo').val(),
    estado:      $('#buscadorEstado').val(),
    edad_desde:  $('#buscadorRangoEtarioD').val(),
    edad_hasta:  $('#buscadorRangoEtarioH').val(),
    genero:      $('#buscadorSexo').val(),
    localidad:   $('#buscadorLocalidad').val(),
    provincia:   $('#buscadorProvincia').val(),
    fecha_autoexclusion_desde:     iso($('#dtpFechaAutoexclusionD')),
    fecha_autoexclusion_hasta:     iso($('#dtpFechaAutoexclusionH')),
    fecha_alta_desde:              iso($('#dtpFechaAltaD')),
    fecha_alta_hasta:              iso($('#dtpFechaAltaH')),
    fecha_ultimo_movimiento_desde: iso($('#dtpFechaUltimoMovimientoD')),
    fecha_ultimo_movimiento_hasta: iso($('#dtpFechaUltimoMovimientoH')),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };

  $.ajax({
    type: 'GET',
    url: 'informeEstadoJuegosJugadores/buscarJugadores',
    data: formData,
    async: async,
    dataType: 'json',
    success: function(resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);

      $('#tablaJugadores tbody').empty();
      for (let i = 0; i < resultados.data.length; i++) {
        const fila = $('#moldeTablaJugadores').clone().removeAttr('id');
        $('#tablaJugadores tbody').append(llenarFila(fila,resultados.data[i]));
      }

      $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);
    },
    error: function(data) {
      console.log('Error:', data);
    }
  });
});

//Paginacion
$(document).on('click', '#tablaJugadores thead tr th[value]', function(e) {
  $('#tablaJugadores th').removeClass('activa');
  if ($(this).children('i').hasClass('fa-sort')) {
    $(this).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(this).children('i').hasClass('fa-sort-down')) {
      $(this).children('i').removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(this).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaJugadores th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam,async = true) {
  if (e != null) {
      e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaJugadores .activa').attr('value');
  const orden = $('#tablaJugadores .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden,async]);
}

function llenarFila(fila,jugador){
  const convertir_fecha = function(fecha){
    if(fecha == null || fecha.length == 0) return '-';
    yyyymmdd = fecha.split('-');
    return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
  }
  const convertir_fechahora = function(fechahora){
    if(fechahora == null || fechahora.length == 0) return '-';
    const aux = fechahora.split(' ');
    return convertir_fecha(aux[0])+' '+aux[1];
  }
  fila.find('.fecha_importacion').text(convertir_fecha(jugador.fecha_importacion)).attr('title',jugador.fecha_importacion);
  fila.find('.plataforma').text(jugador.plataforma);
  fila.find('.codigo').text(jugador.codigo).attr('title',jugador.codigo);
  fila.find('.estado').text(jugador.estado).attr('title',jugador.estado);
  fila.find('.fecha_nacimiento').text(convertir_fecha(jugador.fecha_nacimiento)).attr('title',jugador.fecha_nacimiento);
  fila.find('.sexo').text(jugador.sexo).attr('title',jugador.sexo);
  fila.find('.localidad').text(jugador.localidad).attr('title',jugador.localidad);
  fila.find('.provincia').text(jugador.provincia).attr('title',jugador.provincia);
  fila.find('.fecha_autoexclusion').text(convertir_fechahora(jugador.fecha_autoexclusion)).attr('title',jugador.fecha_autoexclusion);
  fila.find('.fecha_alta').text(convertir_fechahora(jugador.fecha_alta)).attr('title',jugador.fecha_alta);
  fila.find('.fecha_ultimo_movimiento').text(convertir_fecha(jugador.fecha_ultimo_movimiento)).attr('title',jugador.fecha_ultimo_movimiento);
  fila.find('button').val(jugador.id_estado_jugador);
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

$('#agregarCSV').click(function(){
  //Realizo una busqueda sincronica para no agregar mal si esta escrito un filtro pero no hizo click en buscar.
  clickIndice(null,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize(),false);

  const fila = $('#tablaCSV tbody .filaTablaCSV').clone().removeClass('filaTablaCSV').css('display','');
  fila.find('.padding').css('display','none');
  fila.dblclick(function(){$(this).remove();exportarCSV();});
  
  fila.find('td').each(function(){
    if($(this).hasClass('cant')) return;//para la cantidad es un caso especial
    const val  = obtenerVal($(this));
    const nbsp = val.length == 0? '\xa0' : val;
    $(this).text(nbsp).attr('title',nbsp);
  });

  const cant = $('#herramientasPaginacion h4').text().split(' ')[6];//@HACK
  const val = cant == null? '0' : cant;
  fila.find('.cant').text(val).attr('title',val);

  fila.find('td').filter(function () { return $(this).text() == '\xa0';}).css('background','rgba(0,0,0,0.1)');
  $('#tablaCSV tbody').append(fila);
  
  exportarCSV()
});

$('#limpiarCSV').click(function(e){
  $('#tablaCSV tbody tr').not('.filaTablaCSV').remove();
  exportarCSV();
});

$('#columnasCSV').change(function(){
  exportarCSV();
});

$('#importarCSV').click(function(){
  $('#importarCSVinput').click();
});

$('#importarCSVinput').change(function(){
  const archivos = $('#importarCSVinput')[0].files;
  if(archivos.length == 0) return;
  const csv = archivos[0];
  const reader = new FileReader();
  reader.onload = function(){
    importarCSV(reader.result);
  }
  reader.readAsText(csv);
});

function exportarCSV(){
  const vacio = function(s){
    const trim = s.trim();
    return trim == '' || trim == '-';
  }
  const filas = [];
  const borrar = $('#columnasCSV').is(':checked');
  const borrar_col = [];
  const cabezera = [];
  $('#tablaCSV thead tr th').each(function(idx,val){
    cabezera.push($(val).text());
    borrar_col.push(borrar);
  });
  filas.push(cabezera);

  $('#tablaCSV tbody tr').not('.filaTablaCSV').each(function(rowidx,val){
    const f = [];
    $(val).find('td').each(function(colidx,val2){
      const t = $(val2).text();
      borrar_col[colidx] = borrar_col[colidx] && vacio(t);
      f.push(t);
    });
    filas.push(f);
  });

  transformadas = [];
  for(const f in filas){
    const sin_cols_innecesarias = filas[f].filter(function(elem,idx){
      return !borrar_col[idx];
    });
    const vaciado = sin_cols_innecesarias.map(elem => vacio(elem)? '' : ('"'+elem+'"'))
    transformadas.push(vaciado);
  }

  let csv = "";
  transformadas.forEach(function(f){
    f.join(',');
    csv += f + '\n';
  });

  const a = document.getElementById("descargarCSV");
  const file = new Blob([csv], {type: 'text/csv'});
  a.href = URL.createObjectURL(file);
  const date = new Date();
  let date_s = date.getFullYear();
  {
    const mes = date.getMonth()+1;
    const dia = date.getDate();
    date_s += (mes < 10? '0' : '') + mes + (dia < 10? '0' : '') + dia;
    date_s += date.toTimeString().split(' ')[0].replaceAll(':','');//Le saco el timezone y los dos puntos al tiempo
  }
  a.download = 'informeAE-'+date_s+'.csv';
  mostrarColumnas(borrar_col);
}

function mostrarColumnas(hidecols){
  $('#tablaCSV thead tr th').each(function(idx,elem){
    $(elem).css('display',hidecols[idx]? 'none' : '');
  });
  $('#tablaCSV tbody tr').not('.filaTablaCSV').each(function(){
    $(this).find('td').each(function(idx,elem){
      $(elem).css('display',hidecols[idx]? 'none' : '');
    })
  });
}

const to_iso = function(s){
  const ddmmyy = s.split('/');
  if(ddmmyy.length < 3) return null;
  //@HACK timezone de Argentina, supongo que esta bien porque el servidor esta en ARG
  return '20'+ddmmyy[2]+'-'+ddmmyy[1]+'-'+ddmmyy[0]+'T00:00:00.000-03:00';
}

function importarCSV(s){
  $('#limpiarCSV').click();
  s = s.replace(/\r\n/g,'\n');//Saco el retorno de linea de Windows
  let lines = s.split('\n');
  if(lines.length == 0) return;
  const colnames = lines[0].split(',');
  const tablecols = $('#tablaCSV thead tr');
  const colidxs = {};
  // Nota: Las columnas pueden faltar por la opcion de remover columnas, por eso
  // es necesario este paso
  for(const idx in colnames){// Saco cual es el numero de la columna
    const col = colnames[idx].replace(/"/g,'');//Le saco comillas
    const th = tablecols.find('th:contains('+col+')');
    if(th.length == 0) continue;//No existe columna con ese nombre
    colidxs[idx] = {
      filtro: th.attr('data-busq'),attr: th.attr('data-busq-attr'),es_fecha:  th.is('[fecha]'),es_rango: th.is('[rango]')
    };
  }
  lines  = lines.slice(1);
  if(lines.length == 0) return;
  //NOTA: esto tal vez termino siendo artificialmente generico, capaz era mejor hardcodear cada opcion en un switch
  for(const lineidx in lines){
    if(lines[lineidx].length == 0) continue;
    const cols = lines[lineidx].split(',');
    limpiarFiltros();
    for(const colidx in cols){
      if(!colidxs.hasOwnProperty(colidx)) continue;
      const aux = colidxs[colidx];
      const text = cols[colidx].replace(/"/g,'');
      if(aux.es_fecha){
        const fechas = text.split('-');
        const desde = to_iso(fechas[0]? fechas[0].replace(/ /g,'') : '');
        const hasta = to_iso(fechas[1]? fechas[1].replace(/ /g,'') : '');
        const dtpD = $(aux.filtro+'D');
        const dtpH = $(aux.filtro+'H');
        if(desde != null) dtpD.data("datetimepicker").setDate(new Date(desde));
        if(hasta != null) dtpH.data("datetimepicker").setDate(new Date(hasta));
      }
      else if(aux.es_rango){
        const vals = text.split('-');
        cargarVal($(aux.filtro+'D'),aux.attr,vals[0]? vals[0] : '');
        cargarVal($(aux.filtro+'H'),aux.attr,vals[1]? vals[1] : '');
      }
      else{
        cargarVal($(aux.filtro),aux.attr,text);
      }
    }
    $('#agregarCSV').click();
  }
}

function cargarVal(dom,attr,text){
  if(dom.is('select')){
    const selval = dom.find('option').filter(function () { //Busco el val del option para setearlo
        const seltext = (attr)? $(this).attr(attr) : $(this).text();
        return seltext == text; 
    }).val();
    dom.val(selval);
  }
  else if(dom.is('input')){
    dom.val(text);
  }
}

function limpiarFiltros(){
  $('#collapseFiltros input').val('');
  $('#collapseFiltros select').val('');
  $('#collapseFiltros .no_contesta').prop('checked',true).change().prop('checked',false).change();
}

function mostrarHistorial(id_estado_jugador,pagina){
  $('#modalHistorial').find('.prevPreview,.nextPreview').val(id_estado_jugador);

  const sort_by =  { columna: $('#modalHistorial .cuerpo .activa').attr('value'), orden: $('#modalHistorial .cuerpo .activa').attr('estado') };
  sort_by.columna = sort_by.columna ?? 'fecha_importacion';
  sort_by.orden   = sort_by.orden ?? 'desc';

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const formData = {
    id_estado_jugador: id_estado_jugador,
    page: pagina,
    page_size: 30,
    sort_by: sort_by,
  };
  $.ajax({
    type: "GET",
    url: '/informeEstadoJuegosJugadores/historial',
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
  $('#tablaJugadores th').removeClass('activa');
  if ($(this).children('i').hasClass('fa-sort')) {
    $(this).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(this).children('i').hasClass('fa-sort-down')) {
      $(this).children('i').removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(this).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaJugadores th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  mostrarHistorial($('#modalHistorial').find('.prevPreview').val(),$('#modalHistorial').find('.previewPage').val());
});

function reiniciarModalImportar(){
  ocultarErrorValidacion($('#modalImportacion').find('input,select'));
  $('#modalImportacion').find('.modal-footer').children().show();
  $('#mensajeExito').hide();
  //Mostrar: rowArchivo
  $('#modalImportacion').find('#datosImportacion,#mensajeError,#mensajeInvalido,#iconoCarga,#btn-guardarImportacion').hide();
  $('#modalImportacion #fechaImportacion').data('datetimepicker').reset();
  $('#modalImportacion').find('#plataformaImportacion,.hashCalculado,.hashRecibido').val("");
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacion #archivo')[0].files[0] = null;
  $('#modalImportacion #archivo').attr('data-borrado','false');
  $("#modalImportacion #archivo").fileinput('destroy').fileinput({
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
  $('#animacionImportando').hide();
}

$('#btn-importar-jugadores').click(function(e){
  e.preventDefault();
  reiniciarModalImportar();
  $('#modalImportacion').modal('show');
});

//Eventos de la librería del input
$('#modalImportacion #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacion #datosProducido').hide();
  $('#modalImportacion #mensajeInvalido').show();
  $('#modalImportacion #mensajeInvalido p').text(msg);
  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
});

$('#modalImportacion #archivo').on('fileclear',reiniciarModalImportar);

$('#modalImportacion #archivo').on('fileselect', function(event) {
  $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
  let reader = new FileReader();
  reader.onload = procesarDatosJugadores;
  reader.readAsText($('#modalImportacion #archivo')[0].files[0]);
});

function procesarDatosJugadores(e) {
  const csv = e.target.result;
  const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(s => s.length > 0);

  if(allTextLines.length <= 0 || allTextLines[0].split(';').length != 9){
    $('#modalImportacion #mensajeInvalido p').text('El archivo es invalido');
    $('#modalImportacion #mensajeInvalido').show();
    $('#modalImportacion #iconoCarga').hide();
    $('#btn-guardarImportacion').hide();
    return;
  }

  $('#modalImportacion').find('#btn-guardarImportacion,#datosImportacion').show();
  $('#modalImportacion #mensajeInvalido').hide();
}

$('#btn-guardarImportacion').on('click', function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  const formData = new FormData();
  formData.append('id_plataforma', $('#plataformaImportacion').val());
  formData.append('fecha', $('#fechaImportacion_hidden').val());
  formData.append('md5',$('#modalImportacion').find('.hashCalculado').val());
  
  //Si subió archivo lo guarda
  if($('#modalImportacion #archivo').attr('data-borrado') == 'false' && $('#modalImportacion #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacion #archivo')[0].files[0]);
  }
  ocultarErrorValidacion($('#modalImportacion').find('input,select'));

  let progress = 0;
  $('#animacionImportando').show();
  const loading = setInterval(function(){
      const message = ['―','/','|','\\'];
      $('#animacionImportando').text(message[progress]);
      progress = (progress + 1)%4;
  },100);

  $.ajax({
    type: "POST",
    url: 'informeEstadoJuegosJugadores/importarJugadores',
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    success: function (data) {
      clearInterval(loading);
      $('#animacionImportando').hide();
      $('#btn-buscar').click();
      $('#modalImportacion').modal('hide');
      $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN');
      $('#mensajeExito p').text('El archivo se importo correctamente');
      $('#mensajeExito').show();
    },
    error: function (data) {
      clearInterval(loading);
      $('#animacionImportando').hide();
      console.log(data);
      mensajeError('Error al subir el archivo');
      const response = data.responseJSON;
      if(typeof response['fecha'] !== 'undefined'){
        mostrarErrorValidacion($('#fechaImportacion input'),response['fecha'].join(', '),true);
      }
      if(typeof response['id_plataforma'] !== 'undefined'){
        mostrarErrorValidacion($('#plataformaImportacion'),response['id_plataforma'].join(', '),true);
      }
    }
  });
});

function mensajeError(msg){
  $('#mensajeError .textoMensaje').empty();
  $('#mensajeError .textoMensaje').append($('<h4>'+msg+'</h4>'));
  $('#mensajeError').modal('hide');
  setTimeout(function() {
    $('#mensajeError').modal('show')
  }, 100);
  setTimeout(function() {
    $('#mensajeError').modal('hide')
  }, 3000);
}
