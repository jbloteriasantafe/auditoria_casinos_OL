import "./calendario.js";
import "./actividades.js";

$(function(){
  $('.tituloSeccionPantalla').text('Actividades');
  
  $('[data-js-calendario]').on('recibio_actividades',function(e,actividades){
    $('[data-js-actividades]').trigger('mostrar_actividades',[actividades]);
  });
  $('[data-js-calendario]').on('selecciono_fechas',function(e,desde,hasta){
    $('[data-js-actividades]').trigger('setear_fechas',[desde,hasta]);
  });
  $('[data-js-calendario]').on('clickeo_evento',function(e,numero,es_tarea){
    $('[data-js-actividades]').trigger('ver_actividad',[numero,es_tarea]);
  });
  $('[data-js-calendario]').on('cambio_fecha_evento',function(e,numero,fecha_nueva){
    $('[data-js-actividades]').trigger('cambiar_fecha_actividad',[numero,fecha_nueva]);
  });
  $('[data-js-actividades]').on('actualizar_eventos',function(e){
    $('[data-js-calendario]').trigger('actualizar_eventos');
  });
  
  $('[data-js-actividades]').on('set_mostrar_sin_completar',function(e,mostrar_sin_completar){
    $('[data-js-calendario]').trigger('set_mostrar_sin_completar',[mostrar_sin_completar]);
  });
  $('[data-js-actividades]').trigger('get_mostrar_sin_completar',[function(mostrar_sin_completar){
    $('[data-js-calendario]').trigger('set_mostrar_sin_completar',[mostrar_sin_completar]);
  }]);
});
