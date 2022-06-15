@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
setlocale(LC_TIME, 'es_ES.UTF-8');//??
$cols_juegos = ['cod_juego' => 'center','categoria' => 'center','jugadores' => 'right','apuesta_efectivo' => 'right','apuesta_bono' => 'right',
'apuesta' => 'right','premio_efectivo' => 'right','premio_bono' => 'right','premio' => 'right','beneficio_efectivo' => 'right',
'beneficio_bono' => 'right','beneficio' => 'right'];
$cols_jugadores = ['jugador' => 'center','juegos' => 'right','apuesta_efectivo' => 'right','apuesta_bono' => 'right',
'apuesta' => 'right','premio_efectivo' => 'right','premio_bono' => 'right','premio' => 'right','beneficio_efectivo' => 'right',
'beneficio_bono' => 'right','beneficio' => 'right'];
$cols_poker = ['cod_juego' => 'center','categoria' => 'center','jugadores' => 'right','droop' => 'right','utilidad' => 'right'];
$cols_b_juegos = ['fecha' => 'center','jugadores' => 'right','depositos' => 'right','retiros' => 'right','apuesta' => 'right','premio' => 'right','beneficio' => 'right'];
$cols_b_poker = ['fecha' => 'center','jugadores' => 'right','mesas' => 'right','buy' => 'right','rebuy' => 'right','total_buy' => 'right',
        'cash_out' => 'right','otros_pagos' => 'right','total_bonus' => 'right','utilidad' => 'right'];
$cols = ['producido_juegos' => $cols_juegos,'producido_jugadores' => $cols_jugadores,'producido_poker' => $cols_poker,'beneficio_juegos' => $cols_b_juegos,'beneficio_poker' => $cols_b_poker];
$abbr = ['producido_juegos' =>         '+P','producido_jugadores' =>           '+PJ','producido_poker' =>      '+PPk','beneficio_juegos' =>           '+B','beneficio_poker' =>        '+BPk'];
$to_header = function($s){return str_replace("_"," ",strtoupper($s));};
$hdrs = array_combine(array_keys($abbr),array_map($to_header,array_keys($abbr)));
?>

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/animacionCarga.css">
<link rel="stylesheet" href="/css/paginacion.css">
<style>
  #botonesImportar {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    align-content: center;
  }
  #botonesImportar > *{
    /*flex: 1;*/
    width: 50%;
  }
  #tablaVistaPrevia th,#tablaImportaciones th,#tablaImportaciones td{
    text-align: center;
  }
  #infoImportaciones .fa-check {
      color: #00C853;
  }
  #infoImportaciones .fa-times {
      color: #FF1744;
  }
  #infoImportaciones td {
      height: 50px;
  }
  #infoImportaciones td.true .fa-check,#infoImportaciones td.false .fa-times  {
    display: inline;
  }
  #infoImportaciones td.true .fa-times,#infoImportaciones td.false .fa-check {
    display: none;
  }
  #infoImportaciones td,#infoImportaciones th{
    text-align: center;
  }
  #infoImportaciones td,#infoImportaciones th{
    padding: 0px;
    margin: 0px;
    width: <?php echo 100.0/(count($cols)+1) ?>%;
  }
</style>
@endsection

<div class="row" style="height: 80vh;">
  <!-- columna de los BOTONES  -->
  <div id="botonesImportar" style="width: 25%;height: 90%;float: left;">
    @foreach($abbr as $modo => $abbr)
    <div>
      <a href="" class="btn-importar" data-modo="{{$modo}}" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
          <div class="backgroundNuevo"></div>
          <div class="col-xs-12">
            <center>
              <h5 class="txtLogo"><span style="position: relative;font-size: 0.3em;top: -4vh;">{{$abbr}}</span></h5>
              <h4 class="txtNuevo">IMPORTAR {{str_replace("_"," ",strtoupper($modo))}}</h4>
            </center>
          </div>
        </div>
      </a>
    </div>
    @endforeach
  </div>
  <!-- tabla info -->
  <div style="width: 75%;height: 100%;float: left;">
    <div id="importacionesDiarias" class="panel panel-default" style="height: 90%;">
      <div class="panel-heading"><h4>IMPORTACIONES POR DÍA</h4></div>
      <div class="panel-body" style="height: 90%;">
        <div class="row">
          <div class="col-md-3">
            <select id="plataformaInfoImportacion" class="form-control" name="">
              @foreach ($plataformas as $plataforma)
              <option value="{{$plataforma->id_plataforma}}">{{$plataforma->nombre}}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <div class='input-group date' id='mesInfoImportacion' data-link-field="mes_info_hidden" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
              <input type='text' class="form-control"/>
              <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
              <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
            <input type="hidden" id="mes_info_hidden" value=""/>
          </div>
          <div class="col-md-3">
            <select id="monedaInfoImportacion" class="form-control" name="">
              @foreach($tipoMoneda as $tipo)
              <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
              @endforeach
            </select>
          </div>
        </div>
        <br>
        <div style="height: 90%;overflow: auto;">
          <table id="infoImportaciones" class="table tablesorter">
            <thead>
              <tr>
                <th value="fecha" estado="">FECHA<i class="fa fa-sort"></i></th>
                @foreach($hdrs as $modo => $h)
                <th>{{$h}}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- columna FILTROS -->
