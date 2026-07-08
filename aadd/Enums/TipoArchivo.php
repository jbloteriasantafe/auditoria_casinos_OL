<?php

namespace AADD\Enums;

// TODO: enums no existen en php 7.0
// Corregir al migrar.
class TipoArchivo
{
    const BENEFICIO_DIARIO = 'BENEFICIO_DIARIO';

    /**
     * Obtener todos los tipos de archivo disponibles.
     *
     * @return array
     */
    public static function values()
    {
        return [
            self::BENEFICIO_DIARIO,
        ];
    }

    public static function columnas($tipoArchivo)
    {
        switch($tipoArchivo) {
            case self::BENEFICIO_DIARIO:
                return [
                    'FECHA',
                    'JUGADORES ACTIVOS',
                    'APUESTAS REALIZADAS',
                    'TURNOVER',
                    'TURNOVER BONOS',
                    'PAYOUT',
                    'PAYOUT BONOS',
                    'GGR',
                ];
            default:
                throw new Exception("Tipo de archivo no soportado: " . $tipoArchivo);
        }
    }
}
