<?php
namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;

class InformesTecnicosController extends Controller
{
    public function index()
    {
        $casinos = [['id_casino' => 4, 'casino' => 'CITY CENTER ONLINE'], ['id_casino' => 5, 'casino' => 'BPLAY'],];

        return view('NotasCasino.indexInformesTecnicos', compact('casinos'));
    }

    public function paginarNotas(Request $request)
    {
        //me faltaria agregar los filtros para el order by
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:5|max:50',
            'nroNota' => 'nullable|string|max:50',
            'nombreEvento' => 'nullable|string|max:1000',
            'idCasino' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            Log::info($validator->errors());
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $pagina = $request->input('page', 1);
        $porPagina = $request->input('perPage', 5);
        $nroNota = $request->input('nroNota');
        $nombreEvento = $request->input('nombreEvento');
        $origen = $request->input('idCasino');
        try {
            $query = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->join('estados', 'eventos.idestado', '=', 'estados.idestado')
                ->select(
                    'eventos.idevento',
                    'eventos.nronota_ev',
                    'eventos.evento',
                    'eventos.adjunto_pautas',
                    'eventos.adjunto_diseño',
                    'eventos.adjunto_basesycond',
                    'eventos.adjunto_inf_tecnico',
                    'eventos.fecha_evento',
                    'eventos.fecha_finalizacion',
                    'estados.estado',
                    'eventos.notas_relacionadas'
                )
                ->whereIn('eventos.idestado', [1, 9]);
            if (!$origen) {
                $query->whereIn('eventos.origen', [4, 5]);
            }

            if ($origen) {
                $query->where('eventos.origen', $origen);
            }

            if ($nroNota) {
                $query->where('eventos.nronota_ev', 'like', "%$nroNota%");
            }

            if ($nombreEvento) {
                $query->where('eventos.evento', 'like', "%$nombreEvento%");
            }

            $notasActuales = $query
                ->orderBy('eventos.idevento', 'desc')
                ->paginate($porPagina, ['*'], 'page', $pagina);

            //encrypto
            $data = collect($notasActuales->items())->map(function ($nota) {
                $nota->idevento_enc = Crypt::encryptString($nota->idevento);
                return $nota;
            });


            return response()->json([
                'current_page' => $notasActuales->currentPage(),
                'per_page' => $notasActuales->perPage(),
                'total' => $notasActuales->total(),
                'data' => $data
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function descargarArchivo($id, $tipo)
    {
        try {
            $idReal = Crypt::decryptString($id);
        } catch (Exception $e) {
            abort(404, 'ID inválido');
        }

        $validator = Validator::make([
            'id' => $idReal,
            'tipo' => $tipo
        ], [
            'id' => 'required|integer',
            'tipo' => 'required|string|in:pautas,disenio,basesycond,inf_tecnico'
        ]);

        if ($validator->fails()) {
            Log::error("Error de validación al descargar archivo: " . json_encode($validator->errors()));
            abort(404);
        }

        try {
            $nota = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $idReal)
                ->first();

            if (!$nota) {
                Log::error("No existe la nota");
                abort(404);
            }
            $rutaArchivo = null;
            $nombreArchivo = null;
            switch ($tipo) {
                case 'pautas':
                    $rutaArchivo = 'Eventos_Pautas/' . $nota->adjunto_pautas;
                    $nombreArchivo = $nota->adjunto_pautas;
                    break;
                case 'disenio':
                    $rutaArchivo = 'Eventos_Diseño/' . $nota->adjunto_diseño;
                    $nombreArchivo = $nota->adjunto_diseño;
                    break;
                case 'basesycond':
                    $rutaArchivo = 'Eventos_byc/' . $nota->adjunto_basesycond;
                    $nombreArchivo = $nota->adjunto_basesycond;
                    break;
                case 'inf_tecnico':
                    $rutaArchivo = 'Eventos_inftec/' . $nota->adjunto_inf_tecnico;
                    $nombreArchivo = $nota->adjunto_inf_tecnico;
                    break;
                default:
                    abort(404);
            }

            if (empty($nombreArchivo)) {
                Log::error("No existe el archivo: " . $nombreArchivo);
                abort(404, 'El archivo no está cargado.');
            }

            if (!Storage::disk('notas_casinos')->exists($rutaArchivo)) {
                Log::error("No existe el archivo: " . $rutaArchivo);
                abort(404);
            }

            $rutaCompleta = Storage::disk('notas_casinos')->path($rutaArchivo);
            $mime = mime_content_type($rutaCompleta);

            if ($mime === 'application/pdf') {
                return response()->file($rutaCompleta, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"'
                ]);
            } else {
                return response()->download($rutaCompleta, $nombreArchivo);
            }
        } catch (Exception $th) {
            Log::info("ha ocurrido un error" . $th->getMessage());
            abort(404, $th->getMessage());
        }
    }

    public function guardarInformeTecnico(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'adjuntoInformeTecnico' => 'required|file|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream|max:153600',
        ]);
        if ($validator->fails()) {
            Log::error("Error de validación al guardar informe técnico: " . json_encode($validator->errors()));
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $archivo = $request->file('adjuntoInformeTecnico');
        $nombreArchivo = $archivo->getClientOriginalName();
        $subCarpeta = 'Eventos_inftec';

        $rutaGuardada = Storage::disk('notas_casinos')->putFileAs(
            $subCarpeta,
            $archivo,
            $nombreArchivo
        );
        $id = $request->input('id');
        $pathGuardado = basename($rutaGuardada);
        try {
            DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->update(['adjunto_inf_tecnico' => $pathGuardado]);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error("Error al guardar el archivo: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
        }
    }

    public function previsualizarInformeTecnico($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|string'
        ]);
        if ($validator->fails()) {
            Log::error("Error de validación al previsualizar informe técnico: " . json_encode($validator->errors()));
            return response()->json(['success' => false, 'message' => 'ID inválido'], 400);
        }
        try {
            $nota = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->first();

            if (!$nota) {
                Log::error("No existe la nota");
                return response()->json(['success' => false, 'message' => 'No existe la nota.'], 404);
            }

            $lista_juegos = DB::connection('gestion_notas_mysql')
                ->table('juegos_nota')
                ->join('juego', 'juegos_nota.id_juego', '=', 'juego.id_juego')
                ->where('idnota', $id)
                ->select(
                    'juego.movil',
                    'juego.escritorio',
                    'juego.nombre_juego',
                    'juego.cod_juego'
                )
                ->get()
                ->map(function ($juego) {
                    return (object) [
                        'desktop_id' => $juego->escritorio ? $juego->cod_juego : '-',
                        'mobile_id' => $juego->movil ? $juego->cod_juego : '-',
                        'nombre_juego' => $juego->nombre_juego,
                    ];
                });

            $numero_nota = $nota->nronota_ev;
            $casino = $nota->origen;
            $duenio_plataforma = null;
            switch ($casino) {
                case 4:
                    $duenio_plataforma = 'Casinos Rosario S.A.';
                    break;
                case 5:
                    $duenio_plataforma = 'Casinos Santa Fe S.A.';
                    break;
                default:
                    $duenio_plataforma = 'DESCONOCIDO';
            }
            $texto_plataforma = null;
            switch ($casino) {
                case 4:
                    $texto_plataforma = 'City Center Online';
                    break;
                case 5:
                    $texto_plataforma = 'BPLAY';
                    break;
                default:
                    $texto_plataforma = 'DESCONOCIDO';
            }
            switch ($casino) {
                case 4:
                    $casino = 'CCOL';
                    break;
                case 5:
                    $casino = 'BPLAY';
                    break;
                default:
                    $casino = 'DESCONOCIDO';
            }
            $nombre_evento = $nota->evento;
            $fecha_nota_recep = Carbon::parse($nota->fecha_nota_recep)->format('d/m/Y');
            setlocale(LC_TIME, 'es_ES.UTF-8');
            $fecha_texto = Carbon::now()->formatLocalized('%d de %B de %Y');
            $fecha_hoy = Carbon::now()->format('d/m/Y');

            $pdf = PDF::loadView('NotasCasino.previewInfTecnico', compact(
                'juegos',
                'numero_nota',
                'casino',
                'duenio_plataforma',
                'texto_plataforma',
                'nombre_evento',
                'fecha_nota_recep',
                'fecha_texto',
                'lista_juegos',
                'fecha_hoy'
            ));
            $pdfContent = $pdf->output();
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="previsualizacion_informe_tecnico.pdf"'
            ]);

        } catch (Exception $th) {
            Log::error("Error al previsualizar el informe técnico: " . $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al previsualizar el informe técnico.'], 500);
        }
    }
    public function generarInformeTecnico($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|string'
        ]);
        if ($validator->fails()) {
            Log::error("Error de validación al previsualizar informe técnico: " . json_encode($validator->errors()));
            return response()->json(['success' => false, 'message' => 'ID inválido'], 400);
        }

        try {
            $nota = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->first();

            if (!$nota) {
                Log::error("No existe la nota");
                return response()->json(['success' => false, 'message' => 'No existe la nota.'], 404);
            }

            $lista_juegos = DB::connection('gestion_notas_mysql')
                ->table('juegos_nota')
                ->join('juego', 'juegos_nota.id_juego', '=', 'juego.id_juego')
                ->where('idnota', $id)
                ->select(
                    'juego.movil',
                    'juego.escritorio',
                    'juego.nombre_juego',
                    'juego.cod_juego'
                )
                ->get()
                ->map(function ($juego) {
                    return (object) [
                        'desktop_id' => $juego->escritorio ? $juego->cod_juego : '-',
                        'mobile_id' => $juego->movil ? $juego->cod_juego : '-',
                        'nombre_juego' => $juego->nombre_juego,
                    ];
                });

            $numero_nota = $nota->nronota_ev;
            $casino = $nota->origen;
            $duenio_plataforma = null;
            switch ($casino) {
                case 4:
                    $duenio_plataforma = 'Casinos Rosario S.A.';
                    break;
                case 5:
                    $duenio_plataforma = 'Casinos Santa Fe S.A.';
                    break;
                default:
                    $duenio_plataforma = 'DESCONOCIDO';
            }
            $texto_plataforma = null;
            switch ($casino) {
                case 4:
                    $texto_plataforma = 'City Center Online';
                    break;
                case 5:
                    $texto_plataforma = 'BPLAY';
                    break;
                default:
                    $texto_plataforma = 'DESCONOCIDO';
            }
            switch ($casino) {
                case 4:
                    $casino = 'CCOL';
                    break;
                case 5:
                    $casino = 'BPLAY';
                    break;
                default:
                    $casino = 'DESCONOCIDO';
            }
            $nombre_evento = $nota->evento;
            $fecha_nota_recep = Carbon::parse($nota->fecha_nota_recep)->format('d/m/Y');
            setlocale(LC_TIME, 'es_ES.UTF-8');
            $fecha_texto = Carbon::now()->formatLocalized('%d de %B de %Y');
            $fecha_hoy = Carbon::now()->format('d/m/Y');

            $templatePath = storage_path('app/templates/TemplateInformeTecnico.docx');

            $template = new TemplateProcessor($templatePath);

            $template->setValue('fecha_texto', $fecha_texto);
            $template->setValue('casino', $casino);
            $template->setValue('numero_nota', $numero_nota);
            $template->setValue('texto_plataforma', $texto_plataforma);
            $template->setValue('duenio_plataforma', $duenio_plataforma);
            $template->setValue('nombre_evento', $nombre_evento);
            $template->setValue('fecha_nota_recep', $fecha_nota_recep);
            $template->setValue('fecha_hoy', $fecha_hoy);

            $template->cloneRow('desktop', count($lista_juegos));
            foreach ($lista_juegos as $index => $juego) {
                $i = $index + 1;
                $template->setValue("desktop#{$i}", $juego->desktop_id);
                $template->setValue("mobile#{$i}", $juego->mobile_id);
                $template->setValue("nombre_juego#{$i}", $juego->nombre_juego);
            }

            $tempPath = storage_path('app/templates/temp_informe_' . $numero_nota . '.docx');
            $template->saveAs($tempPath);
            $fileName = 'Informe_Tecnico_' . $casino . '_' . $numero_nota . '.docx';
            $subCarpeta = 'Eventos_inftec';

            $rutaGuardada = Storage::disk('notas_casinos')->putFileAs(
                $subCarpeta,
                new HttpFile($tempPath),
                $fileName
            );

            $pathGuardado = basename($rutaGuardada);

            unlink($tempPath);

            DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->update(['adjunto_inf_tecnico' => $pathGuardado, 'idestado' => 1]);

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error("Error al generar el informe técnico: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar el informe técnico.'], 500);
        }
    }
}