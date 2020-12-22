<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleProducido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_producido';
  protected $primaryKey = 'id_detalle_producido';
  protected $visible = array('id_detalle_producido','id_producido','cod_juego','categoria','jugadores',
  'TotalWagerCash','TotalWagerBonus','TotalWager','GrossRevenueCash','GrossRevenueBonus','GrossRevenue','valor');
  public $timestamps = false;

  /*public function juego(){
    return $this->belongsTo('App\Juego','cod_juego','cod_juego');
  }*/
  public function producido(){
    return $this->belongsTo('App\Producido','id_producido','id_producido');
  }
  public function tipo_ajuste(){
    return $this->belongsTo('App\TipoAjuste','id_tipo_ajuste','id_tipo_ajuste');
  }
  public function ajuste_producido(){
    return $this->belongsTo('App\AjusteProducido','id_ajuste_producido','id_ajuste_producido');
  }

}
