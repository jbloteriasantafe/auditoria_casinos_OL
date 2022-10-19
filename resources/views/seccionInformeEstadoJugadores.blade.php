@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
$usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">

<style>
.page {
  display: none;
}
.active {
  display: inherit;
}
.easy-autocomplete{
width:initial!important
}

/* Make circles that indicate the steps of the form: */
.step {
height: 15px;
width: 15px;
margin: 0 2px;
background-color: #bbbbbb;
border: none;
border-radius: 50%;
display: inline-block;
opacity: 0.5;
}

/* Mark the active step: */
.step.actived {
opacity: 1;
}

/* Mark the steps that are finished and valid: */
.step.finish {
background-color: #4CAF50;
}
#tablaJugadores th, #tablaJugadores td,#modalHistorial .cuerpo th,#modalHistorial .cuerpo td{
  padding: 0px;
  margin: 0px;
  font-size: 95%;
  text-align: center;
}
#tablaJugadores th, #tablaJugadores td{
  width: 9.09%;
}
#modalHistorial .cuerpo th,#modalHistorial .cuerpo td{
  width: 10%;
}
</style>
@endsection

@section('contenidoVista')

@section('columnas_jugador_thead')
<th value="codigo">CÓDIGO<i class='fa fa-sort'></i></th>
<th value="fecha_alta">F. ALTA<i class='fa fa-sort'></i></th>
<th value="fecha_nacimiento">F. NACIMIENTO<i class='fa fa-sort'></i></th>
<th value="sexo">SEXO<i class='fa fa-sort'></i></th>
<th value="localidad">LOCALIDAD<i class='fa fa-sort'></i></th>
<th value="provincia" >PROVINCIA<i class='fa fa-sort'></i></th>
<th value="estado">ESTADO<i class='fa fa-sort'></i></th>
<th value="fecha_autoexclusion">F. AE<i class='fa fa-sort'></i></th>
<th value="fecha_ultimo_movimiento" >F. ULTIMO MOV.<i class='fa fa-sort'></i></th>
@endsection

@section('columnas_jugador_tbody')
<td class="codigo">CÓDIGO</td>
<td class="fecha_alta">F. ALTA</td>             
<td class="fecha_nacimiento">F. NACIMIENTO</td>
<td class="sexo">SEXO</td>
<td class="localidad">LOCALIDAD</td>
<td class="provincia">PROVINCIA</td>
<td class="estado">ESTADO</td>
<td class="fecha_autoexclusion">F. AE</td>
<td class="fecha_ultimo_movimiento">F. ULTIMO MOV.</td>
@endsection

