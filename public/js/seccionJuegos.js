$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#gestionarJuegos').removeClass();
  $('#gestionarJuegos').addClass('subMenu2 collapse in');
  $('#gestionarJuegos').siblings('div.opcionesHover').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Juegos de Software');
  $('#opcJuegos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcJuegos').addClass('opcionesSeleccionado');

  const url = window.location.pathname.split("/");
  if(url.length >= 3) {
    let id = url[2]; 
    let fila_falsa = crearFilaJuego({id_juego : id}).hide();
    $('#cuerpoTabla').append(fila_falsa);
    fila_falsa.find('.detalle').trigger('click');
  }
  
  $('#buscarCertificado').trigger('click');

  //click forzado
  $('#btn-buscar').trigger('click');
})

//enter en modal
$('#contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
})

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

  mostrarJuego({},[],[]);
  habilitarControles(true);

  $('#modalJuego').modal('show');
});

//Muestra el modal con todos los datos del JUEGO
$(document).on('click','.detalle', function(){
  $('#modalJuego .modal-title').text('| VER MÁS');
  $('#modalJuego .modal-header').attr('style','background-color: #4FC3F7; color: #FFF');
  $('#boton-cancelar').hide();
  $('#boton-salir').show();
  $('#boton-salir').text('SALIR');
  //Remover el boton para guardar
  $('#btn-guardar').css('display','none');

  var id_juego = $(this).val();

  $.get("/juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      mostrarJuego(data.juego,data.certificados,data.plataformas);
      $('#id_juego').val(data.juego.id_juego);
      habilitarControles(false);
      $('#modalJuego').modal('show');
  });
});

$('.modal').on('hidden.bs.modal', function() {
  $('#btn-guardar').val('');
  $('#id_juego').val(0);
  $('.copia').remove();
})

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
    var id_juego = $(this).val();
    //Modificar los colores del modal
    $('#modalJuego .modal-title').text('| MODIFICAR JUEGO');
    $('#modalJuego .modal-header').attr('style','background: #ff9d2d');
    $('#btn-guardar').val('modificar').show();
    $('#id_juego').val(id_juego);
    habilitarControles(true);
    $.get("/juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      mostrarJuego(data.juego,data.certificados,data.plataformas);
      $('#modalJuego').modal('show');
    });

});

$(document).on('click' , '.borrarJuego' , function(){
  $(this).parent().parent().remove();
})

