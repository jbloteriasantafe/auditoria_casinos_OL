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
        <th class="tablaInicio center">%</th>
      </tr>
      
      @foreach($total_por_sexo as $sexo => $cantidad) 
      <tr>
        <td class="tablaCampos center">{{$sexo}}</td>
        <td class="tablaCampos center">{{$cantidad}}</td>
        <td class="tablaCampos center">{{number_format(100*$cantidad/$total,3,',','.')}}%</td>
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos center total">---</td>
        <td class="tablaCampos center total">{{$total}}</td>
        <td class="tablaCampos center total">100%</td>
      </tr>
    </table>
    
    <br>
    <br>

    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center">EDAD</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center">%</th>
      </tr>

      @foreach($total_por_edad as $grupo => $cantidad) 
      <tr>
        <td class="tablaCampos center">{{$grupo}}</td>
        <td class="tablaCampos center">{{$cantidad}}</td>
        <td class="tablaCampos center">{{number_format(100*$cantidad/$total,3,',','.')}}%</td>
      </tr>
      @endforeach
      <tr class="total">
        <td class="tablaCampos center total">---</td>
        <td class="tablaCampos center total">{{$total}}</td>
        <td class="tablaCampos center total">100%</td>
      </tr>
    </table>

    <br>
    <br>
    
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center">SEXO</th>
        <th class="tablaInicio center">EDAD</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center">%</th>
      </tr>

      @foreach($data as $sexo => $por_edad) 
      @foreach($por_edad as $grupo => $cantidad)
      <tr>
        <td class="tablaCampos center">{{$sexo}}</td>
        <td class="tablaCampos center">{{$grupo}}</td>
        <td class="tablaCampos center">{{$cantidad}}</td>
        <td class="tablaCampos center">{{number_format(100*$cantidad/$total,3,',','.')}}%</td>
      </tr>
      @endforeach
      @endforeach
      <tr class="total">
        <td class="tablaCampos center total">---</td>
        <td class="tablaCampos center total">---</td>
        <td class="tablaCampos center total">{{$total}}</td>
        <td class="tablaCampos center total">100%</td>
      </tr>
    </table>

  </body>
</html>





