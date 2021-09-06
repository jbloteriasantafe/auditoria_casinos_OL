@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/fileinput.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

<?php
setlocale(LC_TIME, 'es_ES.UTF-8');
?>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-body" style="text-align:center;">
        <div class="row">
          <br>
          <div class="row">
            <div id="divBeneficiosMensuales">
            </div>
          </div>
          <hr>
          <div class="row">
            <div id="divBeneficiosMensualesEnMeses">
            </div>
          </div>
        </div>
      </div>
  </div>
</div>

<datalist id="beneficiosMensuales">
  @foreach($beneficios_mensuales as $bm)
  <option data-plataforma="{{$bm->plataforma}}" data-año="{{$bm->año}}" data-mes="{{$bm->mes}}">{{$bm->beneficio}}</option>
  @endforeach
</datalist>

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| JUEGOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Informes Generales</h5>
      <p>
        Estadisticas generales de las plataformas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')

    <!-- JavaScript personalizado -->
    <script src="js/highcharts.js"></script>
    <script src="js/highcharts-3d.js"></script>
    <script src="/js/seccionInformesGenerales.js" charset="utf-8"></script>
    @endsection
