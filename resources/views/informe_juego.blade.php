@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/lista-datos.css">
  <style>
  h5 {
    margin-bottom: 0px;
  }
  .infoResaltada {
  padding-left: 12px;
  font-family:Roboto-Condensed;
  display:block;
  }
  #codigo {
    font-size: 40px;
    position: relative; top:-8px;
  }
  #proveedor {
    font-size:16px;
    position: relative; top:-8px;
  }
  #plataforma,#categoria, #estado {
    font-size:20px;
  }
  #denominacion, #moneda, #devolucion {
    font-size:18px;
  }
  #apuesta,#premio,#pdev {
    display: inline;
  }
  #producido {
    display: inline;
    color: #00E676;
  }
  #producidoEsperado {
    display: inline;
    color: #E6C200;
  }
  .filaHistorial {
    padding: 20px 10px 10px 10px;
    margin-left: 10px;
    position: relative;
    height: auto;
    border-left: 3px solid #55f;
  }

  .filaHistorial:hover .circuloTiempo {
    transform: scale(1.2);
  }
  .filaHistorial:hover .link {
    opacity: 1;
  }
  .razon {
    font-size: 20px;
  }
  .circuloTiempo {
    width: 15px; height: 15px; border-radius: 50%;
    background-color: #55f;
    position: absolute;
    left:-9px; top:30px;
  }
  .link {
    color: #55f;
    margin-left: 25px;
    transform: scale(1.8);
    opacity: 0;
    cursor: pointer;
  }
  .tooltip-inner{
    font-size: 100% !important;
    text-align: justify !important;
    text-justify: inter-word !important;
  }

  th.fecha,td.fecha,th.moneda,td.moneda,th.jugadores,td.jugadores {
    width: 7% !important;
  }
  #tablaHeadProducidos > thead > tr > th {
    text-align: center;
    width: 7.9%;
  }
  #tablaBodyProducidos > tbody > tr > td {
    text-align: right;
    width: 7.9%;
  }
  .filaResaltada {
    font-weight: bold;
    background-color: lightgreen;
  }
  </style>
@endsection

