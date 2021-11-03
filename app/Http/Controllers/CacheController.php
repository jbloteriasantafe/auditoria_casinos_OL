<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Cache;

class CacheController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new CacheController();
      }
      return self::$instance;
  }
  public function buscar($codigo,$subcodigo = null,$get = true){
    $ret = Cache::where('codigo','=',$codigo)->orderBy('creado','desc');
    if(!is_null($subcodigo)){
      $ret = $ret->where('subcodigo','=',$subcodigo);
    }
    if($get){
      $ret = $ret->get();
    }
    return $ret;
  }
  public function agregar($codigo,$subcodigo,$data,$dependencias = []){
    $cache = new Cache;
    $cache->codigo = $codigo;
    $cache->subcodigo = $subcodigo;
    $cache->data = $data;
    $cache->creado = date('Y-m-d H:i:s');
    $cache->dependencias = '|'.implode('|',$dependencias).'|';
    $cache->save();
    return $this;
  }
  public function invalidar($codigo,$subcodigo = null){
    $this->buscar($codigo,$subcodigo,false)->delete();
    return $this;
  }
  public function buscarUnico($codigo,$subcodigo){
    $cache = $this->buscar($codigo,$subcodigo);
    if(count($cache) == 1){
      return $cache->first();
    }
    $this->invalidar($codigo,$subcodigo);
    return null;
  }
  public function buscarUnicoDentroDeSegundos($codigo,$subcodigo,$segundos){
    $cache = $this->buscarUnico($codigo,$subcodigo);
    if(!is_null($cache)){
      $s = strtotime(date('Y-m-d H:i:s')) - strtotime($cache->creado);
      if($s < $segundos){//Si esta dentro de la hora retorno lo cacheado
        return $cache;
      }
    }
    //Si no, borro lo cacheado
    $this->invalidar($codigo,$subcodigo);
    return null;
  }
  public function buscarDependientes($codigo){
    return Cache::where('dependencias','LIKE','%|'.$codigo.'|%')->get();
  }
  public function invalidarDependientes($codigo){
    $caches = $this->buscarDependientes($codigo);
    if(empty($caches)) return false;
    foreach($caches as $c) $c->delete();
    return true;
  }
}
