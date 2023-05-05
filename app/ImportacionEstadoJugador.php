<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportacionEstadoJugador extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_estado_jugador';
  protected $primaryKey = 'id_importacion_estado_jugador';
  protected $visible = array('id_importacion_estado_jugador','id_plataforma','fecha_importacion','md5');
  public $timestamps = false;

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }
}
