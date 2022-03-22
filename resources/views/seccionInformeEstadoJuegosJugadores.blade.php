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

.smalltext{
  font-size: 95%;
}
</style>
@endsection

@section('contenidoVista')
<div class="col-md-12">
  <div class="row">
    <div class="col-md-10">
      <div class="row">
        <div><!-- FILTROS DE BÚSQUEDA -->
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
                      <option value="">-Todas las plataformas-</option>
                      @foreach ($plataformas as $p)
                      <option id="{{$p->id_plataforma}}" value="{{$p->id_plataforma}}" data-codigo="{{$p->codigo}}">{{$p->nombre}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <h5>CÓDIGO</h5>
                    <input class="form-control" id="buscadorCodigo" value="">
                  </div>
                  <div class="col-md-3">
                    <h5>Estado</h5>
                    <select id="buscadorEstado" class="form-control">
                      <option selected="" value="">- Todos los estados -</option>
                      @foreach($estados as $e)
                      <option value="{{$e}}">{{$e}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <h5>Rango etario</h5>
                    <div class="input-group">
                      <input id="buscadorRangoEtarioD" class="form-control input-sm" value=""/>
                      <span class="input-group-btn" style="width:0px;"></span>
                      <input id="buscadorRangoEtarioH" class="form-control input-sm" value=""/>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <h5>Sexo</h5>
                    <select id="buscadorSexo" class="form-control" name="">
                      <option selected="" value="">- Todos -</option>
                      @foreach($sexos as $s)
                      <option value="{{$s}}">{{$s}}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <h5>Localidad</h5>
                    <input class="form-control" id="buscadorLocalidad" value=""/>
                  </div>
                  <div class="col-md-3">
                    <h5>Provincia</h5>
                    <input class="form-control" id="buscadorProvincia" value=""/>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <h5>Fecha autoexclusión - Desde</h5>
                    <div class="input-group date" id="dtpFechaAutoexclusionD">
                        <input type="text" class="form-control" placeholder="Fecha de autoexclusión (desde)" id="buscadorFechaAutoexclusionD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                        <span id="input-times-autoexclusionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span id="input-calendar-autoexclusionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <h5>Fecha autoexclusión- Hasta</h5>
                    <div class="input-group date" id="dtpFechaAutoexclusionH">
                        <input type="text" class="form-control" placeholder="Fecha de autoexclusión (hasta)" id="buscadorFechaAutoexclusionH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                        <span id="input-times-autoexclusionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span id="input-calendar-autoexclusionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <h5>Fecha alta - Desde</h5>
                    <div class="input-group date" id="dtpFechaAltaD">
                      <input type="text" class="form-control" placeholder="Fecha de alta (desde)" id="buscadorFechaAltaD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                      <span id="input-times-altaD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span id="input-calendar-altaD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <h5>Fecha alta- Hasta</h5>
                    <div class="input-group date" id="dtpFechaAltaH">
                      <input type="text" class="form-control" placeholder="Fecha de alta (hasta)" id="buscadorFechaAltaH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                      <span id="input-times-altaH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span id="input-calendar-altaH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <h5>Fecha ultimo movimiento - Desde</h5>
                    <div class="input-group date" id="dtpFechaUltimoMovimientoD">
                      <input type="text" class="form-control" placeholder="Fecha del ultimo movimiento (desde)" id="buscadorFechaUltimoMovimientoD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                      <span id="input-times-ultimoMovimientoD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span id="input-calendar-ultimoMovimientoD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <h5>Fecha ultimo movimiento- Hasta</h5>
                    <div class="input-group date" id="dtpFechaUltimoMovimientoH">
                      <input type="text" class="form-control" placeholder="Fecha del ultimo movimiento (hasta)" id="buscadorFechaUltimoMovimientoH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
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
      </div>
      <div class="row">
        <div>
          <div class="panel panel-default" style="width: 100%;">
            <div class="panel-heading">
              <h4>LISTADO DE JUGADORES</h4>
            </div>
            <div class="panel-body">
              <style>
                #tablaJugadores th, #tablaJugadores td{
                  width: 10%;
                  font-size: 95%;
                  text-align: center;
                }
              </style>
              <table id="tablaJugadores" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th value="plataforma" estado="">PLATAFORMA<i class="fa fa-sort"></i></th>
                    <th value="codigo" estado="">CÓDIGO<i class="fa fa-sort"></i></th>
                    <th value="estado" estado="">ESTADO<i class="fa fa-sort"></i></th>
                    <th value="fecha_nacimiento" estado="">F. NACIMIENTO<i class="fa fa-sort"></i></th>
                    <th value="sexo" estado="">SEXO<i class="fa fa-sort"></i></th>
                    <th value="localidad" estado="">LOCALIDAD<i class="fa fa-sort"></i></th>
                    <th value="provincia" estado="">PROVINCIA<i class="fa fa-sort"></i></th>
                    <th value="fecha_ae" estado="">F. AE<i class="fa fa-sort"></i></th>
                    <th value="fecha_alta" estado="">F. ALTA<i class="fa fa-sort"></i></th>
                    <th value="fecha_ultimo_movimiento" estado="">F. ULTIMO MOV.<i class="fa fa-sort"></i></th>
                  </tr>
                </thead>
                <tbody style="height: 350px;">
                </tbody>
              </table>
              <table hidden>
                <tr id="moldeTablaJugadores">
                  <td class="plataforma">PLATAFORMA</td>
                  <td class="codigo">CÓDIGO</td>             
                  <td class="estado">ESTADO</td>
                  <td class="fecha_nacimiento">F. NACIMIENTO</td>
                  <td class="sexo">SEXO</td>
                  <td class="localidad">LOCALIDAD</td>
                  <td class="provincia">PROVINCIA</td>
                  <td class="fecha_ae">F. AE</td>
                  <td class="fecha_alta">F. ALTA</td>
                  <td class="fecha_ultimo_movimiento">F. ULTIMO MOV.</td>
                </tr>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>
          </div>
        </div>
      </div>  <!-- row tabla -->
  </div> <!-- row principal -->
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
    <table id="tablaCSV" class="table table-responsive table-bordered">
      <thead>
        <tr>
          <th class="smalltext plataforma" style="width: 4%;" data-busq="#buscadorPlataforma" data-busq-attr='data-codigo'>PLAT</th>
          <th class="smalltext estado" style="width: 6%;" data-busq="#buscadorEstado">Estado</th>
          <th class="smalltext finalizoAE" style="width: 2%;" data-busq="#finalizoAE">FIN. AE</th>
          <th class="smalltext apellido" style="width: 6%;" data-busq="#buscadorApellido">Apellido</th>
          <th class="smalltext dia_semanal" style="width: 4%" data-busq="#buscadorDia">Día</th>
          <th class="smalltext rango_etario" style="width: 4%" data-busq="#buscadorRangoEtario" rango>Rango Etario</th>
          <th class="smalltext dni" style="width: 7%;" data-busq="#buscadorDni">DNI</th>
          <th class="smalltext sexo" style="width: 5%;" data-busq="#buscadorSexo">Sexo</th>
          <th class="smalltext localidad" style="width: 8%;" data-busq="#buscadorLocalidad">Localidad</th>
          <th class="smalltext provincia" style="width: 8%;" data-busq="#buscadorProvincia">Provincia</th>
          <th class="smalltext f_ae" style="width: 10%;" data-busq="#dtpFechaAutoexclusion" fecha>Fecha AE</th>
          <th class="smalltext f_v" style="width: 10%;" data-busq="#dtpFechaVencimiento"   fecha>Fecha Venc.</th> 
          <th class="smalltext f_r" style="width: 10%;" data-busq="#dtpFechaRevocacion"    fecha>Fecha Revoc.</th>
          <th class="smalltext f_c" style="width: 10%;" data-busq="#dtpFechaCierre"        fecha>Fecha Cierre</th>
          <th class="smalltext hace_encuesta" style="width: 3%;" data-busq="#buscadorEncuesta" >Encuesta</th>
          <th class="smalltext frecuencia" style="width: 3%;" data-busq="#buscadorFrecuencia" >Frecuencia</th>
          <th class="smalltext veces" style="width: 3%;" data-busq="#buscadorVeces" rango opcional>Veces</th>
          <th class="smalltext horas" style="width: 3%;" data-busq="#buscadorHoras" rango opcional>Horas</th>
          <th class="smalltext compania" style="width: 3%;" data-busq="#buscadorCompania" >Compañia</th>
          <th class="smalltext juego" style="width: 3%;" data-busq="#buscadorJuego" >Juego</th>
          <th class="smalltext programa" style="width: 3%;" data-busq="#buscadorJuegoResponsable" >Programa J.R.</th>
          <th class="smalltext socio" style="width: 3%;" data-busq="#buscadorClub" >Socio</th>
          <th class="smalltext autocontrol" style="width: 3%;" data-busq="#buscadorAutocontrol" >Autocontrol</th>
          <th class="smalltext recibir_info" style="width: 3%;" data-busq="#buscadorRecibirInfo" >Recib. Info</th>
          <th class="smalltext medio" style="width: 3%;" data-busq="#buscadorMedio" >Medio</th>
          <th class="smalltext cant" style="width: 6%;">CANT.</th>
        </tr>
      </thead>
      <tbody>
        <tr class="filaTablaCSV" style="display: none">
          <td class="smalltext plataforma"    style="width: 4%;">PLAT</td>
          <td class="smalltext estado"    style="width: 6%;">ESTADO</td>
          <td class="smalltext finalizoAE"    style="width: 2%;">FINALIZO AE</td>
          <td class="smalltext apellido"  style="width: 6%;">APELLIDO</td>
          <td class="smalltext dia_semanal" style="width: 4%">DIA</td>
          <td class="smalltext rango_etario" style="width: 4%">00-99</td>
          <td class="smalltext dni"       style="width: 7%;">DNI</td>
          <td class="smalltext sexo"      style="width: 5%;">S</td>
          <td class="smalltext localidad" style="width: 8%;">LOC</td>
          <td class="smalltext provincia" style="width: 8%;">PROV</td>
          <td class="smalltext f_ae"    style="width: 10%;">Fecha AE</td>
          <td class="smalltext f_v"     style="width: 10%;">Fecha Venc.</td> 
          <td class="smalltext f_r"     style="width: 10%;">Fecha Revoc.</td>
          <td class="smalltext f_c"     style="width: 10%;" >Fecha Cierre</td>
          <td class="smalltext hace_encuesta"     style="width: 3%;">Encuesta</td>
          <td class="smalltext frecuencia"   style="width: 3%;">Frecuencia</td>
          <td class="smalltext veces"        style="width: 3%;">Veces</td>
          <td class="smalltext horas"        style="width: 3%;">Horas</td>
          <td class="smalltext compania"     style="width: 3%;">Compañia</td>
          <td class="smalltext juego"        style="width: 3%;">Juego</td>
          <td class="smalltext programa"     style="width: 3%;">Programa J.R.</td>
          <td class="smalltext socio"        style="width: 3%;" >Socio</td>
          <td class="smalltext autocontrol"  style="width: 3%;">Autocontrol</td>
          <td class="smalltext recibir_info" style="width: 3%;">Recib. Info</td>
          <td class="smalltext medio"        style="width: 3%;">Medio</td>
          <td class="smalltext cant"      style="width: 6%;" >CANT.</td>
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
</div>
  <!-- token -->
  <meta name="_token" content="{!! csrf_token() !!}" />
  @endsection


  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Sesiones</h5>
    <p>
      Agregar nuevos autoexluidos, revocar autoexclusiones, ver listado y estados.
  </div>
  @endsection
  <!-- Termina modal de ayuda -->


  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="js/paginacion.js" charset="utf-8"></script>
  <!-- JavaScript personalizado -->
  <script src="/js/seccionInformeEstadoJuegosJugadores.js" charset="utf-8"></script>
  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
  @endsection
