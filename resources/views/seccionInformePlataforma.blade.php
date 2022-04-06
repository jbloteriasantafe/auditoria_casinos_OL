@extends('includes.dashboard')
<?php
use Illuminate\Http\Request;
use App\Http\Controllers\informesController;

$convertir_a_nombre = function($str){
  return strtoupper(str_replace("_"," ",$str));
};
$separar_sql = function($col){
  $vals = explode(' as ',$col);
  return ['sql' => trim($vals[0]),'alias' => trim($vals[1])];
};
$juegoFaltantesSelect = array_map($separar_sql,informesController::$obtenerJuegoFaltantesSelect);
$jugadorFaltantesSelect = array_map($separar_sql,informesController::$obtenerJugadorFaltantesSelect);
$juegoAlertasDiariasSelect = array_map($separar_sql,informesController::$obtenerAlertasJuegoSelect);
$jugadorAlertasDiariasSelect = array_map($separar_sql,informesController::$obtenerAlertasJugadorSelect);
?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
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

.tabContent {
  text-align:center;
  padding-bottom:25px;
  overflow-y: scroll;
  max-height: 650px;
}

#juegoFaltantesConMovimientos th,
#juegoFaltantesConMovimientos td,
#moldeJuegoFaltantesConMovimientos td {
  text-align: right;
  padding: 0px;
  width: {{100.0/count($juegoFaltantesSelect)}}%;
}

#jugadorFaltantesConMovimientos th,
#jugadorFaltantesConMovimientos td,
#moldeJugadorFaltantesConMovimientos td {
  text-align: right;
  padding: 0px;
  width: {{100.0/count($jugadorFaltantesSelect)}}%;
}

#juegoAlertasDiarias th,
#juegoAlertasDiarias td {
  text-align: right;
  padding: 0px;
  width: {{100.0/count($juegoAlertasDiariasSelect)}}%;
}

#jugadorAlertasDiarias th,
#jugadorAlertasDiarias td {
  text-align: right;
  padding: 0px;
  width: {{100.0/count($jugadorAlertasDiariasSelect)}}%;
}

