$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Juegos de Software');
  const url = window.location.pathname.split("/");
  if(url.length >= 3) {
    let id = url[2]; 
    let fila_falsa = crearFilaJuego({id_juego : id}).hide();
    $('#cuerpoTabla').append(fila_falsa);
    fila_falsa.find('.detalle').trigger('click');
  }
  
  $('#btn-buscar').trigger('click');
});

//enter en modal
$('#contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
})

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const estado = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',estado? '0.1' : '0.5');
  $(this).data("minimizar",!estado);
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| JUEGOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

//Mostrar modal para agregar nuevo Juego
$('#btn-nuevo').click(function(e){
  e.preventDefault();
  $('#mensajeExito').hide();
  $('#modalJuego .modal-title').text(' | NUEVO JUEGO');
  $('#modalJuego .modal-header').attr('style','background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass('btn-warningModificar');
  $('#btn-guardar').addClass('btn-successAceptar');
  $('#btn-guardar').text('ACEPTAR');
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').css('display','inline-block');
  $('#boton-salir').text('CANCELAR');
  $('#selectLogJuego').empty();

  mostrarJuego({},[],[]);
  habilitarControles(true);
  
  $('#modalJuego').modal('show');
});

$('#selectLogJuego').change(function(e){
  const data = $(this).find('option:selected').data('data');
  mostrarJuego(data.juego,data.certificados,data.plataformas);
});

$(document).on('click','.detalle',function(){
  $('#modalJuego .modal-title').text('| VER MAS');
  $('#modalJuego .modal-header').attr('style','background-color: #4FC3F7; color: #FFF');
  $('#boton-cancelar').hide();
  $('#boton-salir').text('SALIR').show();
  $('#btn-guardar').val("historial").hide();
  $('#selectLogJuego').empty();
  habilitarControles(false);

  const id_juego = $(this).val();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "GET",
    url: "/juegos/obtenerLogs/" + id_juego,
    success: function (data) {
      for(const idx in data){
        const log = data[idx];
        const usuario = log?.usuario?.nombre ?? '';
        const option = $('<option>').text(log.juego.updated_at+' - '+usuario).data('data',log);
        $('#selectLogJuego').append(option);
      }
      $('#selectLogJuego').val($('#selectLogJuego option').first().val()).change();
      $('#modalJuego').modal('show');
    },
    error: function (data) {
      console.log(data);
    }
  });
});

$('.modal').on('hidden.bs.modal', function() {
  $('#btn-guardar').val('');
  $('#id_juego').val(0);
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
  //Modificar los colores del modal
  $('#modalJuego .modal-title').text('| MODIFICAR JUEGO');
  $('#modalJuego .modal-header').attr('style','background: #ff9d2d');
  $('#btn-guardar').val('modificar').show();
  const id_juego = $(this).val();
  $('#id_juego').val(id_juego);
  $('#selectLogJuego').empty();
  habilitarControles(true);
  $.get("/juegos/obtenerJuego/" + id_juego, function(data){
    console.log(data);
    mostrarJuego(data.juego,data.certificados,data.plataformas);
    $('#modalJuego').modal('show');
  });
});

$(document).on('click' , '.borrarCertificado' , function(){
  $(this).parent().parent().remove();
});

function obtenerIdCertificado(nro_archivo){
  const found = $('#datalistCertificados option:contains("'+nro_archivo+'")');
  let cert = null;
  for(let i = 0;i<found.length;i++){
    if(found[i].textContent == nro_archivo){
      cert = found[i].getAttribute('data-id');
      break;
    }
  }
  return cert;
}

$(document).on('click', '.verCertificado', function(){
  const input = $(this).parent().parent().find('.codigo');
  const val = input.val();
  const id = obtenerIdCertificado(val);
  if(id != null) window.open('/certificadoSoft/' + id,'_blank');
});

