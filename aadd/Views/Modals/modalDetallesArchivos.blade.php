<!-- Modal Detalles Archivos -->
<div class="modal fade" id="modalDetallesArchivos" tabindex="-1" role="dialog" aria-labelledby="modalDetallesArchivosLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header" style="background-color: #ffffff; border-bottom: 1px solid #C4C3E3;">
        <h4 class="modal-title" id="modalDetallesArchivosLabel" style="font-weight: bold; color: #504E76;"><i class="fa fa-list"></i> Archivos Cargados</h4>
      </div>
      <div class="modal-body">
        <div id="cargandoDetalles" style="display: none; text-align: center; padding: 20px;">
            <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
            <p style="margin-top: 10px;">Buscando archivos...</p>
        </div>
        <ul id="listaArchivosDetalle" class="list-group" style="margin-bottom: 0; display: none;">
            <!-- Acá se llenan los archivos -->
        </ul>
        <div id="seccionArchivosEliminados" style="margin-top: 15px; display: none;">
            <h5 style="font-weight: bold; color: #d9534f; margin-bottom: 10px; border-bottom: 1px solid #f2dede; padding-bottom: 5px;"><i class="fa fa-trash-o"></i> Archivos Eliminados</h5>
            <ul id="listaArchivosEliminados" class="list-group" style="margin-bottom: 0;">
                <!-- Acá se llenan los archivos eliminados -->
            </ul>
        </div>
        <div id="mensajeSinArchivos" class="alert alert-warning" style="display: none; margin-bottom: 0;">
            No hay archivos cargados para esta fecha y plataforma.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
