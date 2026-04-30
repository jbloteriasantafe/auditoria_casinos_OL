<?php

$rangos_edad = [
  [-1,17.99999,'-18'],
  [17.9999,35.0,'18-35'],
  [35.0,45.0,'36-45'],
  [45.0,55.0,'46-55'],
  [55.0,65.0,'56-65'],
  [65.0,75.0,'66-75'],
  [75.0,INF,'75+']
];

$en_bd = $en_bd->transform(function(&$j) use ($rangos_edad){
  $edad = floatval($j->edad);
  assert($edad >= 18 || $edad === null);
  if($edad === null){
    $edad = '-';
  }
  else{
    assert($edad >= 18);//No deberia venir ningún menor
    $entro_en_alguno = false;
    foreach($rangos_edad as $rango){
      if($edad > $rango[0] && $edad <= $rango[1]){
        $edad = $rango[2];
        $entro_en_alguno = true;
        break;
      }
    }
  }
  $j->edad = $edad;
  return $j;
});


$total_en_bd = 0;
$total_en_bd_jugo = [];
$total_en_bd_sexo = [];
$total_en_bd_edad = [];
$total_en_bd_jugo_sexo = [];
$total_en_bd_jugo_edad = [];
$total_en_bd_jugo_sexo_edad = [];
$total_en_bd_sexo_edad = [];

foreach($en_bd as $j){
  $total_en_bd++;
  $total_en_bd_jugo[$j->jugo] = $total_en_bd_jugo[$j->jugo] ?? 0;
  $total_en_bd_jugo[$j->jugo]++;
  
  $total_en_bd_sexo[$j->sexo] = $total_en_bd_sexo[$j->sexo] ?? 0;
  $total_en_bd_sexo[$j->sexo]++;
  $total_en_bd_edad[$j->edad] = $total_en_bd_edad[$j->edad] ?? 0;
  $total_en_bd_edad[$j->edad]++;
  
  $total_en_bd_jugo_sexo[$j->jugo] = $total_en_bd_jugo_sexo[$j->jugo] ?? [];
  $total_en_bd_jugo_sexo[$j->jugo][$j->sexo] = $total_en_bd_jugo_sexo[$j->jugo][$j->sexo] ?? 0;
  $total_en_bd_jugo_sexo[$j->jugo][$j->sexo]++;
  
  $total_en_bd_jugo_edad[$j->jugo] = $total_en_bd_jugo_edad[$j->jugo] ?? [];
  $total_en_bd_jugo_edad[$j->jugo][$j->edad] = $total_en_bd_jugo_edad[$j->jugo][$j->edad] ?? 0;
  $total_en_bd_jugo_edad[$j->jugo][$j->edad]++;
  
  $total_en_bd_sexo_edad[$j->sexo] = $total_en_bd_sexo_edad[$j->sexo] ?? [];
  $total_en_bd_sexo_edad[$j->sexo][$j->edad] = $total_en_bd_sexo_edad[$j->sexo][$j->edad] ?? 0;
  $total_en_bd_sexo_edad[$j->sexo][$j->edad]++;
  
  $total_en_bd_jugo_sexo_edad[$j->jugo] = $total_en_bd_jugo_sexo_edad[$j->jugo] ?? [];
  $total_en_bd_jugo_sexo_edad[$j->jugo][$j->sexo] = $total_en_bd_jugo_sexo_edad[$j->jugo][$j->sexo] ?? [];
  $total_en_bd_jugo_sexo_edad[$j->jugo][$j->sexo][$j->edad] = $total_en_bd_jugo_sexo_edad[$j->jugo][$j->sexo][$j->edad] ?? 0;
  $total_en_bd_jugo_sexo_edad[$j->jugo][$j->sexo][$j->edad]++;
}

$total_en_bd_jugo_sexo_edad['TOTAL'] = $total_en_bd_sexo_edad;//Los pongo asi para poder iterar
$total_en_bd_jugo_sexo['TOTAL'] = $total_en_bd_sexo;
$total_en_bd_jugo_edad['TOTAL'] = $total_en_bd_edad;
$total_en_bd_jugo['TOTAL'] = $total_en_bd;

$total_no_en_bd = 0;
$total_no_en_bd_jugo = [];
foreach($no_en_bd as $j){
  $total_no_en_bd++;
  $total_no_en_bd_jugo[$j->jugo] = $total_no_en_bd_jugo[$j->jugo] ?? 0;
  $total_no_en_bd_jugo[$j->jugo]++;
}

$total_menores = 0;
$total_menores_jugo = [];
foreach($menores as $j){
  $total_menores++;
  $total_menores_jugo[$j->jugo] = $total_menores_jugo[$j->jugo] ?? 0;
  $total_menores_jugo[$j->jugo]++;
}

$jugo_keys = ['TOTAL',1,0];//Si / No
$sexo_keys = ['HOMBRE','MUJER','X'];//@TODO: recibir del front
$edad_keys = array_map(function($e){return $e[2];},$rangos_edad);

