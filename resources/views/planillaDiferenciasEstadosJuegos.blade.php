<!DOCTYPE html>
<?php
  $cols_x_pag = 2;
  $ancho_tabla = (104.0/$cols_x_pag);
  $filas_por_col = 69.0;
  $posicion = [
    0 =>  'position: absolute;top: 100px;left: -5%;',
    1 =>  'position: absolute;top: 100px;right: -5%;',
  ];
  $filas_por_pag = $filas_por_col*$cols_x_pag;
  $paginas_por_estado = [];
  foreach($resultado as $e => $detalles){
    $paginas_por_estado[$e] = ceil(count($detalles)/$filas_por_pag);
  }
  $hoy = date('j-m-y / h:i');

  $primer_estado = '';//Usado para NO insertar el salto de pagina en la primer pagina
  if(count($resultado) > 0) $primer_estado = array_keys($resultado)[0];
?>

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

.center {
  text-align: center;
}
.small {
  font-size: 7.5 !important;
  padding: 0 !important;
}
.elipses {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
</style>

  <head>
    <meta charset="utf-8">
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{public_path()}}/css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>
    @foreach($paginas_por_estado as $e => $pags)
    @for($p = 0;$p < $pags;$p++)

    @if($e != $primer_estado || $p > 0)
    <div style="page-break-after:always;"></div>
    @endif
    <?php
      $detalles = $resultado[$e];
      $cantidad = count($detalles);
    ?>
    <div class="encabezadoImg">
      <img src="{{public_path()}}/img/logos/banner_nuevo2_portrait.png" width="900">
      <h2 style="text-align: center;">
        <span>Informe de diferencias de estados ({{$plataforma}})</span>
        <br style="margin: 0;">
        <span>Estado en sistema: {{$e}} ({{$cantidad}} juego{{$cantidad > 1? 's' : ''}})</span>
      </h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span>{{$hoy}}</span></div>
    <br>

    <?php
      $startidxpag = $p*$filas_por_pag;
      $endidxpag   = ($p+1)*$filas_por_pag;
    ?>

    @for($col=0;$col<$cols_x_pag;$col++)

    <?php 
        $start = $startidxpag+$filas_por_col*$col;
        $end   = min($startidxpag+$filas_por_col*($col+1),count($detalles));
    ?>

    @if($start<$end)
    <table style="table-layout:fixed;width: {{$ancho_tabla}}%;{{$posicion[$col%$cols_x_pag]}}">
      <tr>
        <th class="tablaInicio center small" width="25%">CÓDIGO</th>
        <th class="tablaInicio center small" width="50%">JUEGO</th>
        <th class="tablaInicio center small" width="25%">ESTADO RECIBIDO</th>
      </tr>
      @for($i=$start;$i<$end;$i++)
      <?php $d = $detalles[$i] ?>
      <tr>
        <td class="tablaCampos center small">{{$d["codigo"]}}</td>
        <td class="tablaCampos elipses small">{{$d["juego"]}}</td>
        <td class="tablaCampos center small">{{$d["estado_recibido"]}}</td>
      </tr>
      @endfor
    </table>
    @endif

    @endfor
    <!-- for $cols -->

    @endfor
    <!-- for $pags  -->

    @endforeach
    <!-- foreach $paginas_por_estado -->
  </body>
</html>
