@extends('includes.dashboard')
<?php
use Illuminate\Http\Request;
?>

@section('estilos')
<style>

.tab {
  display: inline-block;
  font-family: Roboto-BoldCondensed;
  color: rgba(0, 0, 0, 0.4);
  text-align: center !important;
  border-bottom: 2px solid rgba(0, 0, 0, 0.4);
}

.tab > h4 {
  display: inline;
}

.tab[activa]{
  border-bottom: 6px solid white !important;
  color: white !important;
}

#juegosFaltantesConMovimientos >table > thead > tr > th {
  text-align: center;
}

#juegosFaltantesConMovimientos > table > tbody > tr > td {
  text-align: right;
}

.tabContent {
  text-align:center;
  padding-bottom:25px;
  overflow-y: scroll;
  max-height: 650px;
}
</style>
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
  <div class="modal-dialog" style="width:90%;">
    <div class="modal-content">
      <div class="modal-header" style="background:#304FFE; padding-bottom: 1px;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title" style="color: #fff; text-align:center">ESTADO DE PLATAFORMA</h3>
          <div style="text-align: center; margin: 0px;">
            <div class="tab" activa="activa" style="width: 10%;" div-asociado="#divGraficos">
              <h4>CLASIFICACIÓN</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divTablas">
              <h4>% DEVS</h4>
            </div>
            <div class="tab" style="width: 15%;" div-asociado="#divJuegosFaltantesConMovimientos">
              <h4>JUEGOS FALTANTES C/ MOV</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divAlertasDiariasJuegos">
              <h4>ALERTAS DIARIAS (JUEGOS)</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divAlertasDiariasJugadores">
              <h4>ALERTAS DIARIAS (JUGADORES)</h4>
            </div>
          </div>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <div class="row">
              <div class="col-md-12" style="border-right:1px solid #ccc;">
                <div id="divGraficos" class="row tabContent">
                    <h5>CLASIFICACIÓN DE JUEGOS</h5>
                    <div id="graficos" class="col-md-12"></div>
                </div>
                <div id="divTablas" class="row tabContent">
                    <h5>PORCENTAJES DE DEVOLUCION</h5>
                    <div id="tablas" class="col-md-12"></div>
                </div>
                <div id="divJuegosFaltantesConMovimientos" class="row tabContent">
                    <div id="juegosFaltantesConMovimientos" class="col-md-12">
                      <table class="col-md-12 table table-fixed">
                        <thead>
                          <tr> 
                            <th class="col-md-1">JUEGO</th>
                            <th class="col-md-1">CATEGORIA</th>
                            <th class="col-md-1">APOSTADO EFEC.</th>
                            <th class="col-md-1">APOSTADO BO.</th>
                            <th class="col-md-1">APOSTADO</th>
                            <th class="col-md-1">PREMIO EFEC.</th>
                            <th class="col-md-1">PREMIO BO.</th>
                            <th class="col-md-1">PREMIO</th>
                            <th class="col-md-1">BENEFICIO EFEC:</th>
                            <th class="col-md-1">BENEFICIO BO.</th>
                            <th class="col-md-1">BENEFICIO</th>
                            <th class="col-md-1">%DEV</th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>
                      </table>
                    </div>
                </div>
                <div id="divAlertasDiariasJuegos" class="row tabContent">
                  <div class="row" id="inputsAlertas">
                    <div class="col-md-2">
                      <h5>BENEFICIO ≷</h5>
                      <input id="inputBeneficioJuegos" type="number" class="form-control" value="150000" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>JUEGO %DEV ±</h5>
                      <input id="inputPdevJuegos" type="number" class="form-control" value="0" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>&nbsp;</h5>
                      <button id="btn-buscarAlertasJuegos" class="btn btn-infoBuscar" type="button" style="width:100%;">BUSCAR</button>
                    </div>
                  </div>
                  <br>
                </div>
                <div id="divAlertasDiariasJugadores" class="row tabContent">
                  <div class="row" id="inputsAlertas">
                    <div class="col-md-2">
                      <h5>BENEFICIO ≷</h5>
                      <input id="inputBeneficioJugadores" type="number" class="form-control" value="150000" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>&nbsp;</h5>
                      <button id="btn-buscarAlertasJugadores" class="btn btn-infoBuscar" type="button" style="width:100%;">BUSCAR</button>
                    </div>
                  </div>
                  <br>
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

