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
</style>
  <?php 
  $widths = ["fecha" => "9","jugadores" => "11","apostado" => "21","premios" => "21","ajuste" => "10", "beneficio" => "21","dev" => "7"];
  if($cotizacionDefecto != 1){
    $widths = ["fecha" => "9","jugadores" => "11","apostado" => "16","premios" => "16", "cotizacion" => "15","ajuste" => "10","beneficio" => "16","dev" => "7"];
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
      <h2 style="left:35%;"><span>MTM | Informe de beneficios ({{$total->moneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al mes de <b>{{$mesTexto}}</b> de la <b>Plataforma de {{$total->plataforma}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Juegos Online</b>
      se obtuvo un beneficio de <b>${{$total_beneficio}}</b>, detallando a continuación el beneficio diario.
    </div>
    <br>
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio" width="{{$widths['fecha']}}%">FECHA</th>
        <th class="tablaInicio" width="{{$widths['jugadores']}}%">JUGADORES</th>
        <th class="tablaInicio" width="{{$widths['apostado']}}%">APOSTADO</th>
        <th class="tablaInicio" width="{{$widths['premios']}}%">PREMIOS</th>
        <th class="tablaInicio" width="{{$widths['ajuste']}}%">AJUSTES</th>
        @if($cotizacionDefecto != 1)
        <th class="tablaInicio" width="{{$widths['cotizacion']}}%">COTIZACION (*)</th>
        @endif
        <th class="tablaInicio" width="{{$widths['beneficio']}}%">BENEFICIO</th>
        <th class="tablaInicio" width="{{$widths['dev']}}%">% DEV</th>
      </tr>
      <?php $ultima_cotizacion = $cotizacionDefecto;?>
      @foreach ($dias as $d)
      <?php 
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion; 
      ?>
      <tr>
        <td class="tablaCampos">{{$d->fecha}}</td>
        <td class="tablaCampos">{{$d->jugadores}}</td>
        <td class="tablaCampos">{{$d->apuesta}}</td>
        <td class="tablaCampos">{{$d->premio}}</td>
        <td class="tablaCampos">{{$d->ajuste}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos">{{$ultima_cotizacion}}</td>
        @endif
        <td class="tablaCampos">{{$d->beneficio*$ultima_cotizacion}}</td>
        <td class="tablaCampos">{{$d->apuesta != 0.0? round(100*$d->premio/$d->apuesta,2) : '-'}}</td>
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos total">{{$total->fecha}}</td>
        <td class="tablaCampos total">{{$total->jugadores}}</td>
        <td class="tablaCampos total">{{$total->apuesta}}</td>
        <td class="tablaCampos total">{{$total->premio}}</td>
        <td class="tablaCampos total">{{$total->ajuste}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos total">-</td>
        @endif
        <td class="tablaCampos total">{{$total_beneficio}}</td>
        <td class="tablaCampos total">{{$total->apuesta != 0.0? round(100*$total->premio/$total->apuesta,2) : '-'}}</td>
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
