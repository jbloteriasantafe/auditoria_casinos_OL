<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Laboratorio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'laboratorio';
  protected $primaryKey = 'id_laboratorio';
  protected $visible = array('id_laboratorio','codigo','denominacion','pais','url','nota');
  public $timestamps = false;

  public function certificados(){
    return $this->hasMany('App\GliSoft','gli_soft','id_laboratorio');
  }
}