//Homogeneizo este porque es el unico que toma los tres tipos
foreach($jugo_keys as $jugo){
  $total_en_bd_jugo[$jugo]    = $total_en_bd_jugo[$jugo] ?? 0;
  $total_en_bd_jugo_edad[$jugo] = $total_en_bd_jugo_edad[$jugo] ?? [];
  $total_en_bd_jugo_sexo[$jugo] = $total_en_bd_jugo_sexo[$jugo] ?? [];
  $total_en_bd_jugo_sexo_edad[$jugo] = $total_en_bd_jugo_sexo_edad[$jugo] ?? [];
  $total_no_en_bd_jugo[$jugo] = $total_no_en_bd_jugo[$jugo] ?? 0;
  $total_menores_jugo[$jugo]  = $total_menores_jugo[$jugo] ?? 0;
}

$menores = $menores->groupBy('jugo');//Este si tengo que listarlo todos
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$DECIMALES = 4;
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
    @section('encabezado')
    <div class="encabezadoImg">
      <img src="img/logos/banner_2024_portrait.png" width="900">
      <h2>&nbsp;</h2>
      <h2><span>Juegos Online | Informe demográfico {{$plataforma}} - {{$anio}} - {{$meses[$mes]}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <?php $planilla = $planilla ?? date('j-m-y / h:i'); ?>
    <div class="camposInfo" style="right:0px;"></span>{{ $planilla }}</div>
    @endsection
    @yield('encabezado')

    @foreach($jugo_keys as $jugo)
    <?php 
      $Tjugo_sexo_edad = $total_en_bd_jugo_sexo_edad[$jugo] ?? [];
      $Tjugo_sexo = $total_en_bd_jugo_sexo[$jugo] ?? [];
      $Tjugo_edad = $total_en_bd_jugo_edad[$jugo] ?? [];
      $Tjugo = $total_en_bd_jugo[$jugo] ?? 0;
    ?>
    @if(!$loop->first)
    <div style="page-break-after: always;"></div>
    @yield('encabezado')
    @endif
    <table style="table-layout: fixed;">
      <tr>
        @if($jugo === 1)
        <th class="tablaInicio center" colspan="6">JUGARON</th>
        @elseif($jugo === 0)
        <th class="tablaInicio center" colspan="6">NO JUGARON</th>
        @elseif($jugo == 'TOTAL')
        <th class="tablaInicio center" colspan="6">TOTAL</th>
        @endif
      </tr>
      <tr>
        <th class="tablaInicio center">SEXO</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center total_horizontal">%</th>
        <th class="tablaInicio center">EDAD</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center">%</th>
      </tr>

      @foreach($sexo_keys as $sexo) 
      <?php 
        $Tjugosexo_edad = $Tjugo_sexo_edad[$sexo] ?? [];
        $Tjugosexo = $Tjugo_sexo[$sexo] ?? 0;
      ?>
      @foreach($edad_keys as $edad)
      <?php 
        $Tjugosexoedad = $Tjugosexo_edad[$edad] ?? 0;
      ?>
      <tr>
        @if($loop->first)
        <?php 
          $pje = $total_en_bd?
            number_format(100*$Tjugosexo/$total_en_bd,$DECIMALES,',','.')
          : 100;
        ?>
        <th class="tablaInicio center forzar_blanco" rowspan={{count($edad_keys)}}>{{$sexo}}</th>
        <td class="tablaCampos center forzar_blanco" rowspan={{count($edad_keys)}}>{{$Tjugosexo}}</td>
        <td class="tablaCampos center forzar_blanco total_horizontal" rowspan={{count($edad_keys)}}>{{$pje}}%</td>
        @endif
        <?php 
          $pje = $total_en_bd?
            number_format(100*$Tjugosexoedad/$total_en_bd,$DECIMALES,',','.')
          : 100;
        ?>
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{$edad}}</td>
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{$Tjugosexoedad}}</td>
        <td class="tablaCampos center {{$loop->last && !$loop->parent->last? 'separador_sexo_edades' : ''}}">{{$pje}}%</td>
      </tr>
      @endforeach
      @endforeach
      
      @foreach($edad_keys as $edad)
      <?php 
        $Tjugoedad = $Tjugo_edad[$edad] ?? 0;
      ?>
      <tr class="total">
        @if($loop->first)
        <th class="tablaCampos center total forzar_blanco" rowspan={{count($edad_keys)}}>TOTAL</th>
        <td class="tablaCampos center total forzar_blanco" rowspan={{count($edad_keys)}}>{{$Tjugo}}</td>
        <?php 
          $pje = $total_en_bd?
            number_format(100*$Tjugo/$total_en_bd,$DECIMALES,',','.')
            : 100;
        ?>
        <td class="tablaCampos center total forzar_blanco total_horizontal" rowspan={{count($edad_keys)}}>{{$pje}}%</td>
        @endif
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$edad}}</td>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$Tjugoedad}}</td>
        <?php 
        $pje = $total_en_bd?
          number_format(100*$Tjugoedad/$total_en_bd,$DECIMALES,',','.')
          : 100;
        ?>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$pje}}%</td>
      </tr>
      @endforeach
    </table>
    
    @endforeach
    
    <?php 
      $jugo_keys = array_diff($jugo_keys,['TOTAL']);
    ?>
    <div style="page-break-after: always;"></div>
    @yield('encabezado')
    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center">EN BASE DE DATOS</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center total_horizontal">%</th>
        <th class="tablaInicio center">JUGARON</th>
        <th class="tablaInicio center">CANTIDAD</th>
        <th class="tablaInicio center">%</th>
      </tr>
      
      <?php $total = $total_en_bd+$total_no_en_bd; ?>
      
      @foreach($jugo_keys as $jugo)
      <?php $T = $total_en_bd_jugo[$jugo] ?? 0; ?>
      <tr>
        @if($loop->first)
        <th class="tablaInicio center forzar_blanco" rowspan="{{count($jugo_keys)}}">SÍ</th>
        <td class="tablaInicio center forzar_blanco" rowspan="{{count($jugo_keys)}}">{{$total_en_bd}}</td>
        <td class="tablaInicio center forzar_blanco total_horizontal" rowspan="{{count($jugo_keys)}}">{{@number_format(100*($total_en_bd/$total),$DECIMALES,',','.')}}%</td>
        @endif
        <td class="tablaInicio center {{$loop->last? 'separador_sexo_edades' : ''}}">{{$jugo? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos center {{$loop->last? 'separador_sexo_edades' : ''}}">{{$T}}</td>
        <td class="tablaCampos center {{$loop->last? 'separador_sexo_edades' : ''}}">{{@number_format(100*$T/$total,$DECIMALES,',','.')}}%</td>
      </tr>
      @endforeach
      
      @foreach($jugo_keys as $jugo)
      <?php $T = $total_no_en_bd_jugo[$jugo] ?? 0; ?>
      <tr>
        @if($loop->first)
        <th class="tablaInicio center forzar_blanco" rowspan="{{count($jugo_keys)}}">NO</th>
        <td class="tablaInicio center forzar_blanco" rowspan="{{count($jugo_keys)}}">{{$total_no_en_bd}}</th>
        <td class="tablaInicio center forzar_blanco total_horizontal" rowspan="{{count($jugo_keys)}}">{{@number_format(100*($total_no_en_bd/$total),$DECIMALES,',','.')}}%</td>
        @endif
        <td class="tablaInicio center">{{$jugo? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos center">{{$T}}</td>
        <td class="tablaCampos center">{{@number_format(100*$T/$total,$DECIMALES,',','.')}}%</td>
      </tr>
      @endforeach
      
      @foreach($jugo_keys as $jugo)
      <tr class="total">
        @if($loop->first)
        <th class="tablaCampos center total forzar_blanco" rowspan="{{count($jugo_keys)}}">TOTAL</th>
        <td class="tablaCampos center total forzar_blanco" rowspan="{{count($jugo_keys)}}">{{$total}}</td>
        <td class="tablaCampos center total forzar_blanco total_horizontal" rowspan="{{count($jugo_keys)}}">100%</td>
        @endif
        <?php $Tjugo = ($total_en_bd_jugo[$jugo] ?? 0) + ($total_no_en_bd_jugo[$jugo] ?? 0);?>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$jugo? 'SÍ' : 'NO'}}</td>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{$Tjugo}}</td>
        <td class="tablaCampos center {{$loop->first? 'total' : ''}}">{{@number_format(100*$Tjugo/$total,$DECIMALES,',','.')}}%</td>
      </tr>
      @endforeach
    </table>
    
    <div style="page-break-after: always;"></div>
    @yield('encabezado')
    <table>
      <tr>
        <th class="tablaInicio center" colspan="3">Menores de edad</th>
      </tr>
    @foreach($jugo_keys as $jugo)
      <tr>
        <th class="tablaInicio center" colspan="3">REGISTRADOS {{$jugo? 'JUGANDO' : 'SIN JUGAR'}}</th>
      </tr>
      
      @if($total_menores_jugo[$jugo] ?? 0)
      <tr>
        <th class="tablaInicio center">Jugador</th>
        <th class="tablaInicio center">Sexo</th>
        <th class="tablaInicio center">Edad</th>
      </tr>
        @foreach(($menores[$jugo] ?? []) as $m)
      <tr>
        <td class="tablaCampos center">{{$m->jugador}}</td>
        <td class="tablaCampos center">{{$m->sexo}}</td>
        <td class="tablaCampos center">{{$m->edad}}</td>
      </tr>
        @endforeach
      @else
      <tr>
        <td class="tablaCampos center" colspan="3">No se encuentran jugadores</td>
      </tr>
      @endif
    @endforeach
    </table>
  </body>
</html>
