<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

class MapeadorBplay extends MapeadorGenerico
{
    public function __construct()
    {
        parent::__construct();

        // BPLAY no envía la cantidad de apuestas
        $this->configuracionBeneficioDiario['apuestas_realizadas']['transformacion'] = function($v) {
            return 0;
        };

        // BPLAY utiliza encabezados diferentes para sus montos
        $this->configuracionBeneficioDiario['turnover']['alias'] = ['APUESTAS REALIZADAS EFECTIVO'];
        $this->configuracionBeneficioDiario['turnover_bonos']['alias'] = ['APUESTAS BONOS'];
        $this->configuracionBeneficioDiario['payout']['alias'] = ['PREMIOS PAGADOS EFECTIVO'];
        $this->configuracionBeneficioDiario['payout_bonos']['alias'] = ['PREMIOS BONOS'];
        $this->configuracionBeneficioDiario['ggr']['alias'] = ['GGR EFECTIVO'];
    }
}
