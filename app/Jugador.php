<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Jugador extends Model
{
  protected $table = 'jugador';
  protected $primaryKey = 'id_jugador';
  protected $visible = array(
    'id_jugador','id_plataforma','fecha_importacion','localidad','provincia',
    'fecha_alta','codigo','estado', 'fecha_autoexclusion','fecha_nacimiento',
    'fecha_ultimo_movimiento','sexo'
  );

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }
  
  public static function boot(){
    parent::boot();
    self::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_jugador;
  }
}
