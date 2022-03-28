<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoJugador extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_jugador';
  protected $primaryKey = 'id_estado_jugador';
  protected $visible = array('id_estado_jugador','id_importacion_estado_jugador','id_datos_jugador','estado','fecha_autoexclusion','fecha_ultimo_movimiento');
  public $timestamps = false;

  public function datos(){
    return $this->belongsTo('App\DatosJugador','id_datos_jugador','id_datos_jugador');
  }
  public function importacion(){
    return $this->belongsTo('App\ImportacionEstadoJugador','id_datos_jugador','id_datos_jugador');
  }
}
