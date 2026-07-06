@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')
<style>
    .panel { border-radius: 12px !important; overflow: hidden; box-shadow: 0 4px 15px rgba(80, 78, 118, 0.05) !important; border: 1px solid #C4C3E3 !important; background-color: #ffffff; }
    .panel-heading { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; background-color: #ffffff !important; border-bottom: 1px solid #C4C3E3 !important; color: #504E76 !important; }
    .btn { border-radius: 8px !important; font-weight: 500; transition: all 0.2s ease; box-shadow: none !important; }
    .btn-primary { background-color: #504E76 !important; color: #ffffff !important; border: none !important; }
    .btn-primary:hover { background-color: #3b3958 !important; color: #ffffff !important; }
    .alert { border-radius: 8px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.02); border: none !important; }
    .well { border-radius: 12px !important; box-shadow: 0 2px 10px rgba(80, 78, 118, 0.05) !important; border: 1px solid #C4C3E3 !important; background-color: #fcfbf5 !important; }
    .modal-content { border-radius: 12px !important; overflow: hidden; border: none !important; box-shadow: 0 10px 30px rgba(80, 78, 118, 0.15) !important; }
    .container-fluid { padding-right: 20px !important; padding-left: 20px !important; }

</style>
<div class="row">
    <div class="col-md-9">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Submódulo Temporal AADD</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-success" style="background-color: #6dc7be; color: #ffffff; margin-bottom: 20px; margin-top:10px">
                            <h4 style="color: #ffffff;"><i class="fa fa-fw fa-check-circle"></i> ¡Integración Correcta!</h4>
                            <p>El submódulo <strong>AADD</strong> se ha enlazado de forma exitosa a la estructura y enrutador principal de la aplicación Laravel. Este es un entorno de pruebas aislado y protegido.</p>
                        </div>
                        
                        @include('aadd::Componentes.navegacion-chips', [
                            'pantalla' => 'index',
                            'containerStyle' => 'margin-bottom: 15px;'
                        ])
                        
                        <div class="well" style="padding: 20px;">
                            <h3 style="margin-top: 0; font-family: Roboto-Condensed, sans-serif; font-weight: 500; color: #555;">Información del Submódulo</h3>
                            <hr style="border-top: 1px solid #eee; margin-top: 10px; margin-bottom: 15px;">
                            
                            <table class="table table-striped" style="margin-bottom: 0;">
                                <tbody>
                                    <tr>
                                        <td style="font-weight: bold; width: 30%;">Namespace PSR-4</td>
                                        <td><code>AADD\</code></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">Ruta del Servidor</td>
                                        <td><span class="label label-info" style="font-size: 90%;">/aadd</span></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">Controlador Activo</td>
                                        <td><code>AaddController.php</code> (en <code>aadd/Http/Controllers/</code>)</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">Vista Utilizada</td>
                                        <td><code>index.blade.php</code> (en <code>aadd/Views/</code>)</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">Middleware de Seguridad</td>
                                        <td><span class="label label-warning" style="font-size: 90%;">tiene_permiso</span> (Solo usuarios autenticados)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="panel panel-default" style="border-left: 4px solid #90caf9;">
            <div class="panel-heading">
                <h4><i class="fa fa-info-circle" style="color: #504E76;"></i> Estado</h4>
            </div>
            <div class="panel-body">
                <p>Este submódulo fue diseñado para trabajar de forma modular y autónoma dentro del directorio <code>aadd/</code>, sin afectar el núcleo de la aplicación (<code>app/</code>).</p>
            </div>
        </div>
    </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA SUBMÓDULO AADD</h3>
@endsection

@section('contenidoAyuda')
<div class="col-md-12">
    <h5>Submódulo AADD</h5>
    <p>Este panel demuestra el funcionamiento de una vista modularizada que se integra directamente en la interfaz general de la plataforma CASOnline de la Lotería de Santa Fe.</p>
</div>
@endsection
