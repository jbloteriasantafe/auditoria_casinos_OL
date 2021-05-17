<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Beneficio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio';
  protected $primaryKey = 'id_beneficio';
  protected $visible = array('id_beneficio','id_beneficio_mensual','fecha',
    'jugadores','depositos','retiros',
    'apuesta','premio','beneficio',
    'ajuste','puntos_club_jugadores','observacion');
  protected $appends = array('calculado','diferencia','producido');
  public $timestamps = false;

  public function beneficio_mensual(){
    return $this->belongsTo('App\BeneficioMensual','id_beneficio_mensual','id_beneficio_mensual');
  }

  public function getProducidoAttribute(){
    $bm = $this->beneficio_mensual;
    return Producido::where([['fecha','=',$this->fecha],['id_plataforma','=',$bm->id_plataforma],['id_tipo_moneda','=',$bm->id_tipo_moneda]])->first();
  }

  public function getCalculadoAttribute(){
    $prod = $this->producido;
    if(is_null($prod)) return 0;
    return $prod->beneficio;
  }

  public function getDiferenciaAttribute(){
    return $this->calculado - ($this->beneficio + $this->ajuste);
  }
}
