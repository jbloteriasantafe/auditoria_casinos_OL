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
      <img src="img/logos/banner_nuevo2_portrait.png" width="900">
      <h2 style="left:35%;"><span>Informe de diferencias de estados</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">
      Se realizaron los procedimientos de control correspondientes y se encontraron las siguientes diferencias
    </div>
    @foreach($resultado as $e => $detalles)
    @if(count($detalles) > 0)
    <br>
    <div class="primerEncabezado" style="text-align: center;">Estado esperado: {{$e}}</div>
    <table>
      <tr>
        <th class="tablaInicio" width="25%">CÓDIGO</th>
        <th class="tablaInicio" width="50%">JUEGO</th>
        <th class="tablaInicio" width="25%">ESTADO RECIBIDO</th>
      </tr>
      @foreach($detalles as $d)
      <tr>
        <td class="tablaCampos">{{$d["codigo"]}}</td>
        <td class="tablaCampos">{{$d["juego"]}}</td>
        <td class="tablaCampos">{{$d["estado_recibido"]}}</td>
      </tr>
      @endforeach
    </table>
    @endif
    @endforeach
  </body>
</html>
