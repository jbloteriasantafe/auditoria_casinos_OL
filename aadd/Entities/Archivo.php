<?php

namespace AADD\Entities;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
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
