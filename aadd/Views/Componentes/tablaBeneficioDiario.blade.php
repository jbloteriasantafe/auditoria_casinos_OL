<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Jugadores activos</th>
                <th>Apuestas realizadas</th>
                <th>Turnover diario</th>
                <th>Turnover en bonos</th>
                <th>Payout diario</th>
                <th>Payout en bonos</th>
                <th>GGR</th>
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
                            <td>{{ $b->jugadores_activos }}</td>
                            <td>{{ $b->apuestas_realizadas }}</td>
                            <td>$ {{ number_format($b->turnover, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->turnover_bonos, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->payout, 2, ',', '.') }}</td>
                            <td>$ {{ number_format($b->payout_bonos, 2, ',', '.') }}</td>
                            <td><strong>$ {{ number_format($b->ggr, 2, ',', '.') }}</strong></td>
                        @else
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
                    <td colspan="8" class="text-center text-muted" style="padding: 20px;">
                        <em>Aún no hay datos cargados para mostrar en este rango.</em>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
