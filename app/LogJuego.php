<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogJuego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'log_juego';
  protected $primaryKey = 'id_log_juego';
  protected $visible = array('id_log_juego','id_juego','fecha','motivo','json');
  protected $casts = [
    'json' => 'array',
  ];
  public $timestamps = false;

  public function juegos(){
    return $this->belongsTo('App\Juego','id_juego','id_juego');
  }
}
