<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CacheController;

class InformesGeneralesController extends Controller
{ 
  public function beneficiosMensuales(){
    return DB::table('beneficio_mensual as bm')
    ->selectRaw('p.nombre as plataforma,YEAR(fecha) as año, MONTH(fecha) as mes, beneficio')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->orderBy('fecha','asc')
    ->get();
  }

  public function beneficiosAnuales(){
    return DB::table('beneficio_mensual as bm')
    ->selectRaw('p.nombre as plataforma, SUM(beneficio) as beneficio')
    ->join('plataforma as p','p.id_plataforma','=','bm.id_plataforma')
    ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
    ->groupBy(DB::raw('p.id_plataforma'))
    ->orderByRaw('p.nombre asc')
    ->get();
  }

  public function jugadoresMensuales(){
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresMensuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }

    //@TODO: tal vez agregar una columna "jugadores" a producido_jugadores
    //para poder hacer esta query a 365 dias
    $ret = DB::table('plataforma as p')
    ->selectRaw('p.nombre as plataforma,rmpj.aniomes as aniomes, COUNT(distinct rmpj.jugador) as jugadores')
    ->join('resumen_mensual_producido_jugadores as rmpj','rmpj.id_plataforma','=','p.id_plataforma')
    ->whereRaw('TIMESTAMPDIFF(MONTH,rmpj.aniomes,CURRENT_DATE()) <= 12')
    ->groupBy(DB::raw('p.id_plataforma,rmpj.aniomes'))
    ->orderByRaw('p.nombre asc,rmpj.aniomes asc')
    ->get();
    
    $ret->map(function(&$am){
      $f = explode('-',$am->aniomes);
      $am->año = $f[0];
      $am->mes = $f[1];
    });

