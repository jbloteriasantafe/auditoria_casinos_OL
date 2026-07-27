<?php

namespace AADD\Actions;

use Illuminate\Support\Facades\DB;
use AADD\Entities\BeneficioDiario;
use AADD\Enums\Plataforma;

class ObtenerBeneficioDiarioAction
{
    /**
     * Obtiene los beneficios diarios para una plataforma en un rango de fechas.
     *
     * @param string $plataforma
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ejecutar($plataforma, $fechaDesde, $fechaHasta)
    {
        $idPlataforma = Plataforma::obtenerIdDesdeString($plataforma);
        
        $subquery = BeneficioDiario::select(DB::raw('MAX(id) as id'))
            ->where('id_plataforma', $idPlataforma)
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->groupBy('fecha');

        $beneficios = BeneficioDiario::with('archivo')
            ->whereIn('id', $subquery)
            ->orderBy('fecha', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return date('Y-m-d', strtotime($item->fecha));
            });

        return $beneficios;
    }
}
