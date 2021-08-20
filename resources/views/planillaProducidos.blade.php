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
  
  .center {
    text-align: center;
  }
  .right {
    text-align: right;
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
      <h2 style="left: 25%;"><span>Juegos Online | Producidos diarios por juegos en {{$pro->tipo_moneda}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy);?></span></div>
    <div class="camposInfo" style="top:88px; left: 0%;"><b>Fecha de producido:</b> {{$pro->fecha_prod}}</div>
    <div class="camposInfo" style="top:88px; left: 25%;"><b>Plataforma:</b> {{$pro->plataforma}}</div>
    <br>
    <table>
      <tr>
        <th class="tablaInicio center">BD</th>
        <th class="tablaInicio center">JUEGO</th>
        <th class="tablaInicio center">APUESTA</th>
        <th class="tablaInicio center">PREMIO</th>
        <th class="tablaInicio center">BENEFICIO</th>
      </tr>
      @foreach ($detalles as $d)
      <tr>
        <td class="tablaCampos center">{{$d->en_bd? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos center">{{$d->cod_juego}}</td>
        <td class="tablaCampos right">{{$d->apuesta}}</td>
        <td class="tablaCampos right">{{$d->premio}}</td>
        <td class="tablaCampos right">{{$d->beneficio}}</td>
      </tr>
      @endforeach
    </table>
  </body>
</html>
