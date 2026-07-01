<?php

namespace AADD\Enums;

class TipoArchivo
{
    const BENEFICIO_DIARIO = 'BENEFICIO_DIARIO';

    /**
     * Obtener todos los tipos de archivo disponibles.
     *
     * @return array
     */
    public static function obtenerTodos()
    {
        return [
            self::BENEFICIO_DIARIO,
        ];
    }
}
