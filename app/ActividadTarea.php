<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActividadTarea extends Model
{
  protected $connection = 'mysql';
  protected $table = 'actividad_tarea';
  protected $primaryKey = 'id_actividad_tarea';
  protected $visible = [
    'id_actividad_tarea',
    'numero','padre_numero',
    'fecha','titulo',
    'estado','contenido',
    'cada_cuanto','tipo_repeticion','hasta',
    'grupos','adjuntos',
    'created_by','created_at',
    'modified_by','modified_at',
    'deleted_by','deleted_at',
    'tags_api',
    'color_fondo','color_texto','color_borde'
  ];
  public $timestamps = false;
  protected $appends = [];
  protected $hidden = ['dirty','padre_numero_original','ip','token','deleted_ip','deleted_token'];
      
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_actividad_tarea;
  }
}
