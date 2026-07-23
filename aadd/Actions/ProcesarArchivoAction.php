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
            'diario' => BeneficioDiario::class,
            'mensual' => BeneficioMensual::class,
        ];

        // Envolver todas las inserciones del archivo en una transacción atómica
        DB::connection('aadd')->transaction(function () use ($registrosMapeados, $archivo, $mapeoEntidades) {
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

                    \Illuminate\Support\Facades\Log::info('AADD - Comparacion de Beneficio', [
                        'fecha' => $datos['fecha'],
                        'plataforma' => $datos['id_plataforma'],
                        'original' => $ultimoRegistro->getAttributes(),
                        'nuevo_a_cargar' => $datosSinArchivo,
                    ]);

                    $ultimoRegistro->fill($datosSinArchivo);

                    // Normalizar decimales al formato esperado por Laravel 5.4 para evitar falsos positivos en isDirty()
                    $this->emparchar($ultimoRegistro);

                    \Illuminate\Support\Facades\Log::info('AADD - Resultado de fill', [
                        'dirty_attributes' => $ultimoRegistro->getDirty(),
                        'is_dirty_result' => $ultimoRegistro->isDirty(),
                    ]);

                    $hayCambios = $ultimoRegistro->isDirty();
                }

                // Crear una nueva fila solo si no existe o si los datos cambiaron
                if (!$ultimoRegistro || $hayCambios || $archivo->fecha >= $datos["fecha"]) {
                    $instancia = new $claseEntidad();
                    $instancia->fill($datos);
                    $instancia->save();
                }
            }
        });
    }

    /**
     * Normalizar los atributos decimales de la instancia para evitar falsos positivos en isDirty().
     *
     * En Laravel 5.4, isDirty() compara los atributos brutos de $attributes y $original usando strcmp()
     * para valores numéricos. Si un número entero es cargado como float/int (ej: 0 o 350535169), se guarda 
     * sin decimales, mientras que la base de datos retorna cadenas como "0.00" o "350535169.00".
     * Esta función convierte números enteros o decimales a strings terminados en ".00" (o el formato correspondiente)
     * para lograr el mismo formato de la base de datos en laravel 5.4.
     *
     * @param mixed $instancia
     * @return void
     */
    protected function emparchar($instancia)
    {
        $camposDecimales = [
            'turnover',
            'turnover_bonos',
            'payout',
            'payout_bonos',
            'ggr',
            'ggr_calculado',
            'diferencia',
        ];

        foreach ($camposDecimales as $campo) {
            if (isset($instancia->$campo)) {
                $instancia->$campo = number_format((float) $instancia->$campo, 2, '.', '');
            }
        }
    }
}
