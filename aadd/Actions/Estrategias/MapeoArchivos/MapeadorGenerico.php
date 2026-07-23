<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use AADD\Utils\LectorArchivos;
use Exception;

class MapeadorGenerico implements EstrategiaMapeoArchivo
{
    /**
     * @var array Configuración base para leer beneficios diarios.
     */
    protected $configuracionBeneficioDiario;

    public function __construct()
    {
        $transformacionEntera = function ($v) {
            return (int) ($v ?? 0);
        };

        $transformacionFlotante = function ($v) {
            return (float) ($v ?? 0);
        };

        $this->configuracionBeneficioDiario = [
            'fecha' => [
                'transformacion' => function ($v) {
                    if (!$v)
                        return null;
                    if (strtoupper(trim($v)) === 'TOTAL')
                        return 'TOTAL';
                    return date('Y-m-d', strtotime($v));
                }
            ],
            'jugadores_activos' => [
                'alias' => ['JUGADORES ACTIVOS'],
                'transformacion' => $transformacionEntera
            ],
            'apuestas_realizadas' => [
                'alias' => ['APUESTAS REALIZADAS'],
                'transformacion' => $transformacionEntera
            ],
            'turnover' => [
                'alias' => ['TURNOVER DIARIO'],
                'transformacion' => $transformacionFlotante
            ],
            'turnover_bonos' => [
                'alias' => ['TURNOVER EN BONOS'],
                'transformacion' => $transformacionFlotante
            ],
            'payout' => [
                'alias' => ['PAYOUT DIARIO'],
                'transformacion' => $transformacionFlotante
            ],
            'payout_bonos' => [
                'alias' => ['PAYOUT EN BONOS'],
                'transformacion' => $transformacionFlotante
            ],
            'ggr' => [
                'transformacion' => $transformacionFlotante
            ],
        ];
    }

    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos listos.
     *
     * @param Archivo $archivo
     * @return array
     * @throws Exception
     */
    public function mapear(Archivo $archivo)
    {
        switch ($archivo->tipo_archivo) {
            case TipoArchivo::BENEFICIO_DIARIO:
                return $this->mapearBeneficioDiario($archivo);
            default:
                throw new Exception("Tipo de archivo no soportado: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO.
     *
     * @param Archivo $archivo
     * @return array
     */
    protected function mapearBeneficioDiario(Archivo $archivo)
    {
        $registros = [];

        foreach (LectorArchivos::parsear($archivo, $this->configuracionBeneficioDiario) as $fila) {
            if (empty($fila['fecha'])) {
                continue; // Ignorar fila sin fecha (o vacía)
            }

            if ($fila['fecha'] === 'TOTAL') {
                $fila['fecha'] = $archivo->fecha;
                $registros[] = [
                    'etiqueta' => 'mensual',
                    'datos' => $fila
                ];
            } else {
                $ggrCalculado = ($fila['turnover'] ?? 0.0) - ($fila['payout'] ?? 0.0);
                $diferencia = round($ggrCalculado - ($fila['ggr'] ?? 0.0), 2);
                if ($diferencia == -0) {
                    $diferencia = 0.0;
                }
                $fila['ggr_calculado'] = $ggrCalculado;
                $fila['diferencia'] = $diferencia;

                $registros[] = [
                    'etiqueta' => 'diario',
                    'datos' => $fila
                ];
            }
        }

        return $registros;
    }
}
