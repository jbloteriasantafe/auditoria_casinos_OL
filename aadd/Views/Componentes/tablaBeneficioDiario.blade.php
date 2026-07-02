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
                    <tr>
                        <td><strong>{{ date('d-m-Y', strtotime($dia)) }}</strong></td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
                        <td class="text-muted">-</td>
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
