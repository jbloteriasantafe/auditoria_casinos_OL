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
        return view('aadd::importaciones', [
            'mensaje' => null            // Sin mensajes al cargar la página por primera vez
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
        // 1. Validar que vengan arrays de archivos y plataformas
        $this->validate($request, [
            'archivos' => 'required|array',
            'archivos.*' => 'file|max:10240',
            'plataformas' => 'required|array',
        ]);

        $archivos = $request->file('archivos');
        $plataformas = $request->input('plataformas');
        
        $resultados = [];
        $destinationPath = base_path('aadd/Storage/Imports');

        foreach ($archivos as $index => $file) {
            $nombreOriginal = $file->getClientOriginalName();
            $plataforma = isset($plataformas[$index]) ? $plataformas[$index] : 'Desconocida';
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Validar extensión
            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearError(
                    $nombreOriginal, 
                    $plataforma, 
                    'Extensión inválida. Solo csv, xlsx, xls.'
                );
                continue;
            }

            try {
                // Generar un nombre único para el archivo
                $fileName = time() . '_' . $plataforma . '_' . $nombreOriginal;
                
                // Mover físicamente el archivo
                $file->move($destinationPath, $fileName);
                $path = 'aadd/storage/imports/' . $fileName;

                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearExito(
                    $nombreOriginal, 
                    $plataforma, 
                    $path,
                    'Guardado con éxito'
                );
            } catch (\Exception $e) {
                $resultados[] = \AADD\Dto\ArchivoImportadoDTO::crearError(
                    $nombreOriginal, 
                    $plataforma, 
                    'Error al guardar en disco: ' . $e->getMessage()
                );
            }
        }

        if ($request->ajax()) {
            return response()->json($resultados);
        }

        // Fallback en caso de que no sea AJAX
        return view('aadd::importaciones', [
            'mensaje' => 'Archivos procesados.',
            'resultados' => $resultados
        ]);
    }
}
