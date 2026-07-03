<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Entities\Archivo;
use AADD\Enums\TipoArchivo;
use Exception;

// Requerir la librería de lectura de Excel
require_once base_path('aadd/Libs/SimpleXLSX.php');
use Shuchkin\SimpleXLSX;

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
        $encabezados = array_map(function($h) {
            return strtoupper(trim($h));
        }, $encabezados);

        $indices = [
            'fecha'               => array_search('FECHA', $encabezados),
            'jugadores_activos'   => array_search('JUGADORES ACTIVOS', $encabezados),
            'turnover_efectivo'   => array_search('APUESTAS REALIZADAS EFECTIVO', $encabezados),
            'payout_efectivo'     => array_search('PREMIOS PAGADOS EFECTIVO', $encabezados),
            'apuestas_bonos'      => array_search('APUESTAS BONOS', $encabezados),
            'premios_bonos'       => array_search('PREMIOS BONOS', $encabezados),
            'ggr_efectivo'        => array_search('GGR EFECTIVO', $encabezados),
        ];

        $resultados = [];

        foreach ($filas as $fila) {
            // Ignorar filas donde la fecha esté vacía
            if (!isset($fila[$indices['fecha']]) || empty(trim($fila[$indices['fecha']]))) {
                continue;
            }

            // SimpleXLSX para BPLAY: "2026-06-10 00:00:00"
            $fecha = date('Y-m-d', strtotime(trim($fila[$indices['fecha']])));

            $resultados[] = [
                'fecha'               => $fecha,
                'jugadores_activos'   => (int) ($fila[$indices['jugadores_activos']] ?? 0),
                'apuestas_realizadas' => 0, // BPLAY no envía la cantidad de apuestas
                'turnover'            => (float) ($fila[$indices['turnover_efectivo']] ?? 0),
                'turnover_bonos'      => (float) ($fila[$indices['apuestas_bonos']] ?? 0),
                'payout'              => (float) ($fila[$indices['payout_efectivo']] ?? 0),
                'payout_bonos'        => (float) ($fila[$indices['premios_bonos']] ?? 0),
                'ggr'                 => (float) ($fila[$indices['ggr_efectivo']] ?? 0),
            ];
        }

        return $resultados;
    }
}
