<?php

namespace AADD\Utils;

class RangoDeFechas
{
    /** Tope de días del rango, para no sobrecargar memoria ni ciclar de más. */
    const MAX_DIAS = 366;

    /**
     * Normaliza un par desde/hasta en un rango usable: descarta valores con
     * formato inválido cayendo al mes actual, ordena los extremos si vienen
     * invertidos, recorta el largo máximo y enumera los días incluidos.
     *
     * @param string|null $fechaDesde
     * @param string|null $fechaHasta
     * @return array ['inicio' => int, 'fin' => int, 'dias' => string[]]
     */
    public static function resolver($fechaDesde, $fechaHasta)
    {
        $inicio = self::parsear($fechaDesde, strtotime(date('Y-m-01')));
        $fin = self::parsear($fechaHasta, strtotime(date('Y-m-t')));

        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        $maxDiff = self::MAX_DIAS * 24 * 60 * 60;
        if (($fin - $inicio) > $maxDiff) {
            $fin = $inicio + $maxDiff;
        }

        $dias = [];
        for ($actual = $inicio; $actual <= $fin; $actual = strtotime('+1 day', $actual)) {
            $dias[] = date('Y-m-d', $actual);
        }

        return ['inicio' => $inicio, 'fin' => $fin, 'dias' => $dias];
    }

    /**
     * @param string|null $dateStr
     * @param int $default Timestamp a usar si la fecha no es utilizable.
     * @return int
     */
    private static function parsear($dateStr, $default)
    {
        if (empty($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $default;
        }

        $time = strtotime($dateStr);
        if ($time === false) {
            return $default;
        }

        $anio = (int) date('Y', $time);
        if ($anio < 2000 || $anio > 2099) {
            return $default;
        }

        return $time;
    }
}
