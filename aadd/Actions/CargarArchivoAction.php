<?php

namespace AADD\Actions;

use AADD\Entities\Archivo;
use Illuminate\Http\UploadedFile;

class CargarArchivoAction
{
    /**
     * Guardar el archivo en la base de datos sin procesar su contenido.
     *
     * @param UploadedFile $archivoSubido
     * @param string $tipoArchivo
     * @param string $idPlataforma
     * @param string $fecha
     * @return Archivo
     */
    public function ejecutar(UploadedFile $archivoSubido, $tipoArchivo, $idPlataforma, $fecha)
    {
        $entidadArchivo = new Archivo();
        $entidadArchivo->nombre = $archivoSubido->getClientOriginalName();
        $entidadArchivo->contenido = file_get_contents($archivoSubido->getRealPath());
        $entidadArchivo->id_plataforma = $idPlataforma;
        $entidadArchivo->fecha = $fecha;
        $entidadArchivo->tipo_archivo = $tipoArchivo;
        $entidadArchivo->save();

        return $entidadArchivo;
    }
}
