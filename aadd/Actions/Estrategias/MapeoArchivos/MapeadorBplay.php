<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

class MapeadorBplay implements EstrategiaMapeoArchivo
{
    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos de beneficios para BPLAY.
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
                throw new Exception("Tipo de archivo no soportado para la plataforma BPLAY: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO para BPLAY.
     *
     * @param Archivo $archivo
     * @return array
     */
    private function mapearBeneficioDiario(Archivo $archivo)
    {
        // En una implementación real, se procesaría $archivo->contenido.
        // Estructura de prueba adaptada para BPLAY.
        return [
            [
                'fecha' => $archivo->fecha,
                'jugadores_activos' => 150,
                'apuestas_realizadas' => 3000,
                'turnover' => 60000.00,
                'turnover_bonos' => 6000.00,
                'payout' => 54000.00,
                'payout_bonos' => 5000.00,
                'ggr' => 6000.00,
            ]
        ];
    }
}
