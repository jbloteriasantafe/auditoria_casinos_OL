@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('contenidoVista')

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
$user = UsuarioController::getInstancia()->quienSoy()['usuario'];
$puede_fiscalizar = $user->es_fiscalizador || $user->es_superusuario;
$puede_validar = $user->es_administrador || $user->es_superusuario;
$puede_eliminar = $user->es_administrador || $user->es_superusuario;
$puede_modificar_valores = $user->es_administrador || $user->es_superusuario;
?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">

<style>
  .fondoBlanco {
    background-color: rgb(255,255,255) !important;
  }
</style>
@endsection

<div class="row">
  <div class="col-xl-3">
      <div class="row">
        <div class="col-xl-12 col-md-4">
              <a href="" id="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center>
                      <img class="imgNuevo" src="/img/logos/relevamientos_white.png">
                    </center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                          <center>
                            <h5 class="txtLogo">+</h5>
                            <h4 class="txtNuevo">GENERAR RELEVAMIENTO DE CONTROL AMBIENTAL EN MÁQUINAS</h4>
                          </center>
                        </div>
                    </div>
                </div>
              </a>
        </div>
      </div>
    </div><!-- row botones -->

  <div class="col-xl-9">
      <!-- FILTROS -->
      <div class="row">
          <div class="col-md-12">
              <div id="contenedorFiltros" class="panel panel-default">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                  <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                  <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                          <h5>Fecha</h5>
                          <div class="form-group">
                             <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                 <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecharelevamiento"/>
                                 <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                 <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                             </div>
                             <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
                          </div>
                        </div>
                        <div class="col-md-3">
                            <h5>Casino</h5>
                            <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                <option value="0">-Todos los Casinos-</option>
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Sector</h5>
                            <select id="buscadorSector" class="form-control selectSector" name="">
                                <option value="0">-Todos los sectores-</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Estado Relevamiento</h5>
                            <select id="buscadorEstado" class="form-control selectSector" name="">
                                <option value="0">-Todos los estados-</option>
                                @foreach($estados as $estado)
                                  <option id="estado{{$estado->id_estado_relevamiento}}" value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                      <center>
                        <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                      </center>
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
              <h4>RELEVAMIENTOS DE CONTROL AMBIENTAL CREADOS POR EL SISTEMA</h4>
            </div>

            <div class="panel-body">
              <table id="tablaRelevamientos" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-2 activa" value="relevamiento_ambiental.fecha_generacion" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                    <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3">ACCIÓN </th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla" style="height: 350px;">
                  <tr class='filaEjemplo' style="display: none;">
                    <td class="col-xs-2 fecha"></td>
                    <td class="col-xs-2 casino"></td>
                    <td class="col-xs-2">
                      <i class="fas fa-fw fa-dot-circle iconoEstado"></i>
                      <span class="textoEstado"></span>
                    </td>
                    <td class="col-xs-3 acciones">
                      <button class="btn btn-info planilla" type="button">
                        <i class="far  fa-fw fa-file-alt"></i></button>
                      <span></span>
                      @if($puede_fiscalizar)
                      <button class="btn btn-warning carga" type="button">
                        <i class="fa fa-fw fa-upload"></i></button>
                      <span></span>
                      @endif
                      @if($puede_validar)
                      <button class="btn btn-success validar" type="button">
                        <i class="fa fa-fw fa-check"></i></button>
                      <span></span>
                      @endif
                      @if($puede_eliminar)
                      <button class="btn btn-success eliminar" type="button">
                        <i class="fa fa-fw fa-trash"></i></button>
                      <span></span>
                      @endif
                      <button class="btn btn-info imprimir" type="button">
                        <i class="fa fa-fw fa-print"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>
        </div>
      </div>
    </div>  <!-- row tabla -->
  </div> <!-- row principal -->


  <!--MODAL CREAR RELEVAMIENTO -->
  <div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO RELEVAMIENTO DE CONTROL AMBIENTAL DE MÁQUINAS</h3>
              </div>

              <div  id="colapsadoCrear" class="collapse in">

              <div class="modal-body modalCuerpo">
                        <div class='input-group date' id='fechaRelevamientoDiv' data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                            <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fechaRelevamientoInput" autocomplete="off" style="background-color: rgb(255,255,255);" readonly/>
                            <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                            <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>

                        <br>
                        <div class="row">
                          <div class="col-xs-6">
                            <h5>CASINO</h5>
                            <select id="casino" class="form-control" name="" style="float:right !important">
                                <option value="">- Seleccione un casino -</option>
                                <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))?>
                                 @foreach ($usuario['usuario']->casinos as $casino)
                                 <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                 @endforeach
                            </select>
                            <br> <span id="alertaCasino" class="alertaSpan"></span>
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
                <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                <input type="hidden" id="existeLayoutParcial" name="id_casino" value="0">
                <input type="hidden" id="id_casino" name="id_casino" value="0">
              </div>
            </div>
          </div>
        </div>
  </div>








</div>


<meta name="_token" content="{!! csrf_token() !!}" />

@endsection


<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| RELEVAMIENTO DE CONTROL AMBIENTAL</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamiento de control ambiental</h5>
  <p>
    Genera relevamientos de control ambiental dentro del casino, donde se deberán completar
    las planillas de informes, y luego, cargarlas según correspondan dichos datos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionRelevamientosAmbientalMaquinas.js" charset="utf-8"></script>
<script src="js/paginacion.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="js/fileinput.min.js" type="text/javascript"></script>
<script src="js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="js/lista-datos.js" type="text/javascript"></script>
@endsection
