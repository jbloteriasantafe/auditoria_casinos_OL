<style>
    .table-moderna { border-collapse: separate !important; border-spacing: 0; width: 100%; margin-bottom: 0; background-color: transparent; border: none !important; }
    .table-moderna thead th { background-color: #ffffff !important; color: #504E76 !important; font-weight: 700 !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 16px 12px !important; border-bottom: 2px solid #C4C3E3 !important; border-top: none !important; border-left: none !important; border-right: none !important; vertical-align: middle; position: sticky; z-index: 10; box-shadow: 0 2px 4px rgba(80,78,118,0.05); white-space: nowrap; text-align: center }
    .table-moderna thead th:first-child { left: 0; z-index: 11; box-shadow: 2px 2px 4px rgba(80,78,118,0.05); }
    .table-moderna tbody td { padding: 14px 12px !important; vertical-align: middle !important; border-bottom: 1px solid #eaeaef !important; border-top: none !important; border-left: none !important; border-right: none !important; color: #504E76; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; white-space: nowrap; }
    .table-moderna tbody td:first-child { position: sticky; left: 0; z-index: 5; background-color: #ffffff !important; box-shadow: 2px 0 4px rgba(80,78,118,0.05); }
    .table-moderna tbody tr:last-child td { border-bottom: none !important; }
    .table-moderna tbody tr:hover td { background-color: rgba(199, 218, 129, 0.21) !important; }
    .table-moderna tbody tr:hover td:first-child { background-color: rgba(199, 218, 129, 0.21) !important; }
    .table-responsive-moderna { border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05), 0 2px 4px -1px rgba(80, 78, 118, 0.03); border: 1px solid #C4C3E3; background: #fff; margin-top: 20px; }
    .table-responsive-moderna::-webkit-scrollbar { width: 8px; height: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-track { background: #ffffff; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb { background: #C4C3E3; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb:hover { background: #504E76; }
</style>
<div class="table-responsive table-responsive-moderna">
    <table class="table table-moderna text-center">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Jugadores activos</th>
                <th>Apuestas realizadas</th>
                <th>Turnover diario</th>
                <th>Turnover en bonos</th>
                <th>Payout diario</th>
                <th>Payout en bonos</th>
                <th>GGR Calculado</th>
                <th>GGR</th>
                <th>Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @if(count($dias) > 0)
                @foreach($dias as $dia)
                    @php
                        $b = isset($beneficios) ? $beneficios->get($dia) : null;
                    @endphp
                    <tr>
                        <td><strong>{{ date('d-m-Y', strtotime($dia)) }}</strong></td>
                        @if($b)
                            @php
                                $ggrCalculado = $b->ggr_calculado ?? ($b->turnover - $b->payout);
                                $diferencia = isset($b->diferencia) ? $b->diferencia : round($ggrCalculado - $b->ggr, 2);
                                if ($diferencia == -0) {
                                    $diferencia = 0;
                                }
                            @endphp
                            <td>{{ $b->jugadores_activos }}</td>
                            <td>{{ $b->apuestas_realizadas }}</td>
                            <td>$ {{ number_format($b->turnover, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->turnover_bonos, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->payout, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->payout_bonos, 2, ',', '.') }}</td>
                            <td class="text-info"><strong>$ {{ number_format($ggrCalculado, 2, ',', '.') }}</strong></td>
                            <td><strong>$ {{ number_format($b->ggr, 2, ',', '.') }}</strong></td>
                            <td class="{{ abs($diferencia) > 0.01 ? 'text-danger' : 'text-success' }}"><strong>$ {{ number_format($diferencia, 2, ',', '.') }}</strong></td>
                        @else
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding: 20px;">
                        <em>Aún no hay datos cargados para mostrar en este rango.</em>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
