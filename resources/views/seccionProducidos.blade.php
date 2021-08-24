@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
?>

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/css/perfect-scrollbar.css">
<link rel="stylesheet" href="css/paginacion.css">
<!-- Mesaje de notificación -->
<link rel="stylesheet" href="/css/mensajeExito.css">
<link rel="stylesheet" href="/css/mensajeError.css">
<style>
.numerico {
  text-align: right;
  border-right: 1px solid rgb(221,221,221);
  border-left: 1px solid rgb(221,221,221);
}
</style>
@endsection

<div class="row">
  <div class="col-xl-9"><!-- columna TABLA PLATAFORMAS -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
          </div>
          <div id="collapseFiltros" class="panel-collapse collapse">
            <div class="panel-body">
              <div class="row"> <!-- Primera fila -->
                <div class="col-lg-3">
                  <h5>Plataforma</h5>
                    <select class="form-control" id="selectPlataforma">
                      <option value="" selected>- Seleccione una plataforma -</option>
                      @foreach ($plataformas as $p)
                      <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-lg-3">
                  <h5>Moneda</h5>
                    <select class="form-control" id="selectMoneda">
                      <option value="" selected>- Seleccione una moneda -</option>
                      @foreach ($tipo_monedas as $tm)
                      <option value="{{$tm->id_tipo_moneda}}">{{$tm->descripcion}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-lg-3">
                    <h5>Fecha de inicio</h5>
                    <div class="form-group">
                      <div class='input-group date' id='dtpFechaInicio' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fas fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                      </div>
                      <input class="form-control" type="hidden" id="fecha_inicio" value=""/>
                    </div>
                  </div>
                  <div class="col-lg-3">
                    <h5>Fecha de finalización</h5>
                    <div class="form-group">
                      <div class='input-group date' id='dtpFechaFin' data-link-field="fecha_fin" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                      </div>
                      <input class="form-control" type="hidden" id="fecha_fin" value=""/>
                    </div>
                  </div>
                  <div class="col-lg-3">
                    <h5>Correcto</h5>
                    <select class="form-control" id="selectCorrecto">
                      <option value="">- Todos - </option>
                      <option value="1">Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>
                </div>
                <br>
                <div class="row">
                  <div class="col-md-12">
                    <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                  </div>
                </div>
                <br>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4>Últimos Producidos</h4>
            </div>
            <div class="panel-body">
              <table id="tablaImportacionesProducidos" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-3" value="pdiff.id_plataforma">PLATAFORMA<i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="pdfif.fecha" estado="">FECHA<i class="fa fa-sort"></i></th>
                    <th class="col-xs-1" value="pdiff.id_tipo_moneda">MONEDA<i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="pdiff.beneficio">PRODUCIDO<i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="pjdiff.beneficio">PROD. JUGADORES<i class="fa fa-sort"></i></th>
                    <th class="col-xs-2">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody style="height: 350px;">
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
              </div>
            </div>
            <table hidden>
              <tr id="moldeFilaTabla">
                <td class="plataforma col-xs-3">PLAT</td>
                <td class="fecha_producido col-xs-2">FECHA</td>
                <td class="tipo_moneda col-xs-1">MONEDA</td>
                <td class="producido col-xs-2 numerico">99999</td>
                <td class="producido_jugadores col-xs-2 numerico">99999</td>
                <td class="accion col-xs-2">
                  <button class="btn btn-info carga" title="VER PRODUCIDO"><i class="fa fa-fw fa-search-plus"></i></button>
                  <button class="btn btn-info planilla" title="PLANILLA"><i class="fa fa-fw fa-print"></i></button>
                  <button class="btn btn-info carga_jugadores" title="VER PROD. JUGADORES"><i class="fa fa-fw fa-search-plus"></i></button>
                  <button class="btn btn-info planilla_jugadores" title="PLANILLA PROD. JUGADORES"><i class="fa fa-fw fa-print"></i></button>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-3">
        <a href="importaciones" style="text-decoration:none;">
          <div class="tarjetaSeccionMenor" align="center">
            <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
            <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
            <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
          </div>
        </a>
      </div>
    </div> <!-- / Tarjeta FILTROS -->
  </div>
</div>
<!--Modal nuevo para ajustes-->

<div class="modal fade" id="modalCargaProducidos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 60%;">
    <div class="modal-content" >
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">PRODUCIDO</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" >
            <h6 style="padding-left:15px" id="descripcion_validacion"></h6>
            <h5 style="padding-left:15px">
              Detalles con diferencias: <span id="detalles_con_diferencias">---</span>
            </h5>
            <div class="form-check">
              <label class="form-check-label" for="verSoloDiferencias">Ver solo diferencias</lavel>
              <input type="checkbox" class="form-check-input" id="verSoloDiferencias">
            </div>
          </div>
          <div class="row" >
            <div class="col-md-3">
              <h6><b>Detalles</b></h6>
              <table class="table table-fixed" style="display: block;">
                <thead style="display: block;position: relative;">
                  <tr >
                    <th class="col-xs-8">CÓDIGO</th>
                    <th class="col-xs-4"></th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla" style="display: block;overflow: auto;height: 700px;">
                </tbody>
              </table>
              <table>
                <tbody id="filaClon" style="display:none" class="filaCl" >
                  <td class="col-xs-8 codigo" value=""> codigo</td>
                  <td class="col-xs-4 botones" value="">
                    <button type="button" class="btn btn-info infoDetalle" value="">
                      <i class="fa fa-fw fa-eye"></i>
                    </button>
                  </td>
                </tbody>
              </table>
            </div> 
            <div id="columnaDetalle" class="col-md-9" style="border-right:2px solid #ccc;" hidden>
              <div class="detalleMaq" >
                <form id="frmCargaProducidos" name="frmCargaProducidos" class="form-horizontal" novalidate="">
                  <div class="row" style="border-top: 1px solid #ccc;  border-left:1px solid #ccc;border-right:1px solid #ccc;border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;" >
                    <div class="col-lg-3">
                      <h5>APUESTA (Ef)</h5>
                      <input id="apuesta_efectivo" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-plus"></i></div>
                    <div class="col-lg-3">
                      <h5>APUESTA (Bo)</h5>
                      <input id="apuesta_bono" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-equals"></i></div>
                    <div class="col-lg-3">
                      <h5>APUESTA</h5>
                      <input id="apuesta" type="text" class="form-control" disabled></i>
                    </div>
                  </div>
                  <div class="row" style="color: #ccc">
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-minus"></i></div>
                    <div class="col-lg-1"></div>
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-minus"></i></div>
                    <div class="col-lg-1"></div>
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-minus"></i></div>
                  </div>
                  <div class="row" style="border-top: 1px solid #ccc;  border-left:1px solid #ccc;border-right:1px solid #ccc;border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;" >
                    <div class="col-lg-3">
                      <h5>PREMIO (Ef)</h5>
                      <input id="premio_efectivo" type="text" class="form-control" disabled>
                      <span style="font-size: 85%;">Dev <span id="efectivo_pdev"></span>%</span>
                    </div> 
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-plus"></i></div>
                    <div class="col-lg-3">
                      <h5>PREMIO (Bo)</h5>
                      <input id="premio_bono" type="text" class="form-control" disabled>
                      <span style="font-size: 85%;">Dev <span id="bono_pdev"></span>%</span>
                    </div> 
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-equals"></i></div>
                    <div class="col-lg-3">
                      <h5>PREMIO</h5>
                      <input id="premio" type="text" class="form-control" disabled>
                      <span style="font-size: 85%;">Dev <span id="total_pdev"></span>%</span>
                    </div>
                  </div>
                  <div class="row" style="color: #ccc">
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-equals"></i></div>
                    <div class="col-lg-1"></div>
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-equals"></i></div>
                    <div class="col-lg-1"></div>
                    <div class="col-lg-3" style="text-align: center"><i class="fa fa-fw fa-equals"></i></div>
                  </div>
                  <div class="row" style="border-top: 1px solid #ccc;  border-left:1px solid #ccc;border-right:1px solid #ccc;border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;" >
                    <div class="col-lg-3">
                      <h5>BENEFICIO (Ef)</h5>
                      <input id="beneficio_efectivo" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-plus"></i></div>
                    <div class="col-lg-3">
                      <h5>BENEFICIO (Bo)</h5>
                      <input id="beneficio_bono" type="text" class="form-control" disabled>
                    </div> 
                    <div class="col-lg-1" style="text-align: center;color: #ccc"><h5>&nbsp;</h5><i class="fa fa-fw fa-equals"></i></div>
                    <div class="col-lg-3">
                      <h5>BENEFICIO</h5>
                      <input id="beneficio" type="text" class="form-control" disabled>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-4 datosJuego">
                      <h5>CATEGORIA INFORMADA</h5>
                      <input id="categoria" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-4">
                      <h5 class="datosJuego">JUGADORES</h5>
                      <h5 class="datosJugadores">JUEGOS</h5>
                      <input id="agrupados" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-4 datosJuego">
                      <h5>EN BASE DE DATOS</h5>
                      <input id="en_bd" type="text" class="form-control" disabled>
                    </div>
                  </div>
                  <hr>
                  <h6 class="datosJuego">JUEGO</h6>
                  <div class="row datosJuego">
                    <div class="col-lg-3">
                      <h5>NOMBRE</h5>
                      <input id="nombre_juego" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-3">
                      <h5>CATEGORIA</h5>
                      <input id="categoria_juego" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-3">
                      <h5>MONEDA</h5>
                      <input id="moneda_juego" type="text" class="form-control" disabled>
                    </div>
                    <div class="col-lg-3">
                      <h5>% DEV</h5>
                      <input id="devolucion_juego" type="text" class="form-control" disabled>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title">ADVERTENCIA</h3>
      </div>
      <div class="modal-body franjaRojaModal">
        <form id="frmEliminar" name="frmPlataforma" class="form-horizontal" novalidate="">
            <div class="form-group error ">
                <div class="col-xs-12">
                  <strong>¿Seguro desea eliminar Producido? Podría ocasionar errores serios en el sistema.</strong>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="btn-eliminarModal" value="0">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| PRODUCIDOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Producidos</h5>
      <p>
        Se presenta la información obtenida de producidos por día, según sus estados de validación, de inicio (contador inicial) y final (contador final).
        Se generan planillas con los datos obtenidos, aportando las diferencias con sus respectivos ajustes si los hubiere.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="js/seccionProducidos.js" charset="utf-8"></script>

    <script src="/js/perfect-scrollbar.js" charset="utf-8"></script>



    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script type="text/javascript">
    var ps = new PerfectScrollbar('.opcionesMenu');
    </script>

    @endsection
