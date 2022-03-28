<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DatosJugador extends Model
{
  protected $connection = 'mysql';
  protected $table = 'datos_jugador';
  protected $primaryKey = 'id_datos_jugador';
  protected $visible = array('id_datos_jugador','codigo','provincia','localidad','fecha_alta','fecha_nacimiento','sexo');
  public $timestamps = false;

  public function estados(){
    return $this->hasMany('App\EstadoJugador','id_datos_jugador','id_datos_jugador');
  }
}
