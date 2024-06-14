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
  $widths = ["fecha" => "10","usuarios" => "17","apostado" => "17","premios" => "17","ajuste" => "13", "beneficio" => "17","dev" => "8","poker" =>"17"];
  if($cotizacionDefecto != 1){
    $widths = ["fecha" => "9","usuarios" => "15","apostado" => "15","premios" => "15", "cotizacion" => "11","ajuste" => "12","beneficio" => "15","dev" => "8","poker" => "15"];
  }
  //El campo beneficio YA VIENE ajustado, la apuesta y premio no
  //Si es simplificado
  //APUESTA = A
  //PREMIO = P + Ajus
  //BENEFICIO(AJUSTADO) = APUESTA - PREMIO (A - P - Ajus)
  $sumar_ajuste_al_premio = !$jol;
  $mostrar_usuarios       = $jol;
  $mostrar_poker          = !$jol;
  $mostrar_pdev           = $jol;
  $total_cotizado_beneficio_y_poker = $total_cotizado->beneficio + $total_cotizado->poker;
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
      <img src="img/logos/banner_2024_portrait.png" width="900">
      <h2 style="left:35%;"><span>Juegos Online | Informe de beneficios ({{$total->moneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al mes de <b>{{$mesTexto}}</b> de la <b>Plataforma de {{$total->plataforma}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Juegos Online</b>
      se obtuvo un beneficio de <b>${{number_format($total_cotizado_beneficio_y_poker,2,",",".")}}</b>, detallando a continuación el beneficio diario.
    </div>
    <br>
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center" width="{{$widths['fecha']}}%">FECHA</th>
        @if($mostrar_usuarios)
        <th class="tablaInicio center" width="{{$widths['usuarios']}}%">USUARIOS</th>
        @endif
        <th class="tablaInicio center" width="{{$widths['apostado']}}%">APOSTADO</th>
        <th class="tablaInicio center" width="{{$widths['premios']}}%">PREMIOS</th>
        @if(!$sumar_ajuste_al_premio)
        <th class="tablaInicio center" width="{{$widths['ajuste']}}%">AJUSTES</th>
        @endif
        @if($cotizacionDefecto != 1)
        <th class="tablaInicio center" width="{{$widths['cotizacion']}}%">COTIZACION (*)</th>
        @endif
        <th class="tablaInicio center" width="{{$widths['beneficio']}}%">BENEFICIO</th>
        @if($mostrar_pdev)
        <th class="tablaInicio center" width="{{$widths['dev']}}%">% DEV</th>
        @endif
        @if($mostrar_poker)
        <th class="tablaInicio center" width="{{$widths['poker']}}%">POKER</th>
        @endif
      </tr>
      @foreach ($dias as $d)
      <tr>
        <td class="tablaCampos center">{{$d->fecha}}</td>
        @if($mostrar_usuarios)
        <td class="tablaCampos center">{{$d->jugadores}}</td>
        @endif
        <td class="tablaCampos right">{{number_format($d->apuesta,2,",",".")}}</td>
        <td class="tablaCampos right">{{number_format($d->premio + ($sumar_ajuste_al_premio? $d->ajuste : 0),2,",",".")}}</td>
        @if(!$sumar_ajuste_al_premio)
        <td class="tablaCampos right">{{number_format($d->ajuste,2,",",".")}}</td>
        @endif
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos right">{{number_format($d->cotizacion,3,",",".")}}</td>
        @endif
        <td class="tablaCampos right">{{number_format($d->beneficio*$d->cotizacion,2,",",".")}}</td>
        @if($mostrar_pdev)
        <td class="tablaCampos right">{{$d->apuesta != 0.0? number_format(round(100*$d->premio/$d->apuesta,2),2,",",".") : '-'}}</td>
        @endif
        @if($mostrar_poker)
        <td class="tablaCampos right">{{number_format($d->poker*$d->cotizacion,2,",",".")}}</td>
        @endif
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos total center">{{$total->fecha}}</td>
        @if($mostrar_usuarios)
        <td class="tablaCampos total center">{{$total->jugadores}}</td>
        @endif
        <td class="tablaCampos total right">{{number_format($total->apuesta,2,",",".")}}</td>
        <td class="tablaCampos total right">{{number_format($total->premio + ($sumar_ajuste_al_premio? $total->ajuste : 0),2,",",".")}}</td>
        @if(!$sumar_ajuste_al_premio)
        <td class="tablaCampos total right">{{number_format($total->ajuste,2,",",".")}}</td>
        @endif
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos total right">-</td>
        @endif
        <td class="tablaCampos total right">{{number_format($total_cotizado->beneficio,2,",",".")}}</td>
        @if($mostrar_pdev)
        <td class="tablaCampos total right">{{$total->apuesta != 0.0? number_format(round(100*$total->premio/$total->apuesta,2),2,",",".") : '-'}}</td>
        @endif
        @if($mostrar_poker)
        <td class="tablaCampos total right">{{number_format($total_cotizado->poker,2,",",".")}}</td>
        @endif
      </tr>
    </table>
    @if($cotizacionDefecto != 1)
    <div>
      <p> 
        <FONT SIZE=1> <strong>* </strong>Cotización establecida por la Dirección General de Casinos y Bingos (Nota N° 277/16 y 212/22) <br>
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
