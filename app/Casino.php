<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Casino extends Model
{
  protected $connection = 'mysql';
  protected $table = 'casino';
  protected $primaryKey = 'id_casino';
  protected $visible = array('id_casino','nombre','codigo','fecha_inicio','porcentaje_sorteo_mesas','minimo_relevamiento_progresivo');
  public $timestamps = false;

  public function usuarios(){
    return $this->belongsToMany('App\Usuario','usuario_tiene_casino','id_casino','id_usuario');
  }
  public function eventos(){
    return $this->hasMany('App\Evento','id_casino','id_casino');
  }
  public function notas(){
    return $this->hasMany('App\Nota','id_casino','id_casino');
  }
  public function plataformas(){
    return $this->belongsToMany('App\Plataforma','plataforma_tiene_casino','id_casino','id_plataforma');
  }
}
