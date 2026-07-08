<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
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
        $beneficios = BeneficioDiario::where('id_plataforma', $idPlataforma)
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->orderBy('fecha', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return date('Y-m-d', strtotime($item->fecha));
            });

        if (strtoupper($plataformaSeleccionada) === 'BPLAY') {
            $beneficios->transform(function ($item) {
                $item->turnover += $item->turnover_bonos;
                $item->payout += $item->payout_bonos;
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
}
