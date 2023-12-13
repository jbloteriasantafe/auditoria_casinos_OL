@component('Components/include_guard',['nombre' => 'actividades'])
<style>
  .actividades {
    padding-top: 1vh;
    height: calc(78vh - 2.5em);
  }
  
  .actividades .tabs {
    margin-bottom: 1% !important;
  }
  
  .actividades .listado {
    overflow-y: scroll;
    height: calc(100% - 2em - 3em);
  }
  
  .actividades .agregar_actividad {
    height: 3em;
  }
  
  .actividades .actividad {
    padding: 1vh 1vh;
    margin: 1vh 0vh;
    box-shadow: 0px 0px 2px rgba(0, 0, 0, 0.35);
  }
  
  .actividades:hover .actividad:not(:hover){
    opacity: 0.80;
  }
  
  .actividades:hover .actividad:hover {
    border: 1px solid orange;
    opacity: 1.0;
    font-size: 1.25em;
  }
  
  .actividades .actividad button {
    cursor: pointer;
    text-shadow: 0 0 0.4vmin white;
    padding: 0.4vmin 0.8vmin;
    margin: 0.2vmin;
    float: left;
  }
  
  .actividades .actividad .botones button:hover {
    box-shadow: 0px 0px 0.35vmin blue;
  }
  
  .actividad .boton_ver {
    background-color: #6dc7be;
  }
  
  .actividades hr {
    border-color: rgb(0,0,0,0.8);
    border-width: 2px;
    border-style: inset;
    margin: 0;
  }
</style>
@endcomponent

<?php $mostrar_tab_grupos = $usuario->es_superusuario; ?>

<div id="{{uniqid()}}" class="actividades row" data-js-actividades>
  @component('Components/tabs')
  @slot('tabs')
  <div data-js-tab-actividad="0">
    Tareas
  </div>
  <div data-js-tab-actividad="1">
    Actividades
  </div>
  @if($mostrar_tab_grupos)
  <div>
    Grupos
  </div>
  @endif
  @endslot
  
  @slot('tab_contents')
  @foreach([0,1] as $es_actividad)
  <div data-js-tab-content-actividad="{{$es_actividad}}" style="width: 100%;height: 100%;">
    <div class="listado col-md-12" data-js-listado-son-actividades="{{$es_actividad}}" class="row">
      <i class="fa fa-spinner fa-spin"></i>
    </div>
    @if($es_actividad)
    <button type="button" class="btn btn-info col-md-12 agregar_actividad" data-js-agregar><i class="fa fa-fw fa-plus"></i>Nuevo</button>
    @endif
  </div>
  @endforeach
  
  @if($mostrar_tab_grupos)
  <div style="width: 100%;height: 100%;">
    @component('Actividades.grupos')
    @endcomponent
  </div>
  @endif
  @endslot
  
  @endcomponent
  
  <div hidden>
    <form class="actividad" data-js-molde-actividad data-js-actividad style="width: 100%;display: flex;flex-wrap: wrap;align-content: center;">  
      <div style="flex: 0.5;display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <button type="button" class="btn boton_ver" data-js-ver-actividad title="VER"><i class="fa fa-fw fa-search-plus"></i></button>
      </div>
      <div style="flex: 1;display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <span name="numero" style="padding: 0.5em;">&nbsp;</span>
      </div>
      <div style="display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <i class="fa fa-check" hidden data-js-estados="{{implode(',',$estados_completados)}}" style="color: green;display: none;"></i>
        <i class="fa fa-times" hidden data-js-estados="{{implode(',',$estados_sin_completar)}}" style="color: red;display: none;"></i>
      </div>
      <div style="flex: 1;display: flex;flex-direction: column;justify-content: center;">
        <span name="estado" style="padding: 0.5em;">&nbsp;</span>
      </div>
      <div style="flex: 3;display: flex;flex-direction: column;justify-content: center;">
        <span name="titulo" style="overflow-wrap: anywhere;padding: 0.5em;">&nbsp;</span>
      </div>
    </form>
    <h4 data-js-molde-fecha-separador style="text-align: center;color: #606060;">XXXX-XX-XX</h4>
  </div>
</div>

@component('Components/modalEliminar',['elemento_a_eliminar' => 'la actividad'])
@endcomponent

@component('Actividades/modalActividadTarea',['es_actividad' => 1,'roles' => $roles,'usuario' => $usuario])
@endcomponent

@component('Actividades/modalActividadTarea',['es_actividad' => 0,'roles' => $roles,'usuario' => $usuario])
@endcomponent
