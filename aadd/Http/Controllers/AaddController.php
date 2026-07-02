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
    public function importaciones(Request $request)
    {
        $plataformas = \AADD\Enums\Plataforma::obtenerTodas();
        
        $mesSeleccionado = $request->input('mes');
        if ($mesSeleccionado && preg_match('/^\d{4}-\d{2}$/', $mesSeleccionado)) {
            $partes = explode('-', $mesSeleccionado);
            $anio = $partes[0];
            $mes = $partes[1];
        } else {
            $mes = date('m');
            $anio = date('Y');
        }
        
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $dias = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $dias[] = sprintf("%04d-%02d-%02d", $anio, $mes, $i);
        }

        $archivosMes = \AADD\Entities\Archivo::where('fecha', 'like', "$anio-$mes-%")
                                             ->select('fecha', 'id_plataforma', \DB::raw('count(*) as cantidad'))
                                             ->groupBy('fecha', 'id_plataforma')
                                             ->get();
        
        $matriz = [];
        foreach ($dias as $dia) {
            foreach ($plataformas as $plat) {
                $matriz[$dia][$plat] = 0;
            }
        }

        foreach ($archivosMes as $archivo) {
            if (isset($matriz[$archivo->fecha][$archivo->id_plataforma])) {
                $matriz[$archivo->fecha][$archivo->id_plataforma] = $archivo->cantidad;
            }
        }

        if ($request->ajax()) {
            return view('aadd::Componentes.tablaEstadoMensual', [
                'plataformas' => $plataformas,
                'dias' => $dias,
                'matriz' => $matriz,
                'mesActual' => "$anio-$mes"
            ]);
        }

        return view('aadd::importaciones', [
            'mensaje' => null,
            'plataformas' => $plataformas,
            'dias' => $dias,
            'matriz' => $matriz,
            'mesActual' => "$anio-$mes"
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

    /**
     * Obtiene la cantidad de archivos recibidos en una fecha para una plataforma.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cantidadArchivos(Request $request)
    {
        $this->validate($request, [
            'plataforma' => 'required|string',
            'fecha' => 'required|date'
        ]);

        $plataformaString = $request->input('plataforma');
        $fecha = $request->input('fecha');
        
        $idPlataforma = \AADD\Enums\Plataforma::obtenerIdDesdeString($plataformaString);
        
        if (is_null($idPlataforma)) {
            return response()->json(['error' => 'Plataforma no válida.'], 400);
        }

        $cantidad = \AADD\Entities\Archivo::where('id_plataforma', $idPlataforma)
                                          ->where('fecha', $fecha)
                                          ->count();

        return response()->json([
            'plataforma' => $plataformaString,
            'fecha' => $fecha,
            'cantidad' => $cantidad
        ]);
    }

    /**
     * Obtiene los nombres de los archivos recibidos en una fecha para una plataforma.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detallesArchivos(Request $request)
    {
        $this->validate($request, [
            'plataforma' => 'required|string',
            'fecha' => 'required|date'
        ]);

        $plataformaString = $request->input('plataforma');
        $fecha = $request->input('fecha');
        
        $idPlataforma = \AADD\Enums\Plataforma::obtenerIdDesdeString($plataformaString);
        
        if (is_null($idPlataforma)) {
            return response()->json(['error' => 'Plataforma no válida.'], 400);
        }

        $archivos = \AADD\Entities\Archivo::where('id_plataforma', $idPlataforma)
                                          ->where('fecha', $fecha)
                                          ->select('nombre', 'tipo_archivo')
                                          ->get();

        return response()->json([
            'plataforma' => $plataformaString,
            'fecha' => $fecha,
            'archivos' => $archivos
        ]);
    }

    /**
     * Muestra la vista de beneficio diario o la tabla filtrada si es AJAX.
     *
     * @return \Illuminate\View\View
     */
    public function beneficioDiario(Request $request)
    {
        $plataformas = \AADD\Enums\Plataforma::obtenerTodas();
        
        $fechaDesde = $request->input('fecha_desde', date('Y-m-01'));
        $fechaHasta = $request->input('fecha_hasta', date('Y-m-t'));
        
        $plataformaSeleccionada = $request->input('plataforma');
        if (!$plataformaSeleccionada || !in_array($plataformaSeleccionada, $plataformas)) {
            $plataformaSeleccionada = $plataformas[0] ?? null;
        }
        
        // Validar fechas
        $inicio = strtotime($fechaDesde);
        $fin = strtotime($fechaHasta);
        if ($inicio > $fin) {
            $temp = $inicio;
            $inicio = $fin;
            $fin = $temp;
        }

        // Limitar a máximo 60 días para evitar sobrecarga (opcional, pero buena práctica)
        $diferenciaDias = round(($fin - $inicio) / (60 * 60 * 24));
        if ($diferenciaDias > 60) {
            $fin = strtotime('+60 days', $inicio);
        }
        
        $dias = [];
        for ($current = $inicio; $current <= $fin; $current = strtotime('+1 day', $current)) {
            $dias[] = date('Y-m-d', $current);
        }

        if ($request->ajax()) {
            return view('aadd::Componentes.tablaBeneficioDiario', [
                'dias' => $dias,
                'plataformaSeleccionada' => $plataformaSeleccionada
            ]);
        }

        return view('aadd::beneficio-diario', [
            'plataformas' => $plataformas,
            'plataformaSeleccionada' => $plataformaSeleccionada,
            'dias' => $dias,
            'fechaDesde' => date('Y-m-d', $inicio),
            'fechaHasta' => date('Y-m-d', $fin)
        ]);
    }
}
