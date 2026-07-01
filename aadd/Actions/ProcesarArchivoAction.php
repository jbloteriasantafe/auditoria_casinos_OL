<?php

namespace AADD\Actions;

use AADD\Entities\Archivo;
use AADD\Entities\BeneficioDiario;
use AADD\Entities\BeneficioMensual;
use AADD\Enums\TipoArchivo;
use AADD\Enums\Plataforma;
use AADD\Actions\Estrategias\EstrategiaMapeo;
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
        // 1. Resolver la estrategia de mapeo adecuada según la plataforma y tipo de archivo
        $estrategia = $this->resolverEstrategia($archivo->id_plataforma, $archivo->tipo_archivo);

        // 2. Procesar y mapear el contenido binario del archivo usando la estrategia resuelta
        $registrosMapeados = $estrategia->mapear($archivo->contenido);

        // 3. Persistir los registros de beneficios correspondientes en la base de datos de forma plana
        foreach ($registrosMapeados as $datos) {
            $datos['id_archivo'] = $archivo->id;
            $datos['id_plataforma'] = $archivo->id_plataforma;

            if ($archivo->tipo_archivo === TipoArchivo::DIARIO) {
                BeneficioDiario::create($datos);
            } else {
                BeneficioMensual::create($datos);
            }
        }
    }

    /**
     * Resolver la estrategia de mapeo según la plataforma y tipo de archivo.
     *
     * @param string $idPlataforma
     * @param string $tipoArchivo
     * @return EstrategiaMapeo
     * @throws Exception
     */
    private function resolverEstrategia($idPlataforma, $tipoArchivo)
    {
        // NOTA: De momento utilizamos una única estrategia placeholder (MapeadorGenerico).
        // Cuando se implementen los mapeadores específicos de cada plataforma y tipo de archivo,
        // se deben instanciar y retornar según las combinaciones aquí.
        // Ej:
        // switch ($idPlataforma) {
        //     case Plataforma::PLATAFORMA_A:
        //         return $tipoArchivo === TipoArchivo::DIARIO 
        //             ? new MapeadorPlataformaADiario() 
        //             : new MapeadorPlataformaAMensual();
        //     case Plataforma::PLATAFORMA_B:
        //         ...
        // }

        return new \AADD\Actions\Estrategias\MapeadorGenerico($tipoArchivo);
    }
}
