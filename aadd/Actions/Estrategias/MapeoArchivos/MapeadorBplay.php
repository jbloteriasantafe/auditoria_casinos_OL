<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

class MapeadorBplay extends MapeadorGenerico
{
    public function __construct()
    {
        parent::__construct();


        // BPLAY utiliza encabezados diferentes para sus montos
        $this->configuracionBeneficioDiario['turnover']['alias'] = ['Turnover real'];
        $this->configuracionBeneficioDiario['turnover_bonos']['alias'] = ['Turnover bonos'];
        $this->configuracionBeneficioDiario['payout']['alias'] = ['Payout real'];
        $this->configuracionBeneficioDiario['payout_bonos']['alias'] = ['Payout bonos'];
        $this->configuracionBeneficioDiario['ggr']['alias'] = ['GGR'];
    }
}