@section('contenidoVista')

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default" style="height:650px; padding-top:100px;">
                <div class="panel-heading" style="text-align:center;">
                    <h4>¿QUÉ JUEGO DESEA VER?</h4>
                </div>
                <div class="panel-body" style="text-align:center;">
                    <img src="/img/logos/tragaperras.png" alt="" width="250px" style="margin-bottom:40px; margin-top:20px;">
                    <div class="row">
                        <div class="col-md-4 col-md-offset-4">
                          <select id="selectPlataforma" class="form-control" name="">
                              <option value="" style="text-align: center;">- Seleccione la plataforma -</option>
                              @foreach($plataformas as $p)
                              <option value="{{$p->id_plataforma}}" data-codigo="{{$p->codigo}}" style="text-align: center;">{{$p->nombre}}</option>
                              @endforeach
                          </select>
                          <br>
                          <input id="inputJuego" class="form-control" type="text" placeholder="CÓDIGO DEL JUEGO" style="text-align: center;" disabled>
                          <br>
                          <button id="btn-buscarJuego" class="btn btn-infoBuscar" type="button" style="width:100%;">VER DETALLES</button>
                        </div>
                    </div>
                    <br>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalJuegoContable" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:90%;">
             <div class="modal-content">
                  <div class="modal-header" style="background:#304FFE;">
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                      <h3 class="modal-title" style="color: #fff; text-align:center">DETALLES CONTABLES DEL JUEGO</h3>
                  </div>
                <div id="colapsado" class="collapse in">
                  <div class="modal-body" style="height: 750px;overflow-y: scroll;">
                    <div class="row" style="padding-bottom:12px;">
                        <div class="col-md-3" style="text-align:center; border-right: 1px solid #ccc;">
                            <span id="codigo" class="infoResaltada">CODIGO</span>
                            <img src="/img/logos/tragaperras_blue.png" alt="" width="140px;" style="position:relative;left:10px;top:-14px;">
                            <span id="proveedor" class="infoResaltada">PROVEEDOR</span>
                            <span id="tipo" class="infoResaltada">ESCRITORIO/MÓVIL</span>
                        </div>
                        <div class="col-md-9" style="text-align:center;">
                            <div class="row" style="padding-top:10px;padding-bottom:10px;border-bottom: 1px solid #ccc;">
                              <div class="col-md-4">
                                <h5>PLATAFORMA</h5>
                                <span id="plataforma" class="infoResaltada">PLATAFORMA</span>
                              </div>
                              <div class="col-md-4">
                                <h5>ESTADO</h5>
                                <span id="estado" class="infoResaltada">ESTADO</span>
                              </div>
                              <div class="col-md-4">
                                <h5>CATEGORÍA</h5>
                                <span id="categoria" class="infoResaltada">CATEGORÍA</span>
                              </div>
                            </div>
                            <div class="row" style="padding-top:20px;">
                              <div class="col-md-4">
                                  <h5>DENOMINACIÓN</h5>
                                  <span id="denominacion" class="infoResaltada">DENOMINACIÓN</span>
                              </div>
                              <div class="col-md-4">
                                  <h5>MONEDA</h5>
                                  <span id="moneda" class="infoResaltada">MONEDA</span>
                              </div>
                              <div class="col-md-4">
                                  <h5>% DEVOLUCIÓN</h5>
                                  <span id="devolucion" class="infoResaltada">PDEV%</span>
                              </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="border-top: 1px solid #ddd;padding:10px 0px 15px 0px;">
                        <div class="col-md-9" style="text-align:center; border-right: 1px solid #ccc;">
                          <div class="row">
                              <div class="col-md-2">
                                <h5>APUESTA TOTAL</h5>
                                <span id="apuesta" class="infoResaltada">9999</span>
                              </div>
                              <div class="col-md-2">
                                <h5>PREMIO TOTAL</h5>
                                <span id="premio" class="infoResaltada">99999</span>
                              </div>
                              <div class="col-md-2">
                                <h5>BENEF. TOTAL</h5>
                                <span id="producido" class="infoResaltada">99999</span>
                              </div>
                              <div class="col-md-2">
                                <h5>% DEV</h5>
                                <span id="pdev" class="infoResaltada">99.999</span>
                              </div>
                              <div class="col-md-3">
                                <h5>BENEF. (esp.)</h5>
                                <span id="producidoEsperado" class="infoResaltada">99999</span>
                              </div>
                              <div class="col-md-12">
                                  <div id="graficoSeguimientoProducido"></div>
                              </div>
                          </div>
                        </div>
                        <h5 style="text-align: center;">HISTORIAL</h5>
                        <div id="listaHistorial" class="col-md-3" style="text-align:left;height: 250px;overflow-y: scroll;">
                            <div id="hist" class="filaHistorial" hidden>
                                <div class="circuloTiempo"></div>
                                <span class="fecha">88-ABC-9999</span>
                                <span class="motivo infoResaltada">RAZÓN CAMBIO</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                      <button id="prevPreview" type="button" class="btn btn-link col-md-1" disabled="disabled"><i class="fas fa-arrow-left"></i></button>
                      <div class="col-md-offset-5 col-md-1">
                        P <span id="previewPage">9</span>/<span id="previewTotal">99</span>
                        <div class="form-check form-switch">
                          <input class="form-check-input" type="checkbox" id="verTodosProducidos">
                          <label class="form-check-label" for="verTodosProducidos">Ver todos</label>
                        </div>
                      </div>
                      <button id="nextPreview" type="button" class="btn btn-link col-md-offset-4 col-md-1"><i class="fas fa-arrow-right"></i></button>
                      <div class="col-md-12">
                        <table id="tablaHeadProducidos" class="table" style="margin-bottom: 0px;">
                          <thead>
                            <tr>
                              <th class="fecha">FECHA</th>
                              <th class="moneda">MONEDA</th>
                              <th class="categoria">CATEGORÍA INFORMADA</th>
                              <th class="jugadores">JUGADORES</th>
                              <th class="apuesta_efectivo">APUESTA (Ef)</th>
                              <th class="apuesta_bono">APUESTA (Bo)</th>
                              <th class="apuesta">APUESTA</th>
                              <th class="premio_efectivo">PREMIO (Ef)</th>
                              <th class="premio_bono">PREMIO (Bo)</th>
                              <th class="premio">PREMIO</th>
                              <th class="beneficio_efectivo">BENEFICIO (Ef)</th>
                              <th class="beneficio_bono">BENEFICIO (Bo)</th>
                              <th class="beneficio">BENEFICIO</th>
                            </tr>
                          </thead>
                        </table>
                        <div style="max-height: 300px;overflow-y: scroll;">
                          <table id="tablaBodyProducidos" class="table" > 
                            <tbody>
                            </tbody>
                          </table>
                        </div>
                        <table hidden>
                          <tr id="filaEjemploProducido">
                            <td class="fecha" style="text-align: center;">9999-88-77</td>
                            <td class="moneda" style="text-align: center;">MON</td>
                            <td class="categoria" style="text-align: center;">CATEGORIA</td>
                            <td class="jugadores" style="text-align: center;">987</td>
                            <td class="apuesta_efectivo">123</td>
                            <td class="apuesta_bono">456</td>
                            <td class="apuesta">789</td>
                            <td class="premio_efectivo">987</td>
                            <td class="premio_bono">654</td>
                            <td class="premio">321</td>
                            <td class="beneficio_efectivo">111</td>
                            <td class="beneficio_bono">222</td>
                            <td class="beneficio">333</td>
                          </tr>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                  </div>
                </div>
             </div>
          </div>
    </div>
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| INFORME DE JUEGO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>
      Detalle de información contable de un juego. Son visibles los datos caracteristicos, estado y sus últimos movimientos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<script src="/js/informe_juego.js" charset="utf-8"></script>

<script src="/js/lista-datos.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>

@endsection
