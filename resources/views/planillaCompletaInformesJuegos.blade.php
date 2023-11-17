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
  font-size: 4.5 !important;
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

@page{
  margin-left: 2%;
  margin-right: 2%;
}

</style>
  <?php
    function es_fecha($f){
      return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$f);
    }
    function sacar_dia($f){
      return intval(explode('-',$f)[2]);
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
    <div class="encabezadoImg" >
      <center>
        <img src="img/logos/banner_nuevo2_landscape.png" style="width: 900px;">
      </center>
      <h2 style="left:22%;"><span>Juegos Online | Informe completo de beneficios {{$plataforma}} - {{$fecha}} - {{$moneda}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:200px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:200px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <table style="table-layout: fixed;" style="width: 150%;">
      <tr>
        @foreach($header as $h)
        <th class="tablaInicio center">{{$h}}</th>
        @endforeach
      </tr>
      @foreach ($dias as $d)
      <tr>
        @foreach ($d as $campo)
        <td class="tablaCampos center">{{es_fecha($campo)? sacar_dia($campo) : $campo}}</td>
        @endforeach
      </tr>
      @endforeach
      <tr class="total">
        @foreach($total as $campo)
        <td class="tablaCampos total center">{{$campo}}</td>
        @endforeach
      </tr>
    </table>
  </body>
</html>
