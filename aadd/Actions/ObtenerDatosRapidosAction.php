<?php

namespace AADD\Actions;

use Illuminate\Support\Facades\DB;
use AADD\Entities\BeneficioDiario;

class ObtenerDatosRapidosAction
{
    /**
     * Obtiene, para cada día del rango, los jugadores activos y el GGR de todas
     * las plataformas de una sola vez.
     *
     * Ante varias importaciones del mismo día y plataforma se toma la más
     * reciente (MAX(id)), igual criterio que ObtenerBeneficioDiarioAction.
     *
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array ['Y-m-d' => ['PLATAFORMA' => ['jugadores_activos' => int, 'ggr' => float]]]
     */
    public function ejecutar($fechaDesde, $fechaHasta)
    {
        $subquery = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->groupBy('id_plataforma', 'fecha');

        $registros = BeneficioDiario::whereIn('id', $subquery)
            ->select('id_plataforma', 'fecha', 'jugadores_activos', 'ggr')
            ->orderBy('fecha', 'asc')
            ->get();

        $datos = [];
        foreach ($registros as $registro) {
            $dia = date('Y-m-d', strtotime($registro->fecha));
            $datos[$dia][$registro->id_plataforma] = [
                'jugadores_activos' => (int) $registro->jugadores_activos,
                'ggr' => (float) $registro->ggr,
            ];
        }

        return $datos;
    }
}
