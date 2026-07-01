<?php

namespace AADD\Enums;

class Plataforma
{
    const PLATAFORMA_A = 1;
    const PLATAFORMA_B = 2;

    /**
     * Obtener todas las plataformas disponibles.
     *
     * @return array
     */
    public static function obtenerTodas()
    {
        return [
            self::PLATAFORMA_A => 'Plataforma A',
            self::PLATAFORMA_B => 'Plataforma B',
        ];
    }
}
