import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";
import "/js/Components/tabs.js";
import "/js/Components/modalEliminar.js";
import "/js/Actividades/modalActividadTarea.js";
import "/js/Actividades/grupos.js";

$(function(){ $('[data-js-actividades]').each(function(){
  const $actividades = $(this);
  const modalActividad = $('[data-js-modal-actividad-tarea][data-tipo="actividad"]');
  const modalTarea = $('[data-js-modal-actividad-tarea][data-tipo="tarea"]');
   
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
  
  $actividades.on('ver_actividad',function(e,numero,fecha_nueva = null){      
    AUX.GET('/actividades/obtener/'+numero,{},function(datos){      
      if(fecha_nueva !== null){
        datos[0].fecha = fecha_nueva;
      }
      
      (datos[0].padre_numero === null? modalActividad : modalTarea).trigger(
        'mostrar',
        [datos[0],datos,fecha_nueva !== null? 'editando' : 'visualizando']
      );
    });
  });
  
  $actividades.on('cambiar_fecha_actividad',function(e,numero,fecha_nueva){
    $actividades.trigger('ver_actividad',[numero,fecha_nueva]);
  });
  
  $actividades.on('mostrar_actividades',function(e,actividades){
    $actividades.find('[data-js-listado-son-actividades]').empty(); 
    const fecha_anterior = [null,null];
    const tab = [
      $actividades.find(`[data-js-listado-son-actividades="0"]`),
      $actividades.find(`[data-js-listado-son-actividades="1"]`)
    ];
    actividades.forEach(function(a){
      const es_actividad = a.es_actividad? 1 : 0;
      
      if(a.fecha != fecha_anterior[es_actividad]){
        if(fecha_anterior[es_actividad] !== null){
          const f = $actividades.find('[data-js-molde-fecha-separador]')
          .clone().removeAttr('data-js-molde-fecha-separador').text(fecha_anterior[es_actividad]);
          tab[es_actividad].prepend(f);
        }
        fecha_anterior[es_actividad] = a.fecha;
      }
      
      const $a = $actividades.find(`[data-js-molde-actividad]`).clone()
      .removeAttr('data-js-molde-actividad');
      
      $a.find('span[name]').text('------');
      Object.keys(a).forEach(function(k){
        if(a[k] !== undefined){
          $a.find(`span[name="${k}"]`).text(a[k]);
        }
      });
      
      $a.find('[data-js-estados]').filter(function(){
        return $(this).attr('data-js-estados').split(',').includes(a.estado);
      }).show();
      
      tab[es_actividad].prepend($a);
      
      $a.find('[data-js-ver-actividad]').on('click',function(){
        $actividades.trigger('ver_actividad',[a.numero]);
      });
    });
    
    fecha_anterior.forEach(function(fa,idx){
      if(fa === null) return;
      const f = $actividades.find('[data-js-molde-fecha-separador]')
      .clone().removeAttr('data-js-molde-fecha-separador').text(fa);
      tab[idx].prepend(f);
    });
  });
    
  modalActividad.on('hidden.bs.modal',function(e){
    $actividades.trigger('actualizar_eventos');
    $actividades.find('[data-js-grupos]').trigger('actualizar_grupos');
  });
  modalTarea.on('hidden.bs.modal',function(e){
    $actividades.trigger('actualizar_eventos');
    $actividades.find('[data-js-grupos]').trigger('actualizar_grupos');
  });
  $actividades.find('[data-js-grupos]').on('actualizo_grupos',function(e){
    //Como los grupos estan bakeados en el HTML de los modales, reinicio la pantalla
    location.reload();
  }).trigger('actualizar_grupos');
}); });
