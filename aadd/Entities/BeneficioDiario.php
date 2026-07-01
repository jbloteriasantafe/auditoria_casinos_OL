<?php

namespace AADD\Entities;

use Illuminate\Database\Eloquent\Model;

class BeneficioDiario extends Model
{
    /**
     * Conexión de base de datos para el modelo.
     *
     * @var string
     */
    protected $connection = 'aadd';

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'aadd_t_beneficio_diario';

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
