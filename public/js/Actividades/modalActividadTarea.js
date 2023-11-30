import "/js/Components/inputFecha.js";
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-actividad-tarea]').each(function(){
  const at = $(this);
   
  const setearEstadoActividad = function(estado = null){
    const datos = at.data('datos') ?? {};
            
    at.find('span[name]').text('------');
    at.find('form')[0].reset();   
    
    Object.keys(datos).forEach(function(k){
      if(datos[k] !== undefined){
        at.find(`input[name="${k}"],select[name="${k}"],textarea[name="${k}"]`).val(datos[k]);
        at.find(`span[name="${k}"]`).text(datos[k]);
      }
    });
    
    {
      const adjuntos = datos?.adjuntos ?? {};
      at.find('[data-js-archivos]').empty().append(Object.keys(adjuntos).map(function(nro_adjunto){
        return crearArchivoViejo(adjuntos[nro_adjunto],datos?.numero,datos?.fecha,nro_adjunto);
      }));
    }
    {
      at.find('[data-js-archivos]').append((datos?.adjuntos_nuevos ?? []).map(function(adj){
        const archivo_dom = crearArchivo(adj.file);
        archivo_dom.setHref(adj.link);
        return archivo_dom.obj;
      }));
    }
    
    if(datos?.numero){
      const roles = datos?.roles ?? [];
      at.find('[name="roles[]"]').each(function(_,o){
        $(this).prop('checked',roles.includes($(this).val()));
      });
    }
    
    const historial = at.data('historial') ?? [];
    
    at.find('[data-js-cambio-historial]').empty().append(historial.map(function(act,idx){
      return $('<option>').text(act.modified_at).val(idx);
    }));
    
    if(estado !== null){
      at.attr('data-estado',estado);
    }
    estado = at.attr('data-estado');
    
    at.find('[data-js-habilitar]').each(function(_,o){
      const estados = ($(this).attr('data-js-habilitar') ?? '').split(',');
      const deshabilitar = !estados.includes(estado);
      if($(o).is('[data-js-fecha]')){
        o.readonly(deshabilitar);
      }
      else{
        $(o).attr('readonly',deshabilitar);
      }
    });
    
    at.find('[data-js-ver]').hide().filter(function(){
      const estados = ($(this).attr('data-js-ver') ?? '').split(',');
      const visible = estados.includes(estado);
      $(this).attr('data-js-visible',visible);
      return visible;
    }).show();
    
    at.find(`[name="generar_tareas"]`).prop('checked',!!datos?.hasta);
    at.find('[data-js-cambio-esconder-guardar]').each(function(){
      const val = $(this).is('[type="checkbox"]')? $(this).prop('checked') : $(this).val();
      $(this).attr('data-valor-original',val);
    }).eq(0).change();
  }
  
  at.on('mostrar',function(e,datos,historial,estado = 'visualizando'){
    at.data('datos',datos);
    at.data('historial',historial);
    setearEstadoActividad(estado);
    at.modal('show');
  });
  
  at.find('select').change(function(e){
    const val = $(this).val();
    $(this).find('option').each(function(idx,o){
      $(o).removeAttr('selected');
      if(val == $(o).val()) {
        $(o).attr('selected',true);
      }
    });
    $(this).val(val);
  });
      
  at.find('[data-js-guardar]').click(function(e){
    const formData = new FormData(at.find('form')[0]);
    if(at.data('datos')?.numero !== undefined)
      formData.append('numero',at.data('datos')?.numero);
    
    at.find('[data-js-archivo][data-nro-archivo]').each(function(){
      formData.append('adjuntos_viejos[]',$(this).attr('data-nro-archivo'));
    });
    at.find('[data-js-archivo]:not([data-nro-archivo])').each(function(){
      formData.append('adjuntos[]',$(this).data('file'),$(this).data('file').name);
    });
    
    formData.append('generar_tareas',$(this).attr('data-generar_tareas'));
    ocultarErrorValidacion(at.find('[name]'));
    $.ajax({
      type: "POST",
      url: '/actividades/guardar',
      data: formData,
      dataType: "json",
      processData: false,
      contentType:false,
      cache:false,
      success: function (data) {
        at.modal('hide');
      },
      error: function (data) {
        console.log(data);
        const json = data.responseJSON ?? {};
        AUX.mostrarErroresNames(at,json);
        if(json.roles){
          mostrarErrorValidacion(at.find('[name="roles[]"]:first'),json.roles.join(', '),true);
        }
      }
    });
  });
    
  at.find('[data-js-editar]').click(function(){
    setearEstadoActividad('editando');
  });
  
  at.find('[data-js-cancelar]').click(function(){
    setearEstadoActividad('visualizando');
  });
  
  at.find('[data-js-eliminar]').click(function(){
    $('[data-js-modal-eliminar]').trigger('mostrar_para_eliminar',[function(){
      const numero = at.data('datos')?.numero;
      if(numero === undefined){
        return $('[data-js-modal-eliminar]').trigger('esconder');
      }
      AUX.DELETE(
        '/actividades/borrar/'+numero,
        {},
        function(data){
          $('[data-js-modal-eliminar]').trigger('esconder');
        },
      );
    }]);
  });
  
  at.find('[data-js-historial]').click(function(){
    if(at.attr('data-estado') == 'visualizando'){
      setearEstadoActividad('historial');
    }
    else{
      at.data('datos',at.data('historial')[0]);
      setearEstadoActividad('visualizando');
    }
    $(this).toggleClass('activo');
  });
  
  at.find('[data-js-cambio-historial]').change(function(){
    const val = $(this).val();
    at.data('datos',at.data('historial')[val]);
    setearEstadoActividad('historial');
    $(this).val(val);
  });
  
  at.find('[data-js-adjuntar]').click(function(){
    at.find('[data-js-selecciono-archivos]').trigger('click');
  });
  
  at.find('[data-js-selecciono-archivos]').change(function(event){
    for(const file of (event.target.files ?? [])){
      const archivo_dom = crearArchivo(file);
      at.find('[data-js-archivos]').append(archivo_dom.obj);
      
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
  
  at.find('[data-js-click-evento]').on('click',function(e){
    e.stopPropagation();
    $(this).find('[data-js-recibir-click-evento]:not([readonly],[disabled])').trigger('click');
  });
  at.find('[data-js-recibir-click-evento]').on('click',function(e){
    e.stopPropagation();
  });
  
  at.find('[name]').change();
  
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
  
  at.find('[data-js-toggle-generar]').change(function(){
    const checked = $(this).prop('checked');
    at.find('[data-js-datos-generar]').toggle(checked);
    if(!checked){
      at.find('[data-js-datos-generar] [name]').each(function(){
        $(this).val($(this).attr('data-valor-original'));
      }).change();
    }
  });
  
  at.find('[data-js-cambio-esconder-guardar]').change(function(){
    let todos_iguales = true;
    at.find('[data-js-cambio-esconder-guardar]').each(function(){
      const val = $(this).is('[type="checkbox"]')? ($(this).prop('checked')+'') : $(this).val();
      todos_iguales = todos_iguales && (val == $(this).attr('data-valor-original'));
    });
    const mostrarObj = function(o,visible){
      visible = visible;
      o.toggle(visible);
    };
    mostrarObj(at.find('[data-js-guardar][data-generar_tareas="0"][data-js-visible="true"]'),todos_iguales);
    mostrarObj(at.find('[data-js-guardar][data-generar_tareas="1"][data-js-visible="true"]'),!todos_iguales);
  });
}); });
