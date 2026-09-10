<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Comparación de beneficios por plataforma</title>
    <link href="css/estiloPlanillaLandscape.css" rel="stylesheet">
    <style>
        /* La hoja de estilos landscape no trae clases de tabla (son de la
           portrait), asi que las definimos aca. */
        table.datos {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size: 7px;
        }

        table.datos td,
        table.datos th {
            border: 1px solid #bbbbbb;
            padding: 2px 3px;
            text-align: right;
        }

        table.datos thead th {
            background-color: #dddddd;
            text-align: center;
            font-size: 7px;
        }

        table.datos thead th.plataforma {
            background-color: #c9c8e0;
        }

        table.datos td.fecha {
            text-align: center;
        }

        table.datos tbody tr:nth-child(even) td {
            background-color: #f2f2f2;
        }

        table.datos tr.total td {
            border-top: 2px double black;
            font-weight: bold;
            background-color: #ffffff;
        }

        .grupo {
            border-left: 2px solid #999999 !important;
        }

        .introduccion {
            font: 11px Helvetica, Sans-Serif;
            text-align: justify;
            margin-bottom: 8px;
        }

        .nota {
            font: 8px Helvetica, Sans-Serif;
            text-align: justify;
            margin-top: 6px;
            color: #444444;
        }
    </style>
</head>

<body>
    <div class="encabezadoImg">
        <img src="_aadd/encabezado-sin-IRAM.jpeg" width="1100">
        <h2 style="left:35%;"><span>Apuestas Deportivas | Comparación de beneficios por plataforma (ARS)</span></h2>
    </div>

    <div class="camposTab titulo" style="right:-15px;">FECHA EXPORTACIÓN</div>
    <div class="camposInfo" style="right:0px;"><span>{{ date('d-m-y / H:i') }}</span></div>

    <div class="introduccion">
        Detalle diario de <b>jugadores activos</b> y <b>GGR</b> por plataforma para el período del
        <b>{{ date('d/m/Y', strtotime($fechaDesde)) }}</b> al <b>{{ date('d/m/Y', strtotime($fechaHasta)) }}</b>.
        Los jugadores activos son el conteo informado por cada plataforma para ese día.
    </div>

    @php
        $totalJugadores = [];
        $totalGgr = [];
        foreach ($plataformas as $plat) {
            $totalJugadores[$plat] = 0;
            $totalGgr[$plat] = 0;
        }
    @endphp

    <table class="datos">
        <thead>
            <tr>
                <th rowspan="2">FECHA</th>
                @foreach ($plataformas as $plat)
                    <th colspan="2" class="plataforma grupo">{{ $plat }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($plataformas as $plat)
                    <th class="grupo">JUG. ACTIVOS</th>
                    <th>GGR</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($dias as $dia)
                <tr>
                    <td class="fecha">{{ date('d/m/Y', strtotime($dia)) }}</td>
                    @foreach ($plataformas as $plat)
                        @php
                            $d = isset($datos[$dia][$plat]) ? $datos[$dia][$plat] : null;
                            if ($d) {
                                $totalJugadores[$plat] += $d['jugadores_activos'];
                                $totalGgr[$plat] += $d['ggr'];
                            }
                        @endphp
                        <td class="grupo">{{ $d ? number_format($d['jugadores_activos'], 0, ',', '.') : '-' }}</td>
                        <td>{{ $d ? number_format($d['ggr'], 2, ',', '.') : '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="total">
                <td class="fecha">TOTAL</td>
                @foreach ($plataformas as $plat)
                    <td class="grupo">{{ number_format($totalJugadores[$plat], 0, ',', '.') }} <sup>(1)</sup></td>
                    <td>{{ number_format($totalGgr[$plat], 2, ',', '.') }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="nota">
        <b>(1)</b> Jugador-día, <b>no jugadores distintos</b>: quien jugó varios días del período suma una vez por cada
        día. La cantidad de jugadores distintos del mes la informa cada plataforma en su archivo mensual, y se consulta
        en la tarjeta “Acumulado Mensual” de la pantalla.
    </div>
</body>

</html>
