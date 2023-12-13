@component('Components/include_guard',['nombre' => 'grupos_actividades'])
<style>
  .grupos {
    width: 100%;
    height: 100%;
  }
  .grupos .grupo {
    padding: 1vh 1vh;
    margin: 1vh 0vh;
    box-shadow: 0px 0px 2px rgba(0, 0, 0, 0.35);
  }
  .grupos .listado_grupos {
    height: calc(100% - 2.5em);
    overflow-y: scroll;
  }
  .grupos .agregar_grupo {
    height: 2.5em;
  }
</style>
@endcomponent

<div class="grupos" data-js-grupos>
  <div class="listado_grupos" data-js-listado-grupos>
  </div>
  <button type="button" class="btn btn-info col-md-12 agregar_grupo" data-js-agregar-grupo><i class="fa fa-fw fa-plus"></i>Nuevo</button>
  <form hidden class="grupo" data-js-molde-grupo data-js-grupo style="width: 100%;">
    <div style="width: 100%;display: flex;flex-wrap: wrap;align-content: center;">
      <span hidden name="numero"></span>
      <div style="flex: 0.8;display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <input name="nombre" class="form-control" style="padding: 0.5em;" value="" placeholder="NOMBRE GRUPO">
      </div>
      <div style="flex: 1;display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <input name="usuarios" class="form-control" style="padding: 0.5em;" value="" placeholder="usuario1, usuario2, ...">
      </div>
      <div style="flex: 0.5;display: flex;flex-direction: column;justify-content: center;align-items: center;">
        <div>
          <button type="button" class="btn" data-js-guardar-grupo title="GUARDAR">GUARDAR</button>
          <button type="button" class="btn boton_borrar" data-js-borrar-grupo title="BORRAR" style="background-color: #ef3e42;"><i class="fa fa-fw fa-trash-alt"></i></button>
        </div>
      </div>
    </div>
  </form>
</div>
