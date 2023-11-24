@component('Components/include_guard',['nombre' => 'calendario'])
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
<style>
  .calendario .fc-list-item-time {/*Hack para no mostrar "Todo el día"*/
    color: transparent;
    user-select: none;
  }
  .calendario .es_tarea {
    opacity: 0.5;
  }
  .calendario .finalizado {/*Si el evento esta terminado*/
    background: repeating-linear-gradient(45deg, transparent, transparent 3.5%, rgba(0,0,0,0.2) 3.5%, rgba(0,0,0,0.2) 6.25%);
  }
  .calendario .finalizado .fc-title {
    color: white !important;
    text-shadow: black 0 0 0.05em;
  }
</style>
@endcomponent

<div id="{{uniqid()}}" class="calendario" data-js-calendario>
  <div data-js-inner-calendario></div>
  <i class="fa fa-spinner fa-spin"></i>
</div>
