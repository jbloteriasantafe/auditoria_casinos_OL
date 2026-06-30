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
     * Recibe la petición HTTP con el archivo subido desde el formulario,
     * lo valida, lo guarda físicamente en la carpeta de imports y devuelve
     * el resultado de la operación a la vista.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function importar(Request $request)
    {
        // 1. Validar que el archivo exista y no exceda los 10MB
        $this->validate($request, [
            'archivo' => 'required|file|max:10240',
        ]);

        $file = $request->file('archivo');
        
        // 2. Obtener y limpiar la extensión original del archivo a minúsculas
        $extension = strtolower($file->getClientOriginalExtension());
        
        // 3. Validar manualmente la extensión permitida (evitando fallos de tipos MIME)
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            return back()->withErrors(['archivo' => 'El archivo debe ser de tipo: csv, xlsx, xls.']);
        }
        
        /* Esto luego es ir a la bd */

        // 4. Construir las rutas de destino absolutas (directamente dentro del módulo AADD)
        $destinationPath = base_path('aadd/Storage/Imports');
        
        // 5. Generar un nombre único para el archivo usando el tiempo actual (timestamp)
        $fileName = time() . '_' . $file->getClientOriginalName();
        
        // 6. Mover físicamente el archivo temporal subido hacia nuestra carpeta destino segura
        $file->move($destinationPath, $fileName);
        
        // 7. Preparar las rutas para mostrar en el mensaje de éxito o usarlas a futuro
        $path = 'aadd/storage/imports/' . $fileName;
        $fullPath = $destinationPath . '/' . $fileName;
    
        $mensaje = '';

        // 8. Verificar que la instancia del archivo sigue existiendo y setear el mensaje de estado
        if ($file) {
            $mensaje = 'Archivo subido y guardado correctamente en: ' . $path . '.';
        } else {
            $mensaje = 'No se pudo leer el archivo';
        }

        // 9. Retornar a la vista de importaciones con los mensajes resultantes
        return view('aadd::importaciones', [
            'mensaje' => $mensaje
        ]);
    }
}
