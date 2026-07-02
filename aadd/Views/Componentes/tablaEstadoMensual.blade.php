<div id="contenedor-tabla-mensual">
    <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="panel-title" style="font-weight: bold; color: #333; margin: 0;"><i class="fa fa-calendar"></i> Estado Mensual</h3>
            <div style="margin: 0; display: flex; align-items: center;">
                <label for="mes" style="margin-right: 10px; margin-bottom: 0;">Mes:</label>
                <input type="month" id="mes-selector" class="form-control" style="width: auto; display: inline-block; padding: 4px 8px; height: 30px;" value="{{ $mesActual ?? '' }}" onchange="actualizarTablaMensual(this.value)">
            </div>
        </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center">
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
                                            <td>
                                                @if($matriz[$dia][$plat] == 4)
                                                    <span class="text-success" style="font-weight: bold;"><i class="fa fa-check"></i> {{ $matriz[$dia][$plat] }} / 4</span>
                                                @elseif($matriz[$dia][$plat] > 0)
                                                    <span class="text-warning" style="font-weight: bold;">{{ $matriz[$dia][$plat] }} / 4</span>
                                                @else
                                                    <span class="text-danger">{{ $matriz[$dia][$plat] }} / 4</span>
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
