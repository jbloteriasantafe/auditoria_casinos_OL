<div style="width:100%;position: absolute;z-index: 3;">
  <aside id="menuDesplegable" style="height: 100vh;width: 15%;float: left;overflow-y: scroll;" hidden>
    <ul class="menu_con_opciones_desplegado" style="margin-top: 5%;">
    @foreach($opciones as $op => $datos)
      @if(count($datos['hijos']) == 0)
        @component('includes.menuDesplegable_link',[
          'link'         => $datos['link'],
          'op'           => $op,
        ])
        @endcomponent
      @else
        @component('includes.menuDesplegable_desplegable',[
          'op'           => $op,
          'hijos'        => $datos['hijos'],
        ])
        @endcomponent
      @endif
    @endforeach
    </ul>
  </aside>
  <div style="float: left;">
    <button id="botonMenuDesplegable" type="button" class="btn" 
      data-toggle="#menuDesplegable,#oscurecerContenido,#botonDerecha,#botonIzquierda" 
      style="z-index: 4;position: absolute;">
      <i id="botonDerecha" class="fa fa-fw fa-solid fa-arrow-right"></i>
      <i id="botonIzquierda" class="fa fa-fw fa-solid fa-arrow-left" style="display: none;"></i>
    </button>
  </div>
  <div id="oscurecerContenido" style="position:absolute;z-index: 3;height: 100%;left: 15%;width: 100%;float:left;background: rgba(0,0,0,0.2);" hidden>
    &nbsp;
  </div>
</div>

<script type="module">
$(document).ready(function(){
  $('#menuDesplegable .menu_con_opciones > span,#menuDesplegable .menu_con_opciones_desplegado > span').click(function(e){
    if($(this).parent().hasClass('menu_con_opciones_desplegado')){//Si esta desplegado solo escondo todo lo por debajo
      //Submenues
      $(this).parent().find('.menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      //Padre
      $(this).parent().removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      return;
    }
    //Si hizo click en otro menu, escondo todo y desplego el arbol hasta ahi
    //Escondo todo
    $('#menuDesplegable .menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
    //Abro todos los padres
    $(this).parents('.menu_con_opciones').removeClass('menu_con_opciones').addClass('menu_con_opciones_desplegado');
  });
  $('#botonMenuDesplegable').click(function(e){
    //Busco la opcion basado en la URL y la diferencio
    const opcion_actual = $('#menuDesplegable a').filter(function(){
      return $(this).attr('href') == ("/"+window.location.pathname.split("/")[1]);
    });
    //Lo marco como que es la opción actual mostrandose
    opcion_actual.parent().toggleClass('opcion_actual');
    //Desplego la opcion
    opcion_actual.closest('.menu_con_opciones').children('span').click();
    //Muestro el menu
    $($(this).attr('data-toggle')).toggle();
  });
})
</script>