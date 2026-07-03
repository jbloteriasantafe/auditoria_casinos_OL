<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

use AADD\Enums\Plataforma;
use Exception;

class SelectorEstrategiaMapeoArchivos
{
    /**
     * Resolver la estrategia de mapeo específica de la plataforma.
     *
     * @param string $idPlataforma
     * @return EstrategiaMapeoArchivo
     * @throws Exception
     */
    public function resolverEstrategia($idPlataforma)
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
                // Fallback al mapeador genérico si no hay estrategia específica para la plataforma
                return new MapeadorGenerico();
        }
    }
}
