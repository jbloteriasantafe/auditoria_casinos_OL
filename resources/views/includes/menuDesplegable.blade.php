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