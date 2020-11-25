<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\JuegoObserver;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Juego extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'juego';
  protected $primaryKey = 'id_juego';
  protected $visible = array(
    'id_juego','nombre_juego','cod_identificacion','cod_juego',
    'denominacion_contable','denominacion_juego','porcentaje_devolucion', 'escritorio','movil',
    'id_unidad_medida','id_tipo_moneda','id_categoria_juego','id_estado_juego','deleted_at'
  );
  public $timestamps = true;
  protected $dates = ['deleted_at'];
  protected $appends = array('cod_identificacion','casinos');

  public function getCodIdentificacionAttribute(){
    if($this->id_gli_soft != null){
      return GliSoft::find($this->id_gli_soft)->nro_archivo;}
      return null;
  }

  public function gliSoft(){
    return $this->belongsToMany('App\GliSoft','juego_glisoft','id_juego','id_gli_soft');
  }

  public function agregarGliSofts($garray,$ids=False){
    $arr = [];
    if(!$ids){
      foreach($garray as $g) $arr[] = $g->id_gli_soft;
    }
    else $arr = $garray;

    $this->gliSoft()->syncWithoutDetaching($arr);
  }
  public function setearGliSofts($garray,$ids=False){
    $arr = [];
    if(!$ids){
      foreach($garray as $g) $arr[] = $g->id_gli_soft;
    }
    else $arr = $garray;

    $this->gliSoft()->sync($arr);
  }

  public function tablasPago(){
    return $this->hasMany('App\TablaPago','id_juego','id_juego');
  }

  public function maquinas_juegos(){
     return $this->belongsToMany('App\Maquina','maquina_tiene_juego','id_juego','id_maquina')->withPivot('denominacion' , 'porcentaje_devolucion');;
  }

  public function getCasinosAttribute(){
    $casinos = DB::table('plataforma_tiene_juego')->select('plataforma_tiene_casino.id_casino')
    ->join('plataforma_tiene_casino','plataforma_tiene_juego.id_plataforma','=','plataforma_tiene_casino.id_plataforma')
    ->where('plataforma_tiene_juego.id_juego','=',$this->id_juego)
    ->distinct()->get();
    $ids = [];
    foreach($casinos as $c) $ids[]=$c->id_casino;
    return Casino::whereIn('casino.id_casino',$ids);
  }

  public function plataformas(){
    return $this->belongsToMany('App\Plataforma','plataforma_tiene_juego','id_juego','id_plataforma');
  }

  public function maquinas(){//En realidad obtiene las maquinas que lo tienen como activo.
    return $this->hasMany('App\Maquina','id_juego','id_juego');
  }

  public function pack(){
    return $this->belongsToMany('App\PackJuego','pack_tiene_juego','id_juego','id_pack');
  }

  public function unidad_medida(){
    return $this->belongsTo('App\UnidadMedida','id_unidad_medida','id_unidad_medida');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function categoria_juego(){
    return $this->belongsTo('App\CategoriaJuego','id_categoria_juego','id_categoria_juego');
  }

  public function estado_juego(){
    return $this->belongsTo('App\EstadoJuego','id_estado_juego','id_estado_juego');
  }

  public function logs(){
    return $this->hasMany('App\LogJuego','id_juego','id_juego');
  }

  public static function boot(){
    parent::boot();
    Juego::observe(new JuegoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_juego;
  }

}
