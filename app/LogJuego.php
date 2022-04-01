<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogJuego extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'juego_log_norm';
  protected $primaryKey = 'id_juego_log_norm';
  protected $visible = [
    'id_juego','nombre_juego','cod_juego','codigo_operador','proveedor',
    'denominacion_contable','denominacion_juego','porcentaje_devolucion', 'escritorio','movil',
    'id_unidad_medida','id_tipo_moneda','id_categoria_juego','motivo','id_usuario','created_at','updated_at','deleted_at'
  ];
  public $timestamps = false;

  public function juego(){
    return $this->belongsTo('App\Juego','id_juego','id_juego');
  }

  public function gliSoft(){
    return $this->belongsToMany('App\GliSoft','juego_log_norm_glisoft','id_juego_log_norm','id_gli_soft');
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
    return $this->belongsToMany('App\Plataforma','plataforma_tiene_juego_log_norm','id_juego_log_norm','id_plataforma')->withPivot('id_estado_juego');
  }
  
  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function categoria_juego(){
    return $this->belongsTo('App\CategoriaJuego','id_categoria_juego','id_categoria_juego');
  }

  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_juego_log_norm;
  }
}
