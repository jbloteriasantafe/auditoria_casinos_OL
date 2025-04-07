import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-importacion]').each(function(_,Mobj){
  const  M = $(Mobj);
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e){
    e.preventDefault();
    $M('[name]').val('');
    $M('[data-tabla-juegos-a-importar] tbody').empty();
    $M('[data-tabla-certificados-a-importar] tbody').empty();
    $M('[data-mensaje]').empty();
    M.attr('data-estado-visible','IMPORTAR');
    $M('#modalImportacionCertificadosList').empty();
    M.modal('show');
  });
  
  const crearFilaJuego = function(){
    return $M('[data-molde-tabla-juegos-a-importar]').clone().removeAttr('data-molde-tabla-juegos-a-importar');
  };
  
  const recalcularIndicesJuegos = function(){
    $M('[data-tabla-juegos-a-importar] tbody tr').each(function(jidx,trobj){
      $(trobj).find('[data-name]').each(function(_,nobj){
        const name = $(this).attr('data-name');
        $(this).attr('name',`juegos[${jidx}][${name}]`);
      });
    });
  };
    
  const actualizarCertificados = function(){
    const nro_archivos = {};
    const id_laboratorios = {};
    $M('[data-tabla-juegos-a-importar] tbody tr')
    .find('.data-css-esconder-disabled[disabled]').removeAttr('disabled');
    
    $M('[data-tabla-juegos-a-importar] tbody tr').each(function(tridx,trobj){
      const tr = $(trobj);
      const nro_archivo = tr.find('[data-name="nro_archivo"]').val();
      nro_archivos[nro_archivo] = nro_archivos[nro_archivo] ?? tridx;
    });
    
    $M('[data-tabla-juegos-a-importar] tbody tr').each(function(tridx,trobj){
      const tr = $(trobj);
      const nro_archivo = tr.find('[data-name="nro_archivo"]').val();
      if(nro_archivos[nro_archivo] !== null && tridx != nro_archivos[nro_archivo]){
        tr.find('[data-name="certificado"],[data-name="id_laboratorio"]').val('');
        tr.find('.data-css-esconder-disabled').attr('disabled',true);
      }
    });
    
    $M('#modalImportacionCertificadosList').empty();//Para certificados uso un datalist aparte porque generalmente son certificados nuevos...
    Object.keys(nro_archivos).forEach(function(nro_archivo){
      $M('#modalImportacionCertificadosList').append(
        `<option>${nro_archivo}</option>`
      );
    });
  };
    
  $M('[data-js-click-agregar-fila]').click(function(e){
    e.preventDefault();
    $M('[data-tabla-juegos-a-importar] tbody').append(crearFilaJuego());
    recalcularIndicesJuegos();//@SLOW
  });
  
  $M('[data-js-cambio-enviar-a-parsear]').on('change',function(e){
    const archivo = $M('[name="archivo"]')[0].files?.[0] ?? null;    
    if(archivo === null) return;
        
    const formData = new FormData();
    formData.append('archivo',archivo);
    const url = $(this).attr('data-js-cambio-enviar-a-parsear');
    const tabla = $M('[data-tabla-juegos-a-importar] tbody').empty();
    $.ajax({
      type: "POST",
      url: url,
      data: formData,
      dataType: "json",
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
        $M('[name="id_plataforma"]').val(data?.id_plataforma ?? '');
        $M('[data-mensaje]').empty().append(data?.mensaje ?? '');
        const juegos = data?.juegos ?? [];//data-molde-tabla-juegos-a-importar
        const jcols  = [];
        {
          const aux = Object.assign({},juegos?.[0] ?? []);
          Object.keys(aux).forEach(key => {
            jcols[aux[key]] = key;
          });
        }
        for(let jidx=1;jidx < juegos.length;jidx++){
          const fila = crearFilaJuego();
          fila.find('[data-name]').each(function(_,nobj){
            const name = $(nobj).attr('data-name');
            $(nobj).val(juegos[jidx][jcols[name]]);
          });
          tabla.append(fila);
        }
        recalcularIndicesJuegos();
        actualizarCertificados();
        M.attr('data-estado-visible','JUEGOS');
      },
      error: function (data) {
        console.log(data,data?.responseJSON ?? {});
      }
    });
  });
    
  $M('[data-js-click-cambiar-estado]').click(function(e){
    const tgt = $(e.currentTarget);
    const estado = tgt.attr('data-js-click-cambiar-estado');
    $M('[data-mensaje]').empty();
    M.attr('data-estado-visible',estado);
  });
  
  const mapJSONtoNames = function(json){
    const ret = {};
    Object.keys(json).forEach(function(k){
      const namearr = k.split('.');
      let name = namearr?.[0] ?? '';
      for(let nidx = 1;nidx < namearr.length;nidx++){
        name+='['+namearr[nidx]+']';
      }
      ret[name] = json[k];
    });
    return ret;
  };
  
  $M('[data-js-click-cargar]').click(function(e){
    const tgt = $(e.currentTarget);
    const datalistProveedores = $('#'+tgt.attr('data-datalistProveedores'));
    const fd = new FormData(M.find('form')[0]);
    const fd_sin_certificados = new FormData();
    for(const k of fd.keys()){//Envio sin PDF para validar
      const aux = k.substring(0,k.length-'[certificado]'.length);
      if(k.substring(aux.length) != '[certificado]'){
        fd_sin_certificados.set(k,fd.get(k));
      }
    }
    $.ajax({
      type: "POST",
      url: tgt.attr('data-url-validar-carga-masiva'),
      data: fd_sin_certificados,
      dataType: "json",
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
        $M('[data-mensaje]').empty();
        $M('[data-tabla-juegos-a-importar] tbody tr').attr('data-estado-carga','CARGANDO');
        $.ajax({
          type: "POST",
          url: tgt.attr('data-url-carga-masiva'),
          dataType: "json",
          processData: false,
          contentType: false,
          cache: false,
          data: fd,
          success: function(data){            
            $M('[data-tabla-juegos-a-importar] tbody tr').attr('data-estado-carga','COMPLETADO');
            datalistProveedores.find('option[data-es-nuevo]').removeAttr('data-es-nuevo');
            
            AUX.mensajeExito('');
            
            M.modal('hide').trigger('completado',[data?.certificados ?? []]);
          },
          error: function (data) {
            const json = data?.responseJSON ?? {};
            console.log(data,json);
            const names = mapJSONtoNames(json);
            console.log(names);
            AUX.mostrarErroresNames(M.find('form'),names,false);
            M.find('[data-tabla-juegos-a-importar] [data-estado-carga]').attr('data-estado-carga','ERROR');
          }
        });
      },
      error: function (data) {
        const json = data?.responseJSON ?? {};
        console.log(data,json);
        const names = mapJSONtoNames(json);
        console.log(names);
        AUX.mostrarErroresNames(M.find('form'),names,false);
        M.find('[data-tabla-juegos-a-importar] [data-estado-carga]').attr('data-estado-carga','ERROR');
      }
    });
  });
  
  $M('[data-js-click-limpiar-archivo]').click(function(e){
    $M('[name="archivo"]').val('');
  });
  
  M.on('change','[data-js-cambio-actualizar-certificados]',function(e){
    actualizarCertificados();
  });
  
  M.on('click','[data-tabla-juegos-a-importar] tbody [data-js-click-eliminar-fila]',function(e){
    e.preventDefault();
    const tr = $(this).closest('tr');
    tr.find('[data-js-change-actualizar-list]').trigger('focusin').val('').trigger('change');//Simulo un cambio para actualizar el datalist
    tr.remove();
    recalcularIndicesJuegos();
    actualizarCertificados();
  });
  
  M.on('focusin','[data-js-change-actualizar-list]',function(e){
    const tgt = $(e.currentTarget);
    tgt.attr('data-js-change-actualizar-list',tgt.val());
  });
  M.on('change','[data-js-change-actualizar-list]',function(e){
    const tgt = $(e.currentTarget);
    const old_val = tgt.attr('data-js-change-actualizar-list').trim();
    const val = tgt.val().trim();
    if(val == old_val) return;
    
    const list = $('#'+tgt.attr('list'));
    
    const buscar_option = function(v){
      let option = null;
      list.find('option').each(function(){
        if($(this).text() == v){
          option = $(this);
          return false;//@BREAK
        }
      });
      return option;
    }
    
    if(old_val.length){
      const old_option = buscar_option(old_val);
      if(old_option !== null){
        if(old_option.is('[data-es-nuevo]')){
          const refCount = parseInt(old_option.attr('data-es-nuevo'));
          old_option.attr('data-es-nuevo',refCount-1);
        }
      }
    }
    
    if(val.length){
      const new_option = buscar_option(val);
      if(new_option !== null){
        if(new_option.is('[data-es-nuevo]')){
          const refCount = parseInt(new_option.attr('data-es-nuevo'));
          new_option.attr('data-es-nuevo',refCount+1);
        }
      }
      else{
        list.append(`<option data-es-nuevo="1">${val}</option>`);
      }
    }
    
    list.find('option[data-es-nuevo]').filter(function(){
      return parseInt($(this).attr('data-es-nuevo')) <= 0;
    }).remove();
  });
  
  M.on('click','[data-js-click-clear-sibling]',function(e){
    const tgt = $(e.currentTarget);
    const selector = tgt.attr('data-js-click-clear-sibling');
    $(e.currentTarget).siblings(selector).each(function(_,s){
      if(s.files) s.files = null;
      $(s).val('');
    });
  });
}); });
