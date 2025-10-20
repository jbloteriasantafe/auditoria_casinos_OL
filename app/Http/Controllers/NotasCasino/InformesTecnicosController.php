<?php
namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;

class InformesTecnicosController extends Controller
{
    public function index()
    {   
        $casinos = [['id_casino' => 4, 'casino' => 'CITY CENTER ONLINE'], ['id_casino' => 5, 'casino' => 'BPLAY'],];

        return view('NotasCasino.indexInformesTecnicos',compact('casinos'));
    }

    public function paginarNotas (Request $request){
        //me faltaria agregar los filtros para el order by
        $validator = Validator::make($request->all(),[
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:5|max:50',
            'nroNota' => 'nullable|string|max:50',
            'nombreEvento' => 'nullable|string|max:1000',
            'idCasino' => 'nullable|integer',
        ]);
        if($validator->fails()){
            Log::info($validator->errors());
            return response()->json(['success' => false, 'error' => $validator->errors()],400);
        }
        
        $pagina = $request->input('page',1);
        $porPagina = $request->input('perPage',5);
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
            ->whereIn('eventos.idestado',[1,9]);
            if(!$origen){
                $query->whereIn('eventos.origen', [4, 5]);
            }
            
            if($origen){
                $query->where('eventos.origen', $origen);
            }

            if($nroNota) {
                $query->where('eventos.nronota_ev', 'like', "%$nroNota%");
            }

            if($nombreEvento) {
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
            return response()->json(['success' => false, 'error' => $e->getMessage()],500);
        }
    }

    public function descargarArchivo ($id,$tipo){
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
                    $rutaArchivo = 'Eventos_Pautas/'.$nota->adjunto_pautas;
                    $nombreArchivo = $nota->adjunto_pautas;
                    break;
                case 'disenio':
                    $rutaArchivo = 'Eventos_Diseño/'.$nota->adjunto_diseño;
                    $nombreArchivo = $nota->adjunto_diseño;
                    break;
                case 'basesycond':
                    $rutaArchivo =  'Eventos_byc/'.$nota->adjunto_basesycond;
                    $nombreArchivo = $nota->adjunto_basesycond;
                    break;
                case 'inf_tecnico':
                    $rutaArchivo = 'Eventos_inftec/'.$nota->adjunto_inf_tecnico;
                    $nombreArchivo = $nota->adjunto_inf_tecnico;
                    break;
                default:
                    abort(404);
            }

            if (empty($nombreArchivo)) {
                Log::error("No existe el archivo: " . $nombreArchivo);
                abort(404, 'El archivo no está cargado.');
            }

            if(!Storage::disk('notas_casinos')->exists($rutaArchivo)) {
                Log::error("No existe el archivo: " . $rutaArchivo);
                abort(404);
            }

            $rutaCompleta = Storage::disk('notas_casinos')->path($rutaArchivo);
            $mime = mime_content_type($rutaCompleta);
        
