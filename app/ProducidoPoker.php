<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProducidoPoker extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido_poker';
  protected $primaryKey = 'id_producido_poker';
  protected $visible = array('id_producido_poker','fecha','id_plataforma','id_tipo_moneda','droop','utilidad','md5');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleProducidoPoker','id_producido_poker','id_producido_poker');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public static function boot(){
    parent::boot();
    ProducidoPoker::observe(Observers\FullObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_producido_poker;
  }
}
