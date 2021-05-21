<!DOCTYPE html>

<html>
  <style>
  table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }

  td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
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
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <h2><span>RMTM09 | Producidos diarios por juegos en {{$pro->tipo_moneda}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy);?></span></div>
    <div class="titulo">
      Fecha de producido: <div class="camposInfo" style="top:88px; right:545px;">{{$pro->fecha_prod}}</div>
    </div>
    <div class="camposInfo" style="top:88px; right:345px; font: bold 12px Helvetica, Sans-Serif;">Plataforma:</div>
    <div class="camposInfo" style="top:88px; right:220px;">{{$pro->plataforma}}</div>
    <br><br>
    <table>
      <tr>
        <th class="tablaInicio">BD</th>
        <th class="tablaInicio">JUEGO</th>
        <th class="tablaInicio">APUESTA</th>
        <th class="tablaInicio">PREMIO</th>
        <th class="tablaInicio">BENEFICIO</th>
      </tr>
      @foreach ($detalles as $d)
      <tr>
        <td class="tablaCampos">{{$d->en_bd? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos">{{$d->cod_juego}}</td>
        <td class="tablaCampos">{{$d->apuesta}}</td>
        <td class="tablaCampos">{{$d->premio}}</td>
        <td class="tablaCampos">{{$d->beneficio}}</td>
      </tr>
      @endforeach
    </table>
  </body>
</html>
