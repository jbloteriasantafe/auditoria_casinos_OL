$(document).ready(function(){
  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes de beneficios de Juegos');
  $('#opcInformesJuegos').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesJuegos').addClass('opcionesSeleccionado');
  $('.selectMoneda').change();
});

//MUESTRA LA PLANILLA
$(document).on('click','.planilla',function(){
  const anio = $(this).attr('data-anio');
  const mes = $(this).attr('data-mes');
  const plataforma = $(this).attr('data-plataforma');
  const moneda = $(this).attr('data-moneda');
  window.open(`informesJuegos/generarPlanilla/${anio}/${mes}/${plataforma}/${moneda}/0`,'_blank');
});

$(document).on('click','.planilla2',function(){
  const anio = $(this).attr('data-anio');
  const mes = $(this).attr('data-mes');
  const plataforma = $(this).attr('data-plataforma');
  const moneda = $(this).attr('data-moneda');
  window.open(`informesJuegos/generarPlanilla/${anio}/${mes}/${plataforma}/${moneda}/1`,'_blank');
});

$(document).on('click','.planilla_sin_ajuste',function(){
  const anio = $(this).attr('data-anio');
  const mes = $(this).attr('data-mes');
  const plataforma = $(this).attr('data-plataforma');
  const moneda = $(this).attr('data-moneda');
  window.open(`informesJuegos/generarPlanillaSinAjuste/${anio}/${mes}/${plataforma}/${moneda}`,'_blank');
});

$(document).on('click','.informe_completo',function(){
  const anio = $(this).attr('data-anio');
  const mes = $(this).attr('data-mes');
  const plataforma = $(this).attr('data-plataforma');
  const moneda = $(this).attr('data-moneda');
  window.open(`informesJuegos/informeCompleto/${anio}/${mes}/${plataforma}/${moneda}`,'_blank');
});

$('.selectMoneda').change(function(){
  const moneda = $(this).val();
  const plataforma = $(this).attr('data-plataforma');
  $(`table[data-plataforma="${plataforma}"] tr[data-moneda!="${moneda}"]`).hide();
  $(`table[data-plataforma="${plataforma}"] tr[data-moneda="${moneda}"]`).show();
});
