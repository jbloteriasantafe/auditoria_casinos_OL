<?php
namespace App\Http\Controllers\AuditoriaEventos;

use App\Http\Controllers\Controller;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditoriaEventosController extends Controller
{
    public function index()
    {
        return view('AuditoriaEventos.indexAuditoriaEventos');
    }

    public function importarEventos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'adjuntoEventos' => 'required|file|mimes:csv,txt,x-csv,application/vnd.ms-excel|max:153600',

        ], [
            'adjuntoEventos.required' => 'Debe seleccionar un archivo para importar.',
            'adjuntoEventos.file' => 'El archivo seleccionado no es válido.',
            'adjuntoEventos.mimes' => 'El archivo debe ser de tipo CSV.',
            'adjuntoEventos.max' => 'El tamaño del archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150 MB.',
        ]);

        if ($validator->fails()) {
            Log::error('Error de validación al importar eventos: ', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $adjuntoEventos = $request->file('adjuntoEventos');

        if (($handle = fopen($adjuntoEventos->getRealPath(), 'r')) === false) {
            Log::error('Error al abrir el archivo CSV: ', ['file' => $adjuntoEventos->getRealPath()]);
            return response()->json([
                'success' => false,
                'errors' => ['adjuntoEventos' => ['No se pudo abrir el archivo.']]
            ], 422);
        }

        //primera fila
        $header = fgetcsv($handle, 0, ';');
        $data = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === count($header)) {
                $fila = array_combine($header, $row);
                $data[] = $fila;
            }
        }

        fclose($handle);

        try {
            foreach ($data as $evento) {
                $fecha_evento = Carbon::createFromFormat('d/m/Y', $evento['fecha_evento'])->format('Y-m-d');
                $fecha_finalizacion = Carbon::createFromFormat('d/m/Y', $evento['fecha_finalizacion'])->format('Y-m-d');

                $eventoDB = DB::connection('gestion_notas_mysql')
                    ->table('eventos')
                    ->where('nronota_ev', '=', $evento['nro_nota'])
                    ->where('origen', '=', $evento['origen'])
                    ->select('fecha_evento', 'fecha_finalizacion')
                    ->first();

                if (!$evento) {
                    Log::error('El evento no existe en la base de datos de gestión de notas: ', ['nro_nota' => $evento['nro_nota'], 'origen' => $evento['origen']]);
                    return response()->json([
                        'success' => false,
                        'errors' => ['adjuntoEventos' => ['El evento con Nro Nota ' . $evento['nro_nota'] . ' y Origen ' . $evento['origen'] . ' no existe en la base de datos de gestión de notas.']]
                    ], 422);
                }

                $fechaEventoDB = Carbon::parse($eventoDB->fecha_evento);
                $fechaFinalizacionDB = Carbon::parse($eventoDB->fecha_finalizacion);
                $fechaHoy = Carbon::now();
                $esValido = false;
                $esActivo = false;

                //SI LA FECHA DE HOY SE ENCUENTRA ENTRE LAS FECHAS DEL EVENTO ESTA ACTIVO
                if ($fechaHoy->between($fechaEventoDB, $fechaFinalizacionDB)) {
                    $esActivo = true;
                }

                //SI EL EVENTO ESTA ACTIVO Y SU ESTADO ES 'ACTIVO' ES VALIDO
                if ($esActivo && $evento['estado'] === 'ACTIVO') {
                    $esValido = true;
                }
                //SI EL EVENTO NO ESTA ACTIVO Y SU ESTADO ES 'FINALIZADO' ES VALIDO
                if (!$esActivo && $evento['estado'] === 'FINALIZADO') {
                    $esValido = true;
                }

                //SI HAY DIFERENCIAS ENTRE LAS FECHAS O DATOS DEL EVENTO ES INVALIDO
                if (
                    $fecha_evento !== $eventoDB->fecha_evento && $fecha_finalizacion !== $eventoDB->fecha_finalizacion
                ) {
                    $esValido = false;
                }

                DB::table('evento_publicidad_casino')->insert([
                    'nro_nota' => $evento['nro_nota'],
                    'origen' => $evento['origen'],
                    'evento' => $evento['evento'],
                    'fecha_evento' => $fecha_evento,
                    'fecha_finalizacion' => $fecha_finalizacion,
                    'estado' => $evento['estado'],
                    'url_promo' => $evento['url_promo'] ?? null,
                    'valido' => $esValido,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error('Error al procesar el archivo CSV: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'errors' => ['adjuntoEventos' => ['Error al procesar el archivo.']]
            ], 500);
        }
    }
}