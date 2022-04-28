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
        @foreach($resultados as $id_plat => $r)
          <div class="col-md-{{intval(12.0/count($resultados))}}">
            <div class="row">
              <div class="col-lg-12 col-xl-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4>{{$r["plataforma"]}}</h4>
                    <div class="row">
                      <select id="selectMoneda{{$id_plat}}" class="form-control selectMoneda" data-plataforma="{{$id_plat}}">
                        @foreach($monedas as $m)
                        <option value="{{$m->id_tipo_moneda}}">{{$m->descripcion}}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="panel-body">
                    <table class="table table-fixed tablesorter" data-plataforma="{{$id_plat}}">
                      <thead>
                        <tr>
                          <th class="col-xs-4">FECHA</th>
                          <th class="col-xs-5">MONEDA</th>
                          <th class="col-xs-3">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody style="height: 356px;">
                      @foreach($r["beneficios"] as $b)
                        <tr data-moneda="{{$b->id_tipo_moneda}}">
                          <td class="col-xs-4">{{$b->anio_mes}}</td>
                          <td class="col-xs-3" style="text-align: center;">{{$b->moneda}}</td>
                          <td class="col-xs-5" style="text-align: right;">
                            <button data-plataforma="{{$id_plat}}" data-moneda="{{$b->id_tipo_moneda}}"
                                    data-anio="{{$b->anio}}"       data-mes="{{$b->mes}}" 
                                    class="btn btn-info planilla" type="button">
                                  <i class="fa fa-fw fa-print"></i>
                            </button>
                            <button data-plataforma="{{$id_plat}}" data-moneda="{{$b->id_tipo_moneda}}"
                                    data-anio="{{$b->anio}}"       data-mes="{{$b->mes}}" 
                                    class="btn btn-info planilla2" type="button">
                              <i class="fa fa-fw fa-dollar-sign"></i>
                            </button>
                            <button data-plataforma="{{$id_plat}}" data-moneda="{{$b->id_tipo_moneda}}"
                                    data-anio="{{$b->anio}}"       data-mes="{{$b->mes}}" 
                                    class="btn btn-info planilla_sin_ajuste" type="button">
                              <b style="color: black;font-size: 80%">S/AJU</b>
                            </button>
                            <button data-plataforma="{{$id_plat}}" data-moneda="{{$b->id_tipo_moneda}}"
                                    data-anio="{{$b->anio}}"       data-mes="{{$b->mes}}" 
                                    class="btn btn-info informe_completo" type="button">
                              <i class="fa fa-fw fa-search-plus"></i>
                            </button>
                            <button data-plataforma="{{$id_plat}}" data-moneda="{{$b->id_tipo_moneda}}"
                                    data-anio="{{$b->anio}}"       data-mes="{{$b->mes}}" 
                                    class="btn btn-info planilla_poker" type="button">
                              <b style="color: black;font-size: 80%">Pk</b>
                            </button>
                            @if(!$b->existe)
                            <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                              <i class="fa fa-exclamation" style="color: #FFA726;width: 1em;"></i>
                            </a>
                            @else
                            <a style="display: inline-block;width: 1em">&nbsp;</a>
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
    <script src="js/seccionInformesJuegos.js?3" charset="utf-8"></script>
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
