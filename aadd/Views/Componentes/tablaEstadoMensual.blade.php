<div id="contenedor-tabla-mensual">
    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading" style="background-color: #fafbfc; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="panel-title" style="font-weight: 500; color: #555; margin: 0;"><i class="fa fa-calendar"></i> Estado Mensual</h3>
            <div style="margin: 0; display: flex; align-items: center;">
                <label for="mes" style="margin-right: 10px; margin-bottom: 0;">Mes:</label>
                <input type="month" id="mes-selector" class="form-control" style="width: auto; display: inline-block; padding: 4px 8px; height: 30px;" value="{{ $mesActual ?? '' }}" onchange="actualizarTablaMensual(this.value)">
            </div>
        </div>
            <div class="panel-body">
<style>
    .table-moderna { border-collapse: separate !important; border-spacing: 0; width: 100%; margin-bottom: 0; background-color: transparent; border: none !important; }
    .table-moderna thead th { background-color: #ffffff !important; color: #504E76 !important; font-weight: 700 !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 16px 12px !important; border-bottom: 2px solid #C4C3E3 !important; border-top: none !important; border-left: none !important; border-right: none !important; vertical-align: middle; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(80,78,118,0.05); }
    .table-moderna tbody td { padding: 14px 12px !important; vertical-align: middle !important; border-bottom: 1px solid #eaeaef !important; border-top: none !important; border-left: none !important; border-right: none !important; color: #504E76; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; }
    .table-moderna tbody tr:last-child td { border-bottom: none !important; }
    .table-moderna tbody tr:hover td { background-color: #f8f6e9 !important; }
    .table-responsive-moderna { border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.05), 0 2px 4px -1px rgba(80, 78, 118, 0.03); border: 1px solid #C4C3E3; background: #fff; margin-top: 20px; }
    .table-responsive-moderna::-webkit-scrollbar { width: 8px; height: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-track { background: #ffffff; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb { background: #C4C3E3; border-radius: 8px; }
    .table-responsive-moderna::-webkit-scrollbar-thumb:hover { background: #504E76; }
    
    .badge-m { font-weight: 600; font-size: 0.9rem; transition: all 0.2s ease; }
    .badge-m:hover { transform: scale(1.05); cursor: pointer; }
    .badge-success-m { color: #A3B565; }
    .badge-warning-m { color: #b89c58; /* slightly darker yellow for text readability */ }
    .badge-danger-m { color: #F1642E; }
    .badge-m i { margin-right: 4px; font-size: 0.9em; }
</style>
                <div class="table-responsive table-responsive-moderna" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-moderna text-center">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Fecha</th>
                                @if(isset($plataformas))
                                    @foreach($plataformas as $plat)
                                        <th style="text-align: center;">{{ $plat }}</th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($dias) && isset($matriz) && isset($plataformas))
                                @foreach($dias as $dia)
                                    <tr>
                                        <td><strong>{{ date('d-m-Y', strtotime($dia)) }}</strong></td>
                                        @foreach($plataformas as $plat)
                                            <td style="cursor: pointer;" onclick="verDetallesArchivos('{{ $dia }}', '{{ $plat }}')" title="Ver detalles de archivos">
                                                @if($matriz[$dia][$plat] == 4)
                                                    <span class="badge-m badge-success-m"><i class="fa fa-check"></i> {{ $matriz[$dia][$plat] }} / 4</span>
                                                @elseif($matriz[$dia][$plat] > 0)
                                                    <span class="badge-m badge-warning-m"><i class="fa fa-exclamation-triangle" style="display:none;"></i> {{ $matriz[$dia][$plat] }} / 4</span>
                                                @else
                                                    <span class="badge-m badge-danger-m"><i class="fa fa-times"></i> {{ $matriz[$dia][$plat] }} / 4</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
