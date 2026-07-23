<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\Facades\DB;
use AADD\Enums\Plataforma;
use AADD\Entities\BeneficioDiario;
use Dompdf\Dompdf;

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

        $idPlataforma = Plataforma::obtenerIdDesdeString($plataformaSeleccionada);
        $subqueryPdf = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->where('id_plataforma', $idPlataforma)
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->groupBy('fecha');

        $beneficios = BeneficioDiario::whereIn('id', $subqueryPdf)
            ->orderBy('fecha', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return date('Y-m-d', strtotime($item->fecha));
            });

        if (strtoupper($plataformaSeleccionada) === 'BPLAY') {
            $beneficios->transform(function ($item) {
                $item->turnover += $item->turnover_bonos;
                $item->payout += $item->payout_bonos;
                $item->ggr_calculado = $item->turnover - $item->payout;
                $item->diferencia = round($item->ggr_calculado - $item->ggr, 2);
                if ($item->diferencia == -0) {
                    $item->diferencia = 0.0;
                }
                return $item;
            });
        }

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

        $idPlataforma = Plataforma::obtenerIdDesdeString($plataformaSeleccionada);
        $subqueryExcel = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->where('id_plataforma', $idPlataforma)
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->groupBy('fecha');

        $beneficios = BeneficioDiario::whereIn('id', $subqueryExcel)
            ->orderBy('fecha', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return date('Y-m-d', strtotime($item->fecha));
            });

        if (strtoupper($plataformaSeleccionada) === 'BPLAY') {
            $beneficios->transform(function ($item) {
                $item->turnover += $item->turnover_bonos;
                $item->payout += $item->payout_bonos;
                $item->ggr_calculado = $item->turnover - $item->payout;
                $item->diferencia = round($item->ggr_calculado - $item->ggr, 2);
                if ($item->diferencia == -0) {
                    $item->diferencia = 0.0;
                }
                return $item;
            });
        }

        // Generar CSV
        $filename = 'beneficio_diario_' . strtolower($plataformaSeleccionada) . '_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
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

        $callback = function() use($dias, $beneficios, $mapaColumnas, $columnasAMostrar) {
            $file = fopen('php://output', 'w');
            // Escribir BOM para Excel (ayuda con acentos)
            fputs($file, "\xEF\xBB\xBF");
            
            // Fila de encabezados
            $headersRow = [];
            foreach ($columnasAMostrar as $col) {
                $headersRow[] = $mapaColumnas[$col];
            }
            fputcsv($file, $headersRow, ';'); // Excel prefiere punto y coma en regiones hispanas

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
                    
                    switch($col) {
                        case 'jugadores_activos':
                            $row[] = $b->jugadores_activos; break;
                        case 'apuestas_realizadas':
                            $row[] = $b->apuestas_realizadas; break;
                        case 'turnover':
                            $row[] = number_format($b->turnover, 2, '.', ''); break;
                        case 'turnover_bonos':
                            $row[] = number_format($b->turnover_bonos, 2, '.', ''); break;
                        case 'payout':
                            $row[] = number_format($b->payout, 2, '.', ''); break;
                        case 'payout_bonos':
                            $row[] = number_format($b->payout_bonos, 2, '.', ''); break;
                        case 'ggr_calculado':
                            $ggrCalculado = $b->ggr_calculado ?? ($b->turnover - $b->payout);
                            $row[] = number_format($ggrCalculado, 2, '.', ''); break;
                        case 'ggr':
                            $row[] = number_format($b->ggr, 2, '.', ''); break;
                        case 'diferencia':
                            $ggrCalculado = $b->ggr_calculado ?? ($b->turnover - $b->payout);
                            $diferencia = isset($b->diferencia) ? $b->diferencia : round($ggrCalculado - $b->ggr, 2);
                            if ($diferencia == -0) $diferencia = 0.0;
                            $row[] = number_format($diferencia, 2, '.', ''); break;
                        default:
                            $row[] = ''; break;
                    }
                }
                
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
