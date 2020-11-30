@extends('includes.dashboard')
@section('estilos')
 <link rel="stylesheet" href="/css/bootstrap-datetimepicker.css">
<link rel="stylesheet" href="/css/paginacion.css">
@endsection
@section('headerLogo')
<span class="etiquetaLogoExpedientes">@svg('expedientes','iconoExpedientes')</span>
@endsection

<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

@section('contenidoVista')
<div class="row">
  <!-- columna FILTRO Y TABLA juego-->
  <div class="col-lg-9">
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
                  <h5>Número de expediente</h5>
                  <div class="input-group triple-input">
                    <input id="B_nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                    <input id="B_nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                    <input id="B_nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                  </div>
                </div>
                <div class="col-lg-3">
                  <h5>Plataforma</h5>
                  <select class="form-control" id="B_plataforma">
                    <option value="0">Todas las plataformas</option>
                    @foreach ($plataformas as $p)
                    <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>Fecha de inicio</h5>
                  <!-- FORM GROUP -->
                  <div class="form-group">
                    <div class='input-group date' id='B_dtpFechaInicio' data-link-field="fecha_inicio1" data-date-format="dd MM yyyy" data-link-format="yyyy-mm">
                      <input id="B_fecha_inicio" type='text' class="form-control" placeholder="Fecha Desde"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fecha_inicio1" value=""/>
                  </div>
                </div>
                <div class="col-lg-3">
                  <h5>Ubicación</h5>
                  <input id="B_ubicacion" type="text" class="form-control" maxlength="100" placeholder="Ubicación">
                </div>
              </div> <!-- / Primera fila -->
              <br>
              <div class="row"> <!-- Segunda fila -->
                <div class="col-lg-3">
                  <h5>Remitente</h5>
                  <input id="B_remitente" type="text" class="form-control" maxlength="45" placeholder="Remitente">
                </div>
                <div class="col-lg-3">
                  <h5>Concepto</h5>
                  <input id="B_concepto" type="text" class="form-control" maxlength="45" placeholder="Concepto">
                </div>
                <div class="col-lg-3">
                  <h5>Tema</h5>
                  <input id="B_tema" type="text" class="form-control" maxlength="45" placeholder="Tema">
                </div>
                <div class="col-lg-3">
                  <h5>Destino</h5>
                  <input id="B_destino" type="text" class="form-control" maxlength="45" placeholder="Destino">
                </div>
              </div> <!-- / Segunda fila -->
              <br>
              <div class="row">
                <div class="col-lg-4">
                  <h5>IDENTIFICACIÓN NOTA</h5>
                  <input id="B_nota" type="text" class="form-control" maxlength="45" placeholder="Identificación">
                </div>
                <div class="col-lg-4">
                  <br>
                  <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                </div>
              </div>
              <div class="row">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- / .row  Tarjeta FILTROS -->
    <!-- TABLA -->
    <div class="row"> <!-- Tarjeta TABLA Expedientes -->
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>Todos los expedientes</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultados" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-xs-3" value="expediente.nro_expediente" estado="">NRO EXPEDIENTE  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-3 activa" value="expediente.fecha_iniciacion" estado="desc">FECHA DE INICIO  <i class="fas fa-sort-down"></i></th>
                  <th class="col-xs-3" value="plataforma.nombre" estado="">PLATAFORMA  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-3">ACCIONES</th>
                </tr>
              </thead>
              <tbody id="cuerpoTabla" style="height: 350px;">
              </tbody>
            </table>
            <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
          </div>
        </div> <!-- ./panel -->
      </div> <!-- ./col-md-12 -->
    </div> <!-- /.row -->
  </div>      <!-- /.col-lg-12 col-xl-9 -->
  <!-- columna AGREGAR juego-->
  <div class="col-lg-3">
    <div class="row">
      <div class="col-xl-12 ">
        <a href="" id="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/expedientes_white.png"><center>
            <div class="backgroundNuevo">
            </div>
            <div class="row">
              <div class="col-xs-12">
               <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">NUEVO EXPEDIENTE</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))
      <div class="col-xl-12 ">
        <a href="resoluciones" style="text-decoration:none;">
          <div class="tarjetaSeccionMenor" align="center">
            <h2 class="tituloFondoMenor">RESOLUCIONES</h2>
            <h2 class="tituloSeccionMenor">RESOLUCIONES</h2>
            <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/resoluciones_white.png" alt="">
          </div>
        </a>
      </div>
      @endif
      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))
      <div class="col-xl-12">
        <a href="disposiciones" style="text-decoration:none;">
          <div class="tarjetaSeccionMenor" align="center">
            <h2 class="tituloFondoMenor">DISPOSICIONES</h2>
            <h2 class="tituloSeccionMenor">DISPOSICIONES</h2>
            <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/disposiciones_white.png" alt="">
          </div>
        </a>
      </div>
      @endif
    </div>
  </div>
