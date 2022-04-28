<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BeneficioPoker extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio_poker';
  protected $primaryKey = 'id_beneficio_poker';
  protected $visible = array('id_beneficio_poker','id_beneficio_mensual_poker','fecha','jugadores','mesas','buy','rebuy','total_buy',
                             'cash_out','otros_pagos','total_bonus','utilidad','observacion');
  protected $appends = array('calculado','diferencia','producido');
  public $timestamps = false;

  public function beneficio_mensual(){
    return $this->belongsTo('App\BeneficioMensualPoker','id_beneficio_mensual_poker','id_beneficio_mensual_poker');
  }

  public function getProducidoAttribute(){
    $bm = $this->beneficio_mensual;
    return ProducidoPoker::where([['fecha','=',$this->fecha],['id_plataforma','=',$bm->id_plataforma],['id_tipo_moneda','=',$bm->id_tipo_moneda]])->first();
  }

  public function getCalculadoAttribute(){
    $prod = $this->producido;
    if(is_null($prod)) return 0;
    return $prod->utilidad;
  }

  public function getDiferenciaAttribute(){
    return $this->calculado - $this->utilidad;
  }
}
