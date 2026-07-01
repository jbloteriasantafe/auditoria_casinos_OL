<?php

namespace AADD\Enums;

class Plataforma
{
    const BPLAY = 'BPLAY';
    const CITYCENTER = 'CITYCENTER';
    const JUGADON = 'JUGADON';
    const BETWARRIOR = 'BETWARRIOR';
    const BETSON = 'BETSON';
    const BET365 = 'BET365';

    /**
     * Obtener todas las plataformas disponibles.
     *
     * @return array
     */
    public static function obtenerTodas()
    {
        return [
            self::BPLAY,
            self::CITYCENTER,
            self::JUGADON,
            self::BETWARRIOR,
            self::BETSON,
            self::BET365,
        ];
    }

    /**
     * Obtener el ID de la plataforma a partir de su representación en string.
     *
     * @param string $nombre
     * @return string|null
     */
    public static function obtenerIdDesdeString($nombre)
    {
        $todas = self::obtenerTodas();
        $nombreUpper = strtoupper($nombre);
        return in_array($nombreUpper, $todas) ? $nombreUpper : null;
    }
}
