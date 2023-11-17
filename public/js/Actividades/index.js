import "./actividades.js";
import "./calendario.js";

$(function(){
  $('[data-js-calendario]').on('recibio_actividades',function(e,actividades){
    $('[data-js-actividades]').trigger('mostrar_actividades',[actividades]);
  });
  $('[data-js-calendario]').on('selecciono_fechas',function(e,desde,hasta){
    $('[data-js-actividades]').trigger('setear_fechas',[desde,hasta]);
  });
  $('[data-js-calendario]').on('clickeo_evento',function(e,numero){
    $('[data-js-actividades]').trigger('desplegar_actividad',[numero]);
  });
  $('[data-js-calendario]').on('cambio_fecha_evento',function(e,numero,fecha_nueva){
    $('[data-js-actividades]').trigger('cambiar_fecha_actividad',[numero,fecha_nueva]);
  });
  $('[data-js-actividades]').on('guardo_actividad borro_actividad cancelo_actividad',function(e){
    $('[data-js-calendario]').trigger('actualizar_eventos');
  });
});
