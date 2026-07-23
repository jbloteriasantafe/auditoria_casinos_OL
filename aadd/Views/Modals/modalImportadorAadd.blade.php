{{-- MODAL DE IMPORTACION --}}
<style>
    #modalImportadorAadd .modal-content {
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    #modalImportadorAadd .modal-header {
        background: linear-gradient(135deg, #4a4f87 0%, #292639 100%);
        color: white;
        border-bottom: none;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #modalImportadorAadd .modal-title {
        font-family: 'Roboto', sans-serif;
        font-weight: 600;
        font-size: 1.25rem;
        letter-spacing: 0.5px;
        margin: 0;
        display: inline-block;
    }

    #modalImportadorAadd .header-buttons {
        display: inline-flex;
        gap: 15px;
        align-items: center;
    }

    #modalImportadorAadd .close {
        color: white;
        opacity: 0.8;
        text-shadow: none;
        font-size: 1.2rem;
        margin: 0;
        float: none;
    }

    #modalImportadorAadd .close:hover {
        opacity: 1;
    }

    #modalImportadorAadd .modal-body {
        padding: 30px;
        background-color: #fcfcfd;
    }

    #modalImportadorAadd .file-upload-wrapper {
        position: relative;
        width: 100%;
        height: 120px;
        border: 2px dashed #C4C3E3;
        border-radius: 12px;
        background-color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    #modalImportadorAadd .file-upload-wrapper:hover {
        border-color: #4a4f87;
        background-color: #f4f5f8;
    }

    #modalImportadorAadd .file-upload-wrapper input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        top: 0;
        left: 0;
        z-index: 2;
    }

    #modalImportadorAadd .file-upload-icon {
        font-size: 2.5rem;
        color: #4a4f87;
        margin-bottom: 10px;
        transition: transform 0.3s ease;
    }

    #modalImportadorAadd .file-upload-wrapper:hover .file-upload-icon {
        transform: translateY(-5px);
    }

    #modalImportadorAadd .file-upload-text {
        color: #504E76;
        font-weight: 500;
        font-size: 1.1rem;
    }

    #modalImportadorAadd .file-upload-subtext {
        color: #8c8b9f;
        font-size: 0.85rem;
        margin-top: 5px;
    }

    #modalImportadorAadd .table-import {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    #modalImportadorAadd .table-import th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 0.85rem;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
    }

    #modalImportadorAadd .table-import td {
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }

    #modalImportadorAadd .table-import tr:last-child td {
        border-bottom: none;
    }

    #modalImportadorAadd .modal-footer {
        padding: 20px 30px;
        background-color: #ffffff;
        border-top: 1px solid #edf2f7;
        text-align: right;
    }

    #modalImportadorAadd .btn-importar {
        background-color: #10b981 !important;
        color: white !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2) !important;
    }

    #modalImportadorAadd .btn-importar:hover {
        background-color: #059669 !important;
        box-shadow: 0 6px 8px rgba(16, 185, 129, 0.3) !important;
        color: white !important;
    }

    #modalImportadorAadd .btn-cancelar {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        margin-right: 10px !important;
    }

    #modalImportadorAadd .btn-cancelar:hover {
        background-color: #e2e8f0 !important;
        color: #1e293b !important;
    }

    #modalImportadorAadd .form-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        box-shadow: none;
        padding: 8px 12px;
        height: auto;
    }

    #modalImportadorAadd .form-control:focus {
        border-color: #4a4f87;
        box-shadow: 0 0 0 2px rgba(74, 79, 135, 0.2);
    }

    #modalImportadorAadd .btn-danger {
        border-radius: 6px !important;
        padding: 6px 12px !important;
    }
</style>

<div class="modal fade" id="modalImportadorAadd" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 80%; max-width: 900px;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa fa-cloud-upload" style="margin-right: 10px;"></i> Importar
                    Beneficio Diario</h3>
            </div>
            <div id="colapsado" class="collapse in">
                <div class="modal-body">
                    <form id="formImportadorAadd" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-md-12">

                                <div class="file-upload-wrapper">
                                    <i class="fa fa-cloud-upload file-upload-icon"></i>
                                    <div class="file-upload-text">Haz clic o arrastra archivos aquí</div>
                                    <div class="file-upload-subtext">Formatos soportados: CSV, Excel (.xlsx, .xls)</div>
                                    <input type="file" name="archivos[]" id="archivoModal" accept=".csv,.xlsx,.xls"
                                        multiple required>
                                </div>

                                <!-- Contenedor Dinámico para Lista de Archivos y Plataformas -->
                                <div id="listaArchivosPreview"
                                    style="display: none; max-height: 400px; overflow-y: auto; margin-top: 20px;">
                                    <table class="table-import">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th style="width: 25%;">Plataforma</th>
                                                <th style="width: 20%;">Fecha</th>
                                                <th style="width: 15%;">Estado</th>
                                                <th style="width: 10%; text-align: center;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyArchivosPreview">
                                            <!-- Filas generadas por JS -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-danger"
                                    style="display: none; margin-top: 15px; border-radius: 8px; border: none; background-color: #fee2e2; color: #991b1b;"
                                    id="errorAjaxForm"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancelar" data-dismiss="modal">CERRAR</button>
                <button id="btn-importar-ajax" type="button" class="btn btn-importar"><i class="fa fa-upload"></i>
                    IMPORTAR</button>
            </div>
        </div>
    </div>
</div>
