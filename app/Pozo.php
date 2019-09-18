<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\PozoObserver;


class Pozo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'pozo';
  protected $primaryKey = 'id_pozo';
  protected $visible = array('id_pozo','descripcion','id_progresivo');
  public $timestamps = false;

  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

  public function niveles(){
    return $this->hasMany('App\NivelProgresivo','id_pozo','id_pozo');
  }

  public static function boot(){
    parent::boot();
    Pozo::observe(new PozoObserver());
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_pozo;
  }
}
