<?php
$total = 0;
foreach($data as $sexo => $por_edad) foreach($por_edad as $grupo => $cantidad)  $total += $cantidad;

$total_por_sexo = [];
foreach($data as $sexo => $por_edad) foreach($por_edad as $grupo => $cantidad)  $total_por_sexo[$sexo] = ($total_por_sexo[$sexo] ?? 0) + $cantidad;

$total_por_edad = [];
foreach($data as $sexo => $por_edad) foreach($por_edad as $grupo => $cantidad)  $total_por_edad[$grupo] = ($total_por_edad[$grupo] ?? 0) + $cantidad;

$total_por_sexo = collect($total_por_sexo)->sortBy(function($arr,$k){return $k;});
$total_por_edad = collect($total_por_edad)->sortBy(function($arr,$k){return $k;});
?>


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
  padding: 0;
  margin: 0;
  padding-top: 1;
  padding-bottom: 1;
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

.forzar_blanco {
  background-color: white !important;
}

.total_horizontal {
  border-right: 2px double black;
}

.separador_sexo_edades {
  border-bottom: 2px double #a0a0a0 !important;
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
      <img src="img/logos/banner_2024_portrait.png" width="900">
      <h2><span>Juegos Online | Informe demográfico {{$plataforma}} - {{$anio}} - {{$mes}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>

    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center">SEXO</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center total_horizontal">%</th>
        <th class="tablaInicio center">EDAD</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center">%</th>
      </tr>

      @foreach($data as $sexo => $por_edad) 
      @foreach($por_edad as $grupo => $cantidad)
      <tr>
        @if($loop->first)
        <th class="tablaInicio center forzar_blanco" rowspan={{count($por_edad)}}>{{$sexo}}</th>
        <td class="tablaCampos center forzar_blanco" rowspan={{count($por_edad)}}>{{$total_por_sexo[$sexo] ?? 0}}</td>
        <td class="tablaCampos center forzar_blanco total_horizontal" rowspan={{count($por_edad)}}>{{number_format(100*($total_por_sexo[$sexo] ?? 0)/$total,3,',','.')}}%</td>
        @endif
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{$grupo}}</td>
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{$cantidad}}</td>
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{number_format(100*$cantidad/$total,3,',','.')}}%</td>
      </tr>
      @endforeach
      @endforeach
      @foreach($total_por_edad as $grupo => $cantidad)
      <tr class="total">
        @if($loop->first)
        <th class="tablaCampos center total forzar_blanco" rowspan={{count($total_por_edad)}}>TOTAL</th>
        <td class="tablaCampos center total forzar_blanco" rowspan={{count($total_por_edad)}}>{{$total}}</td>
        <td class="tablaCampos center total forzar_blanco total_horizontal" rowspan={{count($total_por_edad)}}>100%</td>
        @endif
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$grupo}}</td>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$cantidad}}</td>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{number_format(100*$cantidad/$total,3,',','.')}}%</td>
      </tr>
      @endforeach
    </table>
  </body>
</html>
