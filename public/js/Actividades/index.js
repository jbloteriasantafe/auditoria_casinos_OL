import "./actividades.js";
import "./calendario.js";

$(function(){
  $('.tituloSeccionPantalla').text('Actividades');
  
  $('[data-js-actividades]').on('set_mostrar_sin_completar',function(e,val){
    $('[data-js-calendario]').trigger('set_mostrar_sin_completar',[val]);
  });
  $('[data-js-calendario]').on('recibio_actividades',function(e,actividades){
    $('[data-js-actividades]').trigger('mostrar_actividades',[actividades]);
  });
  $('[data-js-calendario]').on('selecciono_fechas',function(e,desde,hasta){
    $('[data-js-actividades]').trigger('setear_fechas',[desde,hasta]);
  });
  $('[data-js-calendario]').on('clickeo_evento',function(e,numero,es_tarea){
    $('[data-js-actividades]').trigger('desplegar_actividad',[numero,es_tarea]);
  });
  $('[data-js-calendario]').on('cambio_fecha_evento',function(e,numero,fecha_nueva){
    $('[data-js-actividades]').trigger('cambiar_fecha_actividad',[numero,fecha_nueva]);
  });
  $('[data-js-actividades]').on('guardo_actividad borro_actividad cancelo_actividad',function(e){
    $('[data-js-calendario]').trigger('actualizar_eventos');
  });
});
