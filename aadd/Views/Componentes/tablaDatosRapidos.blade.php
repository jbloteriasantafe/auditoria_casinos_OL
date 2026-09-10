<style>
    .table-moderna { border-collapse: separate !important; border-spacing: 0; width: 100%; margin-bottom: 0; background-color: transparent; border: none !important; }
    .table-moderna thead th { background-color: #ffffff !important; color: #504E76 !important; font-weight: 700 !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid #C4C3E3 !important; border-top: none !important; border-left: none !important; border-right: none !important; vertical-align: middle; position: sticky; z-index: 10; white-space: nowrap; text-align: center }
    .table-moderna tbody td { padding: 14px 12px !important; vertical-align: middle !important; border-bottom: 1px solid #eaeaef !important; border-top: none !important; border-left: none !important; border-right: none !important; color: #504E76; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; white-space: nowrap; }
    .table-moderna tbody tr:last-child td { border-bottom: none !important; }
    .table-moderna tbody tr:hover td { background-color: rgba(199, 218, 129, 0.21) !important; }
    .table-responsive-moderna { border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05), 0 2px 4px -1px rgba(80, 78, 118, 0.03); border: 1px solid #C4C3E3; background: #fff; margin-top: 20px; }
    .table-responsive-moderna::-webkit-scrollbar { width: 8px; height: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-track { background: #ffffff; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb { background: #C4C3E3; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb:hover { background: #504E76; }

    /* Cabecera de dos niveles: plataforma arriba, métricas abajo. */
    .tabla-datos-rapidos thead th { padding: 0 12px !important; }
    .tabla-datos-rapidos thead tr:first-child th { height: 38px; border-bottom: 1px solid #C4C3E3 !important; }
    .tabla-datos-rapidos thead tr:last-child th { height: 32px; font-size: 0.68rem; letter-spacing: 0.03em; color: #8c8b9f !important; }

    /* Solo la columna Fecha queda fija al hacer scroll horizontal. */
    .tabla-datos-rapidos .celda-fecha { position: sticky; left: 0; background-color: #ffffff !important; box-shadow: 2px 0 4px rgba(80, 78, 118, 0.05); }
    .tabla-datos-rapidos thead .celda-fecha { z-index: 12; }
    .tabla-datos-rapidos tbody .celda-fecha { z-index: 5; }
    .tabla-datos-rapidos tbody tr:hover .celda-fecha { background-color: #f4f7e9 !important; }

    /* Separador entre grupos de plataforma. */
    .tabla-datos-rapidos .grupo-inicio { border-left: 1px solid #e2e1ee !important; }
    .tabla-datos-rapidos thead tr:first-child th.col-plat { color: #4a4f87 !important; }
    .tabla-datos-rapidos .ggr-negativo { color: #EF4444; }
    .tabla-datos-rapidos .sin-dato { color: #b9b8c8; }
</style>
<div class="table-responsive table-responsive-moderna">
    <table class="table table-moderna tabla-datos-rapidos text-center">
        <thead>
            <tr>
                <th rowspan="2" class="celda-fecha">Fecha</th>
                @foreach ($plataformas as $plat)
                    <th colspan="2" class="col-plat col-plat-{{ $plat }} grupo-inicio">{{ $plat }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($plataformas as $plat)
                    <th class="col-plat col-plat-{{ $plat }} grupo-inicio">Jug. activos</th>
                    <th class="col-plat col-plat-{{ $plat }}">GGR</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (count($dias) > 0)
                @foreach ($dias as $dia)
                    <tr>
                        <td class="celda-fecha"><strong>{{ date('d-m-Y', strtotime($dia)) }}</strong></td>
                        @foreach ($plataformas as $plat)
                            @php
                                $d = isset($datos[$dia][$plat]) ? $datos[$dia][$plat] : null;
                            @endphp
                            @if ($d)
                                <td class="col-plat col-plat-{{ $plat }} grupo-inicio">
                                    {{ number_format($d['jugadores_activos'], 0, ',', '.') }}
                                </td>
                                <td class="col-plat col-plat-{{ $plat }} {{ $d['ggr'] < 0 ? 'ggr-negativo' : '' }}">
                                    <strong>$ {{ number_format($d['ggr'], 2, ',', '.') }}</strong>
                                </td>
                            @else
                                <td class="col-plat col-plat-{{ $plat }} grupo-inicio sin-dato">-</td>
                                <td class="col-plat col-plat-{{ $plat }} sin-dato">-</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @else
                <tr class="no-paginar">
                    <td colspan="{{ count($plataformas) * 2 + 1 }}" class="text-center text-muted" style="padding: 20px;">
                        <em>Aún no hay datos cargados para mostrar en este rango.</em>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
