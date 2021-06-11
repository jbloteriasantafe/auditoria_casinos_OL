@extends('includes.dashboard')
<?php
use Illuminate\Http\Request;
?>

@section('estilos')

@endsection

@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default" style="height:650px; padding-top:100px;">
            <div class="panel-heading" style="text-align:center;">
                <h4>¿QUÉ PLATAFORMA DESEA VER?</h4>
            </div>
            <div class="panel-body" style="text-align:center;">
                <img src="/img/logos/casinos_gris.png" alt="" width="250px" style="margin-bottom:40px; margin-top:20px;">

                <div class="row">
                    <div class="col-md-4 col-md-offset-4">
                        <select id="buscadorPlataforma" class="form-control" name="">
                            <option value="" selected>- Seleccione la plataforma -</option>
                            @foreach($plataformas as $p)
                            <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                            @endforeach
                        </select>
                        <br>
                        <button id="btn-buscar" class="btn btn-infoBuscar" type="button" style="width:100%;">VER</button>
                    </div>
                </div>
                <br>
            </div>
        </div>
    </div> <!-- col-md-4 -->
</div>

<div class="modal fade" id="modalPlataforma" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:70%;">
    <div class="modal-content">
      <div class="modal-header" style="background:#304FFE;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title" style="color: #fff; text-align:center">ESTADO DE PLATAFORMA</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <div class="row">
              <div class="col-md-12" style="border-right:1px solid #ccc;">
                <div class="row" style="text-align:center; padding-bottom:25px;">
                  <h5>CLASIFICACIÓN DE JUEGOS</h5>
                  <div id="graficos" class="col-md-12"></div>
                </div>
                <div class="row" style="text-align:center; padding-bottom: 25px;">
                  <h5>PORCENTAJES DE DEVOLUCION</h5>
                  <div id="tablas" class="col-md-12"></div>
                </div>
              </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="tablaModelo" class="col-md-4" hidden>
  <table class="table table-fixed">
    <thead>
      <tr>
        <th class="col-md-3 dato" style="text-align: center">DATO</th>
        <th class="col-md-3" style="text-align: center">% DEV (Teórico)</th>
        <th class="col-md-3" style="text-align: center">% DEV (Esperado)</th>
        <th class="col-md-3" style="text-align: center">% DEV (Prod.)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="filaModelo">
        <td class="col-md-3 fila">fila</td>
        <td class="col-md-3 pdev" style="text-align: right">99.99%</td>
        <td class="col-md-3 pdev_esperado" style="text-align: right">99.99%</td>
        <td class="col-md-3 pdev_producido" style="text-align: right">99.11%</td>
      </tr>
    </tbody>
  </table>
</div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| AYUDA INFORMES DE PLATAFORMA</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Informes</h5>
      <p>
        En esta sección se detallan las estadisticas pertinentes a la plataforma en su totalidad
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionInformePlataforma.js" charset="utf-8"></script>

    <!-- Highchart -->
    <script src="js/highcharts.js"></script>
    <script src="js/highcharts-3d.js"></script>
    @endsection
