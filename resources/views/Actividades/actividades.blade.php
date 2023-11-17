@component('Components/include_guard',['nombre' => 'actividades'])
<style>
  .actividades select[readonly],
  .actividades input[readonly]
  {/* FIX select sigue usable cuando esta en readonly */
    pointer-events: none;
  }
  
  .actividades {
    padding-top: 1vh;
    height: 100%;
  }
  
  .actividades .botones_actividades {
    height: 3.5em;
  }
  
  .actividades .listado {
    overflow-y: scroll;
    height: calc(100% - 3.5em);
  }
  
  .actividades .actividad {
    padding: 1vh 1vh;
    box-shadow: 0px 0px 2px rgba(0, 0, 0, 0.35);
  }
  
  .actividades .actividad:not(.expandido),
  .actividades:hover .actividad:not(:hover){
    opacity: 0.70;
  }
  
  .actividades .actividad.expandido,
  .actividades:hover .actividad:hover
  {
    border: 1px solid orange;
    opacity: 1.0;
  }
  
  .actividades .actividad:not(.expandido) .solo_ver_expandido {
    display: none;
  }
  
  .actividad .botones button {
    cursor: pointer;
    text-shadow: 0 0 0.4vmin white;
    padding: 0.4vmin 0.8vmin;
    margin: 0.2vmin;
    float: left;
  }
  .actividad .botones button:hover {
    box-shadow: 0px 0px 0.35vmin blue;
  }
  
  .actividad .boton_realizar {
    background-color: #0F9D58;  
  }
  .actividad .boton_ver {
    background-color: #6dc7be;
  }
  .actividad .boton_editar {
    background-color: #ffbc40;
  }
  .actividad .boton_borrar {
    background-color: #ef3e42;
    float: right !important;
  }
  .actividades .btn.activo {
    mix-blend-mode: difference;
  }
  
  .actividades .form-control[disabled],.form-control[readonly]{
    background: rgba(0, 0, 0, 0.05);
    border: 0;
    box-shadow: unset;
  }
  
  a.sin_decoracion_de_link {
    text-decoration: none; 
    color: unset;
  }
  
  .actividad .guardar {
    background-color: #6dc7be !important;
    color: white;
  }
  
  .actividades .tabs {
    margin-bottom: 1% !important;
  }
</style>
@endcomponent

