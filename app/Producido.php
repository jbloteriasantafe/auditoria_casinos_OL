<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ProducidoObserver;

class Producido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido';
  protected $primaryKey = 'id_producido';
  protected $visible = array('id_producido','fecha','id_plataforma','id_tipo_moneda','cant_juegos_forzados','id_juegos_forzados','beneficio_calculado');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function getBeneficioCalculadoAttribute(){
    return DetalleProducido::where('id_producido','=',$this->id_producido)->sum('valor');
  }

  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }

  public function ajustes_producido(){
    return $this->hasManyThrough('App\AjusteProducido','App\DetalleProducido','id_producido','id_detalle_producido');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleProducido','id_producido','id_producido');
  }
  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function ajuste_temporal_producido(){
    return $this->HasMany('App\AjusteTemporalProducido','id_producido','id_producido');
  }

  public static function boot(){
    parent::boot();
    Producido::observe(new ProducidoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_producido;
  }


}
