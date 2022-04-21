<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DatosJuegoImportado extends Model
{
  protected $connection = 'mysql';
  protected $table = 'datos_juego_importado';
  protected $primaryKey = 'id_datos_juego_importado';
  protected $visible = array('id_datos_juego_importado','codigo','nombre','categoria','tecnologia');
  public $timestamps = false;

  public function estados(){
    return $this->hasMany('App\EstadoJuegoImportado','id_datos_juego_importado','id_datos_juego_importado');
  }
}
