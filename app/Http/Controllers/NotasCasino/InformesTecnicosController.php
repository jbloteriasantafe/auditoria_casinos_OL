<?php
namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InformesTecnicosController extends Controller
{
    public function index()
    {   
        $casinos = [['id_casino' => 4, 'casino' => 'CITY CENTER ONLINE'], ['id_casino' => 5, 'casino' => 'BPLAY'],];
        try {
            $juegos = DB::connection('mysql')
                        ->table('juego')
                        ->select('id_juego', 'nombre_juego')
                        ->get();
        } catch (Exception $e) {
            Log::error('Error al obtener juegos: ' . $e->getMessage());
        }

        return view('NotasCasino.indexInformesTecnicos',compact('casinos', 'juegos'));
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
}