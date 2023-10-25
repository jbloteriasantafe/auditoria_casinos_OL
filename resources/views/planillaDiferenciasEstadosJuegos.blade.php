<!DOCTYPE html>
<?php
  $filas_por_col = 69.0;
  $filas_por_pag = $filas_por_col*2;
  
  $cantidates_por_estado = [];
  $tablas_por_estado = [];
  foreach($resultado as $e => $detalles){
    $tablas_por_estado[$e] = array_chunk($detalles,$filas_por_pag);
    $cantidades_por_estado[$e] = count($detalles);
  }
  $hoy = date('j-m-y / h:i');
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
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>
	@section('cabezera')
	<div class="encabezadoImg">
	  <img src="img/logos/banner_nuevo2_portrait.png" width="900">
	  <h2 style="text-align: center;">
		<span>Informe de diferencias de estados ({{$plataforma}})</span>
	  </h2>
	</div>
	@endsection
	@yield('cabezera')
		
    @foreach($tablas_por_estado as $e => $tablas)		
		<?php $cantidad = $cantidades_por_estado[$e]; ?>
		
		@foreach($tablas as $detalles)
		
		@if(!$loop->parent->first || !$loop->first)
		<div style="page-break-after:always;"></div>
		@yield('cabezera')
		@endif
		
		<table style="table-layout:fixed;width: 105.5%;position: absolute;top: 75px;left: -5.5%;">
			@if($loop->first)
			<thead>
				<tr>
					<th class="tablaInicio center" width="100%" colspan="6" style="border-left: 0px;border-right: 0px;border-top: 0px;">
						Estado en sistema: {{$e}} ({{$cantidad}} juego{{$cantidad > 1? 's' : ''}})
					</th>
				</tr>
			</thead>
			@endif
			<thead>
				<tr>
					<th class="tablaInicio center small" width="12.5%">CÓDIGO</th>
					<th class="tablaInicio center small" width="25%">JUEGO</th>
					<th class="tablaInicio center small" width="12.5%">ESTADO RECIBIDO</th>
					<th class="tablaInicio center small" width="12.5%">CÓDIGO</th>
					<th class="tablaInicio center small" width="25%">JUEGO</th>
					<th class="tablaInicio center small" width="12.5%">ESTADO RECIBIDO</th>
				</tr>
			</thead>
			<tbody>
				@for($i=0;$i<$filas_por_col;$i++)
				<tr>
					@if(isset($detalles[$i]))
					<td class="tablaCampos center small">{{$detalles[$i]["codigo"]}}</td>
					<td class="tablaCampos center small">{{$detalles[$i]["juego"]}}</td>
					<td class="tablaCampos center small">{{$detalles[$i]["estado_recibido"]}}</td>
					@else
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					@endif
					@if(isset($detalles[$i+$filas_por_col]))
					<td class="tablaCampos center small">{{$detalles[$i+$filas_por_col]["codigo"]}}</td>
					<td class="tablaCampos center small">{{$detalles[$i+$filas_por_col]["juego"]}}</td>
					<td class="tablaCampos center small">{{$detalles[$i+$filas_por_col]["estado_recibido"]}}</td>
					@else
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					<td class="tablaCampos center small" style="border: 0;background: white;">&nbsp;</td>
					@endif
				</tr>
				@endfor
			</tbody>
		</table>
		@endforeach
    @endforeach
  </body>
</html>