$(document).on('click' , '.borrarCertificado' , function(){
  var fila = $(this).parent().parent();
  fila.remove();
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ //limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  formData={
    id_plataforma: $('#buscadorPlataforma').val(),
    id_categoria_juego: $('#buscadorCategoria').val(),
    id_estado_juego: $('#buscadorEstado').val(),
    sistema: $('#buscadorSistema').val(),
    nombreJuego: $('#buscadorNombre').val(),
    cod_juego: $('#buscadorCodigoJuego').val(),
    certificado: $('#buscadorCodigo').val(),
    proveedor: $('#buscadorProveedor').val(),
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

//borrar una tabla de pago
$(document).on('click','.borrarTablaDeJuego',function(){
  $(this).parent().parent().remove();
  var cant_filas=0;
  $('#columna #unaTablaDePago').each(function(){
      cant_filas++;
  });
  if(cant_filas == 0){
    $('#tablaPagosEncabezado').hide();
  }
});

//Borrar Juego y remover de la tabla
$(document).on('click','.eliminar',function(){
    var id_juego = $(this).val();
    $('#btn-eliminarModal').val(id_juego);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar el juego "' + $(this).parent().parent().find('.nombre_juego').text()+'"?');
});

$('#btn-eliminarModal').click(function (e) {
    var id_juego = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "/juegos/eliminarJuego/" + id_juego,
        success: function (data) {
          //Remueve de la tabla
          $('#btn-buscar').trigger('click');
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

$(document).on('click','.historia',function(){
  const id_juego = $(this).val();
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: "GET",
    url: "/juegos/obtenerLogs/" + id_juego,
    success: function (data) {
      $('#modalLogs .columna tr').not('.ejemplo').remove();
      $('#modalLogs .cuerpo tr').not('.ejemplo').remove();
      data.forEach((val,idx) => {
        const col = $('#modalLogs .columna .ejemplo').clone().removeClass('ejemplo').show();
        const cuerpo = $('#modalLogs .cuerpo .ejemplo').clone().removeClass('ejemplo').show();
        col.find('.fecha').text(val['fecha']);
        col.attr('data-idx',idx);
        cuerpo.find('.json').empty().append(estilizarJSON(val['json']));
        cuerpo.attr('data-idx',idx);
        $('#modalLogs .columna tbody').append(col);
        $('#modalLogs .cuerpo tbody').append(cuerpo);
      });
      $('tr[data-idx="0"]').find('.verLog').click();
      $('#modalLogs').modal('show');
    },
    error: function (data) {
      console.log(data);
    }
  });
});

function estilizarJSON(j){
  const bigdiv = $('<div>').addClass('row');
  const blacklist = ['id_juego'];
  //Lo ordeno de manera similar al mostrarJuego
  const order = ['nombre_juego','id_categoria_juego',
  'cod_juego','escritorio','movil','plataformas','codigo_operador','proveedor',
  'certificados','denominacion_juego','porcentaje_devolucion','id_tipo_moneda'];
  const keys = order.concat(Object.keys(j).filter(k => !order.includes(k))); //Si alguna no esta en la lista de arriba se agrega abajo de todo
  for(const idx in keys){
    const key = keys[idx];
    if(blacklist.includes(key)) continue;
    if(!(key in j)) continue;
    const val = j[key];
    const row = $('<div>').addClass('row');
    row.append(estilizarFila(key,val));
    bigdiv.append(row);
    bigdiv.append($('<hr>').css('margin','0px').css('padding','0px'));
  }
  return bigdiv;
}
function isObject(obj){
  return typeof obj === 'object' && obj !== null;
}
function estilizarFila(key,val){
  const mayusculas = function(s){//Reemplaza _ por espacio y empieza cada palabra en mayuscula
    return (s[0].toUpperCase()+s.substring(1)).replaceAll(/_[a-z]/g,x => ' '+x[1].toUpperCase())
  };
  const clearNull = s => s === null? '' : s;
  let newKey = key;
  let newVal = val;
  if(key.substring(0,3) == 'id_'){
    newKey = mayusculas(key.substring(3));
    newVal = obtenerValor(key,val);
  }
  else if(key == "plataformas" && isObject(val[0])){//Lo hardcodeo porque generalizarlo lo haria demasiado confuso al codigo
    newKey = mayusculas(key);
    aux = [];
    for(const idx in val){
      const v = val[idx];
      if(v['id_estado_juego'] === null) continue;
      aux2 = obtenerValor('id_plataforma',v['id_plataforma']);
      aux2+= ": " + obtenerValor('id_estado_juego',v['id_estado_juego']);
      aux.push(aux2);
    }
    newVal = aux.join(', ');
  }
  else if(Array.isArray(val)){
    newKey = mayusculas(key);
    newVal = val.map(v => obtenerValor(key,v)).join(', ');
  }
  else{
    newKey = mayusculas(key);
  }
  const div = $('<div>').addClass('col-md-12');
  div.append($('<h4>').addClass('col-md-4').text(newKey));
  div.append($('<h4>').addClass('col-md-8').css('word-break','break-all').text(clearNull(newVal)));
  return div;
}

var cacheObtenerValor = {};
function obtenerValor(tipo,id){
  //undefined es falsy, si no esta shortcircuitea la primer condicion (no puede tirar excepcion la segunda)
  if(cacheObtenerValor[tipo] && cacheObtenerValor[tipo][id]) return cacheObtenerValor[tipo][id];
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  let ret = "ERROR";
  $.ajax({
    type: "GET",
    url: "/juegos/obtenerValor/"+tipo+"/"+id,
    async: false,
    success: data => {
      ret = data;
      if(cacheObtenerValor[tipo]) cacheObtenerValor[tipo][id] = data;
      else{
        cacheObtenerValor[tipo] = {};
        cacheObtenerValor[tipo][id] = data;
      }
    },
    error: data => console.log(data)
  });
  return ret;
}

$(document).on('click','.verLog',function(){
  const idx = $(this).parent().parent().attr('data-idx');
  $(`#modalLogs .cuerpo tr[data-idx!="${idx}"]`).hide();
  $(`#modalLogs .cuerpo tr[data-idx="${idx}"]`).show();
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    let certificados = [];
    $('#listaSoft .copia').each(function(){
      const texto = $(this).find('.codigo').val();
      const cert = obtenerIdCertificado(texto);
      if(cert != null) certificados.push(cert);
    });

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = '/juegos/guardarJuego';

    
    var formData = {
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
      plataformas: $.map($('.plataforma'), 
        function(p){
          return {
            id_plataforma: $(p).attr('data-id'), 
            id_estado_juego: $(p).val()
          };
        }
      )
    };

    if (state == "modificar") {
      url = '/juegos/modificarJuego';
      formData.id_juego =  $('#id_juego').val();
    }

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            $('#btn-buscar').trigger('click');
            $('#modalJuego').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text(' ');
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);

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
  var fila = $(document.createElement('tr'));

  var codigo;
  juego.certificados == null ?  codigo = '-' :   codigo= juego.certificados;
  juego.cod_juego == null ?  codigojuego = '-' :   codigojuego= juego.cod_juego;
  const categoria = $(`#buscadorCategoria option[value="${juego.id_categoria_juego}"]`);
  const estado = juego.estado.length > 0? juego.estado : "-";

  fila.attr('id',juego.id_juego)
  .append($('<td>')
      .addClass('col-xs-3')
      .addClass('nombre_juego')
      .text(juego.nombre_juego)
  )
  .append($('<td>')
      .addClass('col-xs-1')
      .addClass('categoria')
      .text(categoria.length > 0? categoria.text() : "-")
  )
  .append($('<td>')
      .addClass('col-xs-1')
      .addClass('estado')
      .text(estado).attr("title",estado)
  )
  .append($('<td>')
      .addClass('col-xs-2')
      .addClass('codigo_juego')
      .text(codigojuego)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .addClass('codigo_certif')
      .text(codigo)
      .attr('title',codigo)
  )
  .append($('<td>')
      .addClass('col-xs-2')
      .append($('<button>')
          .append($('<i>')
              .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
          )
          .append($('<span>').text('VER MÁS')).attr('title','VER MÁS')
          .addClass('btn').addClass('btn-info').addClass('detalle')
          .val(juego.id_juego)
      )
      .append($('<button>')
          .append($('<i>')
              .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
          )
          .append($('<span>').text('MODIFICAR')).attr('title','MODIFICAR')
          .addClass('btn').addClass('btn-warning').addClass('modificar')
          .val(juego.id_juego)
      )
      .append($('<button>')
      .append($('<i>')
          .addClass('fa')
          .addClass('fa-fw')
          .addClass('fa-clock')
      )
      .append($('<span>').text('HISTORIA')).attr('title','HISTORIA')
      .addClass('btn').addClass('btn-danger').addClass('historia')
      .val(juego.id_juego)
      )
      .append($('<button>')
          .append($('<i>')
              .addClass('fa')
              .addClass('fa-fw')
              .addClass('fa-trash-alt')
          )
          .append($('<span>').text('ELIMINAR')).attr('title','ELIMINAR')
          .addClass('btn').addClass('btn-danger').addClass('eliminar')
          .val(juego.id_juego)
      )
  )
  return fila;
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function habilitarControles(habilitado){
  $('#modalJuego input').prop('disabled',!habilitado);
  $('#modalJuego select').attr('disabled',!habilitado);
  $('.borrarFila').attr('disabled',!habilitado);
  $('#btn-agregarCertificado').attr('disabled',!habilitado);
  $('#modalJuego #motivo').prop('readonly',!habilitado).parent().toggle(habilitado);
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

  for (var i = 0; i < certificados.length; i++){
    let fila = agregarRenglonCertificado();
    const cert = certificados[i];
    fila.find('.codigo').val(cert.nro_archivo)
    .attr('data-id',cert.id_gli_soft);
  }

  $('.plataforma').val('');
  plataformas.forEach( p => {
    $(`.plataforma[data-id="${p.id_plataforma}"`).val(p.id_estado_juego);
  });

  $('#denominacion_juego').val(juego.denominacion_juego);
  $('#porcentaje_devolucion').val(juego.porcentaje_devolucion);
  $('#tipo_moneda').val(juego.id_tipo_moneda);
}

function agregarRenglonCertificado(){
  let fila =  $('#soft_mod').clone().show()
  .css('padding-top','2px')
  .css('padding-bottom','2px')
  .addClass('copia')
  .removeAttr('id');
  
  $('#listaSoft').append(fila);
  return fila;
}

$('#btn-agregarCertificado').click(function(){
  agregarRenglonCertificado();
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
  if($('#modalVerificarEstados #archivo').attr('data-borrado') == 'false' && $('#modalVerificarEstados #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalVerificarEstados #archivo')[0].files[0]);
  }
  $.ajax({
    type: "POST",
    url: "/juegos/generarDiferenciasEstadosJuegos",
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    responseType: "blob",
    success: function (data) {//Robado de https://stackoverflow.com/questions/2805330/opening-pdf-string-in-new-window-with-javascript
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
      const mm = lPad(ahora.getMonth()+1);
      const dd = lPad(ahora.getDate());
      const hh = lPad(ahora.getHours());
      const mi = lPad(ahora.getMinutes());
      const ss = lPad(ahora.getSeconds());
      $('#resultado_diferencias').attr('download',`Diferencias-Estados-${yyyy}-${mm}-${dd}-${hh}-${mi}-${ss}.pdf`);
      $('#resultado_diferencias').show();
    },
    error: function (data) {
      console.log(data);
      mensajeError(data.responseJSON["errores"]);
    }
  });
});