<div class="row">
  <div class="col-md-12">
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>FILTROS DE BÚSQUEDA</h4>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <h5>TIPO DE ARCHIVO</h5>
                <select id="tipo_archivo" class="form-control">
                @foreach($hdrs as $modo => $h)
                <option value="{{$modo}}">{{$h}}</option>
                @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5>Plataforma</h5>
                <select id="plataforma_busqueda" class="form-control">
                  <option value="0">Todas las plataformas</option>
                  @foreach ($plataformas as $plataforma)
                  <option value="{{$plataforma->id_plataforma}}">{{$plataforma->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <h5>FECHA</h5>
                <div class='input-group date' id='fecha_busqueda' data-link-field="fecha_busqueda_hidden" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                  <input id="fecha_busqueda_input" type='text' class="form-control" placeholder="Fecha de Inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input type="hidden" id="fecha_busqueda_hidden" value=""/>
              </div>
              <div class="col-md-2">
                <h5>MONEDA</h5>
                <select id="moneda_busqueda" class="form-control">
                  <option value="0">Todos las monedas</option>
                  @foreach($tipoMoneda as $tipo)
                  <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <h5 style="color:#ffffff;">búsqueda</h5>
                <button id="btn-buscarImportaciones" class="btn btn-infoBuscar" type="button">
                  <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
              </div>
            </div> <!-- row -->
          </div> <!-- panel-body -->
        </div>
      </div>
    </div>
  </div> <!-- columna tabla -->
  <!-- columna TABLA -->
  <div class="col-md-12">
    <!-- TABLA -->
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 id="tituloTabla">IMPORTACIONES</h4>
          </div>
          <div class="panel-body">
            <table id="tablaImportaciones" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th id="tipo_fecha" class="col-xs-3 activa" value="fecha" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                  <th class="col-xs-3" value="plataforma.nombre" estado="">PLATAFORMA <i class="fa fa-sort"></i></th>
                  <th class="col-xs-3" value="tipo_moneda.descripcion" estado="">MONEDA <i class="fa fa-sort"></i></th>
                  <th class="col-xs-3" value="" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody style="height: 300px;">
              </tbody>
            </table>
            <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
          </div> <!-- .panel-body -->
        </div> <!-- .panel -->
      </div> <!-- .col-lg-12 -->
    </div> <!-- .row | TABLA -->
  </div> <!-- .col-md-12 -->
</div> <!-- .row -->

<!-- Modal planilla -->
<div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width:95%;">
    <div class="modal-content">
      <div class="modal-header" style="background: #4FC3F7">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">VISTA PREVIA</h3>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <h5>FECHA</h5>
            <input id="fecha" class="form-control" type="text" value="" disabled>
          </div>
          <div class="col-md-4">
            <h5>PLATAFORMA</h5>
            <input id="plataforma" class="form-control" type="text" value="" disabled>
          </div>
          <div class="col-md-4">
            <h5>TIPO MONEDA</h5>
            <input id="tipo_moneda" class="form-control" type="text" value="" disabled>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-12">
            <table id="tablaVistaPrevia" class="table table-fixed">
              <thead>
                <tr></tr>
              </thead>
              <tbody style="max-height:400px;">
              </tbody>
            </table>
          </div>
          <button id="prevPreview" type="button" class="btn btn-link col-md-1"><i class="fas fa-arrow-left"></i></button>
          <div class="col-md-offset-5 col-md-1">P <span id="previewPage">9</span>/<span id="previewTotal">999</span></div>
          <button id="nextPreview" type="button" class="btn btn-link col-md-offset-4 col-md-1"><i class="fas fa-arrow-right"></i></button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
      </div>
      <div class="modal-body franjaRojaModal">
        <form id="frmEliminar" name="frmPlataforma" class="form-horizontal" novalidate="">
          <div class="form-group error ">
            <div class="col-xs-12">
              <strong id="titulo-modal-eliminar">¿Seguro que desea eliminar la importación?</strong>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="moldeFilaImportacionPorDia">
    <td class="fecha">12 AGO 2018</td>
    @foreach($cols as $modo => $ignorar)
    <td class="{{$modo}}"><i class="fa fa-check"></i><i class="fa fa-times"></i></td>
    @endforeach
  </tr>
  @foreach($cols as $modo => $cols_modo)
  <tr class="moldeDiario {{$modo}}">
    @foreach($cols_modo as $col => $align)
    <td data-atributo="{{$col}}" style="text-align: {{$align}};width: {{100/count($cols_modo)}}%;">{{$col}}</td>
    @endforeach
  </tr>
  <tr class="moldeDiarioHeader {{$modo}}">
    @foreach($cols_modo as $col => $align)
    <th data-atributo="{{$col}}" style="text-align: center;width: {{100/count($cols_modo)}}%;">{{$col == "droop"? "DROP" : $to_header($col)}}</th>
    @endforeach
  </tr>
  @endforeach
  <tr id="moldeFilaImportaciones">
    <td class="col-xs-3 fecha">DD MMM YYYY</td>
    <td class="col-xs-3 plataforma">PLATAFORMA</td>
    <td class="col-xs-3 tipo_moneda">MONEDA</td>
    <td class="col-xs-3">
      <button class="btn btn-info planilla" title="VER"><i class="far fa-fw fa-file-alt"></i></button>
      <button class="btn btn-danger borrar" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
    </td>
  </tr>
</table>

<div class="modal fade" id="modalImportacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarImportacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoImportacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| IMPORTAR</h3>
      </div>
      <div id="colapsadoImportacion" class="collapse in">
        <div class="modal-body modalCuerpo"> 
          <div id="rowArchivo" class="row">
            <div class="col-xs-12">
              <h5>ARCHIVO</h5>
              <div class="zona-file">
                <input id="archivo" data-borrado="false" type="file" name="" >
                <br>
                <span id="alertaArchivo" class="alertaSpan"></span>
              </div>
            </div>
            @include('includes.md5hash')
          </div>
          <div id="datosImportacion" class="row">
		        <div class="col-xs-5">
		          <h5>FECHA</h5>
		          <div class="input-group date" id="fechaImportacion" data-link-field="fechaImportacion_hidden" data-link-format="yyyy-mm-dd">
		            <input type="text" class="form-control" placeholder="Fecha de la importacion">
		            <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
		            <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
		          </div>
		          <input type="hidden" id="fechaImportacion_hidden" value="">
		        </div>
		        <div class="col-xs-4">
              <h5>PLATAFORMA</h5>
              <select id="plataformaImportacion" class="form-control">
                <option value="">Seleccione</option>
                @foreach ($plataformas as $plataforma)
                <option value="{{$plataforma->id_plataforma}}">{{$plataforma->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-xs-3">
              <h5>MONEDA</h5>
              <select id="monedaImportacion" class="form-control">
                <option value="">Seleccione</option>
                @foreach($tipoMoneda as $tipo)
                <option value="{{$tipo->id_tipo_moneda}}">{{$tipo->descripcion}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div id="mensajeError" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
            <div class="col-md-12">
              <h6>SE PRODUJO UN ERROR DE CONEXIÓN</h6>
              <button id="btn-reintentarBeneficio" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
            </div>
          </div>
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
          <button type="button" class="btn btn-successAceptar" id="btn-guardarImportacion" hidden value="nuevo"> SUBIR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>
  
<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| IMPORTACIONES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Importaciones</h5>1
  <p>
    A simple vista podrán verse por fecha si los producidos y beneficios fueron importados correctamente, en tiempo y forma.
    Luego, se lograrán las importaciones de cargas en formato .csv de los sistemas del concesionario. Para fechas anteriores que muestra a primera vista
    el sistema, existen filtros de búsqueda para su obtención.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="/js/seccionImportaciones.js?9" charset="utf-8"></script>
<script src="/js/md5.js" charset="utf-8"></script>

<!-- JS paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>

<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
