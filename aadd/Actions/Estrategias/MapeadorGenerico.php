<?php

namespace AADD\Actions\Estrategias;

use AADD\Enums\TipoArchivo;

class MapeadorGenerico implements EstrategiaMapeo
{
    /**
     * Tipo de archivo (diario o mensual).
     *
     * @var string
     */
    private $tipoArchivo;

    /**
     * Constructor del mapeador genérico.
     *
     * @param string $tipoArchivo
     */
    public function __construct($tipoArchivo)
    {
        $this->tipoArchivo = $tipoArchivo;
    }

    /**
     * Mapear el contenido binario del archivo a los datos de beneficio (Estructura de prueba genérica).
     *
     * @param string $contenidoBinario
     * @return array
     */
    public function mapear($contenidoBinario)
    {
        // En una implementación real, aquí se procesaría el string binario $contenidoBinario
        // (por ejemplo, usando streams en memoria para leer CSV/XLSX) y se mapearía
        // según el tipo de archivo (diario o mensual) y la plataforma correspondiente.
        if ($this->tipoArchivo === TipoArchivo::DIARIO) {
            return [
                [
                    'fecha' => date('Y-m-d H:i:s'),
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

        // Tipo mensual (primer segundo del mes)
        return [
            [
                'fecha' => date('Y-m-01 00:00:00'),
                'jugadores_activos' => 3000,
                'apuestas_realizadas' => 60000,
                'turnover' => 1200000.00,
                'turnover_bonos' => 120000.00,
                'payout' => 1080000.00,
                'payout_bonos' => 90000.00,
                'ggr' => 120000.00,
            ]
        ];
    }
}
