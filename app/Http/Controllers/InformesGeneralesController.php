<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CacheController;

class InformesGeneralesController extends Controller
{
  public function beneficiosMensuales()
  {
    return DB::table('beneficio_mensual as bm')
      ->selectRaw('p.nombre as plataforma,YEAR(fecha) as año, MONTH(fecha) as mes, beneficio')
      ->join('plataforma as p', 'p.id_plataforma', '=', 'bm.id_plataforma')
      ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
      ->orderBy('fecha', 'asc')
      ->get();
  }

  public function beneficiosAnuales()
  {
    return DB::table('beneficio_mensual as bm')
      ->selectRaw('p.nombre as plataforma, SUM(beneficio) as beneficio')
      ->join('plataforma as p', 'p.id_plataforma', '=', 'bm.id_plataforma')
      ->whereRaw('DATEDIFF(CURRENT_DATE(),fecha) <= 365')
      ->groupBy(DB::raw('p.id_plataforma'))
      ->orderByRaw('p.nombre asc')
      ->get();
  }

  public function jugadoresMensuales()
  {
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresMensuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo, $subcodigo, 3600);
    if (!is_null($cache)) {
      return json_decode($cache->data, true);//true = retornar como arreglo en vez de objecto
    }

    //@TODO: tal vez agregar una columna "jugadores" a producido_jugadores
    //para poder hacer esta query a 365 dias
    $ret = DB::table('plataforma as p')
      ->selectRaw('p.nombre as plataforma,rmpj.aniomes as aniomes, COUNT(distinct rmpj.jugador) as jugadores')
      ->join('resumen_mensual_producido_jugadores as rmpj', 'rmpj.id_plataforma', '=', 'p.id_plataforma')
      ->whereRaw('TIMESTAMPDIFF(MONTH,rmpj.aniomes,CURRENT_DATE()) <= 12')
      ->groupBy(DB::raw('p.id_plataforma,rmpj.aniomes'))
      ->orderByRaw('p.nombre asc,rmpj.aniomes asc')
      ->get();

    $ret->map(function (&$am) {
      $f = explode('-', $am->aniomes);
      $am->año = $f[0];
      $am->mes = $f[1];
    });

