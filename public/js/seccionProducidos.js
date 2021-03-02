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

$('#btn-buscar').on('click' , function () {
  const orden = $('#tablaImportacionesProducidos th.activa').attr('estado');
  var busqueda = {
    id_plataforma : $('#selectPlataforma').val(),
    id_tipo_moneda : $('#selectMoneda').val(),
    fecha_inicio : $('#fecha_inicio').val(),
    fecha_fin : $('#fecha_fin').val() ,
    validado : $('#selectValidado').val(),
    orden: orden? orden: ''
  }

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: 'GET',
      url: 'producidos/buscarProducidos',
      data: busqueda,
      dataType: 'json',
      success: function (data) {
        $('#tablaImportacionesProducidos tbody').empty();
        for (var i = 0; i < data.producidos.length; i++) {
          agregarFilaTabla(data.producidos[i]);
        }
      },
      error: function (data) {
        console.log('ERROR');
        console.log(data);

      },
  });

})

//mostrar popover
$(document).on('mouseenter','.popInfo',function(e){
    $(this).popover('show');
});

//AJUSTAR PRODUCIDO, boton de la lista
$(document).on('click','.carga',function(e){
  e.preventDefault();
  $('#columnaDetalle').hide();
  $('#mensajeExito').modal('hide');
  limpiarCuerpoTabla();

  $('#modalCargaProducidos .mensajeSalida span').hide();
  const tr_html = $(this).parent().parent();
  const id_producido = $(this).val();
  const moneda = tr_html.find('.tipo_moneda').text();
  const fecha_prod = tr_html.find('.fecha_producido').text();
  const plataforma = tr_html.find('.plataforma').text();
  $('#descripcion_validacion').text(plataforma+' - '+fecha_prod+' - $'+moneda);
  $('#juegos_con_diferencias').text('---');
  $('#juegos_no_en_bd').text('---');

  $('#modalCargaProducidos #id_producido').val(id_producido);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/detallesProducido/' + id_producido, function(data){
    for (let i = 0; i < data.detalles.length; i++) {
      const d = data.detalles[i];
      const fila = generarFilaJuego(d.cod_juego,d.id_detalle_producido);
      $('#cuerpoTabla').append(fila);
      $('#btn-salir-validado').hide();
      $('#btn-salir').show();
    }
    $('#juegos_con_diferencias').text(data.diferencias);
    $('#juegos_no_en_bd').text(data.no_en_bd);
  });
  $('#frmCargaProducidos').attr('data-tipoMoneda' ,tr_html.find('.tipo_moneda').attr('data-tipo'));
  $('#modalCargaProducidos').modal('show');
  $('#').modal('hide');
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
  $.get('producidos/datosDetalle/' + id_detalle, function(data){
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
    $('#efectivo_pdev').text(pdev(d.apuesta_efectivo,d.premio_efectivo)).css('color','');
    $('#premio_bono').val(d.premio_bono);
    $('#bono_pdev').text(pdev(d.apuesta_bono,d.premio_bono)).css('color','');
    $('#premio').val(d.premio).css('background-color',validar(diff.premio));
    $('#total_pdev').text(pdev(d.apuesta,d.premio)).css('color','');
    $('#beneficio_efectivo').val(d.beneficio_efectivo).css('background-color',validar(diff.beneficio_efectivo));
    $('#beneficio_bono').val(d.beneficio_bono).css('background-color',validar(diff.beneficio_bono));
    $('#beneficio').val(d.beneficio).css('background-color',validar(diff.beneficio));
    $('#categoria').val(d.categoria).css('background-color','');
    $('#jugadores').val(d.jugadores)
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
      const interpolate = function(p){
        if(p == null) return '';
        p = Math.max(Math.min(p,2.0),0.0); //Clamp entre [0,2]
        p = -p*p + 2*p; //Mapeo [0,2] -> [0,1], con maximo en p=1. Permite interpolar
        const ip = 1.0-p;
        const r = 227*ip + 140*p;
        const g = 140*ip + 227*p;
        const b = 140*ip + 184*p;
        return 'rgb('+[r,g,b].join(',')+')';
      };
      $('#efectivo_pdev').css('color',interpolate(parseFloat(diff.efectivo_pdev)));
      $('#bono_pdev').css('color',interpolate(parseFloat(diff.bono_pdev)));
      $('#total_pdev').css('color',interpolate(parseFloat(diff.total_pdev)));
    }
  });
}); 


/************   FUNCIONES   ***********/
function generarFilaJuego(cod_juego, id_juego){//CARGA LA TABLA DE MÁQUINAS SOLAMENTE, DENTRO DEL MODAL
  var fila=$('#filaClon').clone();
  fila.removeAttr('id');
  fila.attr('id',  id_juego);
  fila.find('.cod_juego').text(cod_juego);
  fila.find('button').val(id_juego);
  fila.css('display', 'block');
  return  fila;
}


//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
  window.open('producidos/generarPlanilla/' + $(this).val(),'_blank');
});

//función para generar el listado inicial
function agregarFilaTabla(producido){
  const plat = $(`#selectPlataforma option[value=${producido.id_plataforma}]`).text();
  const moneda = $(`#selectMoneda option[value=${producido.id_tipo_moneda}]`).text();
  const tr = $('<tr>')
  .append($('<td>').addClass('col-xs-3 plataforma').text(plat))
  .append($('<td>').addClass('col-xs-3 fecha_producido').text(producido.fecha))
  .append($('<td>').addClass('col-xs-3 tipo_moneda').text(moneda))
  .append($('<td>').addClass('col-xs-3')
    .append($('<button>').addClass('btn').addClass('btn-info').addClass('carga').attr('value',producido.id_producido)
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-upload')))
    .append($('<button>').addClass('btn').addClass('btn-info').addClass('planilla').attr('value',producido.id_producido)
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-print'))
    )
  );
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