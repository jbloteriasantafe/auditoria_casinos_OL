<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Juego extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'juego';
  protected $primaryKey = 'id_juego';
  protected $visible = array(
    'id_juego','nombre_juego','cod_juego','codigo_operador','proveedor',
    'denominacion_contable','denominacion_juego','porcentaje_devolucion', 'escritorio','movil',
    'id_unidad_medida','id_tipo_moneda','id_categoria_juego','created_at','updated_at','deleted_at'
  );
  public $timestamps = true;
  protected $appends = [];

  public function gliSoft(){
    return $this->belongsToMany('App\GliSoft','juego_glisoft','id_juego','id_gli_soft');
  }

  public function setearGliSofts($garray,$ids=False){
    $arr = [];
    if(!$ids){
      foreach($garray as $g) $arr[] = $g->id_gli_soft;
    }
    else $arr = $garray;

    $this->gliSoft()->sync($arr);
  }

  public function plataformas(){
    return $this->belongsToMany('App\Plataforma','plataforma_tiene_juego','id_juego','id_plataforma')->withPivot('id_estado_juego');
  }
  
  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function categoria_juego(){
    return $this->belongsTo('App\CategoriaJuego','id_categoria_juego','id_categoria_juego');
  }

  public function logs(){
    return $this->hasMany('App\LogJuego','id_juego','id_juego');
  }

  public static function boot(){
    parent::boot();
    Juego::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_juego;
  }

}
