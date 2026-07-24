<?php

namespace AADD\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archivo extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
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
    protected $table = 'aadd_t_archivo';

    /**
     * Los atributos que son asignables en lote.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'contenido',
        'id_plataforma',
        'fecha',
        'tipo_archivo',
    ];
}
