$(document).ready(function(){

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Informes de BINGO');
  $('#opcInformesMTM').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesMTM').addClass('opcionesSeleccionado');

});

//MUESTRA LA PLANILLA
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
    //cargo los datos del modal
    $('.modal-title-pregunta').text('| GENERAR INFORME');
    $('#modalPregunta').modal('show');
    $('#mensajePregunta').text('Desea generar el informe con observaciones?');
    //cargo los datos en el botón
    $('#btn-sin-observacion').attr('data-fecha',$(this).attr('data-fecha')).attr('data-casino', $(this).attr('data-casino'));
    $('#btn-con-observacion').attr('data-fecha',$(this).attr('data-fecha')).attr('data-casino', $(this).attr('data-casino'));
    //muestro los botones por si anteriormenete tenian el valor 'hide'
    $('#btn-sin-observacion').show();
    $('#btn-con-observacion').show();
    $('#valor-campo').remove();
    $('#btn-generar-con-observacion').hide();
});
$('#btn-sin-observacion').click(function (e) {
  window.open('generarPlanillaInforme/' + $(this).attr('data-fecha') +"/"+ $(this).attr('data-casino'),'_blank');
  });

$('#btn-con-observacion').click(function (e) {
    //oculto los botones que no se utilizarán
    $('#btn-sin-observacion').hide();
    $('#btn-con-observacion').hide();
    //cambio el texto del mensaje
    $('#mensajePregunta').text('Por favor, ingrese el valor de corrección para el informe:');
    //agergo el campo para el valor de corrección
    $('#btn-generar-con-observacion').attr('data-fecha',$(this).attr('data-fecha'))
                                      .attr('data-casino', $(this).attr('data-casino'))
                                      .show();
    $('#campo-valor').show();
    $('#campo-valor')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor-campo')
                  .attr('type','text')
                  .addClass('form-control')
              )


    // window.open('generarPlanillaInforme/' + $(this).attr('data-fecha') +"/"+ $(this).attr('data-casino'),'_blank');

    });
$('#btn-generar-con-observacion').click(function (e) {
  window.open('generarPlanillaInforme/' + $(this).attr('data-fecha') +"/"+ $(this).attr('data-casino') + "/" + $('#valor-campo').val(),'_blank');
  });