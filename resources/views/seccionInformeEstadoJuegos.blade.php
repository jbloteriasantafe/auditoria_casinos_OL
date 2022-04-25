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
  width: 14.285%;
}
#modalHistorial .cuerpo th,#modalHistorial .cuerpo td{
  width: 16.666%;
}
</style>
@endsection

@section('contenidoVista')

@section('columnas_jugador_thead')
<th value="codigo">CÓDIGO<i class='fa fa-sort'></i></th>
<th value="nombre">NOMBRE<i class='fa fa-sort'></i></th>
<th value="categoria">CATEGORÍA<i class='fa fa-sort'></i></th>
<th value="tecnologia">TECNOLOGÍA<i class='fa fa-sort'></i></th>
<th value="estado">ESTADO<i class='fa fa-sort'></i></th>
@endsection

@section('columnas_jugador_tbody')
<td class="codigo">CÓDIGO</td>
<td class="nombre">NOMBRE</td>
<td class="categoria">CATEGORÍA</td>
<td class="tecnologia">TECNOLOGÍA</td>
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
              <div class="col-md-2">
                <h5>NOMBRE</h5>
                <input class="form-control" id="buscadorNombre" >
              </div>
              <div class="col-md-2">
                <h5>CATEGORIA</h5>
                <select id="buscadorCategoria" class="form-control">
                  <option value="!!TODO!!">- Todas las categorías -</option>
                  @foreach($categorias as $c)
                  <option>{{$c}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5>TECNOLOGÍA</h5>
                <select id="buscadorTecnologia" class="form-control">
                  <option value="!!TODO!!">- Todas las tecnologías -</option>
                  @foreach($tecnologias as $t)
                  <option>{{$t}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5>Estado</h5>
                <select id="buscadorEstado" class="form-control">
                  <option value="!!TODO!!" selected>- Todos los estados -</option>
                  @foreach($estados as $e)
                  <option>{{$e}}</option>
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
    <div class="row">
      <div class="col-lg-12">
        <a id="btn-importar-juegos" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
            <div class="backgroundNuevo" style="background-color: #29615c !important;"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">IMPORTAR ESTADOS JUEGOS</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <a href="" id="btn-informe-diferencias" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
          <div class="backgroundNuevo" style="background-color: #29615c !important;"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">-</h5>
                <h4 class="txtNuevo">INFORME DIFERENCIAS</h4>
              </center>
            </div>
          </div>
        </div>
        </a>
      </div>
    </div>
  </div>
</div>

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

<div class="modal fade" id="modalVerificarEstados" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarVerificarEstados" type="button" class="close" 
        data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerificarEstados" style="position:relative; right:20px; top:5px">
          <i class="fa fa-minus"></i>
        </button>
        <h3 class="modal-title">| VERIFICAR ESTADOS DE JUEGOS</h3>
      </div>
      <div  id="colapsadoVerificarEstados" class="collapse in">
        <div class="modal-body modalCuerpo">
          <div class="row">
            <div class="col-md-6">
              <h5>Fecha de estado del sistema</h5>
              <div class="input-group date" id="dtpFechaSistema">
                <input type="text" class="form-control" data-date-format="yyyy-mm-dd hh:ii:ss" id="fechaSistema" autocomplete="off" style="background-color: rgb(255,255,255);">
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="col-md-6">
              <h5>Fecha de la importación</h5>
              <div class="input-group date" id="dtpFechaImportacionEstados">
                <input type="text" class="form-control" data-date-format="yyyy-mm-dd" id="fechaImportacionEstados" autocomplete="off" style="background-color: rgb(255,255,255);">
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="col-md-12">
              <h5 style="text-align: center">PLATAFORMA</h5>
              <select id="plataformaVerificarEstado" class="form-control">
                <option value="">Seleccione</option>
                @foreach ($plataformas as $plataforma)
                <option value="{{$plataforma->id_plataforma}}" data-codigo="{{$plataforma->codigo}}">{{$plataforma->nombre}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row" id="animacionGenerando" style="text-align: center;" hidden>&nbsp;</div>
          <div class="row" style="text-align: center;">
            <a href="#" target="_blank"  id="resultado_diferencias"
            class="btn" type="button" style="font-weight: bold;">
              <span id="resultado_diferencias_span">Descargar PDF</span><!-- Necesito un span para triggerear el click -->
            </a>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-verificarEstados">VERIFICAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="moldeTablaJuegos">
    <td class="plataforma">PLATAFORMA</td>
    @yield('columnas_jugador_tbody')
    <td><button class="btn historia" type="button" title="VER ESTADOS ANTERIORES"><i class="fa fa-fw fa-clock"></i></button></td>
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
<script src="/js/seccionInformeEstadoJuegos.js?2" charset="utf-8"></script>
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
