<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Enums\Plataforma;
use AADD\Enums\TipoArchivo;
use Exception;

class SelectorEstrategiaMapeoArchivos
{
    /**
     * Resolver el mapeador específico de la plataforma.
     *
     * @param string $idPlataforma
     * @return EstrategiaMapeoArchivo
     * @throws Exception
     */
    private function resolverMapeadorPlataforma($idPlataforma)
    {
        switch ($idPlataforma) {
            case Plataforma::BPLAY:
                return new MapeadorBplay();
            case Plataforma::CITYCENTER:
                return new MapeadorCitycenter();
            case Plataforma::JUGADON:
                return new MapeadorJugadon();
            case Plataforma::BETWARRIOR:
                return new MapeadorBetwarrior();
            case Plataforma::BETSON:
                return new MapeadorBetson();
            case Plataforma::BET365:
                return new MapeadorBet365();
            default:
                throw new Exception("No existe una estrategia de mapeo para la plataforma: " . $idPlataforma);
        }
    }

    /**
     * Resolver el nombre del método según el tipo de archivo.
     *
     * @param string $tipoArchivo
     * @return string
     * @throws Exception
     */
    private function resolverMetodoTipoArchivo($tipoArchivo)
    {
        switch ($tipoArchivo) {
            case TipoArchivo::BENEFICIO_DIARIO:
                return 'mapearBeneficioDiario';
            default:
                throw new Exception("Tipo de archivo no soportado para el mapeo: " . $tipoArchivo);
        }
    }

    /**
     * Resolver la estrategia y retornar un callable con el mapeador y método correspondientes.
     *
     * @param string $idPlataforma
     * @param string $tipoArchivo
     * @return callable
     * @throws Exception
     */
    public function resolverEstrategia($idPlataforma, $tipoArchivo)
    {
        $mapeador = $this->resolverMapeadorPlataforma($idPlataforma);
        $metodo = $this->resolverMetodoTipoArchivo($tipoArchivo);

        if (method_exists($mapeador, $metodo)) {
            return [$mapeador, $metodo];
        }

        // Fallback al mapeador genérico si el mapeador de la plataforma no tiene implementado el método
        $mapeadorGenerico = new MapeadorGenerico();
        if (method_exists($mapeadorGenerico, $metodo)) {
            return [$mapeadorGenerico, $metodo];
        }

        throw new Exception(sprintf(
            "Ni el mapeador de la plataforma '%s' ni el MapeadorGenerico implementan el método '%s'",
            get_class($mapeador),
            $metodo
        ));
    }
}
