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
    public $incrementing = false;
    public function tipo_moneda(){
        return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
    }
    public static function boot(){
        parent::boot();
        Cotizacion::observe(Observers\FullObserver::class);
    }
    public function getTableName(){
        return $this->table;
    }
    public function getId(){
        $date = date_create_from_format('Y-m-d',$this->fecha);
        return $date->getTimestamp();//Solo tiene IDs ints la tabla log, asi que lo paso a timestamp
    }
}
