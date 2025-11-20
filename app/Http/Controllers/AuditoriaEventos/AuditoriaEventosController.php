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
        $fechas = DB::table('evento_publicidad_casino')
            ->select(DB::raw('DATE(created_at) as fecha_creacion'))
            ->distinct()
            ->orderByDesc('fecha_creacion')
            ->get();

        $fechas_array = $fechas->map(function ($item) {
            return $item->fecha_creacion;
        })->toArray();
        $casinos = [
            ['id' => '4', 'nombre' => 'CCOL'],
            ['id' => '5', 'nombre' => 'BPLAY'],
        ];

        return view('AuditoriaEventos.indexAuditoriaEventos', compact('casinos', 'fechas_array'));
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
        //HAGO ENCODING A UTF-8 PARA QUE SE ACEPTEN CARACTERES ESPECIALES
        $path = $adjuntoEventos->getRealPath();
        $content = file_get_contents($path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);

        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($path, $content);
        }

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

                if (!$eventoDB) {
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

    public function paginarEventos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:5|max:50',
            'casino' => 'nullable|integer',
            'fechaCarga' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            Log::error('Error de validación al paginar eventos: ', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 5);
        $casino = $request->input('casino', null);
        $fecha = $request->input('fechaCarga', null);

        try {
            $query = DB::table('evento_publicidad_casino')
                ->select(
                    'nro_nota',
                    'origen as casino_origen',
                    'evento as nombre_evento',
                    DB::raw("DATE_FORMAT(fecha_evento, '%d/%m/%Y') as fecha_inicio_evento"),
                    DB::raw("DATE_FORMAT(fecha_finalizacion, '%d/%m/%Y') as fecha_finalizacion_evento"),
                    'estado',
                    'url_promo',
                    'valido',
                    DB::raw("DATE_FORMAT(created_at, '%d/%m/%Y') as fecha_carga")
                );

            if ($casino) {
                $query->where('origen', '=', $casino);
            }

            if ($fecha) {
                $query->whereDate('created_at', '=', $fecha);
            }
            $query->orderBy('created_at', 'desc');

            $resultados = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'current_page' => $resultados->currentPage(),
                'per_page' => $resultados->perPage(),
                'total' => $resultados->total(),
                'data' => $resultados
            ]);
        } catch (Exception $e) {
            Log::error('Error al paginar eventos: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'errors' => ['pagination' => ['Error al paginar los eventos.']]
            ], 500);
        }
    }

    public function fechasCarga()
    {
        try {
            $fechas = DB::table('evento_publicidad_casino')
                ->select(DB::raw('DATE(created_at) as fecha_creacion'))
                ->distinct()
                ->orderByDesc('fecha_creacion')
                ->get()
                ->map(function ($item) {
                    return [
                        'fecha' => $item->fecha_creacion,
                        'formateada' => Carbon::parse($item->fecha_creacion)->format('d/m/Y'),
                    ];
                });

            return response()->json(['fechas' => $fechas]);
        } catch (Exception $e) {
            Log::error('Error al obtener las fechas de carga: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'errors' => ['fechasCarga' => ['Error al obtener las fechas de carga.']]
            ], 500);
        }
    }
}