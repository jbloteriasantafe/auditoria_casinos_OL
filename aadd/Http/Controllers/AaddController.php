<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use AADD\Entities\Archivo;
use AADD\Entities\BeneficioDiario;

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
        try {
            $totalArchivos = Archivo::count();
            $subqueryUltimos = BeneficioDiario::select(DB::raw('MAX(id) as id'))
                ->groupBy('id_plataforma', 'fecha');

            $totalDiasAuditados = BeneficioDiario::whereIn('id', $subqueryUltimos)->count();
            
            // Contar diferencias significativas (diferencia != 0) en los últimos registros importados
            $diferenciasDetectadas = BeneficioDiario::whereIn('id', $subqueryUltimos)
                ->where('diferencia', '!=', 0)
                ->count();

            // Obtener última fecha de importación
            $ultimoArchivo = Archivo::orderBy('created_at', 'desc')->first();
            $ultimaImportacion = $ultimoArchivo ? $ultimoArchivo->created_at->format('d/m/Y H:i') : 'Sin importaciones';

            // Obtener los últimos 5 archivos importados
            $ultimosArchivos = Archivo::orderBy('created_at', 'desc')->take(5)->get();
        } catch (\Exception $e) {
            $totalArchivos = 0;
            $totalDiasAuditados = 0;
            $diferenciasDetectadas = 0;
            $ultimaImportacion = 'Sin importaciones';
            $ultimosArchivos = collect();
        }

        $mapeadoresActivos = 7; // Bet365, Betson, Betwarrior, Bplay, Citycenter, Generico, Jugadon

        return view('aadd::index', compact(
            'totalArchivos',
            'totalDiasAuditados',
            'diferenciasDetectadas',
            'ultimaImportacion',
            'ultimosArchivos',
            'mapeadoresActivos'
        ));
    }
}
