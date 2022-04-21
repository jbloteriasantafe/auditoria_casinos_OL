<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportacionEstadoJuego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_estado_juego';
  protected $primaryKey = 'id_importacion_estado_juego';
  protected $visible = array('id_importacion_estado_juego','id_plataforma','fecha_importacion','md5');
  public $timestamps = false;

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }
  public function estados(){
    return $this->hasMany('App\EstadoJuegoImportado','id_importacion_estado_juego','id_importacion_estado_juego');
  }
}
