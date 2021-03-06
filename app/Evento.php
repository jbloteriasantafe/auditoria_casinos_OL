<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'evento';
  protected $primaryKey = 'id_evento';
  protected $visible = array('id_evento','fecha_inicio','fecha_fin',
  'hora_inicio','hora_fin','titulo','descripcion','id_plataforma','id_tipo_evento',
  'realizado');

  public $timestamps = false;

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function tipo_evento(){
      return $this->belongsTo('App\TipoEvento','id_tipo_evento','id_tipo_evento');
  }

  public function desde(){
    return $this->hora_inicio;
  }

  public function hasta(){
    return $this->hora_fin;
  }
}
