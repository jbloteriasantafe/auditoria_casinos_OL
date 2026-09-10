<?php

namespace AADD\Utils;

use AADD\Entities\Archivo;
use Exception;

// Requerir la librería de lectura de Excel local
require_once base_path('aadd/Libs/SimpleXLSX.php');
use Shuchkin\SimpleXLSX;

use Illuminate\Support\Facades\Log;

class LectorArchivos
{
    /**
     * Parsea un archivo (CSV o XLSX) y retorna los datos mapeados según la configuración.
     *
     * @param Archivo $archivo
     * @param array $configuracion Un array donde la llave es la 'clave', y el valor es un array con 'alias' (opcional), 'transformacion' (opcional).
     * @return \Generator
     * @throws Exception
     */
    public static function parsear(Archivo $archivo, array $configuracion): \Generator
    {
        $extension = strtolower(pathinfo($archivo->nombre, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            yield from self::parsearXlsx($archivo, $configuracion);
        } else {
            // Asumimos CSV por defecto
            yield from self::parsearCsv($archivo, $configuracion);
        }
    }

    private static function parsearXlsx(Archivo $archivo, array $configuracion): \Generator
    {
        $xlsx = SimpleXLSX::parseData($archivo->contenido);

        if (!$xlsx) {
            throw new Exception("Error al parsear el archivo Excel: " . SimpleXLSX::parseError());
        }

        $filas = $xlsx->rows();
        
        if (count($filas) === 0) {
            return; // Termina el generador vacio
        }

        $encabezados = array_shift($filas);
        $encabezados = array_map(function($h) {
            return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $h)));
        }, $encabezados);

        yield from self::extraerDatos($filas, $encabezados, $configuracion);
    }

    private static function parsearCsv(Archivo $archivo, array $configuracion): \Generator
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $archivo->contenido);
        rewind($stream);

        // Detectar automáticamente el delimitador del CSV validando la consistencia en todas las filas
        $delimitador = self::detectarDelimitador($stream, $archivo);
        rewind($stream);

        $primeraLinea = fgetcsv($stream, 0, $delimitador);
        if (!$primeraLinea) {
            fclose($stream);
            return;
        }

        // Limpiar BOM UTF-8 del primer encabezado si existe
        if (isset($primeraLinea[0])) {
            $primeraLinea[0] = preg_replace('/^\xEF\xBB\xBF/', '', $primeraLinea[0]);
        }

        $encabezados = array_map(function($h) {
            return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $h)));
        }, $primeraLinea);

        $filas = [];
        while (($fila = fgetcsv($stream, 0, $delimitador)) !== false) {
            // Ignorar líneas vacías o de estructura nula
            if (empty($fila) || (count($fila) === 1 && $fila[0] === null)) {
                continue;
            }
            $filas[] = $fila;
        }
        fclose($stream);

        yield from self::extraerDatos($filas, $encabezados, $configuracion);
    }

    /**
     * Detecta el delimitador de un stream CSV analizando que todas las filas tengan
     * la misma cantidad consistente de columnas (> 1).
     *
     * @param resource $stream
     * @param Archivo|null $archivo
     * @return string
     */
    private static function detectarDelimitador($stream, Archivo $archivo = null): string
    {
        $posInicial = ftell($stream);
        rewind($stream);

        // Leer todas las líneas no vacías para validar la consistencia
        $lineas = [];
        while (($linea = fgets($stream)) !== false) {
            $lineaLimpia = trim($linea);
            if ($lineaLimpia !== '') {
                $lineas[] = $linea;
            }
        }
        fseek($stream, $posInicial);

        if (empty($lineas)) {
            return ',';
        }

        // Limpiar BOM UTF-8 de la primera línea si existe
        $lineas[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lineas[0]);

        $delimitadores = [';', ',', "\t", '|'];
        $mejorDelimitador = null;
        $maxColumnas = 1;

        foreach ($delimitadores as $delim) {
            $columnasEncabezado = count(str_getcsv($lineas[0], $delim));

            if ($columnasEncabezado <= 1) {
                continue;
            }

            // Validar que todas las filas tengan exactamente la misma cantidad de columnas
            $esConsistente = true;
            foreach ($lineas as $linea) {
                $columnasFila = count(str_getcsv($linea, $delim));
                if ($columnasFila !== $columnasEncabezado) {
                    $esConsistente = false;
                    break;
                }
            }

            if ($esConsistente && $columnasEncabezado > $maxColumnas) {
                $maxColumnas = $columnasEncabezado;
                $mejorDelimitador = $delim;
            }
        }

        if ($mejorDelimitador === null) {
            $nombreArchivo = $archivo ? $archivo->nombre : 'desconocido';
            Log::warning("AADD LectorArchivos: No se encontró un delimitador válido con más de una columna consistente por fila para el archivo '{$nombreArchivo}'.");
            return ',';
        }

        return $mejorDelimitador;
    }

    private static function extraerDatos(array $filas, array $encabezados, array $configuracion): \Generator
    {
        // 1. Mapear índices
        $indices = [];
        foreach ($configuracion as $clave => $configCol) {
            $alias = $configCol['alias'] ?? [];

            $nombresPosibles = array_merge([$clave], $alias);
            $nombresPosibles = array_map(function($n) {
                return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $n)));
            }, $nombresPosibles);

            $indiceEncontrado = false;
            foreach ($nombresPosibles as $nombreBuscado) {
                $idx = array_search($nombreBuscado, $encabezados);
                if ($idx !== false) {
                    $indices[$clave] = $idx;
                    $indiceEncontrado = true;
                    break;
                }
            }

            if (!$indiceEncontrado) {
                $indices[$clave] = false;
            }
        }

        // 2. Extraer filas
        foreach ($filas as $fila) {
            $filaResultado = [];
            foreach ($configuracion as $clave => $configCol) {
                $idx = $indices[$clave];
                
                $valor = ($idx !== false && isset($fila[$idx])) ? trim($fila[$idx]) : null;

                if (isset($configCol['transformacion']) && is_callable($configCol['transformacion'])) {
                    $valor = call_user_func($configCol['transformacion'], $valor);
                }
                
                $filaResultado[$clave] = $valor;
            }
            yield $filaResultado;
        }
    }
}
