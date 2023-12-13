<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActividadTareaGrupo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'actividad_tarea_grupo';
  protected $primaryKey = 'id_actividad_tarea_grupo';
  protected $visible = [
    'id_actividad_tarea_grupo',
    'nombre','usuarios',
    'created_by','created_at',
    'modified_by','modified_at',
    'deleted_by','deleted_at'
  ];
  public $timestamps = false;
  protected $appends = [];

  public static function boot(){
    parent::boot();
    self::observe(Observers\FullObserver::class);
  }
    
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_actividad_tarea_grupo;
  }
}
