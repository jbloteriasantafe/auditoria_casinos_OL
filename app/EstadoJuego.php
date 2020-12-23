<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoJuego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_juego';
  protected $primaryKey = 'id_estado_juego';
  protected $visible = array('id_estado_juego','nombre',"codigo");
  public $timestamps = false;

  public function juegos(){
    return $this->belongsToMany('App\Juego','plataforma_tiene_juego','id_juego','id_estado_juego');
  }
}
