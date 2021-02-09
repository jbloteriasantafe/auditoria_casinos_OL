<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BeneficioMensual extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio_mensual';
  protected $primaryKey = 'id_beneficio_mensual';
  protected $visible = array('id_beneficio_mensual','id_plataforma','id_tipo_moneda','fecha','jugadores','ingreso','premio','valor','ajuste','validado','canon');
  public $timestamps = false;

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function beneficios(){
    return $this->hasMany('App\Beneficio','id_beneficio_mensual','id_beneficio_mensual');
  }
}
