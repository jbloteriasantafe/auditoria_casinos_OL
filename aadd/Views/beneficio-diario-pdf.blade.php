<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Beneficio Diario - {{ $plataformaSeleccionada }}</title>
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
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

.center {
  text-align: center;
}
.right {
  text-align: right;
}
    </style>
</head>
<body>
    <div class="encabezadoImg">
      <img src="_aadd/encabezado-sin-IRAM.jpeg" width="900">
      <h2 style="left:35%;"><span>Apuestas Deportivas | Informe de beneficios (ARS)</span></h2>
    </div>

    <div class="camposTab titulo" style="right:-15px;">FECHA EXPORTACIÓN</div>
    <div class="camposInfo" style="right:0px;"><span>{{ date('d-m-y / H:i') }}</span></div>
    
    @php
        $totalGgrHeader = 0;
        foreach($dias as $dia) {
            $b = $beneficios->get($dia);
            if ($b) $totalGgrHeader += $b->ggr;
        }

        $nombresPlataformas = [
            'JUGADON' => 'jugadon.bet.ar',
            'BETWARRIOR' => 'www.betwarrior.bet.ar',
            'BPLAY' => 'santafe.bplay.bet.ar',
            'CITYCENTER' => 'www.citycenteronline.bet.ar'
        ];
        $nombrePlataforma = $nombresPlataformas[strtoupper($plataformaSeleccionada)] ?? $plataformaSeleccionada;
    @endphp
    
    <div class="primerEncabezado">
      Se han realizado los procedimientos de control correspondientes
      al período del <b>{{ date('d/m/Y', strtotime($fechaDesde)) }}</b> al <b>{{ date('d/m/Y', strtotime($fechaHasta)) }}</b> de la <b>Plataforma de {{ $nombrePlataforma }}</b>. Teniendo en cuenta lo anterior, se informa que para <b>Apuestas Deportivas</b> se obtuvo un beneficio de <b>${{ number_format($totalGgrHeader, 2, ',', '.') }}</b>, detallando a continuación el beneficio diario.
    </div>
    <br>

    <table style="table-layout: fixed;">
      <tr>
        <th class="tablaInicio center" width="25%">FECHA</th>
        <th class="tablaInicio center" width="25%">APOSTADO</th>
        <th class="tablaInicio center" width="25%">PREMIOS</th>
        <th class="tablaInicio center" width="25%">BENEFICIO</th>
      </tr>
      @php
          $totalTurnover = 0;
          $totalPayout = 0;
          $totalGgr = 0;
      @endphp
      @foreach($dias as $dia)
          @php
              $b = $beneficios->get($dia);
              $turnover = $b ? $b->turnover : 0;
              $payout = $b ? $b->payout : 0;
              $ggr = $b ? $b->ggr : 0;
              
              $totalTurnover += $turnover;
              $totalPayout += $payout;
              $totalGgr += $ggr;
          @endphp
          <tr>
              <td class="tablaCampos center">{{ date('d/m/Y', strtotime($dia)) }}</td>
              <td class="tablaCampos right">{{ number_format($turnover, 2, ',', '.') }}</td>
              <td class="tablaCampos right">{{ number_format($payout, 2, ',', '.') }}</td>
              <td class="tablaCampos right">{{ number_format($ggr, 2, ',', '.') }}</td>
          </tr>
      @endforeach
      <tr class="total">
          <td class="tablaCampos total center">TOTAL</td>
          <td class="tablaCampos total right">{{ number_format($totalTurnover, 2, ',', '.') }}</td>
          <td class="tablaCampos total right">{{ number_format($totalPayout, 2, ',', '.') }}</td>
          <td class="tablaCampos total right">{{ number_format($totalGgr, 2, ',', '.') }}</td>
      </tr>
    </table>
</body>
</html>
