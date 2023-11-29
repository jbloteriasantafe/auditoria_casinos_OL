@component('Components/include_guard',['nombre' => 'actividades'])
<style>
  .actividades {
    padding-top: 1vh;
    height: 100%;
  }
  
  .actividades .tabs {
    margin-bottom: 1% !important;
  }
  
  .actividades .listado {
    overflow-y: scroll;
    height: calc(100% - 2em - 3em);
  }
  .actividades .mostrar_sin_completar {
    height: 2em;
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
    opacity: 0.70;
  }
  
  .actividades:hover .actividad:hover
  {
    border: 1px solid orange;
    opacity: 1.0;
  }
  
  .actividades .actividad .botones button {
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
  
  .actividad .form-control[disabled],.form-control[readonly]{
    background: rgba(0, 0, 0, 0.05);
    border: 0;
    box-shadow: unset;
  }
  
  .actividades .activididad select[readonly],
  .actividades .activididad input[readonly]
  {/* FIX select sigue usable cuando esta en readonly */
    pointer-events: none;
  }
</style>
@endcomponent

<div id="{{uniqid()}}" class="actividades row" data-js-actividades>
  @component('Components/tabs')
  @slot('tabs')
  <div data-js-tab-actividad="0">
    Tareas
  </div>
  <div data-js-tab-actividad="1">
    Actividades
  </div>
  @endslot
  @slot('tab_contents')
  @foreach([0,1] as $es_actividad)
  <div data-js-tab-content-actividad="{{$es_actividad}}" style="width: 100%;height: 100%;">
    <div class="listado col-md-12" data-js-listado-son-actividades="{{$es_actividad}}" class="row">
      <i class="fa fa-spinner fa-spin"></i>
    </div>
    @if($es_actividad)
    <div class="col-md-12 mostrar_sin_completar">
      <input data-js-cambio-mostrar-sin-completar type="checkbox" checked>
      <span>Mostrar sin completar</span>
    </div>
    <button type="button" class="btn btn-info col-md-12 agregar_actividad" data-js-agregar><i class="fa fa-fw fa-plus"></i>Nuevo</button>
    @endif
  </div>
  @endforeach
  @endslot
  @endcomponent
  
  <div hidden>
    <form class="actividad" data-js-molde-actividad data-js-actividad>  
      <div class="row">
        <div class="col-md-12 botones">
          <button type="button" class="btn boton_ver" data-js-ver-actividad title="VER"><i class="fa fa-fw fa-search-plus"></i></button>
          <span name="numero" style="float: left;opacity: 0.8;">---</span>
        </div>
        <div class="col-md-8">
         Titulo
         <input class="form-control" name="titulo" readonly>
        </div>
        <div class="col-md-4">
          Fecha 
          @component('Components/inputFecha',[
            'attrs' => "name='fecha'",
            'attrs_dtp' => 'data-readonly="true"'
          ])
          @endcomponent
        </div>
        <div class="col-md-12">
          Estado
          <select class="form-control" name="estado" readonly>
            <option default>ABIERTO</option>
            <option>ESPERANDO RESPUESTA</option>
            <option>HECHO</option>
            <option>CERRADO SIN SOLUCIÓN</option>
            <option>CERRADO</option>
          </select>
        </div>
      </div>
    </form>
  </div>
</div>

@component('Components/modalEliminar',['elemento_a_eliminar' => 'la actividad'])
@endcomponent

@component('Actividades/modalActividadTarea',['es_actividad' => 1,'roles' => $roles,'usuario' => $usuario])
@endcomponent

@component('Actividades/modalActividadTarea',['es_actividad' => 0,'roles' => $roles,'usuario' => $usuario])
@endcomponent
