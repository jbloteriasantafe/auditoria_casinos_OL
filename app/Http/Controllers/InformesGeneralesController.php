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
  
  private function similarity($s1,$s2,$limit = 0.50){
    $s1 = preg_replace('/[^A-Za-z0-9\s]/', '', $s1);//Saco caracteres especiales
    $s2 = preg_replace('/[^A-Za-z0-9\s]/', '', $s2);
    $s1 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s1);//Saco conectores
    $s2 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s2);
    $s1 = preg_replace('/\s/', ' ', $s1);//Simplifico a espacios simples
    $s2 = preg_replace('/\s/', ' ', $s2);
    
    $MAX = max(strlen($s1),strlen($s2));
    $porcentaje_escrito = ($MAX - levenshtein($s1,$s2))/$MAX;
    
    $M1 = metaphone($s1);
    $M2 = metaphone($s2);
    $MAX = max(strlen($M1),strlen($M2));
    $porcentaje_pronunciado = ($MAX - levenshtein($M1,$M2))/$MAX;
    
    $porcentaje = 0.75*$porcentaje_escrito+0.25*$porcentaje_pronunciado;
    return $porcentaje < $limit? null : $porcentaje;
  }
  
  public function distribucionJugadores(Request $request){
    $cc = CacheController::getInstancia();
    $codigo = 'distribucionJugadores';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo,$subcodigo,3600);
    if(!is_null($cache)){
      return json_decode($cache->data,true);//true = retornar como arreglo en vez de objecto
    }
       
    $ret = [];
    
    $get_max_similarity = function($arr,$val){
      $max_similarity = null;
      $max_similarity_val = null;
      foreach($arr as $from => $to){
        $s = $this->similarity($val,$from);
        if(is_null($s)) continue;
        if($max_similarity === null || $s > $max_similarity){
          $max_similarity = $s;
          $max_similarity_val = $to;
        }
      }
      return [$max_similarity_val,$max_similarity];
    };
    
    $leer_archivo_conversion = function($filename){
      $ret = [];
      $fhandle = fopen(storage_path('app/'.$filename),'r');
      try{
        $header = true;
        while(($datos = fgetcsv($fhandle,'',',')) !== FALSE){
          if($header){
            $header = false;
            continue;
          }
          $ret[strtoupper(trim($datos[0]))] = strtoupper(trim($datos[1]));
        }
      }
      catch(\Exception $e){
        fclose($fhandle);
        throw $e;
      }
      fclose($fhandle);
      return $ret;
    };
    
    $totalizar = function($item){
      return $item->reduce(function($carry,$i){
        return $carry+$i->cantidad;
      },0);
    };
    
    $presentar_llave = function($item,$k){
      return [ucwords(strtolower($k)) => $item];
    };
    
    
    $provincia_a_provincia        = $leer_archivo_conversion('provincia_a_provincia.csv');
    $localidad_a_departamento     = $leer_archivo_conversion('localidad_a_departamento.csv');
    $distrito_a_departamento      = $leer_archivo_conversion('distrito_a_departamento.csv');
    $departamento_a_departamento  = $leer_archivo_conversion('departamento_a_departamento.csv');
        
    foreach(\App\Plataforma::all() as $plat){//El indice de la tabla es por plataforma por eso lo hago asi
      $BD = DB::table('jugador')
      ->selectRaw('TRIM(UPPER(provincia)) as provincia,TRIM(UPPER(localidad)) as localidad,COUNT(distinct codigo) as cantidad')
      ->whereNull('valido_hasta')
      ->where('id_plataforma','=',$plat->id_plataforma)
      ->groupBy(DB::raw('TRIM(UPPER(provincia)),TRIM(UPPER(localidad))'))
      ->get()
      ->groupBy(function(&$item) use ($get_max_similarity,$provincia_a_provincia){
        $s = $get_max_similarity($provincia_a_provincia,$item->provincia);
        $item->s = $s[1]; 
        return $s[0] !== null? $s[0] : 'NO ASIGNABLE / EXTERIOR';
      });
      
      $ret['provincias'][$plat->nombre] = $BD
      ->map($totalizar)
      ->mapWithKeys($presentar_llave);
      //continue;
    
      $ret['localidades'][$plat->nombre] = ($BD['SANTA FE'] ?? collect([]))
      ->groupBy(
        function($item) use ($get_max_similarity,$localidad_a_departamento,$distrito_a_departamento,$departamento_a_departamento)
        {      
          $lo = $get_max_similarity($localidad_a_departamento,$item->localidad); 
          $di = $get_max_similarity($distrito_a_departamento,$item->localidad); 
          $de = $get_max_similarity($departamento_a_departamento,$item->localidad); 
          $max = -1;
          $max_idx = null;
          $lista_s = [$lo,$di,$de];
          foreach($lista_s as $idx => $s){
            if(!is_null($s[1]) && $s[1] > $max){
              $max = $s[1];
              $max_idx = $idx;
            }
          }
          if(is_null($max_idx)) return 'NO ASIGNABLE / EXTERIOR';
          return $lista_s[$max_idx][0];
        }
      )
      ->map($totalizar)
      ->mapWithKeys($presentar_llave);
    }
    
    $cc->agregar($codigo,$subcodigo,json_encode($ret),['estado_jugadores']);
    
    return $ret;
  }
}

