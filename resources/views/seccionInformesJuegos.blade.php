@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInformes">@svg('informes','iconoInformes')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
?>

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
@endsection

        <style>
        .imgwrapper {
          width: 80%;
        }
        </style>
        <div class="row">
        @foreach($resultados as $r)
          <div class="col-md-{{intval(12.0/count($resultados))}}">
            <div class="row">
              <div class="col-lg-12">
                <div class="panel">
                  <center><img width="100%" src="/img/tarjetas/banner_CSF.jpg"></center>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-12 col-xl-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4>{{$r["plataforma"]}}</h4>
                  </div>
                  <div class="panel-body">
                    <table class="table table-fixed tablesorter">
                      <thead>
                        <tr>
                          <th class="col-xs-4">FECHA</th>
                          <th class="col-xs-5">MONEDA</th>
                          <th class="col-xs-3">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody style="height: 356px;">
                      @foreach($r["beneficios"] as $b)
                        <tr id="">
                          <td class="col-xs-4">{{$b->anio_mes}}</td>
                          <td class="col-xs-5">{{$b->moneda}}</td>
                          <td class="col-xs-3">
                            @if($b->estado == 1)
                            <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->plataforma}}" data-moneda="{{$b->moneda}}" class="btn btn-info planilla detalle" type="button">
                                  <i class="fa fa-fw fa-print"></i>
                            </button>
                            @endif
                            @if($b->estado == 0)
                            <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                              <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                            </a>
                            @endif
                        </tr>
                      @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
        </div>
        <!-- Modal planilla relevamientos -->
        <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog" style="width:80%;">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo">
                     <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title">IMPRIMIR PLANILLA</h3>
                    </div>

                    <div  id="colapsadoCargar" class="collapse in">

                    <div class="modal-body modalCuerpo">

                      <form id="frmPlanilla" name="frmPlanilla" class="form-horizontal" novalidate="">

                              <div class="row">
                                  <div class="col-md-12">
                                      <!-- Carga de archivos! | Uno para el modal de nuevo y otro para modificar -->
                                      <div class="zona-file-lg">
                                          <input id="cargaArchivo" data-borrado="false" type="file" multiple>
                                      </div>

                                      <div class="alert alert-danger fade in" role="alert" id="alertaArchivo"><span></span></div>
                                  </div>
                              </div>

                      </form>

                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-successAceptar" id="btn-imprimirPlanilla">IMPRIMIR</button>
                      <button type="button" class="btn btn-default" id="btn-salirPlanilla" data-dismiss="modal">SALIR</button>
                      <input type="hidden" id="id_informe" value="0">
                    </div>
                  </div>
                </div>
              </div>
        </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| INFORMES DE TRAGAMONEDAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Informes de Tragamonedas</h5>
      <p>
        Se presenta un informe final acerca del desempeño mensual de cada casino, teniendo en cuenta puntos como el detalle por día de la cantidad
        de máquinas presentes en cada casino, lo apostado, premios, cantidad de premios totales, el beneficio, su promedio y el % de devolución.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionMTMbeneficios.js" charset="utf-8"></script>
    <script>
      $(document).ready(function(){
          $('[data-toggle="popover"]').popover();
      });
    </script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
