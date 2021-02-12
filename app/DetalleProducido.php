<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleProducido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_producido';
  protected $primaryKey = 'id_detalle_producido';
  protected $visible = array('id_detalle_producido','id_producido','cod_juego','categoria','jugadores','ingreso','premio','valor');
  public $timestamps = false;

  public function producido(){
    return $this->belongsTo('App\Producido','id_producido','id_producido');
  }
}
