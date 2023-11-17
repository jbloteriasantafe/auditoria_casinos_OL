import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";
import "/js/Components/tabs.js";

$(function(){ $('[data-js-actividades]').each(function(){
  const $actividades = $(this);
  
  function crearActividad(estado,expandido,datos,historial){
    const es_actividad = (datos.parent === null)? 1 : 0;
    const a = $actividades.find(`[data-js-molde-actividad][data-es-actividad="${es_actividad}"]`).clone()
    .removeAttr('data-js-molde-actividad');
    $actividades.find(`[data-js-listado-son-actividades="${es_actividad}"]`).prepend(a);
    a.find('[data-js-fecha]').trigger('initInputFecha');//Necesito los inputFecha iniciados para setearEstado
    a.data('datos',datos);
    a.data('historial',historial);
    setearEstadoActividad(a,estado,expandido);
    bindActividad(a);
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

  $actividades.find('[data-js-agregar]').click(function(e){
    crearActividad('creando',true,{
      parent: null,
      fecha: $('[data-js-fecha-seleccionada]').attr('data-fecha'),
      hasta: $('[data-js-fecha-seleccionada]').attr('data-hasta') ?? undefined
    }, []);
  });
  
  $actividades.on('setear_fechas',function(e,desde,hasta){
    if(desde == hasta){
      $actividades.find('[data-js-fecha-seleccionada]').val(desde)
      .attr('data-fecha',desde)
      .removeAttr('data-hasta');
    }
    else{
      $actividades.find('[data-js-fecha-seleccionada]').val(`${desde}⭢${hasta}`)
      .attr('data-fecha',desde)
      .attr('data-hasta',hasta);
    }
  });
  
  function obtenerActividad(numero){
    return $actividades.find('[data-js-actividad]').filter(function(){
      const n = $(this).find('[name="numero"]').text();
      return n == numero;
    });
  }
  
  $actividades.on('desplegar_actividad',function(e,numero){
    const actividad = obtenerActividad(numero);
    if(actividad.length == 0) return;
    
    $actividades.find('[data-js-actividad]').each(function(){
      if($(this).attr('data-estado') == 'visualizando')
        setearEstadoActividad($(this),'visualizando',0);
    });
    
    setearEstadoActividad(actividad.eq(0),'visualizando',1);
    
    actividad[0].scrollIntoView();
  });
  
  $actividades.on('cambiar_fecha_actividad',function(e,numero,fecha_nueva){
    const a = obtenerActividad(numero);
    if(a.length == 0) return;
    
    a.data('datos').fecha = fecha_nueva;
    setearEstadoActividad(a.eq(0));
    a.find('[data-js-guardar]').click();
  });
  
  $actividades.on('mostrar_actividades',function(e,actividades){
    $actividades.find('[data-js-listado-son-actividades]').empty(); 
    Object.keys(actividades).forEach(function(numero){
      const acts = actividades[numero];
      const actual = acts.filter(function(a){
        return !a.deleted_at;
      });
      if(actual.length == 0) return;
      crearActividad('visualizando',false,actual[0],acts);
    });
  });
  
  function setearEstadoActividad(a,estado = null,expandido = null,datos = null,historial = null){   
    datos = datos ?? a.data('datos') ?? {};
            
    Object.keys(datos).forEach(function(k){
      if(datos[k] !== undefined){
        a.find(`input[name="${k}"],select[name="${k}"],textarea[name="${k}"]`).val(datos[k]);
        a.find(`span[name="${k}"]`).text(datos[k]);
      }
    });
    
    a.find(`[name="generar_tareas"]`).prop('checked',!!datos?.hasta);
    
    {
      const adjuntos = datos?.adjuntos ?? {};
      a.find('[data-js-archivos]').empty().append(Object.keys(adjuntos).map(function(nro_adjunto){
        return crearArchivoViejo(adjuntos[nro_adjunto],datos?.numero,datos?.fecha,nro_adjunto);
      }));
    }
    {
      a.find('[data-js-archivos]').append((datos?.adjuntos_nuevos ?? []).map(function(adj){
        const archivo_dom = crearArchivo(adj.file);
        archivo_dom.setHref(adj.link);
        return archivo_dom.obj;
      }));
    }
    
    historial = historial ?? a.data('historial') ?? [];
    
    a.find('[data-js-cambio-historial]').empty().append(historial.map(function(act,idx){
      return $('<option>').text(act.modified_at).val(idx);
    }));
    
    if(estado !== null){
      a.attr('data-estado',estado);
    }
    estado = a.attr('data-estado');
    
    if(expandido !== null){      
      a.toggleClass('expandido',!!expandido);
    }
    expandido = a.hasClass('expandido');
    
    a.find('[data-js-habilitar]').each(function(_,o){
      const estados = ($(this).attr('data-js-habilitar') ?? '').split(',');
      const deshabilitar = !estados.includes(estado);
      if($(o).is('[data-js-fecha]')){
        o.readonly(deshabilitar);
      }
      else{
        $(o).attr('readonly',deshabilitar);
      }
    });
        
    a.find('[data-js-ver]').hide().filter(function(){
      const estados = ($(this).attr('data-js-ver') ?? '').split(',');
      return estados.includes(estado);
    }).show();
  }
  
  function bindActividad(a){
    a.find('select').change(function(e){
      const val = $(this).val();
      $(this).find('option').each(function(idx,o){
        $(o).removeAttr('selected');
        if(val == $(o).val()) {
          $(o).attr('selected',true);
        }
      });
      $(this).val(val);
    });
        
    a.find('[data-js-guardar]').click(function(){
      const formData = objToFormData(AUX.form_entries(a[0]));
      if(a.data('datos')?.numero !== undefined)
        formData.append('numero',a.data('datos')?.numero);
      
      a.find('[data-js-archivo][data-nro-archivo]').each(function(){
        formData.append('adjuntos_viejos[]',$(this).attr('data-nro-archivo'));
      });
      a.find('[data-js-archivo]:not([data-nro-archivo])').each(function(){
        formData.append('adjuntos[]',$(this).data('file'),$(this).data('file').name);
      });
      
      $.ajax({
        type: "POST",
        url: '/actividades/guardar',
        data: formData,
        dataType: "json",
        processData: false,
        contentType:false,
        cache:false,
        success: function (data) {
          $actividades.trigger('guardo_actividad');
        },
        error: function (data) {
          console.log(data);
          AUX.mostrarErroresNames(a,data.responseJSON ?? {});
        }
      });
    });
    
    a.find('[data-js-editar]').click(function(){
      setearEstadoActividad(a,'editando',true);
    });
    
    a.find('[data-js-cancelar]').click(function(){
      $actividades.trigger('cancelo_actividad');
    });
    
    a.find('[data-js-eliminar]').click(function(){
      const numero = a.data('datos')?.numero;
      if(numero === undefined){
        return a.remove();
      }
      AUX.DELETE(
        '/actividades/borrar/'+numero,
        {},
        function(data){
          $actividades.trigger('borro_actividad');
        },
      );
    });
    
    a.find('[data-js-generar-tareas-toggle]').change(function(){
      const generar_tareas = $(this).prop('checked');
      a.find('[data-js-tipo="tarea"]').toggle(generar_tareas);
    });
    
    a.find('[data-js-expandir-contraer]').click(function(){
      const expandido = a.hasClass('expandido');
      setearEstadoActividad(a,'visualizando',(!expandido+0));
    });
    
    a.find('[data-js-historial]').click(function(){
      if(a.attr('data-estado') == 'visualizando'){
        setearEstadoActividad(a,'historial',true,a.data('historial')?.[0],a.data('historial'));
      }
      else{
        setearEstadoActividad(a,'visualizando',false);
      }
      $(this).toggleClass('activo');
    });
    
    a.find('[data-js-cambio-historial]').change(function(){
      const val = $(this).val();
      setearEstadoActividad(a,'historial',true,a.data('historial')?.[val],a.data('historial'));
      $(this).val(val);
    });
    
    a.find('[data-js-adjuntar]').click(function(){
      a.find('[data-js-selecciono-archivos]').trigger('click');
    });
    
    a.find('[data-js-selecciono-archivos]').change(function(event){
      for(const file of (event.target.files ?? [])){
        const archivo_dom = crearArchivo(file);
        a.find('[data-js-archivos]').append(archivo_dom.obj);
        
        const reader = new FileReader();
        reader.onload = function(){
          const byteCharacters = reader.result;
          const byteNumbers = new Array(byteCharacters.length);
          for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
          }
          const byteArray = new Uint8Array(byteNumbers);
          const blob = new Blob([byteArray], { type: file.type });
          
          archivo_dom.setHref(window.URL.createObjectURL(blob));
        }
        
        reader.readAsBinaryString(file);
      }
    });
    
    a.find('[name]').change();
  };
  
  function crearArchivoViejo(filename,nro_ticket,nro_archivo){
    const arch = crearArchivo({
      name: filename
    });
    arch.setHref(`/actividades/archivo/${nro_ticket}/${nro_archivo}`);
    arch.obj.attr('data-nro-archivo',nro_archivo);
    return arch.obj;
  }
  
  function crearArchivo(file){
    const archivo_dom = $('[data-js-molde-archivo]').clone().removeAttr('data-js-molde-archivo');
    archivo_dom.find('[name="nombre_archivo"]').text(file.name);
    
    archivo_dom.find('[data-js-borrar-archivo]').click(function(){
      archivo_dom.remove();
    });
    
    archivo_dom.find('a').addClass('sin_decoracion_de_link')
    .attr('download',file.name);
    
    archivo_dom.data('file',file);
    
    return {
      obj: archivo_dom,
      setHref: function(href){
        archivo_dom.find('a')
        .attr('href',href)
        .removeClass('sin_decoracion_de_link');
        
        archivo_dom.find('[data-js-cargando]').remove();
      }
    };
  }
}); });
