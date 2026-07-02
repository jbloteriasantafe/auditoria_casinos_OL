<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

class MapeadorGenerico implements EstrategiaMapeoArchivo
{
    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos de beneficios de forma genérica.
     *
     * @param Archivo $archivo Instancia del archivo cargado en base de datos.
     * @return array Arreglo de arreglos con los datos listos para crear entidades.
     * @throws Exception
     */
    public function mapear(Archivo $archivo)
    {
        switch ($archivo->tipo_archivo) {
            case TipoArchivo::BENEFICIO_DIARIO:
                return $this->mapearBeneficioDiario($archivo);
            default:
                throw new Exception("Tipo de archivo no soportado para el mapeador genérico: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO de forma genérica.
     *
     * @param Archivo $archivo
     * @return array
     */
    public function mapearBeneficioDiario(Archivo $archivo)
    {
        // Estructura de prueba genérica.
        return [
            [
                'fecha' => $archivo->fecha,
                'jugadores_activos' => 100,
                'apuestas_realizadas' => 2000,
                'turnover' => 40000.00,
                'turnover_bonos' => 4000.00,
                'payout' => 36000.00,
                'payout_bonos' => 3000.00,
                'ggr' => 4000.00,
            ]
        ];
    }
}
