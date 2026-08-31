<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

class MapeadorBplay extends MapeadorGenerico
{
    public function __construct()
    {
        parent::__construct();

        // BPLAY utiliza encabezados específicos en su formato anterior (.xlsx) y en su nuevo formato (.csv)
        $this->configuracionBeneficioDiario['turnover']['alias'] = ['Turnover real', 'TURNOVER DIARIO'];
        $this->configuracionBeneficioDiario['turnover_bonos']['alias'] = ['Turnover en bonos', 'TURNOVER EN BONOS'];
        $this->configuracionBeneficioDiario['payout']['alias'] = ['Payout real', 'PAYOUT DIARIO'];
        $this->configuracionBeneficioDiario['payout_bonos']['alias'] = ['Payout en bonos', 'PAYOUT EN BONOS'];
        $this->configuracionBeneficioDiario['ggr']['alias'] = ['GGR'];
    }
}
