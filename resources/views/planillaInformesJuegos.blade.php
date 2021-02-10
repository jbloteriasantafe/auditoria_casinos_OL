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
          <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
          <h2><span>MTM | Informe de beneficios ({{$total->moneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al mes de <b>{{$mesTexto}}</b> de la <b>Plataforma de {{$total->plataforma}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Juegos Online</b>
      se obtuvo un beneficio de <b>${{$total_beneficio}}</b>, detallando a continuación el producido diario.
    </div>
    <br>
    <table>
      <tr>
        <th class="tablaInicio">FECHA</th>
        <th class="tablaInicio">JUGADORES</th>
        <th class="tablaInicio">APOSTADO</th>
        <th class="tablaInicio">PREMIOS</th>
        @if($cotizacionDefecto != 1)
        <th class="tablaInicio">COTIZACION</th>
        @endif
        <th class="tablaInicio">BENEFICIO</th>
      </tr>
      <?php $ultima_cotizacion = $cotizacionDefecto;?>
      @foreach ($dias as $d)
      <?php 
        $ultima_cotizacion = $d->cotizacion?? $ultima_cotizacion; 
      ?>
      <tr>
        <td class="tablaCampos">{{$d->fecha}}</td>
        <td class="tablaCampos">{{$d->jugadores}}</td>
        <td class="tablaCampos">{{$d->ingreso}}</td>
        <td class="tablaCampos">{{$d->premio}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos">{{$ultima_cotizacion}}</td>
        @endif
        <td class="tablaCampos">{{$d->valor*$ultima_cotizacion}}</td>
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos total">{{$total->fecha}}</td>
        <td class="tablaCampos total">{{$total->jugadores}}</td>
        <td class="tablaCampos total">{{$total->ingreso}}</td>
        <td class="tablaCampos total">{{$total->premio}}</td>
        @if($cotizacionDefecto != 1)
        <td class="tablaCampos total">-</td>
        @endif
        <td class="tablaCampos total">{{$total_beneficio}}</td>
      </tr>
    </table>
  </body>
</html>
