<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\NotaObserver;

class Nota extends Model
{
  protected $connection = 'mysql';
  protected $table = 'nota';
  protected $primaryKey = 'id_nota';
  protected $visible = array('id_nota','fecha','detalle','identificacion',
                          'id_estado_juego','id_expediente','es_disposicion');
  public $timestamps = false;

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }

  public function disposiciones(){
      return $this->hasMany('App\Disposicion','id_nota','id_nota');
  }

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function estado_juego(){
    return $this->belongsTo('App\EstadoJuego','id_estado_juego','id_estado_juego');
  }

  public static function boot(){
        parent::boot();
        Nota::observe(new NotaObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_nota;
  }
}
