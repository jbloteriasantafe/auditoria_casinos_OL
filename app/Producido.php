<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ProducidoObserver;

class Producido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido';
  protected $primaryKey = 'id_producido';
  protected $visible = array('id_producido','fecha','beneficio_calculado','id_tipo_moneda','cant_juegos_forzados','id_juegos_forzados','id_plataforma');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function getBeneficioCalculadoAttribute(){
    $beneficio = Beneficio::where([['fecha',$this->fecha],['id_tipo_moneda',$this->id_tipo_moneda],['id_casino',$this->id_casino]])->first();
    if($beneficio!=""){
      $ajuste = ($beneficio->ajuste_beneficio != null) ? $beneficio->ajuste_beneficio->valor : 0;
    }else{
      $ajuste =0;
    }
    return DetalleProducido::where('id_producido','=',$this->id_producido)->sum('valor') + $ajuste;
  }

  public function producido(){
    return $this->belongsTo('App\Producido','id_plataforma','id_plataforma');
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
