<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plataforma extends Model
{
  protected $connection = 'mysql';
  protected $table = 'plataforma';
  protected $primaryKey = 'id_plataforma';
  protected $visible = array('id_plataforma','nombre','codigo');
  public $timestamps = false;

  public function casinos(){
    return $this->belongsToMany('App\Casino','plataforma_tiene_casino','id_plataforma','id_casino');
  }
  public function juegos(){
    return $this->belongsToMany('App\Juego','plataforma_tiene_juego','id_plataforma','id_juego')->withPivot('id_estado_juego');
  }
  public function logs(){
    return $this->belongsToMany('App\LogJuego','plataforma_tiene_juego_log_norm','id_plataforma','id_juego')->withPivot('id_estado_juego')->withTrashed();
  }
  public function expedientes(){
    return $this->belongsToMany('App\Expediente','expediente_tiene_plataforma','id_plataforma','id_expediente');
  }
}
