<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

class MapeadorBet365 implements EstrategiaMapeoArchivo
{
    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos de beneficios para BET365.
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
                throw new Exception("Tipo de archivo no soportado para la plataforma BET365: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO para BET365.
     *
     * @param Archivo $archivo
     * @return array
     */
    private function mapearBeneficioDiario(Archivo $archivo)
    {
        // En una implementación real, se procesaría $archivo->contenido.
        // Estructura de prueba adaptada para BET365.
        return [
            [
                'fecha' => $archivo->fecha,
                'jugadores_activos' => 180,
                'apuestas_realizadas' => 4000,
                'turnover' => 80000.00,
                'turnover_bonos' => 8000.00,
                'payout' => 72000.00,
                'payout_bonos' => 6000.00,
                'ggr' => 8000.00,
            ]
        ];
    }
}