            if ($mime === 'application/pdf') {
                return response()->file($rutaCompleta, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$nombreArchivo.'"'
                ]);
            } else {
                return response()->download($rutaCompleta, $nombreArchivo);
            } 
        } catch (Exception $th) {
            Log::info("ha ocurrido un error".$th->getMessage());
            abort(404,$th->getMessage());
        }
    }

    public function guardarInformeTecnico (Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'adjuntoInformeTecnico' => 'required|file|mimes:pdf,doc,docx,zip|max:153600'
        ]);
        if($validator->fails()){
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
        try{
            DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->update(['adjunto_inf_tecnico' => $pathGuardado]);
            return response()->json(['success' => true]);
        }catch (Exception $e){
            Log::error("Error al guardar el archivo: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
        }
    }

    public function previsualizarInformeTecnico ($id){
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'ID inválido'], 400);
        }

        try {
            $nota = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $id)
                ->first();

            if (!$nota) {
                return response()->json(['success' => false, 'message' => 'No existe la nota.'], 404);
            }

            $datos_de_prueba = [
                // --- Datos de la Nota ---
                'fecha_texto' => '20 de octubre de 2025', // Simula Carbon::now()->isoFormat(...)
                'casino' => 'CAS-ROS',
                'numero_nota' => 'E-2025-12345-SFE',
                'texto_plataforma' => 'Plataforma "El Gran Juego"',
                'duenio_plataforma' => 'Empresa Operadora S.A.',
                'fecha_nota_recep' => '15/10/2025', // Simula Carbon::parse(...)->format(...)
                'nombre_evento' => 'Torneo Semanal de Slots "Primavera Dorada"',

                // --- Lista de Juegos (para el @foreach) ---
                'lista_juegos' => [
                    // Juego 1
                    (object) [ // Usamos (object) para simular la stdClass de la DB
                        'desktop_id' => 'BG-SL-001A',
                        'mobile_id' => 'BG-SL-001M',
                        'nombre_juego' => 'Gates of Olympus',
                        'porcentaje_dev' => '96.50%',
                    ],
                    // Juego 2
                    (object) [
                        'desktop_id' => 'PR-SL-002D',
                        'mobile_id' => 'PR-SL-002M',
                        'nombre_juego' => 'Sweet Bonanza',
                        'porcentaje_dev' => '96.48%',
                    ],
                    // Juego 3
                    (object) [
                        'desktop_id' => 'EV-RG-005D',
                        'mobile_id' => 'EV-RG-005M',
                        'nombre_juego' => 'Lightning Roulette',
                        'porcentaje_dev' => '97.30%',
                    ],
                    // Juego 4 (con un dato faltante)
                    (object) [
                        'desktop_id' => 'NG-SL-010D',
                        'mobile_id' => null, // Para probar el ?? 'N/A'
                        'nombre_juego' => 'Book of Ra Deluxe',
                        'porcentaje_dev' => '95.10%',
                    ],
                ],
                
                // --- Datos para la Opción A (un solo juego) ---
                // (Solo por si decides no usar el @foreach)
                'desktop' => 'BG-SL-001A',
                'mobile' => 'BG-SL-001M',
                'nombre_juego' => 'Gates of Olympus',
                'porcentaje_devolucion' => '96.50%',
            ];
            //$pdf = Pdf::loadView('NotasCasino.plantillaInformeTecnico', $datos_de_prueba);


        } catch (Exception $th) {
            Log::error("Error al previsualizar el informe técnico: " . $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al previsualizar el informe técnico.'], 500);
        }
    }
    public function generarInformeTecnico (Request $request){
        $templatePath = storage_path('app/templates/TemplateInformeTecnico.docx');
        $template = new TemplateProcessor($templatePath);

        $juegos = [
        [
            'desktop' => 'ea4-10',
            'mobile' => 'ea4-10',
            'nombre_juego' => 'Clasi. Voucher WSOP 250 USD - Evento en Vivo',
            'porcentaje_devolucion' => '97.50%'
        ],
        [
            'desktop' => '584-10',
            'mobile' => '584-10',
            'nombre_juego' => 'Clasi. Freeroll WSOP Fase 1/3 - Evento en Vivo',
            'porcentaje_devolucion' => '95.00%'
        ],
        [
            'desktop' => '58c-10',
            'mobile' => '58c-10',
            'nombre_juego' => 'Clasi. Voucher WSOP Fase 2/3 - Evento en Vivo',
            'porcentaje_devolucion' => '96.00%'
        ]
    ];


        $template->setValue('fecha_texto', '20 de octubre de 2025');
        $template->setValue('casino','CCOL');
        $template->setValue('numero_nota','PK 124/25');
        $template->setValue('texto_plataforma','City Center Online');
        $template->setValue('duenio_plataforma','Casinos Rosario S.A. ');
        $template->setValue('nombre_evento','Evento del juego online');
        $template->setValue('fecha_nota_recep','20/10/2025');

        $template->cloneRow('desktop', count($juegos));
        foreach ($juegos as $index => $juego) {
            $i = $index + 1;

            $template->setValue("desktop#{$i}", $juego['desktop']);
            $template->setValue("mobile#{$i}", $juego['mobile']);
            $template->setValue("nombre_juego#{$i}", $juego['nombre_juego']);
            $template->setValue("porcentaje_devolucion#{$i}", $juego['porcentaje_devolucion']);
        }

            // Guardar archivo
        $fileName = 'Informe_Final1.docx';
        $filePath = storage_path($fileName);
        $template->saveAs($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}