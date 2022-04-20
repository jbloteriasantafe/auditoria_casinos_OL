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
#tablaJuegos th, #tablaJuegos td,#modalHistorial .cuerpo th,#modalHistorial .cuerpo td{
  padding: 0px;
  margin: 0px;
  font-size: 95%;
  text-align: center;
}
#tablaJuegos th, #tablaJuegos td{
  width: 25%;
}
#modalHistorial .cuerpo th,#modalHistorial .cuerpo td{
  width: 33.33%;
}
</style>
@endsection

@section('contenidoVista')

@section('columnas_jugador_thead')
<th value="codigo">CÓDIGO<i class='fa fa-sort'></i></th>
<th value="estado">ESTADO<i class='fa fa-sort'></i></th>
@endsection

@section('columnas_jugador_tbody')
<td class="codigo">CÓDIGO</td>
<td class="estado">ESTADO</td>
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
          <h4>LISTADO DE JUEGOS</h4>
        </div>
        <div class="panel-body">
          <table id="tablaJuegos" class="table table-fixed tablesorter">
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
  <div class="col-md-2 col-sm-3">
    <a id="btn-importar-juegos" style="text-decoration: none;">
      <div class="panel panel-default panelBotonNuevo">
        <center><img class="imgNuevo" src="/img/logos/gestion_usuarios_white.png"><center>
        <div class="backgroundNuevo" style="background-color: #29615c !important;"></div>
        <div class="row">
          <div class="col-xs-12">
            <center>
              <h5 class="txtLogo">+</h5>
              <h4 class="txtNuevo">IMPORTAR JUEGOS</h4>
            </center>
          </div>
        </div>
      </div>
    </a>
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
        <th class="estado"       data-busq="#buscadorEstado">Estado</th>
        <th class="cant">CANT.</th>
      </tr>
    </thead>
    <tbody>
      <tr class="filaTablaCSV" style="display: none">
        <td class="plataforma"   data-busq="#buscadorPlataforma" data-busq-attr='data-codigo'>Plataforma</td>
        <td class="codigo"       data-busq="#buscadorCodigo">Código</td>
        <td class="estado"       data-busq="#buscadorEstado">Estado</td>
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

<div class="modal fade" id="modalImportacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarImportacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoImportacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">IMPORTAR JUEGOS</h3>
      </div>
      <div id="colapsadoImportacion" class="collapse in">
        <div class="modal-body">
          <div id="rowArchivo" class="row">
            <div class="col-xs-12">
              <h5>ARCHIVO</h5>
              <div class="zona-file">
                <input id="archivo" data-borrado="false" type="file">
                <br>
                <span id="alertaArchivo" class="alertaSpan"></span>
              </div>
            </div>
            @include('includes.md5hash')
          </div>
          <div id="datosImportacion" class="row">
            <div class="col-xs-12">
              <div class="col-xs-6">
                <h5>FECHA</h5>
                <div class="input-group date" id="fechaImportacion" data-date-format="dd/mm/yyyy" data-link-field="fechaImportacion_hidden" data-link-format="yyyy-mm-dd">
                  <input type="text" class="form-control" placeholder="Fecha del archivo">
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input type="hidden" id="fechaImportacion_hidden" >
                <br>
              </div>
              <div class="col-xs-6">
                <h5>PLATAFORMA</h5>
                <select id="plataformaImportacion" class="form-control">
                  <option value="" selected>- Seleccione -</option>
                  @foreach ($plataformas as $plataforma)
                  <option value="{{$plataforma->id_plataforma}}">{{$plataforma->nombre}}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row" id="animacionImportando" style="text-align: center;" hidden>&nbsp;</div>
          <div id="mensajeInvalido" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
            <div class="col-xs-12" align="center">
              <i class="fa fa-fw fa-exclamation-triangle"></i>
              <h6> ARCHIVO INCORRECTO</h6>
            </div>
            <br>
            <br>
            <div class="col-xs-12" align="center">
              <p>Solo se aceptan archivos con extensión .csv o .txt</p>
            </div>
          </div>
          <div id="iconoCarga" class="sk-folding-cube">
            <div class="sk-cube1 sk-cube"></div>
            <div class="sk-cube2 sk-cube"></div>
            <div class="sk-cube4 sk-cube"></div>
            <div class="sk-cube3 sk-cube"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-guardarImportacion" value="nuevo"> SUBIR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="moldeTablaJuegos">
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
<h3 class="modal-title2" style="color: #fff;">Estado de Juegos</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>Buscar, filtrar y obtener el historial del estado de los juegos</p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript paginacion -->
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/seccionInformeEstadoJuegos.js" charset="utf-8"></script>
<script src="/js/lib/spark-md5.js" charset="utf-8"></script><!-- Dependencia de md5.js -->
<script src="/js/md5.js?2" charset="utf-8"></script>
<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
