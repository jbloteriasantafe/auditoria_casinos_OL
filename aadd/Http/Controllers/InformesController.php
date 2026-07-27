<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\Facades\DB;
use AADD\Enums\Plataforma;
use AADD\Entities\BeneficioDiario;
use Dompdf\Dompdf;
use AADD\Actions\ObtenerBeneficioDiarioAction;
require_once base_path('aadd/Libs/SimpleXLSXGen.php');
use Shuchkin\SimpleXLSXGen;

class InformesController extends Controller
{
    /**
     * Endpoint general y extensible para exportar diferentes reportes en formato PDF.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $reporte
     * @return mixed
     */
    public function exportarPdf(Request $request, $reporte)
    {
        switch ($reporte) {
            case 'beneficio-diario':
                return $this->generarBeneficioDiarioPdf($request);
            default:
                abort(404, 'Informe en PDF no encontrado.');
        }
    }

    /**
     * Endpoint general y extensible para exportar diferentes reportes en formato Excel (CSV).
     *
     * @param \Illuminate\Http\Request $request
     * @param string $reporte
     * @return mixed
     */
    public function exportarExcel(Request $request, $reporte)
    {
        switch ($reporte) {
            case 'beneficio-diario':
                return $this->generarBeneficioDiarioExcel($request);
            default:
                abort(404, 'Informe en Excel no encontrado.');
        }
    }

    /**
     * Generador específico para el PDF de beneficio diario.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function generarBeneficioDiarioPdf(Request $request)
    {
        $plataformas = Plataforma::values();
        
        $fechaDesde = $request->input('fecha_desde', date('Y-m-01'));
        $fechaHasta = $request->input('fecha_hasta', date('Y-m-t'));
        
        $plataformaSeleccionada = $request->input('plataforma');
        if (!$plataformaSeleccionada || !in_array($plataformaSeleccionada, $plataformas)) {
            $plataformaSeleccionada = $plataformas[0] ?? null;
        }
        
        // Validar fechas
        $inicio = strtotime($fechaDesde);
        $fin = strtotime($fechaHasta);
        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        // Limitar a máximo 60 días
        $diferenciaDias = round(($fin - $inicio) / (60 * 60 * 24));
        if ($diferenciaDias > 60) {
            $fin = strtotime('+60 days', $inicio);
        }
        
        $dias = [];
        for ($current = $inicio; $current <= $fin; $current = strtotime('+1 day', $current)) {
            $dias[] = date('Y-m-d', $current);
        }

        $obtenerBeneficiosAction = new ObtenerBeneficioDiarioAction();
        $beneficios = $obtenerBeneficiosAction->ejecutar(
            $plataformaSeleccionada,
            date('Y-m-d', $inicio),
            date('Y-m-d', $fin)
        );

        

        $view = ViewFacade::make('aadd::beneficio-diario-pdf', compact('dias', 'plataformaSeleccionada', 'beneficios', 'fechaDesde', 'fechaHasta'));
        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_paper('A4', 'portrait');
        $dompdf->loadHtml($view->render());
        $dompdf->render();

        return $dompdf->stream('beneficio_diario_' . strtolower($plataformaSeleccionada) . '.pdf', array("Attachment" => false));
    }

    /**
     * Generador específico para el Excel (CSV) de beneficio diario con filtros dinámicos.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function generarBeneficioDiarioExcel(Request $request)
    {
        $plataformas = Plataforma::values();
        
        $fechaDesde = $request->input('fecha_desde', date('Y-m-01'));
        $fechaHasta = $request->input('fecha_hasta', date('Y-m-t'));
        $plataformaSeleccionada = $request->input('plataforma');
        if (!$plataformaSeleccionada || !in_array($plataformaSeleccionada, $plataformas)) {
            $plataformaSeleccionada = $plataformas[0] ?? null;
        }

        $columnasSeleccionadas = $request->input('columnas', []);
        
        // Validar fechas
        $inicio = strtotime($fechaDesde);
        $fin = strtotime($fechaHasta);
        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        // Limitar a máximo 60 días
        $diferenciaDias = round(($fin - $inicio) / (60 * 60 * 24));
        if ($diferenciaDias > 60) {
            $fin = strtotime('+60 days', $inicio);
        }

        $dias = [];
        for ($current = $inicio; $current <= $fin; $current = strtotime('+1 day', $current)) {
            $dias[] = date('Y-m-d', $current);
        }

        $obtenerBeneficiosAction = new ObtenerBeneficioDiarioAction();
        $beneficios = $obtenerBeneficiosAction->ejecutar(
            $plataformaSeleccionada,
            date('Y-m-d', $inicio),
            date('Y-m-d', $fin)
        );

        

        // Generar XLSX
        $filename = 'beneficio_diario_' . strtolower($plataformaSeleccionada) . '_' . date('Ymd_His') . '.xlsx';
        $headers = [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $mapaColumnas = [
            'fecha' => 'Fecha',
            'jugadores_activos' => 'Jugadores activos',
            'apuestas_realizadas' => 'Apuestas realizadas',
            'turnover' => 'Turnover diario',
            'turnover_bonos' => 'Turnover en bonos',
            'payout' => 'Payout diario',
            'payout_bonos' => 'Payout en bonos',
            'ggr_calculado' => 'GGR Calculado',
            'ggr' => 'GGR original',
            'diferencia' => 'Diferencia'
        ];

        // Definir qué columnas mostrar. 'fecha' siempre va primero.
        $columnasAMostrar = ['fecha'];
        foreach ($columnasSeleccionadas as $col) {
            if (isset($mapaColumnas[$col])) {
                $columnasAMostrar[] = $col;
            }
        }

        $rows = [];

        // Fila de encabezados
        $headersRow = [];
        foreach ($columnasAMostrar as $col) {
            $headersRow[] = $mapaColumnas[$col];
        }
        $rows[] = $headersRow;

        // Filas de datos
        foreach ($dias as $dia) {
            $b = isset($beneficios[$dia]) ? $beneficios[$dia] : null;
            $row = [];

            foreach ($columnasAMostrar as $col) {
                if ($col === 'fecha') {
                    $row[] = date('d/m/Y', strtotime($dia));
                    continue;
                }

                if (!$b) {
                    $row[] = '-';
                    continue;
                }

                $value = '';
                switch($col) {
                    case 'jugadores_activos':
                        $value = $b->jugadores_activos; break;
                    case 'apuestas_realizadas':
                        $value = $b->apuestas_realizadas; break;
                    case 'turnover':
                        $value = $b->turnover; break;
                    case 'turnover_bonos':
                        $value = $b->turnover_bonos; break;
                    case 'payout':
                        $value = $b->payout; break;
                    case 'payout_bonos':
                        $value = $b->payout_bonos; break;
                    case 'ggr_calculado':
                        $value = $b->ggr_calculado ?? ($b->turnover - $b->payout); break;
                    case 'ggr':
                        $value = $b->ggr; break;
                    case 'diferencia':
                        $ggrCalculado = $b->ggr_calculado ?? ($b->turnover - $b->payout);
                        $value = isset($b->diferencia) ? $b->diferencia : round($ggrCalculado - $b->ggr, 2);
                        if ($value == -0) $value = 0.0;
                        break;
                }

                $row[] = $value;
            }
            $rows[] = $row;
        }

        $xlsx = SimpleXLSXGen::fromArray($rows);
        $callback = function() use ($xlsx) {
            echo $xlsx;
        };

        return response()->stream($callback, 200, $headers);
    }
}
