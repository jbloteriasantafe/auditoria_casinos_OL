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
  
  .actividades .mostrar_sin_completar {
    height: 2em;
  }
  .actividades .agregar_actividad {
    height: 3em;
  }
  
  .actividades .listado {
    overflow-y: scroll;
    height: calc(100% - 2em - 3em);
  }
  
  .actividades .actividad {
    padding: 1vh 1vh;
    margin: 1vh 0vh;
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
  
  .actividad .guardar_tareas {
    background-color: #6dc7be !important;
    color: white;
  }
  
  .actividades .tabs {
    margin-bottom: 1% !important;
  }
  
  .actividades .actividad ul.lista_roles {
    list-style-type: none;
    padding-left: 0;
    user-select: none;
  }
  
  .actividades .actividad ul.lista_roles li:hover {
    cursor: pointer;
    background: rgb(0,0,0,0.1);
  }
  
  .actividades .actividad input[type="checkbox"][readonly],
  .actividades .actividad input[type="checkbox"][disabled] {
    filter: invert(15%);
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
            'attrs_dtp' => $es_actividad? "data-js-habilitar='creando,editando'" : "data-js-habilitar=''"
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
      <div class="solo_ver_expandido">
        <span>Generar tareas</span>
        <input type="checkbox" name="generar_tareas" data-js-generar-tareas-toggle data-js-ver="creando,editando,visualizando,historial" data-js-habilitar="creando,editando">
        <div class="col-md-12" style="display: flex;" style="display: none;" data-js-tipo="tarea">
          <div style="flex: 1;">
            <span>Cada: </span>
            <div style="display: flex;">
              <input class="form-control" data-js-habilitar='creando,editando' name="cada_cuanto">
              <select class="form-control" name="tipo_repeticion" data-js-habilitar='creando,editando'>
                <option value="" default>- Seleccionar -</option>
                <option value="d">Días</option>
                <option value="m">Meses</option>
              </select>
            </div>
          </div>
          <div style="flex: 1;">
            <span>Hasta: </span>
            @component('Components/inputFecha',[
              'attrs' => "name='hasta'",
              'attrs_dtp' => "data-js-habilitar='creando,editando'"
            ])
            @endcomponent
          </div>
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
      @if($es_actividad)
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12" data-js-ver="visualizando,creando,editando,historial">
          <span>Visible por:</span>
          <ul class="lista_roles">
            @foreach($roles as $rol)
            <li data-js-click-evento>
              <input data-js-recibir-click-evento type="checkbox" value="{{$rol['id_rol']}}" name="roles[]" data-js-habilitar="creando,editando" checked>
              <span>{{$rol['descripcion']}}</span>
            </li>
            @endforeach
          </ul>
        </div>
      </div>
      @if($usuario->es_superusuario)
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12" data-js-ver="visualizando,creando,editando,historial">
          <span>TAGS API</span>
          <input class="form-control" name="tags_api" value="" data-js-habilitar="creando,editando">
        </div>
      </div>
      @endif
      @endif
      <div class="row solo_ver_expandido">
        <br>
        <div class="col-md-12">
          <button type="button" class="btn guardar" data-js-guardar data-cambiar_tareas="0" data-js-ver="creando,editando">GUARDAR</button>
          @if($es_actividad)
          <button data-js-tipo="tarea" type="button" class="btn guardar_tareas" data-js-guardar data-cambiar_tareas="1" data-js-ver="creando,editando">GUARDAR Y CAMBIAR TAREAS</button>
          @endif
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

@component('Components/modalEliminar',['elemento_a_eliminar' => 'la actividad'])
@endcomponent
