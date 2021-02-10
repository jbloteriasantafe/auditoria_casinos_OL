<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cotizacion';
    protected $primaryKey = 'fecha';
    protected $visible = array('fecha','valor','id_tipo_moneda');
    public $timestamps = false;
    public function tipo_moneda(){
        return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
    }
}