#juegoFaltantesConMovimientos th,
#juegoFaltantesConMovimientos td.cod_juego,
#juegoFaltantesConMovimientos td.categoria,
#jugadorFaltantesConMovimientos th,
#jugadorFaltantesConMovimientos td.jugador,
#juegoAlertasDiarias th,
#juegoAlertasDiarias td.fecha,
#juegoAlertasDiarias td.cod_juego,
#juegoAlertasDiarias td.categoria,
#jugadorAlertasDiarias th,
#jugadorAlertasDiarias td.fecha,
#jugadorAlertasDiarias td.jugador {
  text-align: center;/*Casos especiales donde se visualiza mejor alineado en el centro*/
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
                  <select id="buscadorPlataforma" class="form-control">
                    <option value="" selected>- Seleccione la plataforma -</option>
                    @foreach($plataformas as $p)
                    <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                    @endforeach
                  </select>
                  <div class="row">
                    <div class="form-group" style="margin-bottom: 0px;">
                      <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha Desde"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>
                      <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group">
                      <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha Hasta"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>
                      <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
                    </div>
                  </div>
                  <button id="btn-buscar" class="btn btn-infoBuscar" type="button" style="width:100%;">VER</button>
                </div>
              </div>
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
          <h3 class="modal-title" style="color: #fff; text-align:center">ESTADO DE PLATAFORMA <span id="tituloModal"> PLATAFORMA 9999-99-99/9999-99-99 </span></h3>
          <div style="text-align: center; margin: 0px;">
            <div class="tab" activa="activa" style="width: 10%;" div-asociado="#divGraficos">
              <h4>CLASIFICACIÓN</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divTablas">
              <h4>% DEVS</h4>
            </div>
            <div class="tab" style="width: 15%;" div-asociado="#divJuegoFaltantesConMovimientos">
              <h4>JUEGOS FALTANTES C/ MOV</h4>
            </div>
            <div class="tab" style="width: 15%;" div-asociado="#divJugadorFaltantesConMovimientos">
              <h4>JUGADORES FALTANTES C/ MOV</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divJuegoAlertasDiarias">
              <h4>ALERTAS DIARIAS (JUEGOS)</h4>
            </div>
            <div class="tab" style="width: 10%;" div-asociado="#divJugadorAlertasDiarias">
              <h4>ALERTAS DIARIAS (JUGADORES)</h4>
            </div>
            <div id="tabEvolucionCategorias" class="tab" style="width: 10%;" div-asociado="#divEvolucionCategorias">
              <h4>EVOLUCIÓN %Dev CATEGORIAS</h4>
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
                <div id="divJuegoFaltantesConMovimientos" class="row tabContent">
                  <div id="juegoFaltantesConMovimientos" class="col-md-12" style="padding: 0px !important;">
                    <table class="col-md-12 table table-fixed tablesorter" style="padding: 0px !important;">
                      <thead>
                        <tr>
                          @foreach($juegoFaltantesSelect as $idx => $columna)
                          <th value="{{$columna['sql']}}" class="{{$columna['alias']}}"
                          @if($idx == 0)
                          estado="asc" class="activa"
                          @endif
                          >{{$convertir_a_nombre($columna['alias'])}}<i class="fa fa-sort"></i></th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
                    <div class="row paginado">
                      <div class="col-md-1 col-md-offset-3"><button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button></div>
                      <div class="col-md-4">
                        <div class="input-group">
                          <input class="form-control previewPage" type="number" style="text-align: center;" value="9">
                          <span class="input-group-addon">/</span>
                          <input class="form-control previewTotal" type="number" style="text-align: center;" value="99" disabled="disabled">
                        </div>
                      </div>
                      <div class="col-md-1"><button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button></div>
                    </div>
                  </div>
                </div>
                <div id="divJugadorFaltantesConMovimientos" class="row tabContent">
                  <div id="jugadorFaltantesConMovimientos" class="col-md-12" style="padding: 0px !important;">
                    <table class="col-md-12 table table-fixed tablesorter" style="padding: 0px !important;">
                      <thead>
                        <tr>
                          @foreach($jugadorFaltantesSelect as $idx => $columna)
                          <th value="{{$columna['sql']}}" class="{{$columna['alias']}}"
                          @if($idx == 0)
                          estado="asc" class="activa"
                          @endif
                          >{{$convertir_a_nombre($columna['alias'])}}<i class="fa fa-sort"></i></th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
                    <div class="row paginado">
                      <div class="col-md-1 col-md-offset-3"><button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button></div>
                      <div class="col-md-4">
                        <div class="input-group">
                          <input class="form-control previewPage" type="number" style="text-align: center;" value="9">
                          <span class="input-group-addon">/</span>
                          <input class="form-control previewTotal" type="number" style="text-align: center;" value="99" disabled="disabled">
                        </div>
                      </div>
                      <div class="col-md-1"><button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button></div>
                    </div>
                  </div>
                </div>
                <div id="divJuegoAlertasDiarias" class="row tabContent">
                  <div class="row">
                    <div class="col-md-2">
                      <h5>BENEFICIO ≷</h5>
                      <input id="inputBeneficio" type="number" class="form-control" value="150000" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>JUEGO %DEV ±</h5>
                      <input id="inputPdev" type="number" class="form-control" value="0" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>&nbsp;</h5>
                      <button id="btn-buscarPaginado" class="btn btn-infoBuscar" type="button" style="width:100%;">BUSCAR</button>
                    </div>
                  </div>
                  <div id="juegoAlertasDiarias" class="col-md-12" style="padding: 0px !important;">
                    <table class="col-md-12 table table-fixed tablesorter" style="padding: 0px !important;">
                      <thead>
                        <tr>
                          @foreach($juegoAlertasDiariasSelect as $idx => $columna)
                          <th value="{{$columna['sql']}}" class="{{$columna['alias']}}"
                          @if($idx == 0)
                          estado="asc" class="activa"
                          @endif
                          >{{$convertir_a_nombre($columna['alias'])}}<i class="fa fa-sort"></i></th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
                    <div class="row paginado">
                      <div class="col-md-1 col-md-offset-3"><button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button></div>
                      <div class="col-md-4">
                        <div class="input-group">
                          <input class="form-control previewPage" type="number" style="text-align: center;" value="9">
                          <span class="input-group-addon">/</span>
                          <input class="form-control previewTotal" type="number" style="text-align: center;" value="99" disabled="disabled">
                        </div>
                      </div>
                      <div class="col-md-1"><button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button></div>
                    </div>
                  </div>
                </div>
                <div id="divJugadorAlertasDiarias" class="row tabContent">
                  <div class="row">
                    <div class="col-md-2">
                      <h5>BENEFICIO ≷</h5>
                      <input id="inputBeneficio" type="number" class="form-control" value="150000" style="text-align: center;"/>
                    </div>
                    <div class="col-md-2">
                      <h5>&nbsp;</h5>
                      <button id="btn-buscarPaginado" class="btn btn-infoBuscar" type="button" style="width:100%;">BUSCAR</button>
                    </div>
                  </div>
                  <div id="jugadorAlertasDiarias" class="col-md-12" style="padding: 0px !important;">
                    <table class="col-md-12 table table-fixed tablesorter" style="padding: 0px !important;">
                      <thead>
                        <tr>
                          @foreach($jugadorAlertasDiariasSelect as $idx => $columna)
                          <th value="{{$columna['sql']}}" class="{{$columna['alias']}}"
                          @if($idx == 0)
                          estado="asc" class="activa"
                          @endif
                          >{{$convertir_a_nombre($columna['alias'])}}<i class="fa fa-sort"></i></th>
                          @endforeach
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
                    <div class="row paginado">
                      <div class="col-md-1 col-md-offset-3"><button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button></div>
                      <div class="col-md-4">
                        <div class="input-group">
                          <input class="form-control previewPage" type="number" style="text-align: center;" value="9">
                          <span class="input-group-addon">/</span>
                          <input class="form-control previewTotal" type="number" style="text-align: center;" value="99" disabled="disabled">
                        </div>
                      </div>
                      <div class="col-md-1"><button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button></div>
                    </div>
                  </div>
                </div>
              </div>
              <div id="divEvolucionCategorias" class="row tabContent">
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

<table hidden>
  <tr id="moldeJuegoFaltantesConMovimientos">
    @foreach($juegoFaltantesSelect as $idx => $columna)
    <td class="{{$columna['alias']}}">XXX</td>
    @endforeach
  </tr>
  <tr id="moldeJugadorFaltantesConMovimientos">
    @foreach($jugadorFaltantesSelect as $idx => $columna)
    <td class="{{$columna['alias']}}">XXX</td>
    @endforeach
  </tr>
  <tr id="moldeJuegoAlertasDiarias">
    @foreach($juegoAlertasDiariasSelect as $idx => $columna)
    <td class="{{$columna['alias']}}">XXX</td>
    @endforeach
  </tr>
  <tr id="moldeJugadorAlertasDiarias">
    @foreach($jugadorAlertasDiariasSelect as $idx => $columna)
    <td class="{{$columna['alias']}}">XXX</td>
    @endforeach
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

    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionInformePlataforma.js?5" charset="utf-8"></script>

    <!-- Highchart -->
    <script src="js/highcharts.js"></script>
    <script src="js/highcharts-3d.js"></script>
    @endsection
