<?php

namespace AADD\Enums;

class TipoArchivo
{
    const DIARIO = 'diario';
    const MENSUAL = 'mensual';

    /**
     * Obtener todos los tipos de archivo disponibles.
     *
     * @return array
     */
    public static function obtenerTodos()
    {
        return [
            self::DIARIO => 'Diario',
            self::MENSUAL => 'Mensual',
        ];
    }
}
