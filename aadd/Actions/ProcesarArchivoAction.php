<?php

namespace AADD\Actions;

use AADD\Entities\Archivo;
use AADD\Entities\BeneficioDiario;
use AADD\Entities\BeneficioMensual;
use AADD\Enums\TipoArchivo;
use AADD\Actions\Estrategias\MapeoArchivos\SelectorEstrategiaMapeoArchivos;
use Illuminate\Support\Facades\DB;
use Exception;

class ProcesarArchivoAction
{
    /**
     * Procesar un archivo previamente cargado e importar sus beneficios.
     *
     * @param Archivo $archivo
     * @return void
     * @throws Exception
     */
    public function ejecutar(Archivo $archivo)
    {
        $selector = new SelectorEstrategiaMapeoArchivos();
        $estrategiaMapeo = $selector->resolverEstrategia($archivo->id_plataforma);

        $registrosMapeados = $estrategiaMapeo->mapear($archivo);

        $mapeoEntidades = [
            'diario'  => BeneficioDiario::class,
            'mensual' => BeneficioMensual::class,
        ];

        // Envolver todas las inserciones del archivo en una transacción atómica
        DB::connection('aadd')->transaction(function() use ($registrosMapeados, $archivo, $mapeoEntidades) {
            foreach ($registrosMapeados as $item) {
                $etiqueta = $item['etiqueta'] ?? 'diario';
                $datos = $item['datos'] ?? $item;

                $datos['id_archivo'] = $archivo->id;
                $datos['id_plataforma'] = $archivo->id_plataforma;

                $claseEntidad = $mapeoEntidades[$etiqueta] ?? BeneficioDiario::class;

                // Buscar la última versión registrada para esa plataforma y fecha
                $ultimoRegistro = $claseEntidad::where('id_plataforma', $datos['id_plataforma'])
                    ->where('fecha', $datos['fecha'])
                    ->orderBy('id', 'desc')
                    ->first();

                // Comprobar si hay cambios usando isDirty() (excluyendo id_archivo del análisis)
                $hayCambios = true;
                if ($ultimoRegistro) {
                    $datosSinArchivo = array_diff_key($datos, ['id_archivo' => true]);
                    $ultimoRegistro->fill($datosSinArchivo);
                    $hayCambios = $ultimoRegistro->isDirty();
                }

                // Crear una nueva fila solo si no existe o si los datos cambiaron
                if (!$ultimoRegistro || $hayCambios) {
                    $instancia = new $claseEntidad();
                    $instancia->fill($datos);
                    $instancia->save();
                }
            }
        });
    }
}
