<div id="contenedor-tabla-mensual">
    <div class="panel panel-default"
        style="margin-top: 20px; border-radius: 12px !important; border: 1px solid #C4C3E3 !important; box-shadow: 0 4px 10px rgba(80, 78, 118, 0.05) !important; overflow: hidden;">
        <div class="panel-heading"
            style="background-color: #ffffff !important; border-bottom: 1px solid #C4C3E3 !important; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="panel-title"
                style="font-family: 'Outfit', sans-serif; font-weight: 700; color: #4a4f87; margin: 0;"><i
                    class="fa fa-calendar"></i> Estado Mensual de Carga</h3>
            <div style="margin: 0; display: flex; align-items: center;">
                <label for="mes-selector"
                    style="margin-right: 10px; margin-bottom: 0; font-family: 'Outfit', sans-serif; font-weight: 600; color: #4a4f87; font-size: 13px;">Seleccionar
                    Mes:</label>
                <input type="month" id="mes-selector" class="form-control"
                    style="width: auto; display: inline-block; padding: 4px 10px; height: 32px; border-radius: 8px !important; border: 1px solid #C4C3E3 !important; font-family: 'Inter', sans-serif; color: #504E76; font-size: 13px;"
                    value="{{ $mesActual ?? '' }}" onchange="actualizarTablaMensual(this.value)">
            </div>
        </div>
        <div class="panel-body" style="padding: 20px;">
            <style>
                .table-moderna {
                    border-collapse: separate !important;
                    border-spacing: 0;
                    width: 100%;
                    margin-bottom: 0;
                    background-color: transparent;
                    border: none !important;
                }

                .table-moderna thead th {
                    background-color: #ffffff !important;
                    color: #4a4f87 !important;
                    font-weight: 700 !important;
                    text-transform: uppercase;
                    font-size: 0.75rem;
                    letter-spacing: 0.05em;
                    padding: 16px 12px !important;
                    border-bottom: 2px solid #C4C3E3 !important;
                    border-top: none !important;
                    border-left: none !important;
                    border-right: none !important;
                    vertical-align: middle;
                    position: sticky;
                    top: 0;
                    z-index: 10;
                    box-shadow: 0 2px 4px rgba(80, 78, 118, 0.02);
                }

                .table-moderna tbody td {
                    padding: 14px 12px !important;
                    vertical-align: middle !important;
                    border-bottom: 1px solid #eaeaef !important;
                    border-top: none !important;
                    border-left: none !important;
                    border-right: none !important;
                    color: #504E76;
                    font-size: 0.9rem;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }

                .table-moderna tbody tr:last-child td {
                    border-bottom: none !important;
                }

                .table-moderna tbody tr:hover td {
                    background-color: rgba(80, 78, 118, 0.04) !important;
                }

                .table-responsive-moderna {
                    border-radius: 12px;
                    box-shadow: 0 4px 6px -1px rgba(80, 78, 118, 0.03);
                    border: 1px solid #C4C3E3;
                    background: #fff;
                }

                .table-responsive-moderna::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
                }

                .table-responsive-moderna::-webkit-scrollbar-track {
                    background: #ffffff;
                    border-radius: 8px;
                }

                .table-responsive-moderna::-webkit-scrollbar-thumb {
                    background: #C4C3E3;
                    border-radius: 8px;
                }

                .table-responsive-moderna::-webkit-scrollbar-thumb:hover {
                    background: #4a4f87;
                }

                .badge-m {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    padding: 5px 12px;
                    border-radius: 50px;
                    font-weight: 600;
                    font-size: 0.85rem;
                    transition: all 0.2s ease;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                    border: 1px solid transparent;
                }

                .badge-m:hover {
                    transform: scale(1.05);
                    box-shadow: 0 4px 8px rgba(80, 78, 118, 0.1);
                    cursor: pointer;
                }

                .badge-info-m {
                    background-color: #F0F0FA;
                    color: #4A4F87;
                    border-color: #C4C3E3;
                }

                .badge-danger-m {
                    background-color: #FDE8E8;
                    color: #9B1C1C;
                    border-color: #FBD5D5;
                }

                .badge-m i {
                    font-size: 0.95em;
                }
            </style>

            <div class="table-responsive table-responsive-moderna" style="max-height: 60vh; overflow-y: auto;">
                <table class="table table-moderna text-center">
                    <thead>
                        <tr>
                            <th style="text-align: center; font-family: 'Outfit', sans-serif;">Fecha</th>
                            @if (isset($plataformas))
                                @foreach ($plataformas as $plat)
                                    <th style="text-align: center; font-family: 'Outfit', sans-serif;">
                                        {{ $plat }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($dias) && isset($matriz) && isset($plataformas))
                            @foreach ($dias as $dia)
                                <tr>
                                    <td style="font-family: 'Outfit', sans-serif; font-weight: 700; color: #4a4f87;">
                                        <i class="fa fa-calendar-o text-muted"
                                            style="margin-right: 6px; font-weight: normal; opacity: 0.7;"></i>
                                        {{ date('d-m-Y', strtotime($dia)) }}
                                    </td>
                                    @foreach ($plataformas as $plat)
                                        @php
                                            $archivos = $matriz[$dia][$plat] ?? [];
                                            $cantidad = count($archivos);
                                        @endphp
                                        <td style="cursor: pointer;"
                                            onclick="verDetallesArchivos('{{ $dia }}', '{{ $plat }}', this)"
                                            data-archivos="{{ json_encode($archivos) }}"
                                            title="Ver detalles de archivos">
                                            @if ($cantidad > 0)
                                                <span class="badge-m badge-info-m"><i class="fa fa-file-text-o"></i>
                                                    {{ $cantidad }} {{ $cantidad == 1 ? 'archivo' : 'archivos' }}</span>
                                            @else
                                                <span class="badge-m badge-danger-m"><i class="fa fa-times"></i> Sin
                                                    archivos</span>
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
