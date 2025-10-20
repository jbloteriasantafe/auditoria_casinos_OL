<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Informe Técnico</title>
    <style>
        /* Estilos para simular una hoja de papel */
        body {
            background-color: #f0f0f0;
            font-family: 'Times New Roman', Times, serif;
            /* Fuente común en documentos */
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 210mm;
            /* Ancho A4 */
            min-height: 297mm;
            /* Alto A4 */
            padding: 2cm;
            /* Márgenes estándar */
            margin: 20px auto;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            /* Asegura que el padding no afecte el ancho total */
            font-size: 12pt;
            /* Tamaño de fuente estándar para documentos */
        }

        /* Estilos específicos del documento */
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
            font-weight: bold;
        }

        p {
            margin-bottom: 16px;
            text-align: justify;
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
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }

        .footer-date {
            margin-top: 30px;
        }

        .signature-block {
            margin-top: 40px;
            /* Puedes ajustar la alineación si es necesario */
            /* text-align: right; */
        }

        .signature-image {
            max-width: 250px;
            /* Ajusta el tamaño de la imagen de la firma */
            height: auto;
            display: block;
        }

        .signature-name {
            font-style: italic;
            font-weight: bold;
            font-size: 1.1em;
            font-family: 'Brush Script MT', 'cursive';
            /* Simula una firma, ajusta según tu imagen */
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
            S / D
        </div>

        <div class="ref">
            REF.: Verificación Solicitud N° {{ $casino }} {{ $numero_nota }}.-
        </div>

        <p>
            En referencia a la solicitud de autorización de la Acción Promocional del operador
            de la plataforma {{ $texto_plataforma }} operada por {{ $duenio_plataforma }}
            de nota recibida con fecha {{ $fecha_nota_recep }}, denominada "{{ $nombre_evento }}",
            se informa que se ha evaluado la solicitud y se autoriza la realización de la acción
            promocional mencionada.
        </p>
        <p>
            De acuerdo con lo establecido por la Ley 14293 y con el artículo 20 del Decreto
            Reglamentario N° 0562 de la Ley N° 14.235, se han analizado las Bases y Condiciones
            junto Y podemos confirmar que los elementos cumplen con los requisitos establecidos.
        </p>
        <p>
            Sobre los juegos verificados, detallamos la información de identificación registrada
            en nuestro sistema:
        </p>

        <table>
            <thead>
                <tr>
                    <th colspan="2">Game Code</th>
                    <th rowspan="2">Nombre del Juego</th>
                    <th rowspan="2">% Dev</th>
                </tr>
                <tr>
                    <th>Desktop</th>
                    <th>Mobile</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lista_juegos as $juego)
                    <tr>
                        <td>{{ $juego->desktop_id ?? 'N/A' }}</td> {{-- Ajusta el nombre del campo --}}
                        <td>{{ $juego->mobile_id ?? 'N/A' }}</td> {{-- Ajusta el nombre del campo --}}
                        <td>{{ $juego->nombre_juego ?? 'N/A' }}</td> {{-- Ajusta el nombre del campo --}}
                        <td>{{ $juego->porcentaje_dev ?? 'N/A' }}</td> {{-- Ajusta el nombre del campo --}}
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No se encontraron juegos asociados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-date">
            FECHA: {{ $fecha_nota_recep }} .-
        </div>

        <div class="signature-block">
            {{-- <img src="{{ asset('path/to/signature_image.png') }}" alt="Firma" class="signature-image"> --}}

            <div class="signature-name">
                Ma. Mercedes Irwinkelried.-
            </div>

            <div class="signature-title">
                FIRMA Y ACLARACION
            </div>
        </div>

    </div>

</body>

</html>
