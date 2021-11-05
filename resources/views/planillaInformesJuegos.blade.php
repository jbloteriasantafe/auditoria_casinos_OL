<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 98%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 3px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

.total {
  border-top: 2px double black;
}

.center {
  text-align: center;
}
.right {
  text-align: right;
}

</style>
  <?php 
  $widths = ["fecha" => "9","jugadores" => "11","apostado" => "21","premios" => "21","ajuste" => "10", "beneficio" => "21","dev" => "7"];
  if($cotizacionDefecto != 1){
    $widths = ["fecha" => "9","jugadores" => "11","apostado" => "16","premios" => "16", "cotizacion" => "15","ajuste" => "10","beneficio" => "16","dev" => "7"];
  }
  //El campo beneficio YA VIENE ajustado, la apuesta y premio no
  //Si es simplificado
  //APUESTA = A
  //PREMIO = P + Ajus
  //BENEFICIO(AJUSTADO) = APUESTA - PREMIO (A - P - Ajus)

  //Si es sin ajuste
  //APUESTA = A
  //PREMIO = P
  //BENEFICIO(SIN AJUSTAR) = BENEFICIO_AJUSTADO + AJUSTE

  if($simplificado){
    $sumar_ajuste_al_premio = true;
  }
  if($sin_ajuste){
    $sumar_ajuste_al_beneficio = true;
  }
  $total_correcto = number_format($total->beneficio + ($sumar_ajuste_al_beneficio? $total->ajuste : 0),2,",",".");
  ?>
  <head>
    <meta charset="utf-8">
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_portrait.png" width="900">
      <h2 style="left:35%;"><span>Juegos Online | Informe de beneficios ({{$total->moneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al mes de <b>{{$mesTexto}}</b> de la <b>Plataforma de {{$total->plataforma}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Juegos Online</b>
      se obtuvo un beneficio de <b>${{$total_correcto}}</b>, detallando a continuación el beneficio diario.
    </div>
    <br>
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center" width="{{$widths['fecha']}}%">FECHA</th>
        <th class="tablaInicio center" width="{{$widths['jugadores']}}%">JUGADORES</th>
        <th class="tablaInicio center" width="{{$widths['apostado']}}%">APOSTADO</th>
        <th class="tablaInicio center" width="{{$widths['premios']}}%">PREMIOS</th>
        @if(!$simplificado)
        <th class="tablaInicio center" width="{{$widths['ajuste']}}%">AJUSTES</th>
        @endif
        @if($cotizacionDefecto != 1)
        <th class="tablaInicio center" width="{{$widths['cotizacion']}}%">COTIZACION (*)</th>
        @endif
        <th class="tablaInicio center" width="{{$widths['beneficio']}}%">BENEFICIO</th>
        @if(!$simplificado)
        <th class="tablaInicio center" width="{{$widths['dev']}}%">% DEV</th>
        @endif
      </tr>
      <?php $ultima_cotizacion = $cotizacionDefecto;?>
      @foreach ($dias as $d)
      <?php 
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion; 
      ?>
      <tr>
        <td class="tablaCampos center">{{$d->fecha}}</td>
        <td class="tablaCampos center ">{{$d->jugadores}}</td>
        <td class="tablaCampos right">{{number_format($d->apuesta,2,",",".")}}</td>
        <td class="tablaCampos right">{{number_format($d->premio + ($sumar_ajuste_al_premio? $d->ajuste : 0),2,",",".")}}</td>
        @if(!$simplificado)
        <td class="tablaCampos right">{{number_format($d->ajuste,2,",",".")}}</td>
        @endif
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos right">{{number_format($ultima_cotizacion,3,",",".")}}</td>
        @endif
        <td class="tablaCampos right">{{number_format(($d->beneficio + ($sumar_ajuste_al_beneficio? $d->ajuste : 0))*$ultima_cotizacion,2,",",".")}}</td>
        @if(!$simplificado)
        <td class="tablaCampos right">{{$d->apuesta != 0.0? number_format(round(100*$d->premio/$d->apuesta,2),2,",",".") : '-'}}</td>
        @endif
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos total center">{{$total->fecha}}</td>
        <td class="tablaCampos total center">{{$total->jugadores}}</td>
        <td class="tablaCampos total right">{{number_format($total->apuesta,2,",",".")}}</td>
        <td class="tablaCampos total right">{{number_format($total->premio + ($sumar_ajuste_al_premio? $total->ajuste : 0),2,",",".")}}</td>
        @if(!$simplificado)
        <td class="tablaCampos total right">{{number_format($total->ajuste,2,",",".")}}</td>
        @endif
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos total right">-</td>
        @endif
        <td class="tablaCampos total right">{{$total_correcto}}</td>
        @if(!$simplificado)
        <td class="tablaCampos total right">{{$total->apuesta != 0.0? number_format(round(100*$total->premio/$total->apuesta,2),2,",",".") : '-'}}</td>
        @endif
      </tr>
    </table>
    @if($cotizacionDefecto != 1)
    <div>
      <p> 
        <FONT SIZE=1> <strong>* </strong>Cotización establecida por la Dirección General de Casinos y Bingos (Nota N° 277/16) <br>
        <i> "... se utilizará como tipo de cambio para efectuar la conversión a pesos, el valor del dólar 
          oficial tipo comprador (información suministrada por el Banco de la Nación Argentina) correspondiente a la fecha de producción
            de las MTM. Para el caso de los días Sábados, Domingos y Feriados, se utilizará como tipo de cambio, el del último día hábil disponible.."
          </FONT>
        </i>
      </p>
    </div>
    @endif
  </body>
</html>
