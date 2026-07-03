<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

class MapeadorCitycenter implements EstrategiaMapeoArchivo
{
    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos de beneficios para CITYCENTER.
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
                throw new Exception("Tipo de archivo no soportado para la plataforma CITYCENTER: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO para CITYCENTER (formato CSV).
     *
     * @param Archivo $archivo
     * @return array
     */
    private function mapearBeneficioDiario(Archivo $archivo)
    {
        // Separar el contenido del CSV en líneas
        $lineas = explode("\n", trim($archivo->contenido));

        if (count($lineas) === 0) {
            return [];
        }

        // Obtener encabezados desde la primera línea
        $primeraLinea = array_shift($lineas);
        $encabezados = str_getcsv($primeraLinea);
        $encabezados = array_map('trim', $encabezados);

        $indices = [
            'fecha'               => array_search('FECHA', $encabezados),
            'jugadores_activos'   => array_search('JUGADORES ACTIVOS', $encabezados),
            'apuestas_realizadas' => array_search('APUESTAS REALIZADAS', $encabezados),
            'turnover_diario'     => array_search('TURNOVER DIARIO', $encabezados),
            'turnover_bonos'      => array_search('TURNOVER EN BONOS', $encabezados),
            'payout_diario'       => array_search('PAYOUT DIARIO', $encabezados),
            'payout_bonos'        => array_search('PAYOUT EN BONOS', $encabezados),
            'ggr'                 => array_search('GGR', $encabezados),
        ];

        $resultados = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }

            $fila = str_getcsv($linea);

            $valorFecha = trim($fila[$indices['fecha']] ?? '');

            // Ignorar filas donde la fecha esté vacía o sea la fila de 'TOTAL'
            if (empty($valorFecha) || strtoupper($valorFecha) === 'TOTAL') {
                continue;
            }

            // La fecha viene con zona horaria: 2026-06-10T06:00:00-03:00
            $fecha = date('Y-m-d', strtotime($valorFecha));

            $resultados[] = [
                'fecha'               => $fecha,
                'jugadores_activos'   => (int) ($fila[$indices['jugadores_activos']] ?? 0),
                'apuestas_realizadas' => (int) ($fila[$indices['apuestas_realizadas']] ?? 0),
                'turnover'            => (float) ($fila[$indices['turnover_diario']] ?? 0),
                'turnover_bonos'      => (float) ($fila[$indices['turnover_bonos']] ?? 0),
                'payout'              => (float) ($fila[$indices['payout_diario']] ?? 0),
                'payout_bonos'        => (float) ($fila[$indices['payout_bonos']] ?? 0),
                'ggr'                 => (float) ($fila[$indices['ggr']] ?? 0),
            ];
        }

        return $resultados;
    }
}
