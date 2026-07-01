<?php

namespace AADD\Actions\Estrategias;

interface EstrategiaMapeo
{
    /**
     * Mapear el contenido binario del archivo a un conjunto de arreglos con los datos de beneficios.
     *
     * @param string $contenidoBinario Contenido real del archivo guardado en base de datos.
     * @return array Arreglo de arreglos con los datos listos para crear entidades.
     */
    public function mapear($contenidoBinario);
}