/* busqueda de usuarios */
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ //limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    id_plataforma: $('#buscadorPlataforma').val(),
    id_categoria_juego: $('#buscadorCategoria').val(),
    id_estado_juego: $('#buscadorEstado').val(),
    sistema: $('#buscadorSistema').val(),
    nombreJuego: $('#buscadorNombre').val(),
    cod_juego: $('#buscadorCodigoJuego').val(),
    certificado: $('#buscadorCodigo').val(),
    proveedor: $('#buscadorProveedor').val(),
    pdev_menor: $('#buscadorPdevMenor').val(),
    pdev_mayor: $('#buscadorPdevMayor').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
    type: "POST",
    url: '/juegos/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTabla tr').remove();
      for (var i = 0; i < resultados.data.length; i++) {
        $('#cuerpoTabla').append(crearFilaJuego(resultados.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

//Borrar Juego y remover de la tabla
$(document).on('click','.eliminar',function(){
  $('#btn-eliminarModal').val($(this).val());
  $('#mensajeEliminar').text('¿Seguro que desea eliminar el juego "' + $(this).parent().parent().find('.nombre_juego').text()+'"?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "DELETE",
    url: "/juegos/eliminarJuego/" + $(this).val(),
    success: function (data) {
      $('#btn-buscar').trigger('click');
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

function parseError(response){
  errors = {
    'validation.unique'       :'El valor tiene que ser único y ya existe el mismo.',
    'validation.required'     :'El campo es obligatorio.',
    'validation.max.string'   :'El valor es muy largo.',
    'validation.exists'       :'El valor no es valido.',
    'validation.min.numeric'  :'El valor no es valido.',
    'validation.integer'      :'El valor tiene que ser un número entero.',
    'validation.regex'        :'El valor no es valido.',
    'validation.required_if'  :'El valor es requerido.',
    'validation.required_with':'El valor es requerido.',
    'validation.before'       :'El valor supera el limite.',
    'validation.after'        :'El valor precede el limite.',
    'validation.max.numeric'  :'El valor supera el limite.',
    'validation.numeric'      : 'El valor tiene que ser numérico',
    'validation.between.numeric' : 'El valor no es valido',
  };
  if(response in errors) return errors[response];
  return response;
}

//Crear nuevo Juego / actualizar si existe
$('#btn-guardar').click(function (e) {
  $('#mensajeExito').hide();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  const state = $('#btn-guardar').val();

  const certificados = $('#listaSoft').children().map(function(){
    const texto = $(this).find('.codigo').val();
    const cert = $(this).find('.codigo').attr('data-id') ?? obtenerIdCertificado(texto);
    if(cert != null) return cert;
  }).toArray();

  const plataformas = $('#plataformas').find('.plataforma').map(function(){
    return {
      id_plataforma: $(this).attr('data-id'), 
      id_estado_juego: $(this).val()
    };
  }).toArray();

  const formData = {
    id_juego: state == "modificar"? $('#id_juego').val() : undefined,
    nombre_juego: $('#inputJuego').val(),
    cod_juego:$('#inputCodigoJuego').val(),
    id_categoria_juego: $('#selectCategoria').val(),
    certificados: certificados,
    denominacion_juego: $('#denominacion_juego').val(),
    porcentaje_devolucion:  $('#porcentaje_devolucion').val(),
    id_tipo_moneda:  $('#tipo_moneda').val(),
    motivo: $('#motivo').val(),
    escritorio: $('#escritorio').prop('checked') * 1,
    movil: $('#movil').prop('checked') * 1,
    codigo_operador: $('#inputCodigoOperador').val(),
    proveedor: $('#inputProveedor').val(),
    plataformas: plataformas,
  };

  $.ajax({
    type: "POST",
    url: state == "modificar"? '/juegos/modificarJuego' : '/juegos/guardarJuego',
    data: formData,
    dataType: 'json',
    success: function (data) {
      $('#btn-buscar').trigger('click');
      $('#modalJuego').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text(' ');
      $('#mensajeExito').show();
      //Lo agrego a la lista de sugerencias si no esta
      const ya_esta = $('#datalistProveedores option').filter(function(){return $(this).text() == formData.proveedor;}).length > 0;
      if(!ya_esta) $('#datalistProveedores').append($('<option>').text(formData.proveedor));
    },
    error: function (data) {
      console.log(data);
      const response = data.responseJSON;
      if(typeof response.nombre_juego !== 'undefined'){
        mostrarErrorValidacion($('#inputJuego'),parseError(response.nombre_juego),true);
      }
      if(typeof response.cod_juego !== 'undefined'){
        mostrarErrorValidacion($('#inputCodigoJuego'),parseError(response.cod_juego),true);
      }
      if(typeof response.denominacion_juego !== 'undefined'){
        mostrarErrorValidacion($('#denominacion_juego'),parseError(response.denominacion_juego),true);
      }
      if(typeof response.porcentaje_devolucion !== 'undefined'){
        mostrarErrorValidacion($('#porcentaje_devolucion'),parseError(response.porcentaje_devolucion),true);
      }
      if(typeof response.motivo !== 'undefined'){
        mostrarErrorValidacion($('#motivo'),parseError(response.motivo),true);
      }
      if(typeof response.id_tipo_moneda !== 'undefined'){
        mostrarErrorValidacion($('#tipo_moneda'),parseError(response.id_tipo_moneda),true);
      }
      if(typeof response.id_categoria_juego !== 'undefined'){
        mostrarErrorValidacion($('#selectCategoria'),parseError(response.id_categoria_juego),true);
      }
      if(typeof response.tipos !== 'undefined'){
        mostrarErrorValidacion($('#tipos'),parseError(response.tipos),true);
      }
      if(typeof response.plataformas !== 'undefined'){
        mostrarErrorValidacion($('#plataformas'),parseError(response.plataformas),true);
      }
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

/***********FUNCIONES****************/

function crearFilaJuego(juego){
  const fila          = $('#moldeFilaJuego').clone().removeAttr('id');
  const codigo        = juego.certificados ?? '-';
  const codigojuego   = juego.cod_juego ?? '-';
  const categoria_aux = $(`#buscadorCategoria option[value="${juego.id_categoria_juego}"]`);
  const categoria     = categoria_aux.length > 0? categoria_aux.text() : '-';
  const estado        = juego.estado.length > 0? juego.estado : '-';

  fila.find('.nombre_juego').text(juego.nombre_juego).attr('title',juego.nombre_juego);
  fila.find('.categoria').text(categoria).attr('title',categoria);
  fila.find('.estado').text(estado).attr('title',estado);
  fila.find('.codigo_juego').text(codigojuego).attr('title',codigojuego);
  fila.find('.codigo_certif').text(codigo).attr('title',codigo);
  fila.find('button').val(juego.id_juego);
  return fila;
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function habilitarControles(habilitado){
  $('#modalJuego input').prop('disabled',!habilitado);
  $('#modalJuego select').attr('disabled',!habilitado);
  $('.borrarFila').attr('disabled',!habilitado);
  $('#btn-agregarCertificado').attr('disabled',!habilitado);
  $('#modalJuego #motivo').prop('readonly',!habilitado);
  $('#moldeCertificado').find('input,button').attr('disabled',!habilitado)
  $('#moldeCertificado').find('.verCertificado').attr('disabled',false);
  //El select de historial se quiere habilitar cuando los demas esta deshabilitado
  $('#modalJuego #selectLogJuego').prop('disabled',habilitado).parent().css('visibility',habilitado? 'hidden' : 'visible');
}

function mostrarJuego(juego, certificados,plataformas){
  ocultarErrorValidacion($('#modalJuego input'));
  ocultarErrorValidacion($('#modalJuego select'));
  ocultarErrorValidacion($('#modalJuego #motivo'));
  ocultarErrorValidacion($('#modalJuego #tipos'));
  ocultarErrorValidacion($('#modalJuego #plataformas'));
  $('#inputJuego').val(juego.nombre_juego);
  $('#inputCodigoJuego').val(juego.cod_juego);
  $('#selectCategoria').val(juego.id_categoria_juego);
  $('#inputCodigoOperador').val(juego.codigo_operador);
  $('#inputProveedor').val(juego.proveedor);
  $('#motivo').val("");
  $('#escritorio').prop('checked',juego.escritorio == 1);
  $('#movil').prop('checked',juego.movil == 1);

  $('#listaSoft').empty();
  for (let i = 0; i < certificados.length; i++){
    $('#listaSoft').append(crearRenglonCertificado(certificados[i]))
  }

  $('.plataforma').val('');
  plataformas.forEach( p => {
    $(`.plataforma[data-id="${p.id_plataforma}"`).val(p.id_estado_juego);
  });

  $('#denominacion_juego').val(juego.denominacion_juego);
  $('#porcentaje_devolucion').val(juego.porcentaje_devolucion);
  $('#tipo_moneda').val(juego.id_tipo_moneda);
  $('#motivo').val(juego.motivo);
}

function crearRenglonCertificado(cert){
  const fila =  $('#moldeCertificado').clone().removeAttr('id')
  fila.find('.codigo').val(cert.nro_archivo).attr('data-id',cert.id_gli_soft);
  return fila;
}

$('#btn-agregarCertificado').click(function(){
  $('#listaSoft').append(crearRenglonCertificado({nro_archivo: ""}));
});

function mensajeError(errores,timeout = 3000) {
  $('#mensajeError .textoMensaje').empty();
  for (let i = 0; i < errores.length; i++) {
    $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
  }
  $('#mensajeError').modal('show');
  setTimeout(function() {
    $('#mensajeError').modal('hide');
  }, timeout);
}

/* INFORME DIFERENCIAS */
function reiniciarModalVerificarEstados(){
  $('#modalVerificarEstados #archivo')[0].files[0] = null;
  $('#modalVerificarEstados #archivo').attr('data-borrado','false');
  $("#modalVerificarEstados #archivo").fileinput('destroy').fileinput({
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
      'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
    },
    allowedFileExtensions: ['csv'],
  });
  $('#plataformaVerificarEstado').val("");
  $('#btn-verificarEstados').hide();
  $('#animacionGenerando').empty().append('&nbsp;').hide();
  $('#resultado_diferencias').attr('href','#').removeAttr('download').hide();
}

//Modal para generar informe de diferencias
$('#btn-informe-diferencias').click(function(e){
  e.preventDefault();
  reiniciarModalVerificarEstados();
  $('#modalVerificarEstados').modal('show');
});

$('#modalVerificarEstados #archivo').on('fileerror', function(event, data, msg) {
  mensajeError(["Error al cargar el archivo.",msg]);
  reiniciarModalVerificarEstados();
});

$('#modalVerificarEstados #archivo').on('fileclear', function(event) {
  reiniciarModalVerificarEstados();
});

$('#modalVerificarEstados #archivo').on('fileselect', function(event) {
  $('#btn-verificarEstados').show();
});

$('#btn-verificarEstados').click(function(){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  let formData = new FormData();
  formData.append('id_plataforma', $('#plataformaVerificarEstado').val());
  const codigo_plat = $('#plataformaVerificarEstado option:selected').attr('data-codigo');
  if($('#modalVerificarEstados #archivo').attr('data-borrado') == 'false' && $('#modalVerificarEstados #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalVerificarEstados #archivo')[0].files[0]);
  }

  let progress = 0;
  $('#animacionGenerando').show();
  const loading = setInterval(function(){
    const message = ['―','/','|','\\'];
    $('#animacionGenerando').text(message[progress]);
    progress = (progress + 1)%4;
  },100);

  $.ajax({
    type: "POST",
    url: "/juegos/generarDiferenciasEstadosJuegos",
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
      const ahora = new Date();
      const yyyy = ahora.getFullYear();
      const lPad = function(s){
        return s.length == 1? '0'+s : s;
      };
      const mm = lPad(ahora.getMonth()+1+'');//Lo convierto a str sumandole ''
      const dd = lPad(ahora.getDate()+'');
      const hh = lPad(ahora.getHours()+'');
      const mi = lPad(ahora.getMinutes()+'');
      const ss = lPad(ahora.getSeconds()+'');
      $('#resultado_diferencias').attr('download',`Diferencias-Estados-${codigo_plat}-${yyyy}-${mm}-${dd}-${hh}-${mi}-${ss}.pdf`);
      $('#resultado_diferencias').show();
      $('#resultado_diferencias_span').click();//El evento click sobre el <a> no hace nada
    },
    error: function (data) {
      console.log(data);
      clearInterval(loading);
      $('#animacionGenerando').text('ERROR');
      mensajeError(data.responseJSON["errores"]);
    }
  });
});