import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-grupos]').each(function(){
  const $grupos = $(this);
  
  const crearGrupo = function(){
    const g = $grupos.find('[data-js-grupo][data-js-molde-grupo]').clone().removeAttr('data-js-molde-grupo');
    
    g.find('[data-js-guardar-grupo]').on('click',function(e){
      AUX.POST(
        '/actividades/grupos/guardar',
        {
          ...AUX.form_entries(g[0]),
          numero: g.find('[name="numero"]').text(),
        },
        function(e){
          $grupos.trigger('actualizar_grupos');
          $grupos.trigger('actualizo_grupos');
        },
        function(e){
          console.log(e);
          AUX.mostrarErroresNames(g,e.responseJSON ?? {});
        }
      );
    });
    
    g.find('[data-js-borrar-grupo]').on('click',function(e){
      AUX.DELETE(
        '/actividades/grupos/borrar',
        {
          ...AUX.form_entries(g[0]),
          numero: g.find('[name="numero"]').text(),
        },
        function(e){
          $grupos.trigger('actualizar_grupos');
          $grupos.trigger('actualizo_grupos');
        },
        function(e){
          console.log(e);
          AUX.mostrarErroresNames(g,e.responseJSON ?? {});
        }
      );
    });
    
    return g;
  };
  
  $grupos.on('actualizar_grupos',function(e){
    $grupos.find('[data-js-grupo]:not([data-js-molde-grupo])').remove();
    AUX.GET('/actividades/grupos/buscar',{},function(grupos){
      grupos.forEach(function(gdata){
        const g = crearGrupo();
        Object.keys(gdata).forEach(function(k){
          if(gdata[k] !== undefined){
            g.find(`input[name="${k}"],select[name="${k}"],textarea[name="${k}"]`).val(gdata[k]);
            g.find(`span[name="${k}"]`).text(gdata[k]);
          }
        });
        $grupos.find('[data-js-listado-grupos]').append(g);
        g.show();
      });
    },function(e){
      console.log(e);
    });
  });
  
  $grupos.find('[data-js-agregar-grupo]').on('click',function(e){
    const g = crearGrupo();
    $grupos.find('[data-js-listado-grupos]').prepend(g);
    g.show();
    $grupos.find('[data-js-listado-grupos]').scrollTop();
  });
})});
