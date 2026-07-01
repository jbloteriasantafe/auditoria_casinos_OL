{{-- MODAL DE IMPORTACION --}}
<div class="modal fade" id="modalImportadorAadd" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                <button type="button" class="close" data-dismiss="modal">
                    <i class="fa fa-times"></i>
                </button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse"
                    data-minimizar="true" data-target="#colapsado"
                    style="position:relative; right:20px; top:5px">
                    <i class="fa fa-minus"></i>
                </button>
                <h3 class="modal-title">| IMPORTAR BENEFICIO DIARIO</h3>
            </div>
            <div id="colapsado" class="collapse in">
                <div class="modal-body">
                    <form id="formImportadorAadd" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Seleccione uno o más archivos (CSV o Excel)</h5>
                                <input type="file" class="form-control" name="archivos[]" id="archivoModal" accept=".csv,.xlsx,.xls" multiple required>
                                <br>
                                
                                <!-- Contenedor Dinámico para Lista de Archivos y Plataformas -->
                                <div id="listaArchivosPreview" style="display: none; max-height: 300px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th style="width: 45%;">Plataforma</th>
                                                <th style="width: 15%;">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyArchivosPreview">
                                            <!-- Filas generadas por JS -->
                                        </tbody>
                                    </table>
                                </div>

                                <br>
                                <span class="error-message text-danger" style="display: none;" id="errorAjaxForm"></span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer" style="padding-top: 15px;">
                <button id="btn-importar-ajax" type="button" class="btn btn-success" style="background-color: #2e7d32; border-color: #1b5e20;">IMPORTAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
    </div>
</div>
