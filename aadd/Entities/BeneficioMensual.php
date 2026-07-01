<?php

namespace AADD\Entities;

use Illuminate\Database\Eloquent\Model;

class BeneficioMensual extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'aadd_t_beneficio_mensual';

    /**
     * Los atributos que son asignables en lote.
     *
     * @var array
     */
    protected $fillable = [
        'id_plataforma',
        'id_archivo',
        'fecha',
        'jugadores_activos',
        'apuestas_realizadas',
        'turnover',
        'turnover_bonos',
        'payout',
        'payout_bonos',
        'ggr',
    ];
}
