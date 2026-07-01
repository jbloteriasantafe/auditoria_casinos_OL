<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AaddController extends Controller
{
    /**
     * Muestra la vista principal del submódulo AADD.
     * Esta vista contiene información general y accesos a otras secciones (como el importador).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('aadd::index');
    }

    /**
     * Muestra la pantalla específica para realizar importaciones de archivos.
     * Inicializa las variables necesarias en blanco o nulas para evitar errores en la vista.
     *
     * @return \Illuminate\View\View
     */
    public function importaciones()
    {
        $plataformas = \AADD\Enums\Plataforma::obtenerTodas();
        return view('aadd::importaciones', [
            'mensaje' => null,
            'plataformas' => $plataformas
        ]);
    }

    /**
     * Recibe la petición HTTP con los archivos subidos desde el formulario,
     * los valida, los guarda físicamente en la carpeta de imports asignando la plataforma
     * y devuelve el resultado de la operación en formato JSON usando DTOs.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function importar(Request $request)
    {
        // 1. Validar que vengan arrays de archivos, plataformas y fechas
        $this->validate($request, [
            'archivos' => 'required|array',
            'archivos.*' => 'file|max:10240',
            'plataformas' => 'required|array',
            'fechas' => 'required|array',
        ]);

        $archivos = $request->file('archivos');
        $plataformas = $request->input('plataformas');
        $fechas = $request->input('fechas');
        
        $resultados = [];
        $cargarAction = new \AADD\Actions\CargarArchivoAction();

        foreach ($archivos as $index => $file) {
            $nombreOriginal = $file->getClientOriginalName();
            $plataformaString = isset($plataformas[$index]) ? $plataformas[$index] : 'Desconocida';
            $fecha = isset($fechas[$index]) ? $fechas[$index] : date('Y-m-d');
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Validar extensión
            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearError(
                    $nombreOriginal, 
                    $plataformaString, 
                    'Extensión inválida. Solo csv, xlsx, xls.'
                );
                continue;
            }

            // Obtener el ID de la plataforma a partir de su string
            $idPlataforma = \AADD\Enums\Plataforma::obtenerIdDesdeString($plataformaString);

            if (is_null($idPlataforma)) {
                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearError(
                    $nombreOriginal, 
                    $plataformaString, 
                    'Plataforma no válida.'
                );
                continue;
            }

            try {
                // Guardar en la base de datos usando CargarArchivoAction
                $tipoArchivo = \AADD\Enums\TipoArchivo::BENEFICIO_DIARIO;
                $archivoEntidad = $cargarAction->ejecutar($file, $tipoArchivo, $idPlataforma, $fecha);

                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearExito(
                    $nombreOriginal, 
                    $plataformaString, 
                    'db_id: ' . $archivoEntidad->id, // Guardamos referencia del ID en lugar de la ruta física
                    'Guardado con éxito en BD'
                );
            } catch (\Exception $e) {
                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearError(
                    $nombreOriginal, 
                    $plataformaString, 
                    'Error al guardar en base de datos: ' . $e->getMessage()
                );
            }
        }

        if ($request->ajax()) {
            return response()->json($resultados);
        }

        // Fallback en caso de que no sea AJAX
        $plataformasEnum = \AADD\Enums\Plataforma::obtenerTodas();
        return view('aadd::importaciones', [
            'mensaje' => 'Archivos procesados.',
            'resultados' => $resultados,
            'plataformas' => $plataformasEnum
        ]);
    }
}
