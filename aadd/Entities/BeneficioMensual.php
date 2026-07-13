<?php

namespace AADD\Entities;

use Illuminate\Database\Eloquent\Model;

class BeneficioMensual extends Model
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

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'fecha' => 'datetime',
        'jugadores_activos' => 'integer',
        'apuestas_realizadas' => 'integer',
        'turnover' => 'float',
        'turnover_bonos' => 'float',
        'payout' => 'float',
        'payout_bonos' => 'float',
        'ggr' => 'float',
    ];

    /**
     * Relación con el archivo de origen de la importación/modificación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function archivo()
    {
        return $this->belongsTo(Archivo::class, 'id_archivo');
    }
}

