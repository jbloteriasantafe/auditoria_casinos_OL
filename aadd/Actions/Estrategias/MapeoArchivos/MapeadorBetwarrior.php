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
        
        $ultimoPunto = strrpos($valor, '.');
        $ultimaComa = strrpos($valor, ',');

        if ($ultimoPunto !== false && $ultimaComa !== false) {
            if ($ultimoPunto > $ultimaComa) {
                // Formato inglés: 1,234.56
                $valor = str_replace(',', '', $valor);
            } else {
                // Formato español: 1.234,56
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
        } elseif ($ultimaComa !== false) {
            // Solo coma: 1234,56
            $valor = str_replace(',', '.', $valor);
        }
        // Si solo tiene punto, ya está en formato float: 1234.56 o 1234
        
        return $valor;
    }
}
