<?php

namespace AADD\Utils;

use AADD\Entities\Archivo;
use Exception;

// Requerir la librería de lectura de Excel local
require_once base_path('aadd/Libs/SimpleXLSX.php');
use Shuchkin\SimpleXLSX;

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
            return strtoupper(trim($h));
        }, $encabezados);

        yield from self::extraerDatos($filas, $encabezados, $configuracion);
    }

    private static function parsearCsv(Archivo $archivo, array $configuracion): \Generator
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $archivo->contenido);
        rewind($stream);

        $primeraLinea = fgetcsv($stream);
        if (!$primeraLinea) {
            fclose($stream);
            return;
        }

        $encabezados = array_map(function($h) {
            return strtoupper(trim($h));
        }, $primeraLinea);

        $filas = [];
        while (($fila = fgetcsv($stream)) !== false) {
            // Ignorar líneas vacías o de estructura nula
            if (empty($fila) || (count($fila) === 1 && $fila[0] === null)) {
                continue;
            }
            $filas[] = $fila;
        }
        fclose($stream);

        yield from self::extraerDatos($filas, $encabezados, $configuracion);
    }

    private static function extraerDatos(array $filas, array $encabezados, array $configuracion): \Generator
    {
        // 1. Mapear índices
        $indices = [];
        foreach ($configuracion as $clave => $configCol) {
            $alias = $configCol['alias'] ?? [];

            $nombresPosibles = array_merge([$clave], $alias);
            $nombresPosibles = array_map(function($n) { return strtoupper(trim($n)); }, $nombresPosibles);

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
