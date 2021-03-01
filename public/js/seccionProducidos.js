$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Producidos');
  $('#opcProducidos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcProducidos').addClass('opcionesSeleccionado');

  $('#fecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });
  $('#columnaDetalle').hide();
  $('#btn-buscar').trigger('click');
});

$(function () {
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

    $('#selectCasinos').val("0");
    $('#fecha_inicio').val(" ");
    $('#fecha_fin').val(" ");
    $('#validado').val("-");
    $('#B_fecha_inicio').val(" ");
    $('#B_fecha_fin').val(" ");


});

var guardado = true;

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

//SI INGRESA ALGO EN ALGUN INPUT, se recalcula la diferencia
$(document).on('input', '#frmCargaProducidos input' , function(e){
  /*calculo si se opera en el input*/
  var input=$(this).val();
  guardado = false;
  salida=0;
  $('#modalCargaProducidos .mensajeSalida span').hide();
  $('#btn-guardar').show(); //guardado temporal, saca las diferencias 0
  //actualizo la diferencia
  //calcularDiferencia($(this));
  var denominacion = parseFloat($('#data-denominacion').val());
  var coinin_inicial = parseInt($('#coininIni').val()) * denominacion;
  var coinout_inicial = parseInt($('#coinoutIni').val()) * denominacion;
  var jackpot_inicial = parseInt($('#jackIni').val()) * denominacion;
  var progresivo_inicial =parseInt($('#progIni').val()) * denominacion;
  var coinin_final = parseInt($('#coininFin').val()) * denominacion;
  var coinout_final = parseInt($('#coinoutFin').val()) * denominacion;
  var jackpot_final = parseInt($('#jackFin').val()) * denominacion;
  var progresivo_final = parseInt($('#progFin').val()) * denominacion;
  var producido_sistema = parseFloat($('#prodSist').val());


  var producido_calculado=Math.round(((coinin_final - coinout_final - jackpot_final - progresivo_final) - (coinin_inicial - coinout_inicial - jackpot_inicial - progresivo_inicial)) * 100) / 100;
  var diferencia =Math.round( (producido_calculado - producido_sistema) * 100) / 100;

  $('#prodCalc').val(producido_calculado);
  $('#diferencias').text(diferencia);
  if(diferencia == 0){
    $('#btn-guardar').hide();
    $('#btn-finalizar').show();
  }


})

$(document).on('input', '#frmCargaProducidos textarea' , function(e){
  $('#btn-guardar').show()
})

$(document).on('change','#frmCargaProducidos observacionesAjuste',function(){
  $(this).removeClass('alerta');
})

//sale del campo y deja vacio cambia por 0
$(document).on('focusout' ,'#frmCargaProducidos input' , function(e){

  // $("#frmCargaProducidos").find(':input').each(function() {
    var input=$(this);
    if($(this).val() == ''){
      $(this).val(0)
    }
  // });

  var valor_input=$(this).val();
    //opero lo que haya escrito en el campo
    if(valor_input != ''){
      var arreglo = valor_input.split(/([-+*/])/);
      if(arreglo[0] != '' && arreglo[1] != '' && arreglo[2] != ''){
          switch (arreglo[1]) {
            case "+":
                var val= parseInt(arreglo[0])+parseInt(arreglo[2]);
              break;
            case "-":
              var val= parseInt(arreglo[0])-parseInt(arreglo[2]);
              break;
            case "*":
                var val= parseInt(arreglo[0]) * parseInt(arreglo[2]);
              break;
            case "/":
                var val= parseInt(arreglo[0])/parseInt(arreglo[2]);
              break;
            default: val = valor_input; break;
          }
          input.val(val);
      }
    }
    //calculoAritmetico(input , $(this));
    $(this).trigger('input');
});

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
  $('#maquinas_con_diferencias').text('---');

  $('#modalCargaProducidos #id_producido').val(id_producido);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/detallesProducido/' + id_producido, function(data){
    $('#maquinas_con_diferencias').text('SACAR?');
    for (let i = 0; i < data.detalles.length; i++) {
      const fila = generarFilaJuego(data.detalles[i].cod_juego,data.detalles[i].id_detalle_producido)//agregar otros datos para guardar en inputs ocultos
      $('#cuerpoTabla').append(fila);
      $('#btn-salir-validado').hide();
      $('#btn-salir').show();
    }
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
      return diferencia? '#e38c8c' : '#8ce3b8';
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
        const ip = 1.0-p;
        const r = 227*p + 140*ip;
        const g = 140*p + 227*ip;
        const b = 140*p + 184*ip;
        return 'rgb('+[r,g,b].join(',')+')';
      }
      $('#efectivo_pdev').css('color',interpolate(diff.efectivo_pdev));
      $('#bono_pdev').css('color',interpolate(diff.bono_pdev));
      $('#total_pdev').css('color',interpolate(diff.total_pdev));
    }
  });
}); 

