<?php

namespace AADD\Actions\Estrategias\MapeoArchivos;

class MapeadorBetwarrior extends MapeadorGenerico
{
    public function __construct()
    {
        parent::__construct();

        // Personalizar transformaciones numéricas
        $transformacionNumericaEntera = function($v) {
            return (int) $this->limpiarNumero($v ?? '0');
        };

        $transformacionNumericaFlotante = function($v) {
            return (float) $this->limpiarNumero($v ?? '0');
        };

        $this->configuracionBeneficioDiario['jugadores_activos']['transformacion'] = $transformacionNumericaEntera;
        $this->configuracionBeneficioDiario['apuestas_realizadas']['transformacion'] = $transformacionNumericaEntera;
        
        $this->configuracionBeneficioDiario['turnover']['transformacion'] = $transformacionNumericaFlotante;
        $this->configuracionBeneficioDiario['turnover_bonos']['transformacion'] = $transformacionNumericaFlotante;
        $this->configuracionBeneficioDiario['payout']['transformacion'] = $transformacionNumericaFlotante;
        $this->configuracionBeneficioDiario['payout_bonos']['transformacion'] = $transformacionNumericaFlotante;
        $this->configuracionBeneficioDiario['ggr']['transformacion'] = $transformacionNumericaFlotante;
    }

    /**
     * Limpia el formato de número string "1234,00" a "1234.00" para que PHP lo casteé bien a float/int.
     *
     * @param string $valor
     * @return string
     */
    private function limpiarNumero($valor)
    {
        $valor = trim($valor);
        // Si por casualidad viene con puntos de miles (1.234,00), removemos el punto
        $valor = str_replace('.', '', $valor);
        // Reemplazamos coma decimal por punto decimal
        $valor = str_replace(',', '.', $valor);
        
        return $valor;
    }
}
