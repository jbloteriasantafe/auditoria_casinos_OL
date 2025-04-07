<style>
  .modalImportacion .tabla-a-importar tr{
    display: flex;
  }
  .modalImportacion .tabla-a-importar tr th{
    flex: 1;
    text-align: center;
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
    border-bottom: 1px solid gray;
  }
  .modalImportacion .tabla-a-importar tr td{
    flex: 1;
    text-align: right;
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
  }
  
  .modalImportacion [data-estado] {
    display: none;
  }
  @foreach(['IMPORTAR','JUEGOS'] as $e)
  .modalImportacion[data-estado-visible="{{$e}}"] [data-estado="{{$e}}"] {
    display: block;
  }
  @endforeach
  
  .modalImportacion [data-tabla-juegos-a-importar] [data-estado-carga-objeto] {
    display: none;
  }
  @foreach(['NO ENVIADO','CARGANDO','COMPLETADO','ERROR'] as $e)
  .modalImportacion [data-tabla-juegos-a-importar] [data-estado-carga="{{$e}}"] [data-estado-carga-objeto="{{$e}}"] {
    display: inline-block;
  }
  @endforeach
  
  .data-css-esconder-disabled[disabled]{
    visibility: hidden;
  }
  
  .data-css-hover-orange:hover {
     box-shadow: 0 0 0.5em black;
     cursor: pointer;
  }
</style>

@component('Components/modal',[
  'clases_modal' => 'modalImportacion',
  'attrs_modal' => 'data-js-modal-importacion data-estado-visible="IMPORTAR"',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color: #6dc7be;',
  'grande' => 98
])
  @slot('titulo')
  | IMPORTAR
  @endslot
  @slot('cuerpo')
  <form>
    <div class="row">
      <div class="data-xs-12" data-estado="IMPORTAR">
        <div class="row">
          <div class="col-xs-4">
            <h5>ARCHIVO</h5>
            <input class="form-control" type="file" name="archivo" data-js-cambio-enviar-a-parsear="/juegos/parsearArchivo">
          </div>
        </div>
      </div>
      <div class="col-xs-12" data-estado="JUEGOS">
        <div class="row">
          <div class="col-xs-4">
            <h5>PLATAFORMA</h5>
            <select name="id_plataforma" class="form-control">
              <option value="">Seleccione</option>
              @foreach ($plataformas as $plataforma)
              <option value="{{$plataforma->id_plataforma}}">{{$plataforma->nombre}}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="row">
          <table class="col-md-12 table tabla-a-importar" style="margin-bottom: 0;">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Tecnología</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Proveedor</th>
                <th>Porcentaje Devolución</th>
                <th>Certificado</th>
                <th>Laboratorio</th>
                <th style="flex: 1.5;">PDF</th>
                <th style="flex: 0.35;">&nbsp;</th>
              </tr>
            </thead>
          </table>
        </div>
        <div class="row" style="max-height: 65vh;overflow-y: scroll;">
          <table data-tabla-juegos-a-importar class="col-md-12 table tabla-a-importar">
            <thead>
              <tr>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;flex: 1.5;">&nbsp;</th>
                <th style="border-bottom: 0;border-top: 0;flex: 0.35;">&nbsp;</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
        <datalist id="modalImportacionCertificadosList">
        </datalist>
        <table hidden>
          <tr data-molde-tabla-juegos-a-importar data-estado-carga="NO ENVIADO">
            <td>
              <select data-name="id_categoria_juego" class="form-control">
                <option value="">- SELECCIONE -</option>
                @foreach($categoria_juego as $c)
                <option value="{{$c->id_categoria_juego}}">{{$c->nombre}}</option>
                @endforeach
              </select>
            </td>
            <td>
              <select data-name="tecnologia" class="form-control">
                <option value="">- SELECCIONE -</option>
                <option value="escritorio">Escritorio</option>
                <option value="movil">Móvil</option>
                <option value="escritorio_y_movil">Escritorio y Móvil</option>
              </select>
            </td>
            <td>
              <input data-name="cod_juego" class="form-control">
            </td>
            <td>
              <input data-name="nombre_juego" class="form-control">
            </td>
            <td>
              <input list="{{$datalistProveedores}}" data-name="proveedor" class="form-control" data-js-change-actualizar-list>
            </td>
            <td>
              <input data-name="porcentaje_devolucion" class="form-control">
            </td>
            <td>
              <input list="modalImportacionCertificadosList" data-name="nro_archivo" class="form-control" data-js-cambio-actualizar-certificados>
            </td>
            <td>
              <select data-name="id_laboratorio" class="form-control data-css-esconder-disabled">
                <option value="">- SELECCIONE -</option>
                @foreach($laboratorios as $l)
                <option value="{{$l->id_laboratorio}}">{{$l->denominacion}}</option>
                @endforeach
              </select>
            </td>
            <td style="display: flex;flex: 1.5;">
              <input style="flex: 8;font-size: 0.85em;" data-name="certificado" class="form-control data-css-esconder-disabled" type="file" data-js-cambio-validar-mime="application/pdf" data-js-cambio-actualizar-certificados>
              <div class="data-css-esconder-disabled" data-js-click-clear-sibling='[data-name="certificado"]' style="flex: 1;display:flex;flex-direction: column;justify-content: center;align-items: center;">
                <i class="fa fa-times data-css-hover-orange" style="color: red;"></i> 
              </div>
            </td>
            <td style="flex: 0.35;">
              <button title="ELIMINAR" class="btn" data-js-click-eliminar-fila><i class="fa fa-fw fa-trash-alt"></i></button>
              <i data-estado-carga-objeto="CARGANDO" class="fa fa-spinner fa-spin"></i>
              <i data-estado-carga-objeto="COMPLETADO" class="fa fa-check" style="color: green;"></i>
              <i data-estado-carga-objeto="ERROR" class="fa fa-times"  style="color: red;"></i> 
              <span data-estado-carga-objeto="NO ENVIADO"></span>
            </td>
          </tr>
        </table>
      </div>
      <div class="col-xs-4" data-estado="JUEGOS">
        <button title="AGREGAR" class="btn" data-js-click-agregar-fila><i class="fa fa-fw fa-plus"></i></button>
      </div>
      <div class="col-xs-12">
        <h4 data-mensaje style="color: orange;">
        </h4>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  <button type="button" class="btn btn-warningModificar" data-estado="IMPORTAR" data-js-click-cambiar-estado="JUEGOS" style="float: left;">CARGA MANUAL</button>
  <button type="button" class="btn" data-estado="JUEGOS" data-js-click-cambiar-estado="IMPORTAR" data-js-click-limpiar-archivo style="float: left;">VOLVER</button>
  <button data-datalistProveedores="{{$datalistProveedores}}" type="button" class="btn btn-successAceptar" data-estado="JUEGOS" data-js-click-cargar data-url-validar-carga-masiva="/juegos/validarCargaMasiva" data-url-carga-masiva="/juegos/guardarCargaMasiva" style="float: left;">SUBIR</button>
  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
  @endslot
@endcomponent