</div>  <!-- /row -->
<style media="screen">
  #modalExpediente h6 {
    font-family: Roboto-Condensed;
  }
</style>
    
<!-- Modal Expediente -->
<div class="modal fade" id="modalExpediente" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:94%;">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <a class="btn-ayuda" type="button" name="button" data-toggle="popover" title="Ayuda - NUEVO EXPEDIENTE" data-html="true"
            data-content="@include('ayudas.modalExpedientes')"
            data-placement="bottom">
          <i class="fa fa-fw fa-question-circle fa-2x"></i>
        </a>
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="margin-left:33px; color: #fff; text-align:center">NUEVO EXPEDIENTE</h3>
        <style media="screen">
          .navModal > div {
            display: inline-block;
            margin: 0px 15px 0px 15px;
          }
          .navModal > div a{
            outline: none;
            text-decoration: none;
            margin-bottom: 0px !important;
          }
          .navModal h4 {
            font-family: Roboto-BoldCondensed;
            padding-bottom: 20px;
            margin-bottom: 0px !important;
          }
          .navModal a.navModalActivo h4 {
            color: white;
            font-size: 20px;
            border-bottom: 5px solid #fff;
          }
        </style>
        <div class="navModal" style="position:relative; bottom:-15px; text-align:center; font-family: Roboto-Regular; font-size: 20px; color: #999;">
          <div style="width:25%;">
            <i id="error_nav_config" class="fa fa-times" style="color:red;"></i>
            <a href="" id="navConfig"><h4>CONFIGURACIÓN</h4></a>
          </div>
          <div style="width:25%;">
            <i id="error_nav_notas" class="fa fa-times" style="color:red;"></i>
            <a href="" id="navNotas"><h4>NOTAS</h4></a>
          </div>
        </div>
      </div> <!-- modal-header -->
      <div class="modal-body modalCuerpo" style="padding-bottom:0px;">
        <!-- Panel que se minimiza -->
        <div  id="colapsado" class="collapse in">
          <!-- seccion CONFIGURACION -->
          <div class="seccion" id="secConfig">
            <div class="row" style="border-bottom: 1px solid #eee;">
              <div class="col-md-4 col-lg-4">
                <h5>Número de expediente</h5>
                <div class="input-group triple-input">
                  <input id="nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                  <input id="nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                  <input id="nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                </div>
                <br>
                <span id="alerta-nroExpediente" class="alertaSpan"></span>
              </div>
              <style media="screen">
                div#contenedorPlataformas.alerta {
                  border:3px solid #EF5350; padding:5px; border-radius:4px;
                }
              </style>
              <div class="col-md-8 col-lg-8" style="text-align:center;">
                <h5 style="padding-left:0px;">Plataformas</h5>
                <div id="contenedorPlataformas">
                  @foreach ($plataformas as $p)
                  <input type="checkbox" id="{{$p->id_plataforma}}" value="" class="plataformasExp" style="margin:3px">
                  <span style="font-family:Roboto-Light; font-size:18px; margin-left:2px; margin-right:40px">{{$p->nombre}}</span>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="row" style="padding-bottom: 8px; padding-top: 8px; border-bottom: 1px solid #eee;">
              <div class="col-md-6 col-lg-4">
                <h5>Fecha de Inicio</h5>
                <div class="form-group">
                  <div class='input-group date' id='dtpFechaInicio' data-link-field="fecha_inicio" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                    <input type='text' class="form-control" placeholder="Fecha de inicio"/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                  </div>
                  <input class="form-control" type="hidden" id="fecha_inicio" value=""/>
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <h5>Fecha de pase</h5>
                <div class="form-group">
                  <div class='input-group date' id='dtpFechaPase' data-link-field="fecha_pase" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                    <input type='text' class="form-control" placeholder="Fecha de pase"/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                  </div>
                  <input class="form-control" type="hidden" id="fecha_pase" value=""/>
                </div>
              </div>
              <div class="col-md-6 col-lg-6">
                <h5>Destino</h5>
                <input id="destino" type="text" class="form-control" maxlength="45" placeholder="Ingresar destino">
                <span id="alerta-destino" class="alertaSpan"></span>
              </div>
              <div class="col-md-6 col-lg-6" >
                <h5>Ubicación</h5>
                <input id="ubicacion" type="text" class="form-control" maxlength="100" placeholder="Ingresar ubicación física">
                <br>
                <span id="alerta-ubicacion" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 col-lg-6" >
                <h5>Iniciador</h5>
                <input id="iniciador" type="text" class="form-control" maxlength="60" placeholder="Ingresar iniciador">
                <br>
                <span id="alerta-iniciador" class="alertaSpan"></span>
              </div>
              <div class="col-md-6 col-lg-6">
                <h5>Remitente</h5>
                <input id="remitente" type="text" class="form-control" maxlength="45" placeholder="Ingresar remitente">
                <br>
                <span id="alerta-remitente" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 col-lg-6">
                <h5>Concepto</h5>
                <textarea id="concepto" type="text" class="form-control" maxlength="150" style="height:80px;" placeholder="Ingresar concepto"></textarea>
                <br>
                <span id="alerta-concepto" class="alertaSpan"></span>
              </div>
              <div class="col-md-6 col-lg-6">
                <h5>Tema</h5>
                <textarea id="tema" type="text" class="form-control" maxlength="100" style="height:80px" placeholder="Ingresar tema"></textarea>
                <br>
                <span id="alerta-tema" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row" style="padding-bottom: 4px; padding-top: 4px; border-bottom: 1px solid #eee;">
              <div class="col-md-6 col-lg-4">
                <h5>Cantidad de Cuerpos</h5>
                <input id="nro_cuerpos" type="text" class="form-control" placeholder="Ingresar número de cuerpos">
                <br>
                <span id="alerta-nroCuerpos" class="alertaSpan"></span>
              </div>
              <div class="col-md-6 col-lg-4">
                <h5>Cantidad de Folios</h5>
                <input id="nro_folios" type="text" class="form-control" placeholder="Ingresar número de folios">
                <br>
                <span id="alerta-nroFolios" class="alertaSpan"></span>
              </div>
              <div class="col-md-6 col-lg-4">
                <h5>Anexo</h5>
                <input id="anexo" type="text" class="form-control" maxlength="45" placeholder="Ingresar anexo">
                <br>
                <span id="alerta-anexo" class="alertaSpan"></span>
              </div>
            </div> <!-- / Row Primera sección  style="padding-right: 0px;-->
            <div class="row" style="">
              <div id="columna" class="col-lg-6" style="margin-top:8px;">
                <h5>Resolución</h5>
                <div class="form-inline">
                  <div class="input-group triple-input" style="padding-top:8px;">
                    <input id="nro_resolucion" style="width:60%;" type="text" placeholder="---" maxlength="3" class="form-control" />
                    <input id="nro_resolucion_anio" style="width:40%; border-left:none;" type="text" placeholder="--" maxlength="2" class="form-control" />
                  </div> 
                  <button id="btn-agregarResolucion" class="btn btn-success borrarFila" type="button">
                    <i class="fa fa-fw fa-plus"></i>
                  </button>
                </div>
                <table id="tablaResolucion" class="table" style="margin-top:30px; margin-bottom:20px;">
                  <thead>
                    <tr>
                      <th width="50%">Nro Resolución</th>
                      <th width="30%">Año Resolución</th>
                      <th width="20%">ACCIÓN</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
            <br>
            <div class="row" id="dispoCarg">
              <div class="col-md-9" style="margin-top:8px;" >
                <h5>Disposición(es) Cargadas</h5>
                <!-- TABLA ṔARA MOSTRAR LAS DISPOSICIONES AL MODIFICAR UNE EXPEDIENTE -->
                <table id="tablaDispoCreadas" class="table">
                  <thead>
                    <tr>
                      <th class="col-xs-2" text-align="center">NRO</th>
                      <th class="col-xs-2" text-align="center"><h5>AÑO.</h5></th>
                      <th class="col-xs-4" text-align="center"><h5>DESCRIPCIÓN</h5></th>
                      <th class="col-xs-3" text-align="center"><h5>ESTADO</h5></th>
                      <th class="col-xs-1" text-align="center"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr id="moldeDispoCargada" class="filaDispo" style="display:none;">
                      <td class="col-xs-2 nro_dCreada">9</td>
                      <td class="col-xs-2 anio_dCreada">11 AGO 2018</td>
                      <td class="col-xs-4 desc_dCreada">EGRESO</td>
                      <td class="col-xs-3 mov_dCreada" style="resize:vertical;"> dsfs </td>
                      <td class="col-xs-1"><button class="btn btn-danger borrarDispoCargada" type="button">
                        <i class="fas fa-fw fa-trash-alt"></i>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-9" style="margin-top:8px;">
                <h5 style="display:inline;">Disposición(es)</h5>
                  <button id="btn-agregarDisposicion" class="btn btn-success borrarFila" type="button" style="display:inline;position:relative;top:-2px;">
                    <i class="fa fa-fw fa-plus"></i>
                  </button>
                  <div id="columnaDisposicion" style="padding-top:10px;">
                    <div id="moldeDisposicion" class="row disposicion" style="padding-bottom:15px;" hidden>
                      <div class="col-md-3">
                        <div class="input-group triple-input">
                          <input class="form-control nro_disposicion" style="width:60%;" type="text" placeholder="---" maxlength="3"  />
                          <input class="form-control nro_disposicion_anio" style="width:40%; border-left:none;" type="text" placeholder="--" maxlength="2"  />
                        </div>
                      </div>
                      <div class="col-md-4">
                        <input class="form-control" id="descripcion_disposicion" type="text" name="" value="" placeholder="Descripción">
                      </div>
                      <div class="col-md-3">
                        <select class="form-control" id="tiposMovimientosDisp" disabled>
                          <option val="0">- Tipo -</option>
                          @foreach($estadoJuego as $t)
                          <option value="{{$t->id_estado_juego}}">{{$t->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-2">
                        <button class="btn btn-danger borrarFila borrarDisposicion" type="button" name="button">
                          <i class="fa fa-fw fa-trash-alt"></i>
                        </button>
                      </div>
                    </div> <!-- disposicion -->
                  </div> <!-- columnaDisposicion -->
                </div>
              </div> <!-- / Row Segunda sección -->
            </div>
            <!-- seccion NOTAS -->
            <div class="seccion" id="secNotas">
              <!-- notas creadas -->
              <div class="row notasCreadas" style="border-bottom:1px solid #ddd; padding-bottom:20px; margin-bottom:20px !important;">
                <div class="col-md-12">
                  <h6>Notas creadas</h6>
                  <style media="screen">
                    #tablaNotasCreadas h5 {
                      padding-left: 0px;
                    }
                    #tablaNotasCreadas tr td {
                      white-space: nowrap;
                      overflow: hidden;
                      text-overflow: ellipsis;
                    }
                  </style>
                  <table id="tablaNotasCreadas" class="table">
                    <thead>
                      <tr>
                        <th class="col-xs-1"></th>
                        <th class="col-xs-1"><h5>IDENT.</h5></th>
                        <th class="col-xs-3"><h5>FECHA</h5></th>
                        <th class="col-xs-3"><h5>ESTADO</h5></th>
                        <th class="col-xs-4"><h5>DETALLE</h5></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr id="moldeFilaNota" class="filaNota" style="display:none;">
                        <td class="col-xs-1"><button class="btn btn-danger borrarFila borrarNotaCargada" type="button">
                          <i class="fas fa-fw fa-trash-alt"></i> </td>
                        <td class="col-xs-1 identificacion">9</td>
                        <td class="col-xs-3 fecha">11 AGO 2018</td>
                        <td class="col-xs-3 movimiento">ACTIVO</td>
                        <td class="col-xs-4 detalle" style="resize:vertical;"> dsfs<div class=""></div> </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- mensaje -->
              <div class="row mensajeNotas" style="padding-top:20px;">
                <div class="col-md-12">
                  <i class="fa fa-exclamation" style="margin-left:10px; color:#44f;"></i>
                  <span style="font-family:Roboto-Regular;font-size:18px; margin-left:10px;">Para crear notas nuevas primero debe seleccionar al menos una PLATAFORMA para el EXPEDIENTE.</span>
                </div>
              </div>

              <style media="screen">
                .notaNueva {
                  padding: 10px 0px 20px 0px;
                  border-top: 1px solid #ddd;
                }
              </style>

              <!-- formulario NOTAS Y MOVIMIENTOS NUEVOS -->
              <div class="notasNuevas formularioNotas">
                <div class="row" style="padding-top:0px; padding-bottom:10px;">
                  <div class="col-md-12">
                    <h6>Notas nuevas</h6>
                  </div>
                </div>

                <div id="moldeNotaNueva" class="row notaNueva" hidden>
                  <form id="frmNotas" name="frmNotas" class="form-horizontal">
                    <div class="col-md-4">
                      <h5>FECHA</h5>
                      <!-- FORM GROUP -->
                      <div class="form-group" style="padding-left:20px;">
                        <div class='input-group date dtpFechaNota' data-link-field="" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                          <input id="" type='text' class="form-control" placeholder="Fecha de la NOTA"/>
                          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                        </div>
                        <input class="form-control fecha_notaNueva" type="hidden" id="" value=""/>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <h5>IDENTIFICACIÓN</h5>
                      <input class="form-control identificacion" type="text" name="" value="">
                    </div>
                    <div class="col-md-4">
                      <h5>ESTADO JUEGO</h5>
                      <select class="form-control tiposMovimientos" name="">
                        <option val="0">- Tipo -</option>
                        @foreach($estadoJuego as $t)
                        <option value="{{$t->id_estado_juego}}">{{$t->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-1">
                      <button class="btn btn-danger borrarFila borrarNota" type="button" style="position:relative;top:35px;">
                        <i class="fa fa-fw fa-trash-alt"></i>
                      </button>
                    </div>
                    <div class="col-md-12">
                      <h5>DETALLE</h5>
                      <textarea class="form-control detalleNota" name="name" style="resize:vertical; height:36px;min-height:35px;"></textarea>
                    </div>
                  </form>
                </div>
                            
                <!-- btn agregar nota -->
                <div class="row agregarNota" style="text-align:center;height:40px;border-top:1px solid #ddd;">
                  <a id="btn-notaNueva" href="#">
                    <div class="col-md-12">
                      <span style="margin-right:20px;font-family:Roboto-Regular; font-size:40px;color:#aaa;">+</span>
                      <span style="font-family:Roboto-BoldCondensed;font-size:16px;position:relative;top:-6px;color:#aaa;">AGREGAR NOTA</span>
                    </div>
                  </a>
                </div>
              </div>
            </div>
          </div> <!-- /Fin panel minimizable -->
          <br>
          <div id="iconoCarga" class="sk-folding-cube">
            <div class="sk-cube1 sk-cube"></div>
            <div class="sk-cube2 sk-cube"></div>
            <div class="sk-cube4 sk-cube"></div>
            <div class="sk-cube3 sk-cube"></div>
          </div>
        </div> <!-- Fin modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
          <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal">CANCELAR</button>
          <input type="hidden" id="id_expediente" value="0">
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
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>
                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmPlataforma" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Está seguro de eliminar el Expediente?</strong>
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


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| AYUDA EXPEDIENTES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Expedientes</h5>
      <p>
        Informe detallado que muestra las últimas tareas o acciones realizadas por los usuarios dentro del sistema.
        Estan clasificadas de acuerdo a la actividad, fecha y tabla en la que fue producida.
        También, están asociadas a notas generadas dentro de expedientes que así lo deseen.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')

    <script src="/js/paginacion.js" charset="utf-8"></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionExpedientes.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <script type="text/javascript">
        $('[data-toggle="popover"]').popover();
    </script>
    @endsection