//boton guarda temporal
$("#btn-guardar").click(function(e){
  e.preventDefault();
  var id_maquina=$(this).attr('data-id-maq');
  //Se envía el relevamiento para guardar con estado 2 = 'Carga parcial'
  guardarFilaDiferenciaCero(2,id_maquina);
  $('#modalCargaProducidos .mensajeSalida span').hide();
});

$("#btn-finalizar").click(function(e){
  e.preventDefault();
  var id_maquina=$(this).attr('data-id');


  //Se evnía el relevamiento para guardar con estado 2 = 'Carga parcial'
  guardarFilaDiferenciaCero(3,id_maquina);
  $('#modalCargaProducidos .mensajeSalida span').hide();

})

$('.btn-ajustar').click(function(e){
  e.preventDefault();
  var id_producido = $(this).attr('data-producido');
  $('.carga').each(function(index){
    if(id_producido == $(this).val()){
          $(this).trigger('click');
    }
  })
})

//SALIR DEL AJUSTE
var salida; //cantidad de veces que se apreta salir
$('#btn-salir').click(function(){
  if (guardado) $('#modalCargaProducidos').modal('hide');
  else{
    if (salida == 0) {
      $('#modalCargaProducidos .mensajeSalida span').show();
      salida = 1;
    }else {
      $('#modalCargaProducidos').modal('hide');
      guardado=1;
    }
  }
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

function guardarFilaDiferenciaCero(estado, id){ //POST CON DATOS CARGADOS

  $('#mensajeExito').hide();
  //estado -> generado, carga parcial, finalizado
  var detalles_sin_diferencia = [];
  var errores = 0 ;

  var id_detalle_contador_final = $('#data-detalle-final').val() != undefined ?  $('#data-detalle-final').val() : null;
  var id_detalle_contador_inicial = $('#data-detalle-inicial').val() != undefined ?  $('#data-detalle-inicial').val() : null;

  var producido = {
    id_maquina : id,
    id_detalle_producido :  $('#data-producido').val(),
    id_detalle_contador_final : id_detalle_contador_final,
    id_detalle_contador_inicial : id_detalle_contador_inicial,
    coinin_inicial : parseInt($('#coininIni').val()),
    coinout_inicial : parseInt($('#coinoutIni').val()),
    jackpot_inicial : $('#jackIni').val(),
    progresivo_inicial : $('#progIni').val(),
    coinin_final : parseInt($('#coininFin').val()),
    coinout_final :parseInt($('#coinoutFin').val()),
    jackpot_final : $('#jackFin').val(),
    progresivo_final :$('#progFin').val(),
    producido: $('#prodSist ').val(),
    denominacion: $('#data-denominacion').val(),
    id_tipo_ajuste: $('#observacionesAjuste').val(),
    prodObservaciones: $('#prodObservaciones').val(),
  };

  detalles_sin_diferencia.push(producido);

  //si apreta guardar con todos arreglados
   if(($('#diferencias').text()=='0') && ($('#observacionesAjuste').val() != 0)){
     estado = 3 ;
  }

  if(errores == 0){
    formData = {
      producidos_ajustados : detalles_sin_diferencia,
      estado : estado ,
      id_contador_final :  $('#data-contador-final').val(),
      id_contador_inicial: $('#data-contador-inicial').val(),
      id_tipo_moneda : $('#frmCargaProducidos').attr('data-tipoMoneda'),
      id_producido: $('#id_producido').val()
    };

    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
    $.ajax({
        type: 'POST',
        url: 'producidos/guardarAjusteProducidos',
        data: formData,
        dataType: 'json',
        success: function (data) {
          switch (data.estado) {
            case 1: //Ha finalizado el ajuste de UNA máquina
              $('#columnaDetalle').hide();
              $('#cuerpoTabla').find(id).remove();
              $('#btn-finalizar').hide();
              $('#modalCargaProducidos .mensajeFin').show();
              $('#maquinas_con_diferencias').text(parseInt($('#maquinas_con_diferencias').text())-1);
            case 2: //GUARDADO TEMPORAL
              for (var i = 0; i < data.resueltas.length; i++) {
                $('#cuerpoTabla #' + data.resueltas[i]).remove();
              }
              $('#columnaDetalle').hide();
              $('#textoExito').text('Se arreglaron ' + data.resueltas.length + ' máquinas. Y ocurrieron ' + data.errores.length + ' errores.');
            break;
            case 3: //SE HAN FINALIZADO LOS AJUSTES DE TODAS LAS MÁQUINAS
              $('#columnaDetalle').hide();
              $('#btn-finalizar').hide();
              $('#btn-guardar').hide();
              $('#modalCargaProducidos').modal('hide');

              $('#mensajeExito h3').text('EXITO');
              $('#mensajeExito p').text('Se han ajustado todas las diferencias correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              $('#mensajeExito').show();
              $('#btn-buscar').trigger('click');

              $('#tablaImportacionesProducidos #' + $('#id_producido').val()).find('td').eq(3).children()
                    .replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
            break;
            default:
            break;
          }

          guardado = true;
          $('#btn-guardar').hide();
        },
        error: function (data) {
          console.log('ERROR');
          console.log(data);
        },
    });
  }
};

function limpiarCuerpoTabla(){ //LIMPIA LOS DATOS DEL FORM DE DETALLE
  $('#modalCargaProducidos').find('#data-contador-final').val("");
  $('#modalCargaProducidos').find('#data-contador-inicial').val("");
  $('#btn-guardar').hide();
  $('#btn-finalizar').hide();
  $('#cuerpoTabla').empty();
  $('#coinoutIni').val("");
  $('#coininIni').val("");
  $('#jackIni').val("");
  $('#progIni').val("");
  $('#coininFin').val("");
  $('#coinoutFin').val("");
  $('#jackFin').val("");
  $('#progFin').val("");
  $('#prodCalc').val("");
  $('#prodSist').val("");
  $('#diferencias').val("");
  $('#denominacion').val("");
  $('#data-detalle-final').val("");
  $('#data-detalle-inicial').val("");
  $('#observacionesAjuste option').not('.default1').remove();
  $('#observacionesAjuste').val(0);
  $('#descripcion_validacion').text('');

}

function cerrarContadoresYValidar(id_producido , id_producido_final){

  var tds_inicio = $('#tablaImportacionesProducidos #' + id_producido).find('td');
  var tds_fin= $('#tablaImportacionesProducidos #' + id_producido_final).find('td');

  tds_inicio.eq(3).children().replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
  tds_inicio.eq(6).find('.carga').remove();
  if(id_producido_final != 0 ){
    tds_fin.eq(4).children()
    .replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
      checkEstado(id_producido_final);
  }

}

function checkEstado(id_producido){
  $.get('producidos/checkEstado/' + id_producido, function(data){
    if(data.estado == 1){
      var boton = '<button class="btn btn-warning carga popInfo" type="button" value="' + id_producido + '" data-trigger="hover" data-toggle="popover" data-placement="top" data-content="Ajustar"><i class="fa fa-fw fa-upload"></i></button>'
      $('#tablaImportacionesProducidos #' + id_producido).find('td').eq(6).prepend(boton);
      $('.btn-ajustar').each(function (index){
        if($(this).val() == data.id_casino){
          $(this).attr('data-producido' , id_producido);
        }
      })
    }else {
      $('.btn-ajustar').each(function (index){
        if($(this).val() == data.id_casino){
          $(this).remove();
        }
      })
    }
  });
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