<div id="moldeAlertaJuegos" class="row tablaAlertasJuegos" style="border: 1px solid #eee;" hidden>
  <h5>ALERTAS <span class="moneda">MONEDA</span></h5>
  <div class="row">
    <table class="col-md-12 table table-fixed">
      <thead>
        <tr>
          <th class="col-md-1" style="text-align: center">FECHA</th>
          <th class="col-md-1" style="text-align: center">CÓDIGO</th>
          <th class="col-md-2" style="text-align: center">CATEGORIA</th>
          <th class="col-md-2" style="text-align: center">APUESTA</th>
          <th class="col-md-2" style="text-align: center">PREMIO</th>
          <th class="col-md-2" style="text-align: center">BENEFICIO</th>
          <th class="col-md-1" style="text-align: center">%DEV</th>
          <th class="col-md-1" style="text-align: center">%DEV (Juego)</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
  <div class="row paginado">
    <div class="col-md-2 col-md-offset-5">
      <button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button>
      P <span class="previewPage">9</span>/<span class="previewTotal">99</span>
      <button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button>
    </div>
  </div>
  <table hidden>
    <tr class="moldeFilaAlerta">
      <td class="col-md-1 fecha"  style="text-align: center">AAAA-MM-DD</td>
      <td class="col-md-1 codigo" style="text-align: center">9999</td>
      <td class="col-md-2 categoria" style="text-align: center">CAT</td>
      <td class="col-md-2 apuesta" style="text-align: right">123456.78</td>
      <td class="col-md-2 premio" style="text-align: right">98765.43</td>
      <td class="col-md-2 beneficio" style="text-align: right">-9999.99</td>
      <td class="col-md-1 pdev" style="text-align: right">99.999</td>
      <td class="col-md-1 pdev_juego" style="text-align: right">99.999</td>
    </tr>
  </table>
</div>


<div id="moldeAlertaJugadores" class="row tablaAlertasJugadores" style="border: 1px solid #eee;"  hidden>
  <h5>ALERTAS <span class="moneda">MONEDA</span></h5>
  <div class="row">
    <table class="col-md-12 table table-fixed">
      <thead>
        <tr>
          <th class="col-md-2" style="text-align: center">FECHA</th>
          <th class="col-md-2" style="text-align: center">ID</th>
          <th class="col-md-2" style="text-align: center">APUESTA</th>
          <th class="col-md-2" style="text-align: center">PREMIO</th>
          <th class="col-md-2" style="text-align: center">BENEFICIO</th>
          <th class="col-md-2" style="text-align: center">%DEV</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
  <div class="row paginado">
    <div class="col-md-2 col-md-offset-5">
      <button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button>
      P <span class="previewPage">9</span>/<span class="previewTotal">99</span>
      <button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button>
    </div>
  </div>
  <table hidden>
    <tr class="moldeFilaAlerta">
      <td class="col-md-2 fecha"  style="text-align: center">AAAA-MM-DD</td>
      <td class="col-md-2 id" style="text-align: center">9999</td>
      <td class="col-md-2 apuesta" style="text-align: right">123456.78</td>
      <td class="col-md-2 premio" style="text-align: right">98765.43</td>
      <td class="col-md-2 beneficio" style="text-align: right">-9999.99</td>
      <td class="col-md-2 pdev" style="text-align: right">99.999</td>
    </tr>
  </table>
</div>


<table hidden>
  <tr id="filaEjemploJuegosFaltantesConMovimientos">
    <td class="col-md-1 cod_juego" style="text-align: center;">JUEGO123</td>
    <td class="col-md-1 categoria" style="text-align: center;">CAT321</td>
    <td class="col-md-1 apuesta_efectivo">1111.11</td>
    <td class="col-md-1 apuesta_bono">2222.22</td>
    <td class="col-md-1 apuesta">3333.33</td>
    <td class="col-md-1 premio_efectivo">4444.44</td>
    <td class="col-md-1 premio_bono">5555.55</td>
    <td class="col-md-1 premio">6666.66</td>
    <td class="col-md-1 beneficio_efectivo">7777.77</td>
    <td class="col-md-1 beneficio_bono">8888.88</td>
    <td class="col-md-1 beneficio">9999.99</td>
    <td class="col-md-1 pdev">99.123456%</td>
  </tr>
</table>

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
