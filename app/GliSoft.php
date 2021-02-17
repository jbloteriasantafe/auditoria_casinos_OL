<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GliSoft extends Model
{
  protected $connection = 'mysql';
  protected $table = 'gli_soft';
  protected $primaryKey = 'id_gli_soft';
  protected $visible = array('id_gli_soft','observaciones','nro_archivo','id_archivo','id_laboratorio');
  public $timestamps = false;

  public function archivo(){
      return $this->belongsTo('App\Archivo','id_archivo','id_archivo');
  }
  public function juegos(){
      return $this->belongsToMany('App\Juego','juego_glisoft','id_gli_soft','id_juego');
  }
  public function laboratorio(){
    return $this->belongsTo('App\Laboratorio','id_laboratorio','id_laboratorio');
  }
  
  public function setearJuegos($jarray,$id=False){
    $arr = [];
    if(!$id){
      foreach($jarray as $j) $arr[] = $j->id_juego;
    }
    else $arr = $jarray;

    $this->juegos()->sync($arr);
  }

  public function expedientes(){
     return $this->belongsToMany('App\Expediente','expediente_tiene_gli_sw','id_gli_soft','id_expediente');
  }

  public static function boot(){
    parent::boot();
    GliSoft::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_gli_soft;
  }

}
