<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Informe Técnico</title>
    <style>
        @page {
            margin-top: 6.25%;
            margin-left: 16.3%;
            margin-right: 16.3%;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            /* Fuente común en documentos */
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }



        .header-date {
            text-align: right;
            margin-bottom: 30px;
        }

        .recipient {
            margin-bottom: 30px;
            line-height: 1.3;
        }

        .ref {
            margin-bottom: 20px;
            text-align: right;
        }

        p {
            margin-bottom: 16px;
            text-align: justify;
            text-indent: 120px;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 11pt;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;

        }

        thead tr:first-child th {
            background-color: #2E5C9A;
            color: black;
        }

        thead tr:nth-child(2) th {
            background-color: #7FA9D1;
            color: black;
        }

        tbody tr td {
            background-color: #DCE6F1;
        }

        .footer-date {
            margin-top: 30px;
        }

        .signature-block {
            margin-top: 40px;
            text-align: right;
        }

        .signature-image {
            max-width: 250px;
            height: auto;
            display: block;
        }

        .signature-name {
            font-style: italic;
            font-weight: bold;
            font-size: 1.1em;
            font-family: 'Brush Script MT', 'cursive';
        }

        .signature-title {
            font-weight: bold;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header-date">
            Santa Fe, {{ $fecha_texto }}
        </div>

        <div class="recipient">
            Sr. Guillermo Cervigni<br>
            Sub-Director de<br>
            Casinos y Bingos<br>
            Lotería de Santa Fe<br>
            <strong>S________/________D</strong>
        </div>

        <div class="ref">
            <small><strong>REF.: </strong>Verificación Solicitud N° {{ $casino }} {{ $numero_nota }}.-</small>
        </div>

        <p>
            En referencia a la solicitud de autorización de la Acción Promocional del operador
            <strong>de la plataforma {{ $texto_plataforma }} operada por {{ $duenio_plataforma }}</strong>
            de nota recibida con fecha {{ $fecha_nota_recep }}, <strong>denominada "{{ $nombre_evento }}"</strong>,
            se informa que se ha evaluado la solicitud y se autoriza la realización de la acción
            promocional mencionada.
        </p>
        <p>
            De conformidad con lo establecido en la Ley N.º 14.293 y el Articulo 20 del Decreto Reglamentario N.º 0562
            de la Ley N.º 14.235, se ha analizado las Bases y Condiciones presentadas para la acción. Tras la evaluación
            correspondiente, se <strong>AUTORIZA</strong> la realización de la acción promocional mencionada,
            confirmando que todos sus elementos cumplen con los requisitos legales y normativos vigentes.
        </p>
        <p>
            Sobre los juegos verificados, detallamos la información de identificación registrada
            en nuestro sistema:
        </p>

        <table>
            <thead>
                <tr>
                    <th colspan="2"><strong>Game Code</strong></th>
                    <th rowspan="2"><strong>Nombre del Juego</strong></th>
                </tr>
                <tr>
                    <th><strong>Desktop</strong></th>
                    <th><strong>Mobile</strong></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lista_juegos as $juego)
                    <tr>
                        <td>{{ $juego->desktop_id ?? '-' }}</td>
                        <td>{{ $juego->mobile_id ?? '-' }}</td>
                        <td>{{ $juego->nombre_juego ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">-</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-date">
            FECHA: <strong>{{ $fecha_hoy }} .-</strong>
        </div>

        <div class="signature-block">
            <div class="signature-name">
                Ma. Mercedes Invinkelried.-
            </div>

            <div class="signature-title">
                FIRMA Y ACLARACION
            </div>
        </div>

    </div>

</body>

</html>
