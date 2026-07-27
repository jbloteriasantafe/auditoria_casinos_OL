<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use AADD\Enums\Plataforma;
use AADD\Entities\BeneficioDiario;
use AADD\Actions\ObtenerBeneficioDiarioAction;

class BeneficioDiarioController extends Controller
{
    /**
     * Muestra la vista principal de beneficio diario.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
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

        // Limitar a máximo 60 días para evitar sobrecarga
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

        // Calcular acumulados de rango para todas las plataformas
        $subqueryAcumulados = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->groupBy('id_plataforma', 'fecha');

        $acumuladosQuery = BeneficioDiario::whereIn('id', $subqueryAcumulados)
            ->select('id_plataforma', DB::raw('SUM(ggr) as total_ggr'))
            ->groupBy('id_plataforma')
            ->get();

        $acumuladosRango = [];
        foreach ($plataformas as $plat) {
            $acumuladosRango[$plat] = 0;
        }
        foreach ($acumuladosQuery as $a) {
            $acumuladosRango[$a->id_plataforma] = (float) $a->total_ggr;
        }

        return view('aadd::beneficio-diario', [
            'plataformas' => $plataformas,
            'plataformaSeleccionada' => $plataformaSeleccionada,
            'dias' => $dias,
            'beneficios' => $beneficios,
            'acumuladosRango' => $acumuladosRango,
            'fechaDesde' => date('Y-m-d', $inicio),
            'fechaHasta' => date('Y-m-d', $fin)
        ]);
    }

    /**
     * Endpoint AJAX para actualizar la tabla de beneficio diario.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tabla(Request $request)
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

        // Limitar a máximo 60 días para evitar sobrecarga
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
        // Calcular acumulados de rango para todas las plataformas
        $subqueryAcumulados = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->groupBy('id_plataforma', 'fecha');

        $acumuladosQuery = BeneficioDiario::whereIn('id', $subqueryAcumulados)
            ->select('id_plataforma', DB::raw('SUM(ggr) as total_ggr'))
            ->groupBy('id_plataforma')
            ->get();

        $acumuladosRango = [];
        foreach ($plataformas as $plat) {
            $acumuladosRango[$plat] = 0;
        }
        foreach ($acumuladosQuery as $a) {
            $acumuladosRango[$a->id_plataforma] = (float) $a->total_ggr;
        }

        return response()->json([
            'html' => view('aadd::Componentes.tablaBeneficioDiario', [
                'dias' => $dias,
                'plataformaSeleccionada' => $plataformaSeleccionada,
                'beneficios' => $beneficios
            ])->render(),
            'acumulados_rango' => $acumuladosRango
        ]);
    }

    /**
     * Obtiene el acumulado mensual para todas las plataformas.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acumuladoMensual(Request $request)
    {
        $mes = $request->input('mes');
        if (!$mes || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return response()->json(['error' => 'Mes inválido'], 400);
        }

        $anio = substr($mes, 0, 4);
        $mesNum = substr($mes, 5, 2);

        $fechaInicio = "$anio-$mesNum-01";
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mesNum, $anio);
        $fechaFin = "$anio-$mesNum-$diasEnMes";

        $subqueryMensual = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->groupBy('id_plataforma', 'fecha');

        $acumulados = BeneficioDiario::whereIn('id', $subqueryMensual)
            ->select('id_plataforma', DB::raw('SUM(ggr) as total_ggr'))
            ->groupBy('id_plataforma')
            ->get();

        $plataformas = Plataforma::values();
        $resultados = [];
        foreach ($plataformas as $plat) {
            $resultados[$plat] = 0;
        }

        foreach ($acumulados as $a) {
            $resultados[$a->id_plataforma] = (float) $a->total_ggr;
        }

        $hoy = date('Y-m-d');
        if ($hoy > $fechaFin) {
            $estado = 'concluido';
        } elseif ($hoy < $fechaInicio) {
            $estado = 'proximo';
        } else {
            $estado = 'en_curso';
        }

        return response()->json([
            'concluido' => $estado === 'concluido',
            'estado' => $estado,
            'acumulados' => $resultados
        ]);
    }
}
