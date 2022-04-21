<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoJuegoImportado extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_juego_importado';
  protected $primaryKey = 'id_estado_juego_importado';
  protected $visible = array('id_estado_juego_importado','id_importacion_estado_juego','id_datos_juego_importado','estado','es_ultimo_estado_del_juego');
  public $timestamps = false;

  public function datos(){
    return $this->belongsTo('App\DatosJuegoImportado','id_datos_juego_importado','id_datos_juego_importado');
  }
  public function importacion(){
    return $this->belongsTo('App\ImportacionEstadoJuego','id_importacion_estado_juego','id_importacion_estado_juego');
  }
}
