@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel='stylesheet' href='/css/bootstrap-datetimepicker.min.css'/>
@endsection

<div class="row">
  <div class="col-md-6">
    <div class="row">
      <div class="panel panel-default">
        <div class="panel-body actividades" style="height: 78vh;">
          @component('Actividades.actividades',compact('casinos','usuario','roles','estados','estados_completados','estados_sin_completar'))
          @endcomponent
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4>CALENDARIO</h4>
      </div>
      <div class="panel-body">
        @component('Actividades.calendario',compact('casinos','usuario','estados','estados_completados','estados_sin_completar'))
        @endcomponent
      </div>
    </div>
  </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| Actividades</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamientos</h5>
  <p>
    Actividades y sus tareas.
  </p>
</div>
@endsection

@section('scripts')
<script src='/js/moment.min.js'></script>
<script src='/js/fullcalendar.min.js'></script>
<script src='/js/locale-all.js'></script>
<script src="/js/gcal.min.js" charset="utf-8"></script>

<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="/js/Actividades/index.js?3" type="module"></script>
@endsection