<div class="row">
  <div class="col-md-10 col-sm-9">
    <div class="row">
      <div id="contenedorFiltros" class="panel panel-default" style="width: 100%">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
          <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseFiltros" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-2">
                <h5>Plataformas</h5>
                <select id="buscadorPlataforma" class="form-control">
                  <option data-codigo="" value="">-Todas las plataformas-</option>
                  @foreach ($plataformas as $p)
                  <option id="{{$p->id_plataforma}}" value="{{$p->id_plataforma}}" data-codigo="{{$p->codigo}}">{{$p->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5>CÓDIGO</h5>
                <input class="form-control" id="buscadorCodigo" >
              </div>
              <div class="col-md-3">
                <h5>Estado</h5>
                <select id="buscadorEstado" class="form-control">
                  <option value="" selected>- Todos los estados -</option>
                  @foreach($estados as $e)
                  <option value="{{$e}}">{{$e}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5>Rango etario</h5>
                <div class="input-group">
                  <input id="buscadorRangoEtarioD" class="form-control input-sm" />
                  <span class="input-group-btn" style="width:0px;"></span>
                  <input id="buscadorRangoEtarioH" class="form-control input-sm" />
                </div>
              </div>
              <div class="col-md-2">
                <h5>Sexo</h5>
                <select id="buscadorSexo" class="form-control" >
                  <option value="" selected>- Todos -</option>
                  @foreach($sexos as $s)
                  <option value="{{$s}}">{{$s}}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <h5>Localidad</h5>
                <input class="form-control" id="buscadorLocalidad" />
              </div>
              <div class="col-md-3">
                <h5>Provincia</h5>
                <input class="form-control" id="buscadorProvincia" />
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <h5>Fecha autoexclusión - Desde</h5>
                <div class="input-group date" id="dtpFechaAutoexclusionD">
                    <input type="text" class="form-control" placeholder="Fecha de autoexclusión (desde)" id="buscadorFechaAutoexclusionD" autocomplete="off" style="background-color: rgb(255,255,255);">
                    <span id="input-times-autoexclusionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span id="input-calendar-autoexclusionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha autoexclusión- Hasta</h5>
                <div class="input-group date" id="dtpFechaAutoexclusionH">
                    <input type="text" class="form-control" placeholder="Fecha de autoexclusión (hasta)" id="buscadorFechaAutoexclusionH" autocomplete="off" style="background-color: rgb(255,255,255);">
                    <span id="input-times-autoexclusionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span id="input-calendar-autoexclusionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha alta - Desde</h5>
                <div class="input-group date" id="dtpFechaAltaD">
                  <input type="text" class="form-control" placeholder="Fecha de alta (desde)" id="buscadorFechaAltaD" autocomplete="off" style="background-color: rgb(255,255,255);">
                  <span id="input-times-altaD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-altaD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha alta- Hasta</h5>
                <div class="input-group date" id="dtpFechaAltaH">
                  <input type="text" class="form-control" placeholder="Fecha de alta (hasta)" id="buscadorFechaAltaH" autocomplete="off" style="background-color: rgb(255,255,255);">
                  <span id="input-times-altaH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-altaH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha ultimo movimiento - Desde</h5>
                <div class="input-group date" id="dtpFechaUltimoMovimientoD">
                  <input type="text" class="form-control" placeholder="Fecha del ultimo movimiento (desde)" id="buscadorFechaUltimoMovimientoD" autocomplete="off" style="background-color: rgb(255,255,255);">
                  <span id="input-times-ultimoMovimientoD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-ultimoMovimientoD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha ultimo movimiento- Hasta</h5>
                <div class="input-group date" id="dtpFechaUltimoMovimientoH">
                  <input type="text" class="form-control" placeholder="Fecha del ultimo movimiento (hasta)" id="buscadorFechaUltimoMovimientoH" autocomplete="off" style="background-color: rgb(255,255,255);">
                  <span id="input-times-ultimoMovimientoH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-ultimoMovimientoH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="row">
              <br>
              <center>
                <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
              </center>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="panel panel-default" style="width: 100%;">
        <div class="panel-heading">
          <h4>LISTADO DE JUGADORES</h4>
        </div>
        <div class="panel-body">
          <table id="tablaJugadores" class="table table-fixed tablesorter">
            <thead>
              <tr>
                <th value="plataforma">PLATAFORMA<i class='fa fa-sort'></i></th>
                @yield('columnas_jugador_thead')
                <th>ACCIÓN</th>
              </tr>
            </thead>
            <tbody style="height: 350px;">
            </tbody>
          </table>
          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
        </div>
      </div>
    </div> 
  </div>
</div>

@if($usuario->es_superusuario || $usuario->es_administrador || $usuario->es_despacho)
<div class="row">
  <div class="panel panel-default" style="width: 100%;">
  <div class="panel-heading">
    <h4>EXPORTAR</h4>
    <button type="button" class="btn btn-light" id="agregarCSV">Agregar</button>
    <button type="button" class="btn btn-light" id="limpiarCSV">Limpiar</button>
    <input type="checkbox" class="form-check-input" id="columnasCSV" checked>
    <span>Borrar columnas innecesarias</span>
    <a type="button" class="btn btn-light" id="descargarCSV">Descargar</a>
  </div>
  <div class="panel-body" style="height: 400px;overflow-y: auto;overflow-x: auto;">
  <style>
  #tablaCSV th,#tablaCSV td{
    font-size: 95%;
    padding: 0px;
    margin: 0px;
    width: 9.09%;
  }
  </style>
  <table id="tablaCSV" class="table table-responsive table-bordered">
    <thead>
      <tr>
        <th class="plataforma"   data-busq="#buscadorPlataforma" data-busq-attr='data-codigo'>Plataforma</th>
        <th class="codigo"       data-busq="#buscadorCodigo">Código</th>
        <th class="fecha_alta"   data-busq="#dtpFechaAlta" fecha>Fecha Alta</th>
        <th class="rango_etario" data-busq="#buscadorRangoEtario" rango>Rango Etario</th>
        <th class="sexo"         data-busq="#buscadorSexo">Sexo</th>
        <th class="localidad"    data-busq="#buscadorLocalidad">Localidad</th>
        <th class="provincia"    data-busq="#buscadorProvincia">Provincia</th>
        <th class="estado"       data-busq="#buscadorEstado">Estado</th>
        <th class="fecha_autoexclusion" data-busq="#dtpFechaAutoexclusion" fecha>Fecha Autoexclusión</th>
        <th class="fecha_ultimo_movimiento" data-busq="#dtpFechaUltimoMovimiento" fecha>Fecha Ultimo Movimiento</th>
        <th class="cant">CANT.</th>
      </tr>
    </thead>
    <tbody>
      <tr class="filaTablaCSV" style="display: none">
        <td class="plataforma"   data-busq="#buscadorPlataforma" data-busq-attr='data-codigo'>Plataforma</td>
        <td class="codigo"       data-busq="#buscadorCodigo">Código</td>
        <td class="fecha_alta"   data-busq="#dtpFechaAlta" fecha>Fecha Alta</td>
        <td class="rango_etario" data-busq="#buscadorRangoEtario" rango>Rango Etario</td>
        <td class="sexo"         data-busq="#buscadorSexo">Sexo</td>
        <td class="localidad"    data-busq="#buscadorLocalidad">Localidad</td>
        <td class="provincia"    data-busq="#buscadorProvincia">Provincia</td>
        <td class="estado"       data-busq="#buscadorEstado">Estado</td>
        <td class="fecha_autoexclusion" data-busq="#dtpFechaAutoexclusion" fecha>Fecha Autoexclusión</td>
        <td class="fecha_ultimo_movimiento" data-busq="#dtpFechaUltimoMovimiento" fecha>Fecha Ultimo Movimiento</td>
        <td class="cant">CANT.</td>
      </tr>
    </tbody>
  </table>
  </div>
  <div class="panel-footer" style="background: white;">
    <button type="button" class="btn btn-light" id="importarCSV">Importar Busqueda</button>
    <input type="file" id="importarCSVinput" style="display: none;" accept=".csv">
  </div>
  </div>
</div>  <!-- row tabla -->
@endif

<div class="modal fade" id="modalHistorial" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 90%;">
      <div class="modal-content">
        <div class="modal-header" style="background: lightgray;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3>HISTORIAL</h2>
        </div>
        <div  id="colapsado" class="collapse in">
        <div class="modal-body">
            <div class="row">
              <div class="col-md-12 cuerpo">
                <table class="table table-fixed">
                  <thead>
                    <tr>
                      <th value="fecha_importacion">F. IMPORTACIÓN<i class='fa fa-sort'></i></th>
                      @yield('columnas_jugador_thead')
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
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="moldeTablaJugadores">
    <td class="plataforma">PLATAFORMA</td>
    @yield('columnas_jugador_tbody')
    <td><button class="btn historia" type="button" title="VER ESTADOS ANTERIORES"><i class="fa fa-fw fa-user-clock"></i></button></td>
  </tr>
  <tr  id="moldeCuerpoHistorial">
    <td class="fecha_importacion">9999-99-99</td>
    @yield('columnas_jugador_tbody')
  </tr>
</table>

<!-- token -->
<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title2" style="color: #fff;">Estado de Jugadores</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>Buscar, filtrar y obtener el historial del estado de los jugadores</p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript paginacion -->
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/seccionInformeEstadoJugadores.js?6" charset="utf-8"></script>
<script src="/js/lib/spark-md5.js" charset="utf-8"></script><!-- Dependencia de md5.js -->
<script src="/js/md5.js?3" charset="utf-8"></script>
<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
