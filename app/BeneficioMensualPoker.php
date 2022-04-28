<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BeneficioMensualPoker extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio_mensual_poker';
  protected $primaryKey = 'id_beneficio_mensual_poker';
  protected $visible = array('id_beneficio_mensual','id_plataforma','id_tipo_moneda','jugadores','mesas','buy','rebuy','total_buy',
                             'cash_out','otros_pagos','total_bonus','utilidad','fecha','validado','canon','md5');
  public $timestamps = false;

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function beneficios(){
    return $this->hasMany('App\BeneficioPoker','id_beneficio_mensual_poker','id_beneficio_mensual_poker');
  }

  public static function boot(){
    parent::boot();
    self::observe(Observers\FullObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_beneficio_mensual_poker;
  }
}
