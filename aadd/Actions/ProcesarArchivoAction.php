<?php

namespace AADD\Actions;

use AADD\Entities\Archivo;
use AADD\Entities\BeneficioDiario;
use AADD\Entities\BeneficioMensual;
use AADD\Enums\TipoArchivo;
use AADD\Actions\Estrategias\MapeoArchivos\SelectorEstrategiaMapeoArchivos;
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

        foreach ($registrosMapeados as $item) {
            $etiqueta = $item['etiqueta'] ?? 'diario';
            $datos = $item['datos'] ?? $item;

            $datos['id_archivo'] = $archivo->id;
            $datos['id_plataforma'] = $archivo->id_plataforma;

            $claseEntityda = $mapeoEntidades[$etiqueta] ?? BeneficioDiario::class;

            $claseEntityda::updateOrCreate(
                [
                    'id_plataforma' => $datos['id_plataforma'],
                    'fecha'         => $datos['fecha'],
                ],
                $datos
            );
        }
    }
}

