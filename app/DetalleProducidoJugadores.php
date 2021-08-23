<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleProducidojugadores extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_producido_jugadores';
  protected $primaryKey = 'id_detalle_producido_jugadores';
  protected $visible = array('id_detalle_producido_jugadores','id_producido','jugador','juegos'
  ,'apuesta_efectivo','apuesta_bono','apuesta'
  ,'premio_efectivo','premio_bono','premio'
  ,'beneficio_efectivo','beneficio_bono','beneficio','diferencia_montos');
  public $timestamps = false;

  public function producido(){
    return $this->belongsTo('App\ProducidoJugadores','id_producido_jugadores','id_producido_jugadores');
  }
}
