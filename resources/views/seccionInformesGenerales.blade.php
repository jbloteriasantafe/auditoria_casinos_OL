@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/fileinput.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
  <style>
    .titulo_ala_highchart{
      color: #333333;
      font-size: 18px;
      font-weight: bold;
      fill: #333333;
      font-family: Roboto-Regular;
    }
    .texto_ala_celda{
      font-size: 14px;
      font-weight: bold;
      font-family: Roboto-Regular;
      color: #aaa !important;
    }
    .celda{
      width: 14.2857%;
      text-align: center;
      margin: 0px;padding: 0px;
      display: inline-block;
      float: left;
    }
  </style>
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
          <hr>
          <div class="row">
            <div id="divCalendarioActividadesCompletadas">
            </div>
          </div>
          <hr>
        </div>
      </div>
  </div>
</div>

<div id="moldeMes" style="width: 25%;border-top: 1px solid #ddd;border-right: 1px solid #ddd;" hidden>
  <div class="mesTitulo texto_ala_celda celda" style="width: 100%;">
    MES AÑO
  </div>
</div>


<datalist id="beneficiosMensuales">
  @foreach($beneficios_mensuales as $bm)
  <option data-plataforma="{{$bm->plataforma}}" data-año="{{$bm->año}}" data-mes="{{$bm->mes}}">{{$bm->beneficio}}</option>
  @endforeach
</datalist>

<datalist id="estadoDia">
  @foreach($estado_dia as $d => $e)
  <option fecha="{{$d}}">{{$e}}</option>
  @endforeach
</datalist>

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">Estadísticas Generales</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
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
