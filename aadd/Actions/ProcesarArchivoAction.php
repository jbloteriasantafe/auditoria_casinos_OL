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
        $mapear = $selector->resolverEstrategia($archivo->id_plataforma, $archivo->tipo_archivo);

        $registrosMapeados = call_user_func($mapear, $archivo);

        foreach ($registrosMapeados as $datos) {
            $datos['id_archivo'] = $archivo->id;
            $datos['id_plataforma'] = $archivo->id_plataforma;

            if ($archivo->tipo_archivo === TipoArchivo::BENEFICIO_DIARIO) {
                BeneficioDiario::create($datos);
            } else {
                BeneficioMensual::create($datos);
            }
        }
    }
}