    $cc->agregar($codigo, $subcodigo, json_encode($ret), ['producido_jugadores', 'detalle_producido_jugadores', 'plataforma']);
    return $ret;
  }

  public function jugadoresAnuales()
  {
    $cc = CacheController::getInstancia();
    $codigo = 'jugadoresAnuales';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo, $subcodigo, 3600);
    if (!is_null($cache)) {
      return json_decode($cache->data, true);//true = retornar como arreglo en vez de objecto
    }

    $ret = DB::table('plataforma as p')
      ->selectRaw('p.nombre as plataforma, COUNT(distinct rmpj.jugador) as jugadores')
      ->join('resumen_mensual_producido_jugadores as rmpj', 'rmpj.id_plataforma', '=', 'p.id_plataforma')
      ->whereRaw('TIMESTAMPDIFF(MONTH,rmpj.aniomes,CURRENT_DATE()) <= 12')
      ->groupBy(DB::raw('p.id_plataforma'))
      ->orderByRaw('p.nombre asc')
      ->get();

    $cc->agregar($codigo, $subcodigo, json_encode($ret), ['producido_jugadores', 'detalle_producido_jugadores', 'plataforma']);
    return $ret;
  }
    
  public function estadosDias()
  {
    DB::statement("DROP TEMPORARY TABLE IF EXISTS temp_estados_dias");
    
    DB::statement("CREATE TEMPORARY TABLE temp_estados_dias AS
    SELECT  tmv.descripcion as moneda,
            fv.fecha,
            tv.tbl,
            pv.codigo as plataforma,
            i.tbl IS NOT NULL as importado
    FROM plataforma as pv
    CROSS JOIN tipo_moneda as tmv
    CROSS JOIN  (
        SELECT * 
        FROM (
          SELECT fecha
          FROM producido
          
          UNION SELECT fecha
          FROM producido_jugadores
          
          UNION SELECT fecha
          FROM beneficio
          
          UNION SELECT fecha
          FROM beneficio_poker
          
          UNION SELECT fecha_importacion as fecha
          FROM importacion_estado_jugador
          
          UNION SELECT fecha_importacion as fecha
          FROM importacion_estado_juego
        ) aux
        WHERE aux.fecha IS NOT NULL
        ORDER BY aux.fecha ASC
    ) as fv
    CROSS JOIN (
      SELECT 'producido' as tbl UNION ALL
      SELECT 'producido_jugadores' UNION ALL
      SELECT 'beneficio' UNION ALL
      SELECT 'beneficio_poker' UNION ALL
      SELECT 'estado_jugadores' UNION ALL
      SELECT 'estado_juegos'
    ) as tv
    LEFT JOIN (
      SELECT 'producido' as tbl, fecha, id_tipo_moneda, id_plataforma
      FROM producido
      
      UNION ALL
      SELECT 'producido_jugadores' as tbl, fecha, id_tipo_moneda, id_plataforma
      FROM producido_jugadores
      
      UNION ALL
      SELECT 'beneficio' as tbl, b.fecha, bm.id_tipo_moneda, bm.id_plataforma
      FROM beneficio as b
      JOIN beneficio_mensual as bm ON bm.id_beneficio_mensual = b.id_beneficio_mensual
      
      UNION ALL
      SELECT 'beneficio_poker' as tbl, b.fecha, bm.id_tipo_moneda, bm.id_plataforma
      FROM beneficio_poker as b
      JOIN beneficio_mensual_poker as bm ON bm.id_beneficio_mensual_poker = b.id_beneficio_mensual_poker
      
      UNION ALL
      SELECT 'estado_jugadores' as tbl, iej.fecha_importacion as fecha, tm.id_tipo_moneda, iej.id_plataforma
      FROM importacion_estado_jugador as iej, tipo_moneda as tm
      
      UNION ALL
      SELECT 'estado_juegos' as tbl, iej.fecha_importacion as fecha, tm.id_tipo_moneda, iej.id_plataforma
      FROM importacion_estado_juego as iej, tipo_moneda as tm
    ) as i 
      ON  i.id_plataforma = pv.id_plataforma
      AND i.id_tipo_moneda = tmv.id_tipo_moneda
      AND i.fecha = fv.fecha
      AND i.tbl = tv.tbl");
    
    $fecha_actual = new \DateTimeImmutable();
    $fecha_minima = DB::select("
      SELECT MIN(fecha) as fecha
      FROM temp_estados_dias
      GROUP BY 'constant'
    ");
    $fecha_minima = count($fecha_minima)? new \DateTimeImmutable($fecha_minima[0]->fecha) : $fecha_actual;
    
    $estadosDias = collect(DB::select("
      SELECT moneda, fecha, 
        COUNT(*) as posibles, 
        SUM(importado) as importados, 
        SUM(importado)/COUNT(*) as porcentaje,
        CONCAT('[',GROUP_CONCAT(
          IF(importado,JSON_OBJECT(tbl,plataforma),NULL)
          SEPARATOR ','
        ),']') as detalle
      FROM temp_estados_dias
      GROUP BY moneda, fecha
    "))->groupBy('moneda')->map(function($edm){
      return $edm->keyBy('fecha');
    });
    
    //Saco la lista de posibles keys de la tabla... para evitar otro lugar para modificar
    $tbls = collect(DB::select("
      SELECT  moneda,
              CONCAT('[',GROUP_CONCAT(
                DISTINCT
                JSON_QUOTE(tbl)
                SEPARATOR ','
              ),']') as tblsm
      FROM temp_estados_dias
      GROUP BY moneda        
    "))->keyBy('moneda');
    
    $tbls = $tbls->map(function($tblsm){
      return json_decode($tblsm->tblsm,true);
    });
    
    $ret = [];
    $interval_1dia = new \DateInterval('P1D');
    $fechas = [];
    for($f = $fecha_minima;$f <= $fecha_actual;$f = $f->add($interval_1dia)){
      $fechas[] = $f;
    }
    
    foreach($tbls as $moneda => $tblsm){
      $posibles = count($tblsm);
      
      $retm = [
        'tbls' => $tblsm,
        'fecha_minima' => $fecha_minima,
        'fecha_maxima' => $fecha_actual,
        'estadosDias' => []
      ];
      
      $eDm = [];
      foreach($fechas as $f){
        $fstr = $f->format('Y-m-d');
        $eDm[$fstr] = $estadosDias[$moneda][$fstr] ?? (object)[
          'posibles' => $posibles,
          'importados' => 0,
          'porcentaje' => 0,
          'detalle' => '[]'
        ];
        $eDm[$fstr]->detalle = json_decode($eDm[$fstr]->detalle ?? '[]',true);
        $eDm_fstr_detalle = [];
        foreach($eDm[$fstr]->detalle as $d){
          foreach($d as $k => $v){
            $eDm_fstr_detalle[$k] = $eDm_fstr_detalle[$k] ?? [];
            $eDm_fstr_detalle[$k][] = $v;
          }
        }
        $eDm[$fstr]->detalle = $eDm_fstr_detalle;
      }
      $retm['estadosDias'] = $eDm;
      
      $ret[$moneda] = $retm;
    }
    return $ret;
  }

  private function similarity($s1, $s2)
  {
    $s1 = preg_replace('/[^A-Za-z0-9\s]/', '', $s1);//Saco caracteres especiales
    $s2 = preg_replace('/[^A-Za-z0-9\s]/', '', $s2);
    $s1 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s1);//Saco conectores
    $s2 = preg_replace('/(^|\s)(de|del|el|la|lo|los|las|en)($|\s)/', '', $s2);
    $s1 = preg_replace('/\s/', ' ', $s1);//Simplifico a espacios simples
    $s2 = preg_replace('/\s/', ' ', $s2);

    $sMAX = max(strlen($s1), strlen($s2));
    if ($sMAX == 0)
      return 1.0;
    $porcentaje_escrito = ($sMAX - levenshtein($s1, $s2)) / $sMAX;

    $m1 = metaphone($s1);
    $m2 = metaphone($s2);
    $mMAX = max(strlen($m1), strlen($m1));
    if ($mMAX == 0)
      return 1.0;
    $porcentaje_pronunciado = ($mMAX - levenshtein($m1, $m2)) / $mMAX;

    return 0.75 * $porcentaje_escrito + 0.25 * $porcentaje_pronunciado;
  }

  private $STR_NO_ASIGNABLE = 'NO ASIGNABLE / EXTERIOR';
  public function distribucionJugadores(Request $request)
  {
    $cc = CacheController::getInstancia();
    $codigo = 'distribucionJugadores';
    $subcodigo = '';
    //$cc->invalidar($codigo,$subcodigo);//LINEA PARA PROBAR Y QUE NO RETORNE RESULTADO CACHEADO
    $cache = $cc->buscarUltimoDentroDeSegundos($codigo, $subcodigo, 3600);
    if (!is_null($cache)) {
      return json_decode($cache->data, true);//true = retornar como arreglo en vez de objecto
    }

    $lista_conversiones_provs = $lista_conversiones_deps = null; {
      $leer_archivo_conversion = function ($filename) {
        $ret = [];
        $fhandle = fopen(storage_path('app/' . $filename), 'r');
        try {
          $header = true;
          while (($datos = fgetcsv($fhandle, '', ',')) !== FALSE) {
            if ($header) {
              $header = false;
              continue;
            }
            $ret[strtoupper(trim($datos[0]))] = strtoupper(trim($datos[1]));
          }
        } catch (\Exception $e) {
          fclose($fhandle);
          throw $e;
        }
        fclose($fhandle);
        return $ret;
      };

      $lista_conversiones_provs = [
        [$leer_archivo_conversion('provincia_a_provincia.csv'), 0.5]//Lista y porcentaje minimo de coincidiencia
      ];

      $lista_conversiones_deps = [
        [$leer_archivo_conversion('localidad_a_departamento.csv'), 0.5],
        [$leer_archivo_conversion('distrito_a_departamento.csv'), 0.5],
        [$leer_archivo_conversion('departamento_a_departamento.csv'), 0.5],
        [$leer_archivo_conversion('miscelaneos_a_departamento.csv'), 0.7],
        [$leer_archivo_conversion('codigopostal_a_departamento.csv'), 0.9]
      ];
    }

    $f_agrupar = function ($string_a_convertir, $lista_conversiones) {
      $lista_s = [];
      foreach ($lista_conversiones as $lista_y_porcentaje) {
        $max_s = [-1, $this->STR_NO_ASIGNABLE];
        foreach ($lista_y_porcentaje[0] as $from => $to) {
          $s = $this->similarity($string_a_convertir, $from);
          if ($s >= $lista_y_porcentaje[1] && $s > $max_s[0]) {
            $max_s[0] = $s;
            $max_s[1] = $to;
          }
        }
        $lista_s[] = $max_s;
      }

      //Me quedo con la maxima afinidad
      return array_reduce($lista_s, function ($max, $item) {
        return ($item[0] > $max[0]) ? $item : $max;
      }, [-2, $this->STR_NO_ASIGNABLE])[1];
    };

    $totalizar = function ($item) {
      return $item->reduce(function ($carry, $i) {
        return $carry + $i->cantidad;
      }, 0);
    };

    $presentar_llave = function ($item, $k) {
      return [ucwords(strtolower($k)) => $item];
    };

    $ret = [];
    foreach (\App\Plataforma::all() as $plat) {//El indice de la tabla es por plataforma por eso lo hago asi
      $BD = DB::table('jugador')
        ->selectRaw('TRIM(UPPER(provincia)) as provincia,TRIM(UPPER(localidad)) as localidad,COUNT(distinct codigo) as cantidad')
        ->whereNull('valido_hasta')
        ->where('id_plataforma', '=', $plat->id_plataforma)
        ->groupBy(DB::raw('TRIM(UPPER(provincia)),TRIM(UPPER(localidad))'))
        ->whereRaw('jugador.codigo IN (
        SELECT DISTINCT rm.jugador
        FROM resumen_mensual_producido_jugadores as rm
        WHERE rm.id_plataforma = jugador.id_plataforma AND TIMESTAMPDIFF(MONTH,rm.aniomes,CURDATE()) < 12
      )')
        ->get()
        ->groupBy(function (&$item) use ($f_agrupar, $lista_conversiones_provs) {
          return $f_agrupar($item->provincia, $lista_conversiones_provs);
        });

      $ret['provincias'][$plat->nombre] = $BD
        ->map($totalizar)
        ->mapWithKeys($presentar_llave);
      //continue;

      $ret['departamentos'][$plat->nombre] = ($BD['SANTA FE'] ?? collect([]))
        ->groupBy(function (&$item) use ($f_agrupar, $lista_conversiones_deps) {
          return $f_agrupar($item->localidad, $lista_conversiones_deps);
        })
        ->map($totalizar)
        ->mapWithKeys($presentar_llave);
    }

    $cc->agregar($codigo, $subcodigo, json_encode($ret), ['estado_jugadores']);

    return $ret;
  }


  public function pdevAnualBoxplot()
  {
    $fecha_limite = date('Y-m-d', strtotime('-1 year'));

    $datos = DB::table('producido as p')
      ->join('plataforma as pl', 'pl.id_plataforma', '=', 'p.id_plataforma')
      ->selectRaw('pl.nombre as plataforma, p.fecha, p.premio, p.apuesta')
      ->where('p.fecha', '>=', $fecha_limite)
      ->where('p.apuesta', '>', 0)
      ->orderBy('p.fecha', 'asc')
      ->get();

    $agrupado = [];
    $categorias = [];

    foreach ($datos as $d) {
      $mes = date('Y-m', strtotime($d->fecha));
      $pdev_dia = ($d->premio / $d->apuesta) * 100;

      $agrupado[$d->plataforma][$mes][] = $pdev_dia;

      if (!in_array($mes, $categorias))
        $categorias[] = $mes;
    }
    sort($categorias);

    $calcular_caja = function ($valores) {
      if (empty($valores))
        return [null, null, null, null, null];
      sort($valores);
      $count = count($valores);

      $percentil = function ($p) use ($valores, $count) {
        $pos = ($count - 1) * $p;
        $base = floor($pos);
        $rest = $pos - $base;
        if (isset($valores[$base + 1])) {
          return $valores[$base] + $rest * ($valores[$base + 1] - $valores[$base]);
        }
        return $valores[$base];
      };

      return [
        round($valores[0], 2),           // Min (Low)
        round($percentil(0.25), 2),      // Q1
        round($percentil(0.50), 2),      // Mediana
        round($percentil(0.75), 2),      // Q3
        round($valores[$count - 1], 2)   // Max (High)
      ];
    };

    $series = [];
    foreach ($agrupado as $plat => $meses) {
      $data = [];
      foreach ($categorias as $cat) {
        if (isset($meses[$cat])) {
          $data[] = $calcular_caja($meses[$cat]);
        } else {
          $data[] = [null, null, null, null, null];
        }
      }

      if (stripos($plat, 'CityCenter') !== false) {
        $colorHex = '#5855d6';
        $fillColor = 'rgba(88, 85, 214, 0.4)';
      } else {
        $colorHex = '#2cbaff';
        $fillColor = 'rgba(44, 186, 255, 0.4)';
      }

      $series[] = [
        'name' => $plat,
        'data' => $data,
        'fillColor' => $colorHex,
        'color' => $fillColor
      ];
    }

    return response()->json([
      'categorias' => $categorias,
      'series' => $series
    ]);
  }

  public function holdMensual()
  {
    $fecha_limite = date('Y-m-d', strtotime('-1 year'));

    $datos = DB::table('beneficio_mensual as bm')
      ->selectRaw('pl.nombre as plataforma, YEAR(bm.fecha) as anio, MONTH(bm.fecha) as mes, 
                   SUM(b.beneficio * IFNULL(c.valor, 1)) as beneficio, 
                   SUM(p.apuesta * IFNULL(c.valor, 1)) as apuesta')
      ->join('plataforma as pl', 'pl.id_plataforma', '=', 'bm.id_plataforma')
      ->join('beneficio as b', 'b.id_beneficio_mensual', '=', 'bm.id_beneficio_mensual')
      ->join('producido as p', function ($join) {
        $join->on('p.fecha', '=', 'b.fecha')
             ->on('p.id_plataforma', '=', 'bm.id_plataforma')
             ->on('p.id_tipo_moneda', '=', 'bm.id_tipo_moneda');
      })
      ->leftJoin('cotizacion as c', function ($join) {
        $join->on('c.fecha', '=', 'b.fecha')
             ->on('c.id_tipo_moneda', '=', 'bm.id_tipo_moneda');
      })
      ->where('bm.fecha', '>=', $fecha_limite)
      ->groupBy('pl.nombre', 'anio', 'mes')
      ->orderBy('anio', 'asc')
      ->orderBy('mes', 'asc')
      ->get();

    $data = [];
    $categorias = [];

    foreach($datos as $d) {
        $mes_fmt = date('Y-m', strtotime($d->anio.'-'.$d->mes.'-01'));
        
        $hold = $d->apuesta > 0 ? ($d->beneficio / $d->apuesta) * 100 : 0;
        
        $data[$d->plataforma][$mes_fmt] = round($hold, 2);

        if(!in_array($mes_fmt, $categorias)) $categorias[] = $mes_fmt;
    }
    sort($categorias);

    // Ordenar Data: Bplay primero, luego CityCenter
    uksort($data, function($a, $b) {
        // Si a contiene 'bplay', va antes (-1)
        if (stripos($a, 'bplay') !== false) return -1;
        if (stripos($b, 'bplay') !== false) return 1;
        return strcmp($a, $b);
    });

    $series = [];
    foreach($data as $plat => $meses) {
        $valores = [];
        foreach($categorias as $cat) {
            $valores[] = $meses[$cat] ?? null; 
        }

        if (stripos($plat, 'CityCenter') !== false) {
            $color = '#5855d6';
        } else {
            $color = '#2cbaff';
        }

        $series[] = [
            'name' => $plat,
            'data' => $valores, 
            'color' => $color,
            'fillColor' => $color 
        ];
    }

    return response()->json([
      'categorias' => $categorias,
      'series' => $series
    ]);
  }

  public function arpuMensual()
  {
    $fecha_limite = date('Y-m-d', strtotime('-1 year'));

    $datos = DB::table('beneficio_mensual as bm')
      ->selectRaw('pl.nombre as plataforma, YEAR(bm.fecha) as anio, MONTH(bm.fecha) as mes, 
                   SUM(p.apuesta * IFNULL(c.valor, 1)) as apuesta, SUM(b.jugadores) as jugadores')
      ->join('plataforma as pl', 'pl.id_plataforma', '=', 'bm.id_plataforma')
      ->join('beneficio as b', 'b.id_beneficio_mensual', '=', 'bm.id_beneficio_mensual')
      ->join('producido as p', function ($join) {
        $join->on('p.fecha', '=', 'b.fecha')
             ->on('p.id_plataforma', '=', 'bm.id_plataforma')
             ->on('p.id_tipo_moneda', '=', 'bm.id_tipo_moneda');
      })
      ->leftJoin('cotizacion as c', function ($join) {
        $join->on('c.fecha', '=', 'b.fecha')
             ->on('c.id_tipo_moneda', '=', 'bm.id_tipo_moneda');
      })
      ->where('bm.fecha', '>=', $fecha_limite)
      ->groupBy('pl.nombre', 'anio', 'mes')
      ->orderBy('anio', 'asc')
      ->orderBy('mes', 'asc')
      ->get();

    $data = [];
    $categorias = [];

    foreach($datos as $d) {
        $mes_fmt = date('Y-m', strtotime($d->anio.'-'.$d->mes.'-01'));
        
        $arpu = $d->jugadores > 0 ? ($d->apuesta / $d->jugadores) : 0;
        
        $data[$d->plataforma][$mes_fmt] = round($arpu, 2);

        if(!in_array($mes_fmt, $categorias)) $categorias[] = $mes_fmt;
    }
    sort($categorias);

    // Ordenar Data: Bplay primero, luego CityCenter
    uksort($data, function($a, $b) {
        // Si a contiene 'bplay', va antes (-1)
        if (stripos($a, 'bplay') !== false) return -1;
        if (stripos($b, 'bplay') !== false) return 1;
        return strcmp($a, $b);
    });

    $series = [];
    foreach($data as $plat => $meses) {
        $valores = [];
        $x_values = []; 
        $y_values = []; 
        
        $i = 0;
        foreach($categorias as $cat) {
            $val = $meses[$cat] ?? 0;
            $valores[] = $val;
            
            $x_values[] = $i;
            $y_values[] = $val;
            $i++;
        }

        if (stripos($plat, 'CityCenter') !== false) {
            $color = '#5855d6';
        } else {
            $color = '#2cbaff';
        }

        $series[] = [
            'type' => 'column',
            'name' => $plat,
            'data' => $valores,
            'color' => $color
        ];

        $n = count($x_values);
        if ($n > 1) {
            $sumX = array_sum($x_values);
            $sumY = array_sum($y_values);
            $sumXY = 0;
            $sumXX = 0;
            for($j=0; $j<$n; $j++) {
                $sumXY += $x_values[$j] * $y_values[$j];
                $sumXX += $x_values[$j] * $x_values[$j];
            }
            
            $denom = ($n * $sumXX - $sumX * $sumX);
            if($denom != 0) {
              $slope = ($n * $sumXY - $sumX * $sumY) / $denom;
              $intercept = ($sumY - $slope * $sumX) / $n;

              $tendencia_data = [];
              for($j=0; $j<$n; $j++) {
                  $tendencia_data[] = round($slope * $j + $intercept, 2);
              }

              $series[] = [
                  'type' => 'spline',
                  'name' => $plat . ' (Tendencia)',
                  'data' => $tendencia_data,
                  'color' => $color,
                  'dashStyle' => 'ShortDot',
                  'marker' => ['enabled' => false],
                  'enableMouseTracking' => false,
                  'showInLegend' => false
              ];
            }
        }
    }

    return response()->json([
      'categorias' => $categorias,
      'series' => $series
    ]);
  }

}
