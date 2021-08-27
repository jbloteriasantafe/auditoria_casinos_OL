<!DOCTYPE html>

<?php
/*
    Esta tabla ocupa toda la pagina
    <table style="table-layout:fixed;width: 106.6%;position: absolute;left:-6%;">
      <tr><th class="tablaInicio">TEST!</th></tr>
    </table>
    Osea que la pagina "comienza" en -6 y tiene un ancho de 106.6
*/
  $ancho_total_pagina = 106.6;
  $inicio_pagina = -6;

  //$cols_x_pag viene del controlador
  $ancho_divisiones = $ancho_total_pagina/$cols_x_pag;
  $pad_fijo = 1.0;
  //La tabla mide la division menos los 2 pads (1 de cada lado)
  $ancho_tabla = $ancho_divisiones - 2*$pad_fijo;

  $posicion = [];
  {
    $posx = $inicio_pagina + $pad_fijo;
    $posicion[0] = 'position: absolute;left:'.$posx.'%;';
    for($col=1;$col<$cols_x_pag;$col++){
      $posx += $ancho_tabla;
      $posx += 2*$pad_fijo;
      $posicion[$col] = 'position: absolute;left:'.$posx.'%;';
    }
  }

  //$filas_por_col viene del controlador
  $filas_por_pag = $filas_por_col*$cols_x_pag;
  $paginas = ceil(count($detalles) /$filas_por_pag);

  $hoy = date('j-m-y / h:i');
?>

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
    font-size: 7.5 !important;
    padding: 0 !important;
  }
  .right {
    text-align: right;
    font-size: 7.5 !important;
    padding: 0 !important;
  }
  .helvetica{
    font: 11px Helvetica, Sans-Serif;
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
    @for($p = 0;$p < $paginas;$p++)
    @if($p != 0)
    <div style="page-break-after:always;"></div>
    @endif
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <h2 style="left: 25%;"><span>Juegos Online | Producidos diarios por {{$tipo}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span>{{$hoy}}</span></div>
    <div style="position: absolute; top: 90px">
      <div class="helvetica" style="position: absolute;left:  0%;width: 25%;text-align: center;"><b>Fecha de producido:</b> {{$pro->fecha_prod}}</div>
      <div class="helvetica" style="position: absolute;left: 25%;width: 25%;text-align: center;"><b>Plataforma:</b> {{$pro->plataforma}}</div>
      <div class="helvetica" style="position: absolute;left: 50%;width: 25%;text-align: center;"><b>Moneda:</b> {{$pro->tipo_moneda}}</div>
      <div class="helvetica" style="position: absolute;left: 75%;width: 25%;text-align: center;"><b>Cantidad:</b> {{$cantidad_totales}}</div>
    </div>
    <?php
      $startidxpag = $p*$filas_por_pag;
      $endidxpag   = $startidxpag + $filas_por_pag;
    ?>
    @for($col=0;$col<$cols_x_pag;$col++)
    <?php 
      $start = $startidxpag + $filas_por_col * $col;
      $end   = min($start+$filas_por_col,count($detalles));
    ?>
    @if($start<$end)
    <table style="table-layout:fixed;width: {{$ancho_tabla}}%;{{$posicion[$col]}}">
      <tr>
        @if($tipo == 'juegos')
        <th class="tablaInicio center" style="width: 10%;">BD</th>
        <th class="tablaInicio center">JUEGO</th>
        <th class="tablaInicio center">APUESTA</th>
        <th class="tablaInicio center">PREMIO</th>
        <th class="tablaInicio center">BENEFICIO</th>
        @elseif($tipo == 'jugadores')
        <th class="tablaInicio center">JUGADOR</th>
        <th class="tablaInicio center">APUESTA</th>
        <th class="tablaInicio center">PREMIO</th>
        <th class="tablaInicio center">BENEFICIO</th>
        @endif
      </tr>
      @for($i=$start;$i<$end;$i++)
      <?php $d = $detalles[$i] ?>
      <tr>
        @if($tipo == 'juegos')
        <td class="tablaCampos center">{{$d->en_bd? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos center">{{$d->cod_juego}}</td>
        <td class="tablaCampos right">{{$d->apuesta}}</td>
        <td class="tablaCampos right">{{$d->premio}}</td>
        <td class="tablaCampos right">{{$d->beneficio}}</td>
        @elseif($tipo == 'jugadores')
        <td class="tablaCampos center">{{$d->jugador}}</td>
        <td class="tablaCampos right">{{$d->apuesta}}</td>
        <td class="tablaCampos right">{{$d->premio}}</td>
        <td class="tablaCampos right">{{$d->beneficio}}</td>
        @endif
      </tr>
      @endfor
    </table>
    @endif
    @endfor
    @endfor
  </body>
</html>
