<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use AADD\Enums\Plataforma;
use AADD\Entities\BeneficioDiario;
use AADD\Actions\ObtenerDatosRapidosAction;
use AADD\Utils\RangoDeFechas;

class DatosRapidosController extends Controller
{
    /**
     * Muestra la vista consolidada: una fila por día y todas las plataformas
     * en columnas, con jugadores activos y GGR de cada una.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $plataformas = Plataforma::values();
        $rango = RangoDeFechas::resolver($request->input('fecha_desde'), $request->input('fecha_hasta'));

        $accion = new ObtenerDatosRapidosAction();
        $datos = $accion->ejecutar(date('Y-m-d', $rango['inicio']), date('Y-m-d', $rango['fin']));

        return view('aadd::datos-rapidos', [
            'plataformas' => $plataformas,
            'dias' => $rango['dias'],
            'datos' => $datos,
            'acumuladosRango' => $this->acumuladosGgrDelRango($plataformas, $rango['inicio'], $rango['fin']),
            'fechaDesde' => date('Y-m-d', $rango['inicio']),
            'fechaHasta' => date('Y-m-d', $rango['fin']),
        ]);
    }

    /**
     * Endpoint AJAX para refrescar la tabla al cambiar el rango de fechas.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tabla(Request $request)
    {
        $plataformas = Plataforma::values();
        $rango = RangoDeFechas::resolver($request->input('fecha_desde'), $request->input('fecha_hasta'));

        $accion = new ObtenerDatosRapidosAction();
        $datos = $accion->ejecutar(date('Y-m-d', $rango['inicio']), date('Y-m-d', $rango['fin']));

        return response()->json([
            'html' => view('aadd::Componentes.tablaDatosRapidos', [
                'plataformas' => $plataformas,
                'dias' => $rango['dias'],
                'datos' => $datos,
            ])->render(),
            'acumulados_rango' => $this->acumuladosGgrDelRango($plataformas, $rango['inicio'], $rango['fin']),
        ]);
    }

    /**
     * Total de jugadores activos del mes por plataforma.
     *
     * Es la suma de los conteos diarios, o sea jugador-día: quien jugó varios
     * días del mes se cuenta una vez por día. Los archivos que importamos vienen
     * agregados por día, así que no hay forma de deduplicar a nivel jugador.
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
            ->select('id_plataforma', DB::raw('SUM(jugadores_activos) as total_jugadores'))
            ->groupBy('id_plataforma')
            ->get();

        $resultados = [];
        foreach (Plataforma::values() as $plat) {
            $resultados[$plat] = 0;
        }
        foreach ($acumulados as $acumulado) {
            $resultados[$acumulado->id_plataforma] = (int) $acumulado->total_jugadores;
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
            'acumulados' => $resultados,
        ]);
    }

    /**
     * GGR sumado del rango, por plataforma, con todas las plataformas presentes
     * en el resultado (en cero las que no tengan datos).
     *
     * @param array $plataformas
     * @param int $inicio
     * @param int $fin
     * @return array
     */
    protected function acumuladosGgrDelRango(array $plataformas, $inicio, $fin)
    {
        $subquery = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->whereBetween('fecha', [date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->groupBy('id_plataforma', 'fecha');

        $acumulados = BeneficioDiario::whereIn('id', $subquery)
            ->select('id_plataforma', DB::raw('SUM(ggr) as total_ggr'))
            ->groupBy('id_plataforma')
            ->get();

        $resultados = [];
        foreach ($plataformas as $plat) {
            $resultados[$plat] = 0;
        }
        foreach ($acumulados as $acumulado) {
            $resultados[$acumulado->id_plataforma] = (float) $acumulado->total_ggr;
        }

        return $resultados;
    }
}
