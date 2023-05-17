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
  $widths = ["fecha" => "15","jugadores" => "25","drop" => "30","utilidad" => "30"];
  if($cotizacionDefecto != 1){
    $widths = ["fecha" => "10","jugadores" => "15","drop" => "20","utilidad" => "20","cotizacion" => "15","conversion" => "20"];
  }
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
      <h2 style="left:35%;"><span>Poker Online | Informe de beneficios ({{$total->moneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al mes de <b>{{$mesTexto}}</b> de la <b>Plataforma de {{$total->plataforma}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Poker Online</b>
      se obtuvo un beneficio de <b>${{number_format($total_beneficio,2,",",".")}}</b>, detallando a continuación el beneficio diario.
    </div>
    <br>
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center" width="{{$widths['fecha']}}%">FECHA</th>
        <th class="tablaInicio center" width="{{$widths['jugadores']}}%">JUGADORES</th>
        <th class="tablaInicio center" width="{{$widths['drop']}}%">MONTO JUGADO</th>
        <th class="tablaInicio center" width="{{$widths['utilidad']}}%">BENEFICIO</th>
        @if($cotizacionDefecto != 1)
        <th class="tablaInicio center" width="{{$widths['cotizacion']}}%">COTIZACION (*)</th>
        <th class="tablaInicio center" width="{{$widths['conversion']}}%">CONVERSION</th>
        @endif
      </tr>
      @foreach ($dias as $d)
      <tr>
        <td class="tablaCampos center">{{$d->fecha}}</td>
        <td class="tablaCampos right">{{$d->jugadores}}</td>
        <td class="tablaCampos right">{{number_format($d->droop,2,",",".")}}</td>
        <td class="tablaCampos right">{{number_format($d->utilidad,2,",",".")}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos right">{{number_format($d->cotizacion,3,",",".")}}</td>
        <td class="tablaCampos right">{{number_format($d->utilidad*$d->cotizacion,2,",",".")}}</td>
        @endif
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos total center">{{$total->fecha}}</td>
        <td class="tablaCampos total right">{{$total->jugadores}}</td>
        <td class="tablaCampos total right">{{number_format($total->droop,2,",",".")}}</td>
        <td class="tablaCampos total right">{{number_format($total->utilidad,2,",",".")}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos total right">-</td>
        <td class="tablaCampos total right">{{number_format($total_beneficio,2,",",".")}}</td>
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
