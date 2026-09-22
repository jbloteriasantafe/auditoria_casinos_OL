<style>
  .modalRectificacion .tabla-rectificacion tr{
    display: flex;
  }
  /* min-width:0 es imprescindible en las celdas: con display:flex en la fila, un th/td
     conserva min-width:auto y no se encoge por debajo de su contenido. Sin esto, las
     filas con textos largos (el nombre del PDF heredado) ensanchan su celda y
     desalinean todas las columnas respecto de la cabecera. */
  .modalRectificacion .tabla-rectificacion tr th{
    flex: 1;
    min-width: 0;
    text-align: center;
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
    border-bottom: 1px solid gray;
  }
  .modalRectificacion .tabla-rectificacion tr td{
    flex: 1;
    min-width: 0;
    text-align: right;
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
    overflow: hidden;
  }

  .modalRectificacion [data-estado] {
    display: none;
  }
  @foreach(['IMPORTAR','FILAS'] as $e)
  .modalRectificacion[data-estado-visible="{{$e}}"] [data-estado="{{$e}}"] {
    display: block;
  }
  @endforeach

  .modalRectificacion [data-tabla-rectificacion] [data-estado-carga-objeto] {
    display: none;
  }
  @foreach(['NO ENVIADO','CARGANDO','COMPLETADO','SIN CAMBIOS','ERROR'] as $e)
  .modalRectificacion [data-tabla-rectificacion] [data-estado-carga="{{$e}}"] [data-estado-carga-objeto="{{$e}}"] {
    display: inline-block;
  }
  @endforeach

  /* Los juegos que comparten certificado (escritorio y movil del mismo juego) se
     muestran como un bloque: fondo alternado por grupo y una barra a la izquierda */
  .modalRectificacion [data-tabla-rectificacion] tr[data-grupo-alterno="1"] td {
    background-color: #f4f9f9;
  }
  .modalRectificacion [data-tabla-rectificacion] tr[data-grupo-tam="varios"] td:first-child {
    border-left: 3px solid #6dc7be;
  }
  /* En la fila de abajo se indica que el PDF lo aporta la de arriba */
  .modalRectificacion [data-tabla-rectificacion] tr:not([data-comparte="1"]) [data-pdf-heredado] {
    display: none;
  }
  .modalRectificacion [data-tabla-rectificacion] tr[data-comparte="1"] [data-name="certificado"] {
    display: none;
  }
  .modalRectificacion [data-pdf-heredado] {
    flex: 8;
    /* min-width:0 es imprescindible: sin esto el flex item no se encoge por debajo de
       su contenido (min-width:auto), el ellipsis no aplica y el nombre del PDF estira
       la fila a varios cientos de pixeles */
    min-width: 0;
    font-size: 0.8em;
    color: #4a8c85;
    text-align: left;
    padding-top: 9px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* El juego del anexo no esta en el sistema: esa fila no se puede rectificar */
  .modalRectificacion [data-tabla-rectificacion] tr[data-sin-juego="1"] td {
    background-color: #ffe0e0;
  }
  /* Anomalia: el juego tiene mas de un certificado y el reemplazo dejaria uno solo */
  .modalRectificacion [data-tabla-rectificacion] tr[data-varios-certificados="1"] td {
    background-color: #fff4d6;
  }

  .modalRectificacion .celda-solo-lectura {
    font-size: 0.85em;
    text-align: left;
    padding-top: 8px;
    word-break: break-all;
  }
  .modalRectificacion .pdf-sin-asignar {
    font-size: 0.85em;
  }
  .modalRectificacion .data-css-hover-orange:hover {
     box-shadow: 0 0 0.5em black;
     cursor: pointer;
  }
</style>

@component('Components/modal',[
  'clases_modal' => 'modalRectificacion',
  'attrs_modal' => 'data-js-modal-rectificacion data-estado-visible="IMPORTAR"',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color: #6dc7be;',
  'grande' => 98
])
  @slot('titulo')
  | RECTIFICACIÓN DE CERTIFICADOS
  @endslot
  @slot('cuerpo')
  <form>
    <div class="row">
      <div class="col-xs-12" data-estado="IMPORTAR">
        <div class="row">
          <div class="col-xs-5">
            <h5>LISTADO DE LA RECTIFICACIÓN (ANEXO A)</h5>
            <input class="form-control" type="file" name="archivo" accept=".xlsx,.xls,.ods"
                   data-js-cambio-enviar-a-parsear="/certificadoSoft/parsearRectificacion">
          </div>
        </div>
        <div class="row">
          <div class="col-xs-12">
            <p style="margin-top: 1em; color: #777;">
              Se toma la hoja <b>ANEXO A</b>. Sólo se reemplaza el certificado de cada juego:
              no se modifica la categoría, el RTP ni ningún otro dato.
            </p>
          </div>
        </div>
      </div>

      <div class="col-xs-12" data-estado="FILAS">
        <div class="row">
          <div class="col-xs-5">
            <h5>MOTIVO <small>(queda en el historial de cada juego)</small></h5>
            <input name="motivo" class="form-control" maxlength="256" value="Rectificación ">
          </div>
          <div class="col-xs-5">
            <h5>CERTIFICADOS PDF <small>(se asignan por el código que tienen en el nombre)</small></h5>
            <input class="form-control" type="file" multiple accept="application/pdf" data-js-cambio-repartir-pdfs>
          </div>
          <div class="col-xs-2">
            <h5>&nbsp;</h5>
            <span data-resumen-pdfs style="font-size: 0.9em;"></span>
          </div>
        </div>

        {{-- El JS le pone un padding-right igual al ancho del scrollbar del cuerpo,
             si no las columnas de la cabecera quedan corridas respecto de las filas --}}
        <div class="row" data-cabecera-tabla style="margin-top: 1em;">
          <table class="col-md-12 table tabla-rectificacion" style="margin-bottom: 0;">
            <thead>
              <tr>
                <th style="flex: 1.4;">Juego</th>
                <th style="flex: 0.7;">Código</th>
                <th>Certificado actual</th>
                <th>Certificado nuevo</th>
                <th>Laboratorio</th>
                <th style="flex: 1.5;">PDF</th>
                <th style="flex: 0.35;">&nbsp;</th>
              </tr>
            </thead>
          </table>
        </div>
        <div class="row" data-cuerpo-tabla style="max-height: 55vh;overflow-y: scroll;">
          <table data-tabla-rectificacion class="col-md-12 table tabla-rectificacion">
            <tbody>
            </tbody>
          </table>
        </div>

        <div class="row" data-panel-sin-asignar hidden style="margin-top: 1em;">
          <div class="col-xs-12">
            <h5 style="color: orange;">PDF SIN ASIGNAR</h5>
            <table class="table" style="margin-bottom: 0;">
              <tbody data-tabla-sin-asignar></tbody>
            </table>
          </div>
        </div>

        <table hidden>
          <tr data-molde-tabla-rectificacion data-estado-carga="NO ENVIADO">
            <td style="flex: 1.4;" class="celda-solo-lectura">
              <span data-celda="nombre_juego"></span>
              <input type="hidden" data-name="cod_juego">
            </td>
            <td style="flex: 0.7;" class="celda-solo-lectura">
              <span data-celda="cod_juego"></span>
            </td>
            <td class="celda-solo-lectura">
              <span data-celda="certificado_actual"></span>
            </td>
            <td>
              <input data-name="nro_archivo" class="form-control" data-js-cambio-actualizar-certificados>
            </td>
            <td>
              <select data-name="id_laboratorio" class="form-control">
                <option value="">- SELECCIONE -</option>
                @foreach($laboratorios as $l)
                <option value="{{$l->id_laboratorio}}">{{$l->denominacion}}</option>
                @endforeach
              </select>
            </td>
            <td style="display: flex;flex: 1.5;">
              <input style="flex: 8;font-size: 0.85em;" data-name="certificado" class="form-control"
                     type="file" accept="application/pdf" data-js-cambio-actualizar-certificados>
              <span data-pdf-heredado title="El certificado es el mismo que el de la fila de arriba"></span>
              <div data-js-click-limpiar-pdf style="flex: 1;display:flex;flex-direction: column;justify-content: center;align-items: center;">
                <i class="fa fa-times data-css-hover-orange" style="color: red;"></i>
              </div>
            </td>
            <td style="flex: 0.35;">
              <button title="QUITAR DE LA RECTIFICACIÓN" class="btn" data-js-click-eliminar-fila><i class="fa fa-fw fa-trash-alt"></i></button>
              <i data-estado-carga-objeto="CARGANDO" class="fa fa-spinner fa-spin"></i>
              <i data-estado-carga-objeto="COMPLETADO" class="fa fa-check" style="color: green;"></i>
              <i data-estado-carga-objeto="SIN CAMBIOS" class="fa fa-minus" style="color: gray;" title="Ya tenia ese certificado"></i>
              <i data-estado-carga-objeto="ERROR" class="fa fa-times" style="color: red;"></i>
              <span data-estado-carga-objeto="NO ENVIADO"></span>
            </td>
          </tr>
        </table>
      </div>

      <div class="col-xs-12">
        <h4 data-mensaje style="color: orange;"></h4>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  <button type="button" class="btn" data-estado="FILAS" data-js-click-cambiar-estado="IMPORTAR" data-js-click-limpiar-archivo style="float: left;">VOLVER</button>
  <button type="button" class="btn btn-successAceptar" data-estado="FILAS" data-js-click-cargar
          data-url-validar="/certificadoSoft/validarRectificacion"
          data-url-guardar="/certificadoSoft/guardarRectificacion" style="float: left;">RECTIFICAR</button>
  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
  @endslot
@endcomponent
