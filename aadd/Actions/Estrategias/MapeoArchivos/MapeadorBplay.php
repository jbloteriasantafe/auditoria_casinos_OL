<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

class MapeadorBplay extends MapeadorGenerico
{
    public function __construct()
    {
        parent::__construct();

        $this->configuracionBeneficioDiario['jugadores_activos']['alias'] = ['Jugadores activos', 'JUGADORES ACTIVOS', "JUGADORES_ACTIVOS"];
        $this->configuracionBeneficioDiario['apuestas_realizadas']['alias'] = ['Apuestas realizadas', 'APUESTAS REALIZADAS', "APUESTAS_REALIZADAS"];
        $this->configuracionBeneficioDiario['turnover']['alias'] = ['Turnover real', 'TURNOVER DIARIO', 'TURNOVER_DIARIO'];
        $this->configuracionBeneficioDiario['turnover_bonos']['alias'] = ['Turnover en bonos', 'TURNOVER EN BONOS', 'TURNOVER_BONOS'];
        $this->configuracionBeneficioDiario['payout']['alias'] = ['Payout real', 'PAYOUT DIARIO', 'PAYOUT_DIARIO'];
        $this->configuracionBeneficioDiario['payout_bonos']['alias'] = ['Payout en bonos', 'PAYOUT EN BONOS', 'PAYOUT_BONOS'];
        $this->configuracionBeneficioDiario['ggr']['alias'] = ['GGR'];
    }
}
