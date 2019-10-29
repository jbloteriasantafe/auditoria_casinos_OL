<!DOCTYPE html>
<html>

  <style>
    table {
      font-family: arial, sans-serif;
      border-collapse: collapse;
      width: 98%;
    }

    td, th {
      border: 1px solid #dddddd;
      text-align: left;
      padding: 3px;
      white-space: nowrap;
    }

    tr:nth-child(even) {
      background-color: #dddddd;
    }

    p {
      border-top: 1px solid #000;
    }
  </style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>

  <body>
    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
        <h2><span>PVAR03 | Procedimiento de Control Ambiental</span></h2>
    </div>

    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>

    <!-- Tabla de datos del relevamiento de control ambiental -->
    <table>
      <tr>
        <th class="tablaInicio" style="background-color: #e6e6e6">CASINO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">N° RELEVAMIENTO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA PRODUCCIÓN</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA AUDITORÍA</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FISCALIZADOR</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">ESTADO</th>
      </tr>

      <tr>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['casino']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->nro_relevamiento_ambiental}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_generacion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_ejecucion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['fiscalizador']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['estado']}}</td>
      </tr>
    </table>
    <br><br>


    <!-- Tabla de control ambiental Melincué-->
    <!-- Como Melincué tiene un solo sector, creo que es preferible hacer una fila por cada isla,
    y una columna por turno (la estructura seria la inversa a la de las tablas de Santa Fe)-->
    @if ($relevamiento_ambiental->casino->id_casino == 1)
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">ISLAS</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px" rowspan="2">TOTAL</th>
          </tr>
          <tr>
            @foreach ($relevamiento_ambiental->casino->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">{{$turno->nro_turno}}</th>
            @endforeach
          </tr>
        </thead>
        @foreach ($relevamiento_ambiental->casino->sectores[0]->islas as $isla)
        <tr>
          <td class="tablaAmbiental" style="background-color: white" width="10px">{{$isla->nro_isla}} </td>

          @foreach ($detalles as $detalle)
          @if ($detalle['id_sector'] == $relevamiento_ambiental->casino->sectores[0]->id_sector && $detalle['id_turno'] == $turno->id_turno)
            @for ($i=0; $i<sizeof($relevamiento_ambiental->casino->turnos); $i++)
                <td class="tablaAmbiental" style="background-color: white" width="11px">55</td>
            @endfor
          @endif
          @endforeach
          <td class="tablaAmbiental" style="background-color: white" width="20px">999</td>
        </tr>
        @endforeach
      </table>


    <!-- Tabla de control ambiental Santa Fe-->
    @elseif ($relevamiento_ambiental->casino->id_casino == 2)
      <?php $contador_tablas=0; ?>
      @foreach ($relevamiento_ambiental->casino->sectores as $sector)
      <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">T</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{sizeof($sector->islas)}}">ISLAS</th>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px" rowspan="2">TOTAL</th>
          </tr>
          <tr>
            @foreach ($sector->islas as $isla)
            <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">{{$isla->nro_isla}}</th>
            @endforeach
          </tr>
        </thead>
        @foreach ($relevamiento_ambiental->casino->turnos as $turno)
        <tr>
          <td class="tablaAmbiental" style="background-color: white" width="10px">{{$turno->nro_turno}} </td>
          @foreach ($detalles as $detalle)
          @if ($detalle['id_sector'] == $sector->id_sector && $detalle['id_turno'] == $turno->id_turno)
            @for ($i=0; $i<$detalle['tamanio_vector']; $i++)
                <td class="tablaAmbiental" style="background-color: white" width="11px">55</td>
            @endfor
          @endif
          @endforeach
          <td class="tablaAmbiental" style="background-color: white" width="20px">999</td>
        </tr>
        @endforeach
      </table>
      <?php $contador_tablas++; ?>
      <br>
        @if ($contador_tablas%2 == 0)
          <div style="page-break-after:always;"></div>
        @endif
      @endforeach

    <!-- Tabla de control ambiental Rosario-->
    @else
      <?php $contador_tablas=0; ?>
      @foreach ($relevamiento_ambiental->casino->sectores as $sector)
      <?php $contador_colspan=0; ?>
      <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
      <table>
        <thead>
          <tr>
            @foreach ($islotes_y_sectores as $islote_y_sector)
              @if ($islote_y_sector->id_sector == $sector->id_sector)
                <?php $contador_colspan++; ?>
              @endif
            @endforeach
            <th class="tablaInicio" style="background-color: #e6e6e6" rowspan="2">TURNO</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" colspan="{{$contador_colspan}}">ISLOTES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6" rowspan="2">TOTAL</th>
          </tr>
          <tr>
            @foreach ($islotes_y_sectores as $islote_y_sector)
              @if ($islote_y_sector->id_sector == $sector->id_sector)
                <th class="tablaInicio" style="background-color: #e6e6e6">{{$islote_y_sector->nro_islote}}</th>
              @endif
            @endforeach
          </tr>
        </thead>
        @foreach ($relevamiento_ambiental->casino->turnos as $turno)
        <tr>
          <td class="tablaAmbiental" style="background-color: white">{{$turno->nro_turno}} </td>
          @foreach ($detalles as $detalle)
          @if ($detalle['id_sector'] == $sector->id_sector && $detalle['id_turno'] == $turno->id_turno)
            @for ($i=0; $i<$detalle['tamanio_vector']; $i++)
                <td class="tablaAmbiental" style="background-color: white">55</td>
            @endfor
          @endif
          @endforeach

          <td class="tablaAmbiental" style="background-color: white">999</td>
        </tr>
        @endforeach
      </table>
      <?php $contador_tablas++; ?>
      <br>
        @if ($contador_tablas%3 == 0)
          <div style="page-break-after:always;"></div>
        @endif
      @endforeach
    @endif

    <br><br>
    <div class="primerEncabezado" style="padding-left: 720px;"><p style="width: 250px; padding-left: 60px;">Firma y aclaración/s responsable/s.</p></div>
  </body>

</html>
