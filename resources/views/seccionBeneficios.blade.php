@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;

?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
@endsection

        <div class="row">

            <div class="col-xl-9">
                <div class="row"> <!-- FILTROS -->
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
                                  <select class="form-control" id="selectPlataformas">
                                    <option value="">- Seleccione una plataforma -</option>
                                     @foreach ($plataformas as $p)
                                     <option id="{{$p->id_plataforma}}" value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                                     @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-3">
                                    <h5>Fecha Desde</h5>
                                    <div class="form-group">
                                       <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Fecha Hasta</h5>
                                  <div class="form-group">
                                     <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                         <input type='text' class="form-control" placeholder="Fecha Hasta" id="B_fecha_inicio"/>
                                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                         <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                     </div>
                                     <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
                                  </div>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Tipo Moneda</h5>
                                  <select class="form-control" id="selectTipoMoneda">
                                    <option value="">- Seleccione un Tipo Moneda -</option>
                                     @foreach ($tipos_moneda as $tipo_moneda)
                                     <option id="{{$tipo_moneda->id_tipo_moneda}}" value="{{$tipo_moneda->id_tipo_moneda}}">{{$tipo_moneda->descripcion}}</option>
                                     @endforeach
                                  </select>
                                </div>

                              </div>
                              <div class="row">
                                  <div class="col-md-12">
                                    <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                                  </div>
                              </div>
                                <br>


                            </div> <!-- /.panel-body -->


                          </div>
                        </div> <!-- /.panel -->
                      </div>
                </div>

                <div class="row"> <!-- TABLA -->
                      <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>ÚLTIMOS BENEFICIOS</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaBeneficios" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-2" value="p.nombre" estado="">PLATAFORMA <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="bm.fecha" estado="">MES <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1">AÑO</th>
                                    <th class="col-xs-2" value="tm.descripcion" estado="">T. MONEDA <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-3" value="diferencias_mes" estado="">DÍAS CON DIFERENCIA<i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaResultados" style="height: 350px;">

                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>
                      </div>
                </div>


            <div class="col-xl-3">

              <div class="row">
                <div class="col-md-12">
                  <a href="importaciones" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                      <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                    </div>
                  </a>
                </div>
              </div>
              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'cotizar_dolar_peso'))  
              <div class="row">
                <div class="col-md-12">
                  <a id="btn-cotizacion" href="" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor"> COTIZACIÓN</h2>
                      <h2 class="tituloSeccionMenor">COTIZACIÓN </h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/peso-dollar.svg" alt="">
                    </div>
                  </a>
                </div>
              </div>
              @endif
            </div> <!-- /.col-md-3 -->

        </div> <!-- /.row -->

    <!-- Modal beneficio -->
    <div class="modal fade" id="modalBeneficioMensual" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" style="width:80%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">BENEFICIOS</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmBeneficio" name="frmBeneficio" class="form-horizontal" novalidate="">
                          <div class="row">
                            <div class="col-md-3">
                              <h5>Plataforma</h5>
                              <input type="text" readonly="true" id="plataformaModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Tipo Moneda</h5>
                              <input type="text" readonly="true" id="tipoMonedaModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Año</h5>
                              <input type="text" readonly="true" id="anioModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Mes</h5>
                              <input type="text" readonly="true" id="mesModal" disabled class="form-control">
                            </div>
                          </div>
                          <br>
                          <div class="row" style="height: 500px;overflow: scroll;">
                              <div class="col-md-12">
                                  <table id="tablaModal" class="table">
                                      <thead>
                                          <tr>
                                              <th width="10%">FECHA</th>
                                              <th width="12%">CALCULADO</th>
                                              <th width="12%">IMPORTADO</th>
                                              <th width="12%">AJUSTE</th>
                                              <th width="12%">AJUSTE AUD</th>
                                              <th width="12%">DIFERENCIA</th>
                                              <th width="3%"></th>
                                              <th width="25%">OBSERVACIÓN</th>
                                              <th width="3%"></th>
                                          </tr>
                                      </thead>
                                      <tbody id="cuerpoTabla" style="color:black;">
                                      </tbody>
                                  </table>
                              </div>
                          </div>

                          <br>
                  </form>
                  <div class="row" align="left" style="margin-right:10px; font-weight:bold">
                      <h4 id="textoExito"></h4>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-warningModificar" id="btn-validar-si" value="nuevo">VALIDAR SIN PRODUCIDOS</button>
                  <button type="button" class="btn btn-warningModificar" id="btn-validar" value="nuevo">VALIDAR BENEFICIOS</button>
                  <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal">SALIR</button>
                  <input type="hidden" id="id_beneficio" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>
        
    <!-- Modal cotizacion -->
      <div class="modal fade" id="modal-cotizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg"  >
              <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title">| COTIZACIÓN DÓLAR->PESO</h3>
                </div>

                <div class="modal-body" style="background-color: white;">
                    <div class="row" style="padding-bottom: 15px;">
                        <div class="col-md-12">
                            <div id="calendarioInicioBeneficio"></div>
                      </div>
                </div>

                <div class="modal-footer">
                  <label id="labelCotizacion" for="number"> </label>
                  <input id="valorCotizacion" type="number" step="0.001" min="25" max="200" placeholder="xx,xxx">
                  <button type="button" class="btn btn-successAceptar" id="guardarCotizacion">GUARDAR</button>
                </div> 
            </div>
          </div>
      </div>
    


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| BENEFICIOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Beneficios</h5>
      <p>
        Se observan informes de beneficios con sus respectivas diferencias, donde dispone los datos obtenidos por día en el mes
        que se desea ver. Además, se implementa la validación de aquellos que requieran ajustes.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>

    
    <script src='/js/moment.min.js'></script>
    <script src='/js/fullcalendar.min.js'></script>
    <script src='/js/locale-all.js'></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionBeneficios.js" charset="utf-8"></script>
    

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