<div id="{{uniqid()}}" class="actividades row" data-js-actividades>
  @component('Components/tabs')
  @slot('tabs')
  <div>
    Tareas
  </div>
  <div>
    Actividades
  </div>
  @endslot
  @slot('tab_contents')
  @foreach([0,1] as $es_actividad)
  <div style="width: 100%;height: 100%;">
    @if($es_actividad)
    <div class="botones_actividades col-md-12">
      <button type="button" data-js-agregar><i class="fa fa-fw fa-plus"></i></button>
      <div style="float: right;">
        <input class="form-control" data-js-fecha-seleccionada value="{{date('Y-m-d')}}" data-fecha="{{date('Y-m-d')}}" disabled> 
      </div>
    </div>
    @endif
    <div class="listado col-md-12" data-js-listado-son-actividades="{{$es_actividad}}" class="row">
      <i class="fa fa-spinner fa-spin"></i>
    </div>
  </div>
  @endforeach
  @endslot
  @endcomponent
  
  <div hidden>
    @foreach([0,1] as $es_actividad)
    <form class="actividad expandido" data-js-molde-actividad data-js-actividad data-es-actividad="{{$es_actividad}}">  
      <div class="row">
        <div class="col-md-12 botones">
          @if($es_actividad)
          <button type="button" class="btn boton_borrar" data-js-eliminar data-js-ver="creando,visualizando" title="BORRAR" ><i class="fa fa-fw fa-trash-alt"></i></button>
          @endif
          <button type="button" class="btn boton_ver" data-js-expandir-contraer data-js-ver="visualizando" title="VER"><i class="fa fa-fw fa-search-plus"></i></button>
          <button type="button" class="btn boton_editar" data-js-editar data-js-ver="visualizando" title="EDITAR"><i class="fa fa-fw fa-pencil-alt"></i></button>
          <button type="button" class="btn boton_historial" data-js-historial data-js-ver="visualizando,historial" title="HISTORIAL"><i class="fa fa-fw fa-clock"></i></button>
          <span name="numero" style="float: left;opacity: 0.8;">---</span>
        </div>
        <div class="row solo_ver_expandido" data-js-ver="historial">
          <div class="col-md-12">
            <span>Fecha de modificación</span>
            <select class="form-control" data-js-cambio-historial data-js-habilitar="historial">
            </select>
          </div>
        </div>
        <div class="col-md-8">
         Titulo
         @if($es_actividad)
         <input class="form-control" name="titulo" data-js-habilitar="creando,editando">
         @else
         <input class="form-control" name="titulo" data-js-habilitar="">
         @endif
        </div>
        <div class="col-md-4">
          Fecha 
          @component('Components/inputFecha',[
            'attrs' => "name='fecha'",
            'attrs_dtp' => "data-js-habilitar='creando,editando'"
          ])
          @endcomponent
        </div>
        <div class="col-md-12">
          Estado
          <select class="form-control" name="estado" data-js-habilitar="creando,editando">
            <option default>ABIERTO</option>
            <option>ESPERANDO RESPUESTA</option>
            <option>HECHO</option>
            <option>CERRADO SIN SOLUCIÓN</option>
            <option>CERRADO</option>
          </select>
        </div>
      </div>
      @if($es_actividad)
      <div class="row solo_ver_expandido">
        <div class="col-md-6">
          <span>Generar tareas</span>
          <input type="checkbox" name="generar_tareas" data-js-generar-tareas-toggle data-js-ver="creando,editando,visualizando,historial" data-js-habilitar="creando,editando">
          <select class="form-control" name="repetir" data-js-tipo="tarea" style="display: none;" data-js-habilitar='creando,editando'>
            <option value="" default>- Seleccionar -</option>
            <option value="d">Cada día</option>
            <option value="w">Cada semana</option>
            <option value="m">Cada mes</option>
          </select>
        </div>
        <div class="col-md-6" data-js-tipo="tarea" style="display: none;">
          Hasta
          @component('Components/inputFecha',[
            'attrs' => "name='hasta'",
            'attrs_dtp' => "data-js-habilitar='creando,editando'"
          ])
          @endcomponent
        </div>
      </div>
      @endif
      <div class="row solo_ver_expandido">
        <div class="col-md-12">
          Contenido
          <textarea name="contenido" class="form-control" style="width: 100%;resize: vertical;" data-js-habilitar='creando,editando'></textarea>
        </div>
      </div>
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12">
          <span>Adjuntos</span>
          <div data-js-archivos style="display: flex;flex-wrap: wrap;">
          </div>
        </div>
      </div>
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12">
          <button type="button" class="btn guardar" data-js-guardar data-js-ver="creando,editando">GUARDAR</button>
          <button type="button" class="btn" data-js-adjuntar data-js-ver="creando,editando">ADJUNTAR</button>
          <button type="button" class="btn" data-js-cancelar data-js-ver="editando">CANCELAR</button>
          <span>Creado: </span>
          <span name="user_created" value="{{$usuario->id_usuario}}">{{$usuario->nombre}}</span>
          <span name="created_at">{{date('Y-m-d')}}</span>
          <span> | Modificado: </span>
          <span name="user_modified" value="{{$usuario->id_usuario}}">{{$usuario->nombre}} </span>
          <span name="modified_at">{{date('Y-m-d')}}</span>
        </div>
      </div>
      <div class="row solo_ver_expandido">
        <div style="display: flex;flex-wrap: wrap;" data-js-ver="visualizando,creando,editando,historial">
          <div style="padding: 1% 1%;">
            <span>Fondo</span>
            <input name="color_fondo" type="color" data-js-habilitar="creando,editando" value="#008000">
          </div>
          <div style="padding: 1% 1%;">
            <span>Texto</span>
            <input name="color_texto" type="color" data-js-habilitar="creando,editando" value="#000000">
          </div>
          <div style="padding: 1% 1%;">
            <span>Borde</span>
            <input name="color_borde" type="color" data-js-habilitar="creando,editando" value="#008000">
          </div>
        </div>
      </div>
      @if($usuario->es_superusuario && $es_actividad)
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12">
          <span>TAGS API</span>
          <input class="form-control" name="tags_api" value="">
        </div>
      </div>
      @endif
      <input type="file" multiple data-js-selecciono-archivos style="position: absolute; top: -1000px; left: -1000px;visiblity: hidden;">
    </form>
    @endforeach
    <div data-js-archivo data-js-molde-archivo style="padding: 0vh 1vw;">
      <i class="fa fa-spinner fa-spin" data-js-cargando></i>
      <a name="nombre_archivo">ARCHIVO</a>
      <span class="borrar_archivo" data-js-borrar-archivo data-js-ver="creando,editando" data-js-habilitar="creando,editando">❌</span>
    </div>
  </div>
</div>
