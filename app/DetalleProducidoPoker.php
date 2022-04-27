<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleProducidoPoker extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_producido_poker';
  protected $primaryKey = 'id_detalle_producido_poker';
  protected $visible = array('id_detalle_producido_poker','id_producido_poker','cod_juego','categoria','jugadores','droop','utilidad');
  public $timestamps = false;

  public function producido(){
    return $this->belongsTo('App\ProducidoPoker','id_producido_poker','id_producido_poker');
  }
}
