<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

// Requerir la librería de lectura de Excel que descargamos
require_once base_path('aadd/Libs/SimpleXLSX.php');
use Shuchkin\SimpleXLSX;

class MapeadorJugadon implements EstrategiaMapeoArchivo
{
    /**
     * Mapear el contenido de un archivo a un conjunto de arreglos con los datos de beneficios para JUGADON.
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
                throw new Exception("Tipo de archivo no soportado para la plataforma JUGADON: " . $archivo->tipo_archivo);
        }
    }

    /**
     * Mapear archivo del tipo BENEFICIO_DIARIO para JUGADON.
     *
     * @param Archivo $archivo
     * @return array
     */
    private function mapearBeneficioDiario(Archivo $archivo)
    {
        // Parsear el contenido del archivo cargado en memoria (Blob)
        $xlsx = SimpleXLSX::parseData($archivo->contenido);

        if (!$xlsx) {
            throw new Exception("Error al parsear el archivo Excel: " . SimpleXLSX::parseError());
        }

        $filas = $xlsx->rows();
        
        if (count($filas) === 0) {
            return [];
        }

        // Obtener encabezados para mapear índices
        $encabezados = array_shift($filas);
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

        foreach ($filas as $fila) {
            // Ignorar filas donde la fecha esté vacía
            if (!isset($fila[$indices['fecha']]) || empty(trim($fila[$indices['fecha']]))) {
                continue;
            }

            // SimpleXLSX maneja fechas de diferentes maneras, si es un string lo toma, si es número serial, se puede formatear.
            // Para JUGADON sabemos que viene como string "YYYY-MM-DD".
            $fecha = date('Y-m-d', strtotime(trim($fila[$indices['fecha']])));

            $resultados[] = [
                'fecha'               => $fecha,
                'jugadores_activos'   => (int) $fila[$indices['jugadores_activos']],
                'apuestas_realizadas' => (int) $fila[$indices['apuestas_realizadas']],
                'turnover'            => (float) $fila[$indices['turnover_diario']],
                'turnover_bonos'      => (float) $fila[$indices['turnover_bonos']],
                'payout'              => (float) $fila[$indices['payout_diario']],
                'payout_bonos'        => (float) $fila[$indices['payout_bonos']],
                'ggr'                 => (float) $fila[$indices['ggr']],
            ];
        }

        return $resultados;
    }
}
