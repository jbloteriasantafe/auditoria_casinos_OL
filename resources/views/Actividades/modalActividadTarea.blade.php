@component('Components/include_guard',['nombre' => 'modal-actividad-tarea'])
<style>
  
  .modal-actividad-tarea .botones button{
    cursor: pointer;
    text-shadow: 0 0 0.4vmin white;
    padding: 0.4vmin 0.8vmin;
    margin: 0.2vmin;
    float: left;
  }
  
  .modal-actividad-tarea .botones button:hover {
    box-shadow: 0px 0px 0.35vmin blue;
  }
  
  .modal-actividad-tarea .boton_editar {
    background-color: #ffbc40;
  }
  .modal-actividad-tarea .boton_borrar {
    background-color: #ef3e42;
    float: right !important;
  }
  .modal-actividad-tarea .btn.activo {
    mix-blend-mode: difference;
  }
  
  .modal-actividad-tarea a.sin_decoracion_de_link {
    text-decoration: none; 
    color: unset;
  }
  
  .modal-actividad-tarea .guardar {
    background-color: #6dc7be !important;
    color: white;
  }
  
  .modal-actividad-tarea .guardar_tareas {
    background-color: #6dc7be !important;
    color: white;
  }
  
  .modal-actividad-tarea ul.lista_roles {
    list-style-type: none;
    padding-left: 0;
    user-select: none;
  }
  
  .modal-actividad-tarea ul.lista_roles li:hover {
    cursor: pointer;
    background: rgb(0,0,0,0.1);
  }
  
  .modal-actividad-tarea input[type="checkbox"][readonly],
  .modal-actividad-tarea input[type="checkbox"][disabled] {
    filter: invert(15%);
  }
  
  .modal-actividad-tarea select[readonly],
  .modal-actividad-tarea input[readonly]
  {/* FIX select sigue usable cuando esta en readonly */
    pointer-events: none;
  }
</style>
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'modal-actividad-tarea',
  'attrs_modal' => 'data-js-modal-actividad-tarea data-tipo="'.($es_actividad? 'actividad' : 'tarea').'"',
  'estilo_cabecera' => 'color: white;background: #FFB74D;',
  'grande' => 80,
])
  @slot('titulo')
    @if($es_actividad)
    Actividad
    @else
    Tarea
    @endif
     N° <span name="numero">XXXXXXXXXXXX</span>
  @endslot
  @slot('cuerpo')
  <form class="expandido">  
    <div class="row">
      <div class="col-md-12 botones">
        @if($es_actividad)
        <button data-ver="actividad" type="button" class="btn boton_borrar" data-js-eliminar data-js-ver="visualizando" title="BORRAR" ><i class="fa fa-fw fa-trash-alt"></i></button>
        @endif
        <button type="button" class="btn boton_editar" data-js-editar data-js-ver="visualizando" title="EDITAR"><i class="fa fa-fw fa-pencil-alt"></i></button>
        <button type="button" class="btn boton_historial" data-js-historial data-js-ver="visualizando,historial" title="HISTORIAL"><i class="fa fa-fw fa-clock"></i></button>
      </div>
      <div class="row" data-js-ver="historial">
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
    <div>
      <span>Generar tareas</span>
      <input type="checkbox" name="generar_tareas" data-js-toggle-generar data-js-ver="creando,editando,visualizando,historial" data-js-habilitar="creando,editando" data-js-cambio-esconder-guardar>
      <div class="col-md-12" style="display: flex;" style="display: none;" data-js-datos-generar>
        <div style="flex: 1;">
          <span>Cada: </span>
          <div style="display: flex;">
            <input class="form-control" data-js-habilitar='creando,editando' name="cada_cuanto" data-js-cambio-esconder-guardar>
            <select class="form-control" data-js-habilitar='creando,editando' name="tipo_repeticion"  data-js-cambio-esconder-guardar>
              <option value="" default>- Seleccionar -</option>
              <option value="d">Días</option>
              <option value="m">Meses</option>
            </select>
          </div>
        </div>
        <div style="flex: 1;">
          <span>Hasta: </span>
          @component('Components/inputFecha',[
            'attrs' => "name='hasta' data-js-cambio-esconder-guardar",
            'attrs_dtp' => "data-js-habilitar='creando,editando'"
          ])
          @endcomponent
        </div>
      </div>
    </div>
    @endif
    <div class="row">
      <div class="col-md-12">
        Contenido
        <textarea name="contenido" class="form-control" style="width: 100%;resize: vertical;" data-js-habilitar='creando,editando'></textarea>
      </div>
    </div>
    <div class="row">
      <br>
      <div class="col-md-12">
        <span>Adjuntos</span>
        <div data-js-archivos style="display: flex;flex-wrap: wrap;">
        </div>
      </div>
    </div>
    <div class="row">
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
    <div class="row">
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
    <div class="row">
      <br>
      <div class="col-md-12" data-js-ver="visualizando,creando,editando,historial">
        <span>TAGS API</span>
        <input class="form-control" name="tags_api" value="" data-js-habilitar="creando,editando">
      </div>
    </div>
    @endif
    @endif
    <div>
      <span>Creado: </span>
      <span name="user_created" value="{{$usuario->id_usuario}}">{{$usuario->nombre}}</span>
      <span name="created_at">{{date('Y-m-d')}}</span>
      <span> | Modificado: </span>
      <span name="user_modified" value="{{$usuario->id_usuario}}">{{$usuario->nombre}} </span>
      <span name="modified_at">{{date('Y-m-d')}}</span>
    </div>
    <input type="file" multiple data-js-selecciono-archivos style="position: absolute; top: -1000px; left: -1000px;visiblity: hidden;">
    <div class="row">
      <br>
      <div class="col-md-12">
        <button type="button" class="btn guardar" data-js-guardar data-generar_tareas="0" data-js-ver="creando,editando">GUARDAR</button>
        @if($es_actividad)
        <button type="button" class="btn guardar_tareas" data-js-guardar data-generar_tareas="1" data-js-ver="creando,editando">GUARDAR Y GENERAR TAREAS</button>
        @endif
        <button type="button" class="btn" data-js-adjuntar data-js-ver="creando,editando">ADJUNTAR</button>
        <button type="button" class="btn" data-js-cancelar data-js-ver="editando">CANCELAR</button>
      </div>
    </div>
  </form>
  @endslot
@endcomponent
