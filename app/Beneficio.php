<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\BeneficioObserver;
class Beneficio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio';
  protected $primaryKey = 'id_beneficio';
  protected $visible = array('id_beneficio','id_beneficio_mensual','fecha','Players','TotalWager','TotalOut','GrossRevenue');
  public $timestamps = false;

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
