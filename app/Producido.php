<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Producido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido';
  protected $primaryKey = 'id_producido';
  protected $visible = array('id_producido','fecha','id_plataforma','id_tipo_moneda','jugadores','ingreso','premio','valor','cant_juegos_forzados','id_juegos_forzados');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleProducido','id_producido','id_producido');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public static function boot(){
    parent::boot();
    Producido::observe(Observers\FullObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_producido;
  }
}
