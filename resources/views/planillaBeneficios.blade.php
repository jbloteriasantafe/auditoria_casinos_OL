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
      <h2 style="left:28%;"><span>RMTM03 | Control de beneficio diario en {{$ben->moneda}}.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>

    <div class="titulo">
        Mes de beneficio: <div class="camposInfo" style="position:relative; top:-14px; left:110px;"> {{$ben->mes}} de {{$ben->anio}} </div>
    </div>

    <div class="camposInfo" style="top:85px; right:345px; font: bold 12px Helvetica, Sans-Serif;">Casino:</div>
    <div class="camposInfo" style="position: relative; left:385px; top:-28px;">{{$ben->plataforma}}</div>

    <table>
      <tr>
        <th class="tablaInicio">FECHA</th>
        <th class="tablaInicio">BENEFICIO CALCULADO</th>
        <th class="tablaInicio">BENEFICIO IMPORTADO</th>
        <th class="tablaInicio">AJUSTE</th>
        <th class="tablaInicio">AJUSTE AUDITORIA</th>
        <th class="tablaInicio">DIFERENCIAS</th>
      </tr>
      @foreach ($dias as $d)
      <tr>
        <td class="tablaCampos">{{$d->fecha}}</td>
        <td class="tablaCampos">{{$d->bcalculado}}</td>
        <td class="tablaCampos">{{$d->bimportado}}</td>
        <td class="tablaCampos">{{$d->ajuste}}</td>
        <td class="tablaCampos">{{$d->ajuste_auditoria}}</td>
        <td class="tablaCampos">{{$d->dif}}</td>
      </tr>
      @endforeach
    </table>
    <br>
    <div class="titulo">
        Observaciones: <div style="top:-14px; left:100px; font: normal 12px Helvetica, Sans-Serif;"></div>
        <div style="color: #dddddd;"><!-- Justo te entran 666 puntos!!!! -->
          @for($i = 0; $i<666; $i++)
          .
          @endfor
        </div>
    </div>
  </body>
</html>
