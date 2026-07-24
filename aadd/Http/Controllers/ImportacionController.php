<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use AADD\Enums\Plataforma;
use AADD\Enums\TipoArchivo;
use AADD\Entities\Archivo;
use AADD\Actions\CargarArchivoAction;
use AADD\Actions\ProcesarArchivoAction;
use AADD\Dto\ArchivoImportadoDTO;
use Throwable;

class ImportacionController extends Controller
{

    private function construirMatrizArchivos($plataformas, $dias, $archivosMes)
    {
        $matrizArchivos = [];
        foreach ($dias as $dia) {
            foreach ($plataformas as $plat) {
                $matrizArchivos[$dia][$plat] = [];
            }
        }

        foreach ($archivosMes as $archivo) {
            if (isset($matrizArchivos[$archivo->fecha][$archivo->id_plataforma])) {
                $matrizArchivos[$archivo->fecha][$archivo->id_plataforma][] = [
                    'id' => $archivo->id,
                    'nombre' => $archivo->nombre,
                    'tipo_archivo' => $archivo->tipo_archivo,
                    'eliminado' => !is_null($archivo->deleted_at),
                    'fecha_eliminacion' => $archivo->deleted_at ? $archivo->deleted_at->format('d/m/Y H:i') : null
                ];
            }
        }

        return $matrizArchivos;
    }

    /**
     * Muestra la pantalla específica para realizar importaciones de archivos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $plataformas = Plataforma::values();

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

        $archivosMes = Archivo::withTrashed()->where('fecha', 'like', "$anio-$mes-%")
            ->select('id', 'fecha', 'id_plataforma', 'nombre', 'tipo_archivo', 'deleted_at')
            ->get();

        $matrizArchivos = $this->construirMatrizArchivos($plataformas, $dias, $archivosMes);

        return view('aadd::importaciones', [
            'mensaje' => null,
            'plataformas' => $plataformas,
            'dias' => $dias,
            'matriz' => $matrizArchivos,
            'mesActual' => "$anio-$mes"
        ]);
    }

    /**
     * Devuelve la tabla de estado mensual mediante una petición AJAX.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function tabla(Request $request)
    {
        $plataformas = Plataforma::values();

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

        // Consultamos todos los archivos del mes seleccionando solo las columnas necesarias
        $archivosMes = Archivo::withTrashed()->where('fecha', 'like', "$anio-$mes-%")
            ->select('id', 'fecha', 'id_plataforma', 'nombre', 'tipo_archivo', 'deleted_at')
            ->get();

        $matrizArchivos = $this->construirMatrizArchivos($plataformas, $dias, $archivosMes);

        return view('aadd::Componentes.tablaEstadoMensual', [
            'plataformas' => $plataformas,
            'dias' => $dias,
            'matriz' => $matrizArchivos,
            'mesActual' => "$anio-$mes"
        ]);
    }

    /**
     * Recibe la petición HTTP con los archivos subidos desde el formulario,
     * los valida, los guarda físicamente en la carpeta de imports asignando la plataforma
     * y devuelve el resultado de la operación en formato JSON usando DTOs.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importar(Request $request)
    {
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
        $cargarAction = new CargarArchivoAction();

        foreach ($archivos as $index => $file) {
            $nombreOriginal = $file->getClientOriginalName();
            $plataformaString = isset($plataformas[$index]) ? $plataformas[$index] : 'Desconocida';
            $fecha = isset($fechas[$index]) ? $fechas[$index] : date('Y-m-d');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                $resultados[] = ArchivoImportadoDTO::crearError(
                    $nombreOriginal,
                    $plataformaString,
                    'Extensión inválida. Solo csv, xlsx, xls.'
                );
                continue;
            }

            $idPlataforma = Plataforma::obtenerIdDesdeString($plataformaString);

            if (is_null($idPlataforma)) {
                $resultados[] = ArchivoImportadoDTO::crearError(
                    $nombreOriginal,
                    $plataformaString,
                    'Plataforma no válida.'
                );
                continue;
            }

            try {
                $tipoArchivo = TipoArchivo::BENEFICIO_DIARIO;
                $archivoEntidad = $cargarAction->ejecutar($file, $tipoArchivo, $idPlataforma, $fecha);

                $procesarAction = new ProcesarArchivoAction();
                $procesarAction->ejecutar($archivoEntidad);

                $resultados[] = ArchivoImportadoDTO::crearExito(
                    $nombreOriginal,
                    $plataformaString,
                    'db_id: ' . $archivoEntidad->id,
                    'Guardado'
                );
            } catch (Throwable $e) {
                Log::error("Error procesando archivo: " . $e->getMessage(), ['exception' => $e]);
                $resultados[] = ArchivoImportadoDTO::crearError(
                    $nombreOriginal,
                    $plataformaString,
                    'Error al guardar: ' . $e->getMessage()
                );
            }
        }

        return response()->json($resultados);
    }

    /**
     * Elimina un archivo cargado y revierte/elimina sus beneficios diarios y mensuales.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function eliminar($id)
    {
        try {
            DB::connection('aadd')->transaction(function () use ($id) {
                // Eliminar beneficio diario asociado
                \AADD\Entities\BeneficioDiario::where('id_archivo', $id)->delete();

                // Eliminar beneficio mensual asociado
                \AADD\Entities\BeneficioMensual::where('id_archivo', $id)->delete();

                // Eliminar el archivo
                Archivo::destroy($id);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Archivo y datos asociados eliminados correctamente.'
            ]);
        } catch (Throwable $e) {
            Log::error("Error eliminando archivo: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
