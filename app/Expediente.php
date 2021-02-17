<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
  protected $connection = 'mysql';
  protected $table = 'expediente';
  protected $primaryKey = 'id_expediente';
  protected $visible = array('id_expediente', 'nro_exp_org','nro_exp_interno','nro_exp_control','fecha_iniciacion','iniciador','concepto','ubicacion_fisica','fecha_pase','remitente','destino','nro_folios','tema','anexo','nro_cuerpos','concatenacion');
  protected $appends = array('concatenacion');
  public $timestamps = false;

  public function plataformas(){
    return $this->belongsToMany('App\Plataforma','expediente_tiene_plataforma','id_expediente','id_plataforma');
  }
  public function resoluciones(){
    return $this->hasMany('App\Resolucion','id_expediente','id_expediente');
  }
  public function disposiciones(){
    return $this->hasMany('App\Disposicion','id_expediente','id_expediente');
  }
  public function gli_softs(){
     return $this->belongsToMany('App\GliSoft','expediente_tiene_gli_sw','id_expediente','id_gli_soft');
  }
  public function notas(){
    return $this->HasMany('App\Nota' , 'id_expediente', 'id_expediente');
  }

  public function getConcatenacionAttribute(){
    return $this->nro_exp_org . '-' . $this->nro_exp_interno . '-' . $this->nro_exp_control;
  }

  public static function boot(){
    parent::boot();
    Expediente::observe(Observers\FullObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_expediente;
  }

}
