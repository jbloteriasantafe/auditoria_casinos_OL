@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<div class="row">
    <div class="col-md-9">
        <!-- Inicio Importador -->
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                <h3 class="panel-title" style="font-weight: bold; color: #333;"><i class="fa fa-upload"></i> Importador de Archivos (CSV / PDF)</h3>
            </div>
            <div class="panel-body">
                @if(isset($mensaje) && $mensaje)
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> {{ $mensaje }}
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="/aadd/importar" method="POST" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="form-group">
                        <label for="archivo">Seleccione un archivo (CSV, Excel o PDF)</label>
                        <input type="file" class="form-control" name="archivo" id="archivo" accept=".csv,.txt,.pdf,.xlsx,.xls" required>
                        <p class="help-block">Tamaño máximo permitido: 10MB.</p>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background-color: #2e7d32; border-color: #1b5e20;">
                        <i class="fa fa-upload"></i> Subir e Importar
                    </button>
                    <a href="/aadd" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver</a>
                </form>

            </div>
        </div>
        <!-- Fin Importador -->
    </div>
    
    <div class="col-md-3">
        <div class="panel panel-default" style="border-left: 4px solid #2e7d32;">
            <div class="panel-heading">
                <h4><i class="fa fa-info-circle" style="color: #2e7d32;"></i> Ayuda</h4>
            </div>
            <div class="panel-body">
                <p>En esta sección puedes subir archivos en formato <strong>CSV</strong> y <strong>PDF</strong>.</p>
                <p>El sistema se encargará de guardarlos y pre-procesarlos.</p>
            </div>
        </div>
    </div>
</div>
@endsection