    $cc->agregar($codigo,$subcodigo,json_encode($ret),['producido_jugadores','detalle_producido_jugadores','plataforma']);
    return $ret;
  }

  public function jugadoresAnuales(){
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresAnuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }
    
    $ret = DB::table('plataforma as p')
    ->selectRaw('p.nombre as plataforma, COUNT(distinct rmpj.jugador) as jugadores')
    ->join('resumen_mensual_producido_jugadores as rmpj','rmpj.id_plataforma','=','p.id_plataforma')
    ->whereRaw('TIMESTAMPDIFF(MONTH,rmpj.aniomes,CURRENT_DATE()) <= 12')
    ->groupBy(DB::raw('p.id_plataforma'))
    ->orderByRaw('p.nombre asc')
    ->get();

    $cc->agregar($codigo,$subcodigo,json_encode($ret),['producido_jugadores','detalle_producido_jugadores','plataforma']);
    return $ret;
  }

  public function estadosDias(){
    $estado_dia = [];
    {
      $fecha_mas_vieja_b = DB::table('beneficio')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $fecha_mas_vieja_p = DB::table('producido')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $fecha_mas_vieja_pj = DB::table('producido_jugadores')->select('fecha')->orderBy('fecha','asc')->take(1)->pluck('fecha')->first();
      $f = min($fecha_mas_vieja_b?: date('Y-m-d'),$fecha_mas_vieja_p?: date('Y-m-d'),$fecha_mas_vieja_pj?: date('Y-m-d'));
      $fecha_actual = date('Y-m-d');
      while($f != $fecha_actual){
        $estado_dia[$f] = $this->estado_dia($f)['porcentaje'];
        $f = date('Y-m-d',strtotime($f.' +1 day'));
      }
      $estado_dia[$f] = $this->estado_dia($f)['porcentaje'];
    }
    return array_reverse($estado_dia);
  }
  
  public function infoAuditoria($dia){
    $aux = $this->estado_dia($dia);
    
    foreach($aux['queryes'] as $tipo => $q){
      $aux['queryes'][$tipo] = $q->get()->pluck('codigo');
    }
    
    return array_merge(['total' => $aux['porcentaje']],$aux['queryes']);
  }
  
  private function estado_dia($f){
    $MA = [1];//@HACK: sacar dolares de la BD si no se usa?
    $PA = DB::table('plataforma')->get()->pluck('id_plataforma');
    
    $importaciones = $this->importaciones($f,$MA,$PA);
    $queryes = $importaciones['queryes'];
    
    $cantidad = array_reduce(
      $queryes,
      function($carry,$q){return $carry+(clone $q)->count();},//clono pq ->count modifica la query
      0
    );
    
    $porcentaje = $cantidad / $importaciones['maximas_posibles'];
    return compact('queryes','porcentaje');
  }
  
  private function importaciones($dia,$monedas_habilitadas,$plataformas_habilitadas){
    $queryes = [];
    $maximas_posibles = 0;
    
    $queryes['producido'] = DB::table('producido')
    ->join('plataforma as plat','plat.id_plataforma','=','producido.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($dia)))
    ->whereIn('id_tipo_moneda',$monedas_habilitadas)
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($monedas_habilitadas)*count($plataformas_habilitadas);
    
    $queryes['producido_jugadores'] = DB::table('producido_jugadores')
    ->join('plataforma as plat','plat.id_plataforma','=','producido_jugadores.id_plataforma')
    ->where('fecha',date('Y-m-d',strtotime($dia)))
    ->whereIn('id_tipo_moneda',$monedas_habilitadas)
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($monedas_habilitadas)*count($plataformas_habilitadas);
    
    $queryes['beneficio'] = DB::table('beneficio as b')
    ->join('beneficio_mensual as bm','bm.id_beneficio_mensual','=','b.id_beneficio_mensual')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',date('Y-m-d',strtotime($dia)))
    ->whereIn('id_tipo_moneda',$monedas_habilitadas)
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($monedas_habilitadas)*count($plataformas_habilitadas);
    
    $queryes['beneficio_poker'] = DB::table('beneficio_poker as b')
    ->join('beneficio_mensual_poker as bm','bm.id_beneficio_mensual_poker','=','b.id_beneficio_mensual_poker')
    ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
    ->where('b.fecha',date('Y-m-d',strtotime($dia)))
    ->whereIn('id_tipo_moneda',$monedas_habilitadas)
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($monedas_habilitadas)*count($plataformas_habilitadas);
    
    $queryes['estado_jugadores'] = DB::table('importacion_estado_jugador as iej')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',date('Y-m-d',strtotime($dia)))
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($plataformas_habilitadas);
    
    $queryes['estado_juegos'] = DB::table('importacion_estado_juego as iej')
    ->join('plataforma as plat','plat.id_plataforma','=','iej.id_plataforma')
    ->where('iej.fecha_importacion',date('Y-m-d',strtotime($dia)))
    ->whereIn('plat.id_plataforma',$plataformas_habilitadas);
    $maximas_posibles += count($plataformas_habilitadas);
    
    return compact('queryes','maximas_posibles');
  }
  
  public function distribucionJugadores(Request $request){
    $provincias = ["Misiones","San Luis","San Juan","Entre Ríos","Santa Cruz","Río Negro","Chubut","Córdoba","Mendoza","La Rioja","Catamarca","La Pampa","Santiago del Estero","Corrientes","Santa Fe","Tucumán","Neuquén","Salta","Chaco","Formosa","Jujuy","Ciudad de Buenos Aires","Buenos Aires","Tierra del Fuego"];
    $SIMILARITY_NULL_LIMIT = 71;  
    
    $ret = [];
        
    foreach(\App\Plataforma::all() as $plat){//El indice de la tabla es por plataforma por eso lo hago asi
      $provincias_bd = DB::table('jugador')
      ->selectRaw('TRIM(UPPER(provincia)) as provincia,COUNT(distinct codigo) as cantidad')
      ->whereNull('valido_hasta')
      ->where('id_plataforma','=',$plat->id_plataforma)
      ->groupBy(DB::raw('TRIM(UPPER(provincia))'))
      ->get()
      ->groupBy(function($item) use ($provincias,$SIMILARITY_NULL_LIMIT){
        $max_similarity = null;
        $max_similarity_idx = null;
        foreach($provincias as $pidx => $p){
          $s;
          similar_text(metaphone($item->provincia),metaphone($p),$s);
          if($s < $SIMILARITY_NULL_LIMIT) continue;
          if($max_similarity === null || $s > $max_similarity){
            $max_similarity = $s;
            $max_similarity_idx = $pidx;
          }
        }
        
        return $max_similarity_idx !== null? $provincias[$max_similarity_idx] : 'EXTERIOR';
      })
      ->map(function($item){
        return $item->reduce(function($carry,$i){
          return $carry+$i->cantidad;
        },0);
      })
      ->sort()->reverse();
      
      $ret[$plat->nombre] = $provincias_bd;
    }
    
    return $ret;
  }
}

