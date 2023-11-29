@component('Components/include_guard',['nombre' => 'modal-eliminar'])
<style>
.modal-backdrop + .modal-backdrop {
  opacity: 0;/*FIX doble pantalla negra al abrir el modal de eliminar dentro de otro modal*/
}
.modal-eliminar {
  z-index: 2000 !important;/*Adelantar para que salga adelante de los demas modales*/
}
</style>
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'modal-eliminar',
  'attrs_modal' => 'data-js-modal-eliminar',
  'estilo_cabecera' => 'color: white;background: #db4a4a;'
])
  @slot('titulo')
  ADVERTENCIA
  @endslot
  @slot('cuerpo')
  <h6>¿Seguro que desea eliminar {!! $elemento_a_eliminar ?? 'el elemento' !!}?</h6>
  @endslot
  @slot('pie')
  <button type="button" class="btn btn-dangerEliminar" data-js-modal-eliminar-eliminar>ELIMINAR</button>
  @endslot
@endcomponent
