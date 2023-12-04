import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";
import "/js/Components/tabs.js";
import "/js/Components/modalEliminar.js";
import "/js/Actividades/modalActividadTarea.js";

$(function(){ $('[data-js-actividades]').each(function(){
  const $actividades = $(this);
  const modalActividad = $('[data-js-modal-actividad-tarea][data-tipo="actividad"]');
  const modalTarea = $('[data-js-modal-actividad-tarea][data-tipo="tarea"]');
  
  $actividades.find('[data-js-cambio-mostrar-sin-completar]').change(function(e){
    const mostrar_sin_completar = $(this).prop('checked');
    $actividades.trigger('set_mostrar_sin_completar',[mostrar_sin_completar]);
  });
  
  $actividades.on('get_mostrar_sin_completar',function(e,callback){
    const mostrar_sin_completar = $actividades.find('[data-js-cambio-mostrar-sin-completar]').prop('checked');
    callback(mostrar_sin_completar);
  });
    
  function crearActividad(estado,datos,historial){
    const es_actividad = (datos.padre_numero === null)? 1 : 0;
    const a = $actividades.find(`[data-js-molde-actividad]`).clone()
    .removeAttr('data-js-molde-actividad');
    $actividades.find(`[data-js-listado-son-actividades="${es_actividad}"]`).prepend(a);
    a.find('[data-js-fecha]').trigger('initInputFecha');//Necesito los inputFecha iniciados para setearEstado
    a.data('datos',datos);
    a.data('historial',historial);
    setearEstadoActividad(a);
    a.find('[data-js-ver-actividad]').click(function(){
      $actividades.trigger('ver_actividad',[datos.numero]);
    });
  }
  
  function __buildFormData(formData, data, parentKey) {
    if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
      Object.keys(data).forEach(key => {
        __buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
      });
    } else {
      formData.append(parentKey, data == null ? '' : data);
    }
  }
  
  function objToFormData(data) {
    const formData = new FormData();
    __buildFormData(formData, data);
    return formData;
  }

  let fecha = (new Date()).toISOString().split('T')[0];
  let hasta = undefined;
  
  $actividades.find('[data-js-agregar]').click(function(e){
    modalActividad.trigger('mostrar',[{
      padre_numero: null,
      fecha: fecha,
      hasta: hasta,
    },[],'creando']);
  });

  $actividades.on('setear_fechas',function(e,desde,hasta2){
    fecha = desde;
    hasta = (desde == hasta2)? undefined : hasta2;
  });
  
  function obtenerActividad(numero){
    return $actividades.find('[data-js-actividad]').filter(function(){
      const n = $(this).find('[name="numero"]').text();
      return n == numero;
    });
  }
  
  $actividades.on('ver_actividad',function(e,numero){
    const actividad = obtenerActividad(numero);
    if(actividad.length == 0) return;
    
    const es_actividad = (actividad.data('datos').padre_numero === null)+0;
    
    (es_actividad? modalActividad : modalTarea).trigger(
      'mostrar',
      [actividad.data('datos'),actividad.data('historial'),'visualizando']
    );
        
    $(`[data-js-tab-actividad="${es_actividad}"]`).click();
    actividad[0].scrollIntoView();
  });
  
  
  $actividades.on('cambiar_fecha_actividad',function(e,numero,fecha_nueva){
    const a = obtenerActividad(numero);
    if(a.length == 0) return;
    
    const es_actividad = (a.data('datos').padre_numero === null)+0;
    const new_datos = a.data('datos');
    new_datos.fecha = fecha_nueva;
    (es_actividad? modalActividad : modalTarea).trigger(
      'mostrar',
      [new_datos,a.data('historial'),'editando']
    );
  });
  
  $actividades.on('mostrar_actividades',function(e,actividades){
    $actividades.find('[data-js-listado-son-actividades]').empty(); 
    Object.keys(actividades).forEach(function(numero){
      const acts = actividades[numero];
      const actual = acts.filter(function(a){
        return !a.deleted_at;
      });
      if(actual.length == 0) return;
      crearActividad('visualizando',actual[0],acts);
    });
  });
  
  function setearEstadoActividad(a){   
    const datos = a.data('datos') ?? {};
            
    a.find('span[name]').text('------');
    a[0].reset();   
    
    Object.keys(datos).forEach(function(k){
      if(datos[k] !== undefined){
        a.find(`input[name="${k}"],select[name="${k}"],textarea[name="${k}"]`).val(datos[k]);
        a.find(`span[name="${k}"]`).text(datos[k]);
      }
    });
  }
    
  modalActividad.on('hidden.bs.modal',function(e){
    $actividades.trigger('actualizar_eventos');
  });
  modalTarea.on('hidden.bs.modal',function(e){
    $actividades.trigger('actualizar_eventos');
  });
}); });
