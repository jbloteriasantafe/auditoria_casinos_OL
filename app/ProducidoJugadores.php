<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProducidoJugadores extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido_jugadores';
  protected $primaryKey = 'id_producido_jugadores';
  protected $visible = array('id_producido_jugadores','fecha','id_plataforma','id_tipo_moneda'
  ,'apuesta_efectivo','apuesta_bono','apuesta'
  ,'premio_efectivo','premio_bono','premio'
  ,'beneficio_efectivo','beneficio_bono','beneficio','diferencia_montos','md5');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleProducidoJugadores','id_producido_jugadores','id_producido_jugadores');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public static function boot(){
    parent::boot();
    ProducidoJugadores::observe(Observers\FullObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_producido_jugadores;
  }
}
