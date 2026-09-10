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
use AADD\Actions\ObtenerDatosRapidosAction;
use AADD\Utils\RangoDeFechas;
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
            case 'datos-rapidos':
                return $this->generarDatosRapidosPdf($request);
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
            case 'datos-rapidos':
                return $this->generarDatosRapidosExcel($request);
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
        
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        
        $plataformaSeleccionada = $request->input('plataforma');
        if (!$plataformaSeleccionada || !in_array($plataformaSeleccionada, $plataformas)) {
            $plataformaSeleccionada = $plataformas[0] ?? null;
        }
        
        // Validar fechas de forma robusta
        $parseDate = function ($dateStr, $default) {
            if (empty($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return $default;
            }
            $time = strtotime($dateStr);
            if ($time === false) {
                return $default;
            }
            $year = (int)date('Y', $time);
            if ($year < 2000 || $year > 2099) {
                return $default;
            }
            return $time;
        };

        $inicio = $parseDate($fechaDesde, strtotime(date('Y-m-01')));
        $fin = $parseDate($fechaHasta, strtotime(date('Y-m-t')));

        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        // Limitar rango a un máximo de 366 días para evitar sobrecarga de memoria o ciclos infinitos
        $maxDiff = 366 * 24 * 60 * 60;
        if (($fin - $inicio) > $maxDiff) {
            $fin = $inicio + $maxDiff;
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
        
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $plataformaSeleccionada = $request->input('plataforma');
        if (!$plataformaSeleccionada || !in_array($plataformaSeleccionada, $plataformas)) {
            $plataformaSeleccionada = $plataformas[0] ?? null;
        }

        $columnasSeleccionadas = $request->input('columnas', []);
        
        // Validar fechas de forma robusta
        $parseDate = function ($dateStr, $default) {
            if (empty($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return $default;
            }
            $time = strtotime($dateStr);
            if ($time === false) {
                return $default;
            }
            $year = (int)date('Y', $time);
            if ($year < 2000 || $year > 2099) {
                return $default;
            }
            return $time;
        };

        $inicio = $parseDate($fechaDesde, strtotime(date('Y-m-01')));
        $fin = $parseDate($fechaHasta, strtotime(date('Y-m-t')));

        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        // Limitar rango a un máximo de 366 días para evitar sobrecarga de memoria o ciclos infinitos
        $maxDiff = 366 * 24 * 60 * 60;
        if (($fin - $inicio) > $maxDiff) {
            $fin = $inicio + $maxDiff;
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

    /**
     * Generador del PDF de comparación de beneficios: todas las plataformas en columnas y
     * una fila por día. Va en horizontal porque son dos columnas por plataforma.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function generarDatosRapidosPdf(Request $request)
    {
        $plataformas = $this->plataformasSeleccionadas($request);
        $rango = RangoDeFechas::resolver($request->input('fecha_desde'), $request->input('fecha_hasta'));

        $accion = new ObtenerDatosRapidosAction();
        $datos = $accion->ejecutar(date('Y-m-d', $rango['inicio']), date('Y-m-d', $rango['fin']));

        $view = ViewFacade::make('aadd::datos-rapidos-pdf', [
            'plataformas' => $plataformas,
            'dias' => $rango['dias'],
            'datos' => $datos,
            'fechaDesde' => date('Y-m-d', $rango['inicio']),
            'fechaHasta' => date('Y-m-d', $rango['fin']),
        ]);

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_paper('A4', 'landscape');
        $dompdf->loadHtml($view->render());
        $dompdf->render();

        return $dompdf->stream('datos_rapidos_' . date('Ymd_His') . '.pdf', array("Attachment" => false));
    }

    /**
     * Generador del Excel de comparación de beneficios. Los dos niveles de encabezado de la
     * pantalla se aplanan en un solo título por columna ("BPLAY - GGR") para que
     * la planilla sea filtrable y ordenable.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function generarDatosRapidosExcel(Request $request)
    {
        $plataformas = $this->plataformasSeleccionadas($request);
        $rango = RangoDeFechas::resolver($request->input('fecha_desde'), $request->input('fecha_hasta'));

        $accion = new ObtenerDatosRapidosAction();
        $datos = $accion->ejecutar(date('Y-m-d', $rango['inicio']), date('Y-m-d', $rango['fin']));

        $filename = 'datos_rapidos_' . date('Ymd_His') . '.xlsx';
        $headers = [
            "Content-Type"        => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $rows = [];

        $encabezado = ['Fecha'];
        foreach ($plataformas as $plat) {
            $encabezado[] = $plat . ' - Jugadores activos';
            $encabezado[] = $plat . ' - GGR';
        }
        $rows[] = $encabezado;

        foreach ($rango['dias'] as $dia) {
            $row = [date('d/m/Y', strtotime($dia))];
            foreach ($plataformas as $plat) {
                $d = isset($datos[$dia][$plat]) ? $datos[$dia][$plat] : null;
                $row[] = $d ? $d['jugadores_activos'] : '-';
                $row[] = $d ? $d['ggr'] : '-';
            }
            $rows[] = $row;
        }

        $xlsx = SimpleXLSXGen::fromArray($rows);
        $callback = function() use ($xlsx) {
            echo $xlsx;
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Plataformas tildadas en el filtro, respetando el orden del enum. Si no
     * llega ninguna se informan todas: una planilla con solo la fecha no sirve.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function plataformasSeleccionadas(Request $request)
    {
        $todas = Plataforma::values();

        $pedidas = $request->input('plataformas', []);
        if (!is_array($pedidas)) {
            $pedidas = [$pedidas];
        }

        $filtradas = array_values(array_intersect($todas, $pedidas));

        return empty($filtradas) ? $todas : $filtradas;
    }
}
