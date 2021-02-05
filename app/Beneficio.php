<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\BeneficioObserver;
class Beneficio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio';
  protected $primaryKey = 'id_beneficio';
  protected $visible = array('id_beneficio','id_beneficio_mensual','fecha','jugadores','ingreso','premio','valor','ajuste','observacion','calculado','diferencia');
  protected $appends = array('calculado','diferencia');
  public $timestamps = false;

  public function getCalculadoAttribute(){
    $benMensual = $this->beneficio_mensual;
    $prod = Producido::where([['fecha','=',$this->fecha],['id_plataforma','=',$benMensual->id_plataforma],['id_tipo_moneda','=',$benMensual->id_tipo_moneda]])->first();
    if(is_null($prod)) return 0;
    return $prod->valor;
  }

  public function getDiferenciaAttribute(){
    return $this->calculado - $this->valor + $this->ajuste;
  }

  public function beneficio_mensual(){
    return $this->belongsTo('App\BeneficioMensual','id_beneficio_mensual','id_beneficio_mensual');
  }

  public static function boot(){
    parent::boot();
    Beneficio::observe(new BeneficioObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_beneficio;
  }
}
