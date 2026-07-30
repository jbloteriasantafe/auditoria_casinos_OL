@extends('includes.dashboard')

@section('headerLogo')
    <span class="etiquetaLogoCasinos">@svg('casinos', 'iconoCasinos')</span>
@endsection

@section('contenidoVista')
    <style>
        /* Google Fonts y tipografía premium */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');

        .aadd-container {
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        .aadd-header {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #4A4F87;
        }

        /* Cards Estilo Dashboard */
        .dashboard-stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 4px 10px rgba(80, 78, 118, 0.05), 0 2px 4px rgba(80, 78, 118, 0.03);
            border: 1px solid #C4C3E3;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
        }

        .dashboard-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(80, 78, 118, 0.1);
            border-color: #4A4F87;
        }

        .stat-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .stat-info {
            flex-grow: 1;
            text-align: left;
        }

        .stat-val {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 2px;
            color: #4A4F87;
        }

        .stat-lbl {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8A89A6;
            font-weight: 600;
        }

        /* Temas de Color para Cards */
        .stat-blue .stat-icon-wrapper {
            background-color: #EEF2F6;
            color: #4F46E5;
            border: 1px solid rgba(79, 70, 229, 0.1);
        }

        .stat-green .stat-icon-wrapper {
            background-color: #F0FDF4;
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.1);
        }

        .stat-red .stat-icon-wrapper {
            background-color: #FEF2F2;
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        .stat-cyan .stat-icon-wrapper {
            background-color: #E0F2FE;
            color: #0284C7;
            border: 1px solid rgba(2, 132, 199, 0.1);
        }

        /* Paneles de Contenido */
        .dashboard-panel {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #C4C3E3;
            box-shadow: 0 4px 10px rgba(80, 78, 118, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .dashboard-panel-heading {
            background-color: #ffffff;
            border-bottom: 1px solid #C4C3E3;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard-panel-title {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #4A4F87;
            margin: 0;
        }

        .dashboard-panel-body {
            padding: 20px;
        }

        /* Acciones Rápidas más llamativas y estéticas */
        .action-tile-card {
            display: block;
            text-decoration: none !important;
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #C4C3E3;
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(80, 78, 118, 0.04);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-tile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(80, 78, 118, 0.12) !important;
            border-color: #4A4F87 !important;
        }

        .action-tile-inner {
            display: flex;
            align-items: stretch;
            min-height: 85px;
        }

        .action-tile-icon-box {
            width: 65px;
            background: #4A4F87;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #C4C3E3;
            font-size: 24px;
            transition: background-color 0.2s ease;
        }

        .action-tile-card:hover .action-tile-icon-box {
            background: #3B3F6F;
        }

        .action-tile-text-box {
            padding: 12px 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: left;
        }

        .action-tile-text-box h5 {
            margin: 0 0 3px 0;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #4A4F87;
            font-size: 14px;
        }

        .action-tile-text-box p {
            margin: 0;
            font-size: 11px;
            color: #64748B;
            line-height: 1.4;
        }

        .action-tile-arrow {
            width: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #CBD5E1;
            transition: color 0.2s ease;
        }

        .action-tile-card:hover .action-tile-arrow {
            color: #4A4F87;
        }

        .action-tile-card:hover .action-tile-arrow i {
            transform: translateX(3px);
            transition: transform 0.2s ease;
        }

        /* Acordeón de Manual de Uso */
        .manual-accordion .panel {
            border-radius: 8px !important;
            margin-bottom: 10px !important;
            box-shadow: none !important;
            border: 1px solid #C4C3E3 !important;
            overflow: hidden;
        }

        .manual-accordion .panel-heading {
            background: #C4C3E3 !important;
            padding: 12px 15px !important;
            border-bottom: none !important;
        }

        .manual-accordion .panel-title a {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #4A4F87 !important;
            text-decoration: none !important;
            display: block;
        }

        .manual-accordion .panel-body {
            border-top: 1px solid #C4C3E3 !important;
            background: #ffffff;
            color: #504E76;
            font-size: 13px;
            line-height: 1.6;
        }

        .step-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: #E2E8F0;
            color: #4A4F87;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 8px;
        }

        .manual-step {
            margin-bottom: 12px;
            text-align: left;
        }

        .manual-step:last-child {
            margin-bottom: 0;
        }
    </style>

    <div class="aadd-container">
        <!-- Fila de Tarjetas de Indicadores Centradas en el medio con Flexbox -->
        <div class="row"
            style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; text-align: center;">
            <div style="width: 260px; flex-shrink: 0; display: inline-block;">
                <div class="dashboard-stat-card stat-blue">
                    <div class="stat-icon-wrapper">
                        <i class="fa fa-file-excel-o"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-val">{{ number_format($totalArchivos, 0, ',', '.') }}</div>
                        <div class="stat-lbl">Archivos Cargados</div>
                    </div>
                </div>
            </div>
            <div style="width: 260px; flex-shrink: 0; display: inline-block;">
                <div class="dashboard-stat-card {{ $diferenciasDetectadas > 0 ? 'stat-red' : 'stat-green' }}">
                    <div class="stat-icon-wrapper">
                        <i class="fa {{ $diferenciasDetectadas > 0 ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-val">{{ number_format($diferenciasDetectadas, 0, ',', '.') }}</div>
                        <div class="stat-lbl">Diferencias Halladas</div>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Fila Principal (Contenido) -->
        <div class="row">
            <!-- Columna Izquierda: Manual de Uso y Historial -->
            <div class="col-md-8">
                <!-- Manual de Uso -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-heading">
                        <h3 class="dashboard-panel-title"><i class="fa fa-book text-primary" style="margin-right: 8px;"></i>
                            Manual de Uso y Control</h3>
                    </div>
                    <div class="dashboard-panel-body">
                        <div class="panel-group manual-accordion" id="accordionManual">

                            <!-- Manual de Importación -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordionManual" href="#collapseImportar">
                                            <i class="fa fa-upload text-primary" style="margin-right: 8px;"></i> 1.
                                            Importación Múltiple de Archivos
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseImportar" class="panel-collapse collapse in">
                                    <div class="panel-body">
                                        <div class="manual-step">
                                            <span class="step-badge">1</span> <strong>Acceso al módulo:</strong> Diríjase a
                                            la sección <a href="/aadd/importaciones"
                                                style="font-weight: 600;">Importaciones</a>.
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">2</span> <strong>Subida:</strong> Presione el botón
                                            principal para abrir el selector de archivos. Seleccione uno o más archivos de
                                            formato <code>.csv</code>, <code>.xlsx</code> o <code>.xls</code>.
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">3</span> <strong>Asociación:</strong> En la lista
                                            generada, seleccione de forma obligatoria la plataforma correspondiente para
                                            cada archivo.
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">4</span> <strong>Procesamiento:</strong> Presione
                                            <em>Importar</em>. El sistema procesará el lote de archivos en paralelo y
                                            mostrará el estado de la carga (✅ Éxito / ❌ Error con detalle).
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual de Auditoría -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a class="collapsed" data-toggle="collapse" data-parent="#accordionManual"
                                            href="#collapseAuditar">
                                            <i class="fa fa-calculator text-success" style="margin-right: 8px;"></i> 2.
                                            Control de Beneficios Diarios y Diferencias
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapseAuditar" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="manual-step">
                                            <span class="step-badge">1</span> <strong>Consulta de Rango:</strong> Ingrese a
                                            la sección <a href="/aadd/beneficio-diario" style="font-weight: 600;">Beneficio
                                                Diario</a>, elija la plataforma y defina el rango de fechas (máximo 60 días
                                            para optimizar la velocidad).
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">2</span> <strong>Análisis de Columnas:</strong> Observe
                                            los montos extraídos:
                                            <ul>
                                                <li><strong>GGR Calculado:</strong> Calculado por el sistema restando el
                                                    Payout del Turnover (<code>Turnover - Payout</code>).</li>
                                                <li><strong>GGR Original:</strong> El monto GGR original provisto por el
                                                    archivo de la plataforma.</li>
                                                <li><strong>Diferencia:</strong> Desviación real (<code>GGR Calculado - GGR
                                                        Original</code>).</li>
                                            </ul>
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">3</span> <strong>Alertas:</strong> Las celdas de
                                            diferencia que superen una tolerancia de ±$0.01 se pintarán automáticamente en
                                            <span class="text-danger" style="font-weight: bold;">rojo</span> para indicar
                                            una discrepancia y requerir revisión del auditor.
                                        </div>
                                        <div class="manual-step">
                                            <span class="step-badge">4</span> <strong>Exportar:</strong> Puede descargar la
                                            planilla oficial en formato <strong>PDF</strong> para presentación formal, o
                                            exportar los datos crudos a <strong>Excel / CSV</strong>.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


            </div>

            <!-- Columna Derecha: Accesos -->
            <div class="col-md-4">
                <!-- Panel de Accesos Rápidos llamativos -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-heading">
                        <h3 class="dashboard-panel-title"><i class="fa fa-bolt text-warning" style="margin-right: 8px;"></i>
                            Accesos Rápidos</h3>
                    </div>
                    <div class="dashboard-panel-body" style="padding: 15px;">

                        <!-- Acceso Importaciones -->
                        <a href="/aadd/importaciones" class="action-tile-card">
                            <div class="action-tile-inner">
                                <div class="action-tile-icon-box">
                                    <i class="fa fa-cloud-upload"></i>
                                </div>
                                <div class="action-tile-text-box">
                                    <h5>Importador de Archivos</h5>
                                    <p>Cargue y procese archivos XLSX/CSV de beneficios diarios de forma múltiple.</p>
                                </div>
                                <div class="action-tile-arrow">
                                    <i class="fa fa-chevron-right"></i>
                                </div>
                            </div>
                        </a>

                        <!-- Acceso Beneficio Diario -->
                        <a href="/aadd/beneficio-diario" class="action-tile-card">
                            <div class="action-tile-inner">
                                <div class="action-tile-icon-box" style="color: #FCDD9D;">
                                    <i class="fa fa-bar-chart"></i>
                                </div>
                                <div class="action-tile-text-box">
                                    <h5>Beneficios Diarios</h5>
                                    <p>Consulte reportes, audite diferencias de GGR y exporte informes en PDF/Excel.</p>
                                </div>
                                <div class="action-tile-arrow">
                                    <i class="fa fa-chevron-right"></i>
                                </div>
                            </div>
                        </a>

                        <!-- Acceso Comparación de Beneficios -->
                        <a href="/aadd/datos-rapidos" class="action-tile-card" style="margin-bottom: 0;">
                            <div class="action-tile-inner">
                                <div class="action-tile-icon-box"></div>
                                <div class="action-tile-text-box">
                                    <h5>Comparación de beneficios por plataforma</h5>
                                    <p>Compare todas las plataformas de un vistazo: jugadores activos y GGR por día.</p>
                                </div>
                                <div class="action-tile-arrow">
                                    <i class="fa fa-chevron-right"></i>
                                </div>
                            </div>
                        </a>

                    </div>
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
        <p>Este panel demuestra el funcionamiento de una vista modularizada que se integra directamente en la interfaz
            general de la plataforma CASOnline de la Lotería de Santa Fe.</p>
    </div>
@endsection
