<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Validator;

class BackOfficeController extends Controller {
  //Por algun motivo, las vistas pueden ser exponencialmente mas lentas que una
  //query directa, por eso no queryiero una vista sino una raw query
  //Esto puede evitarse usando el ALGORITHM MERGE de MySQL 8.0
  //Pero para ser rapido en 5.7 hago esto... Octavio 2023-09-12
  //https://dev.mysql.com/doc/refman/8.0/en/derived-table-optimization.html
  //https://stackoverflow.com/questions/62832483/mysql-view-is-very-slow-why

  private $selectComunVals_internal_cache = [];
  private function selectComunVals_internal($tabla,$tabla_valor,$columna_valor,$id_valor){
    $k = implode('|',func_get_args());
    if(!array_key_exists($k,$this->selectComunVals_internal_cache)){
      $this->selectComunVals_internal_cache[$k] = DB::table($tabla)
      ->selectRaw("$tabla_valor.$id_valor as id,$tabla_valor.$columna_valor as valor")->distinct()
      ->join($tabla_valor,"$tabla_valor.$id_valor",'=',"$tabla.$id_valor")
      ->orderby("$tabla_valor.$columna_valor",'asc')
      ->get();
    }
    return $this->selectComunVals_internal_cache[$k];
  }
  
  private function selectPlataformaVals($tabla){
    return $this->selectComunVals_internal($tabla,'plataforma','nombre','id_plataforma');
  }
  private function selectTipoMonedaVals($tabla){
    return $this->selectComunVals_internal($tabla,'tipo_moneda','descripcion','id_tipo_moneda');
  }
  
  private $vistas = null;
  private $mes_actual = [null,null];
  function __construct(){
    $hoy = date('Y-m');
    {
      $inicio_mes = $hoy.'-01';
      $fin_mes=$hoy.'-';
      {
        $hoyarr = explode('-',$hoy);
        $fin_mes .= cal_days_in_month(CAL_GREGORIAN,intval($hoyarr[1]),intval($hoyarr[0]));
      }
      $this->mes_actual[0] = $inicio_mes;
      $this->mes_actual[1] = $fin_mes;
    }
    //Directamente vinculado con 'cols', no cambiar el orden si no se cambia el orden de las columnas
    //select, alias, tipo para formateo, tipo de buscador, cantidad de buscadores y valores por defecto, valores (solo select)
    $cols_indexes = ['BO_SELECT','BO_ALIAS','BO_FMT','BO_TIPO','BO_DEFAULTS','BO_VALUES'];
    foreach($cols_indexes as $val => $constant){
      define($constant,$val);
    }
    
    $this->vistas = [
      'beneficio' => [
        'cols' => [
          ['b.fecha','fecha','string','input_date',$this->mes_actual],
          ['plat.codigo','plataforma','string','select',[0],$this->selectPlataformaVals('beneficio_mensual')],
          ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('beneficio_mensual')],
          ['b.jugadores','jugadores','integer'],
          ['b.depositos','depositos','numeric'],
          ['b.retiros','retiros','numeric'],
          ['b.apuesta','apuesta','numeric'],
          ['b.premio','premio','numeric'],
          ['b.ajuste','ajustes_informados','numeric'],
          ['b.beneficio','beneficio','numeric'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)*b.beneficio','cotizado','numeric'],
        ],
        'query' => DB::table('beneficio_mensual as bm')
        ->join('beneficio as b','b.id_beneficio_mensual','=','bm.id_beneficio_mensual')
        ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','bm.id_tipo_moneda')
        ->leftJoin('cotizacion as cot',function($j){
          return $j->on('cot.fecha','=','b.fecha')
                   ->on('cot.id_tipo_moneda','=','bm.id_tipo_moneda');
        }),
        'default_order_by' => [
          'b.fecha' => 'asc'
        ],
        'indirect_where' => [
          'plataforma' => 'plat.id_plataforma',
          'moneda' => 'tm.id_tipo_moneda',
        ]
      ],
      'beneficio_poker' => [
        'cols' => [
          ['b.fecha','fecha','string','input_date',$this->mes_actual],
          ['plat.codigo','plataforma','string','select',[0],$this->selectPlataformaVals('beneficio_mensual_poker')],
          ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('beneficio_mensual_poker')],
          ['b.jugadores','jugadores','integer'],
          ['b.mesas','mesas','integer'],
          ['b.buy','compra','numeric'],
          ['b.rebuy','recompra','numeric'],
          ['b.total_buy','jugado','numeric'],
          ['b.cash_out','pagado','numeric'],
          ['b.otros_pagos','otros_pagos','numeric'],
          ['b.total_bonus','bonus','numeric'],
          ['b.utilidad','beneficio','numeric'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)*b.utilidad','cotizado','numeric'],
        ],
        'query' => DB::table('beneficio_mensual_poker as bm')
        ->join('beneficio_poker as b','b.id_beneficio_mensual_poker','=','bm.id_beneficio_mensual_poker')
        ->join('plataforma as plat','plat.id_plataforma','=','bm.id_plataforma')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','bm.id_tipo_moneda')
        ->leftJoin('cotizacion as cot',function($j){
          return $j->on('cot.fecha','=','b.fecha')
                   ->on('cot.id_tipo_moneda','=','bm.id_tipo_moneda');
        }),
        'default_order_by' => [
          'b.fecha' => 'asc'
        ],
        'indirect_where' => [
          'plataforma' => 'plat.id_plataforma',
          'moneda' => 'tm.id_tipo_moneda',
        ]
      ],
      'producido' => [
        'cols' => [
          ['p.fecha','fecha','string','input_date',$this->mes_actual],
          ['plat.codigo','plataforma','string','select',[0],$this->selectPlataformaVals('producido')],
          ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('producido')],
          ['p.apuesta_efectivo','apuesta_efectivo','numeric'],
          ['p.apuesta_bono','apuesta_bono','numeric'],
          ['p.apuesta','apuesta','numeric'],
          ['p.premio_efectivo','premio_efectivo','numeric'],
          ['p.premio_bono','premio_bono','numeric'],
          ['p.premio','premio','numeric'],
          ['p.beneficio_efectivo','producido_efectivo','numeric'],
          ['p.beneficio_bono','producido_bono','numeric'],
          ['p.beneficio','producido','numeric'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)*p.beneficio','cotizado','numeric'],
          ['b.ajuste_auditoria','ajuste_beneficio','numeric'],
        ],
        'query' => DB::table('producido as p')
        ->join('plataforma as plat','plat.id_plataforma','=','p.id_plataforma')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
        ->leftJoin('cotizacion as cot',function($j){
          return $j->on('cot.fecha','=','p.fecha')
                   ->on('cot.id_tipo_moneda','=','p.id_tipo_moneda');
        })
        ->leftJoin('beneficio_mensual as bm',function($j){
          return $j->on('bm.id_plataforma','=','p.id_plataforma')
                   ->on('bm.id_tipo_moneda','=','p.id_tipo_moneda')
                   ->on(DB::raw('YEAR(bm.fecha)'),'=',DB::raw('YEAR(p.fecha)'))
                   ->on(DB::raw('MONTH(bm.fecha)'),'=',DB::raw('MONTH(p.fecha)'));
        })
        ->leftJoin('beneficio as b',function($j){
          return $j->on('b.id_beneficio_mensual','=','bm.id_beneficio_mensual')
                   ->on('b.fecha','=','p.fecha');
        }),
        'default_order_by' => [
          'p.fecha' => 'asc'
        ],
        'indirect_where' => [
          'plataforma' => 'plat.id_plataforma',
          'moneda' => 'tm.id_tipo_moneda',
        ]
      ]
    ];
  }
  
  public function index(Request $request){
    $vistas = collect($this->vistas)->map(function($v,$k){
      $columnas = collect($v['cols'])->map(function($c) use($v,$k){
        return collect([
          'nombre' => $c[BO_ALIAS],
          'nombre_fmt' => strtoupper(implode(' ',explode('_',$c[BO_ALIAS]))),
          'tipo' => $c[BO_TIPO] ?? null,
          'default'  => $c[BO_DEFAULTS] ?? [''],
          'valores'  => $c[BO_VALUES] ?? []
        ]);
      });
      
      return collect([
        'nombre' => $k,
        'nombre_fmt' => strtoupper(implode(' ',explode('_',$k))),
        'columnas' => $columnas,
      ]);
    });
    
    return view('seccionBackoffice',compact('vistas'));
  }
  
  public function buscar(Request $request,$para_descargar = false){
    if(!array_key_exists($request->vista,$this->vistas)) return [];
    
    $data = collect($request->all())->map(function($v,$k) use ($request){
      return $this->postprocess_param($request->vista,$k,$v);
    });
        
    $v = $this->vistas[$request->vista];
    $cols = collect($v['cols']);
    
    $QS = [
      clone $v['query'],
      array_key_exists('count',$v)?
         clone $v['count']
      : (clone $v['query'])->selectRaw('COUNT(*) as count')
    ];
  
    foreach($cols as $c){
      $alias = $c[BO_ALIAS];
      if(!isset($data[$alias])) continue;
      $recibido = $data[$alias];
      
      $select = isset($v['indirect_where']) && isset($v['indirect_where'][$alias])?
        $v['indirect_where'][$alias] 
        : $c[BO_SELECT];
      
      $tipo = $c[BO_TIPO] ?? null;
        
      if(is_array($recibido) && $tipo == 'input_vals_list' && !empty($recibido)){
        foreach($QS as &$q)
          $q = $q->whereIn(DB::raw($select),$recibido);
      }
      else if($tipo == 'input_date_month' && !empty($recibido)){
        if(is_array($recibido) && count($recibido) >= 2 && !empty($recibido[0]) && !empty($recibido[1])){
          $d = explode('-',$recibido[0]);
          $h = explode('-',$recibido[1]);
          foreach($QS as &$q)
            $q = $q->whereYear(DB::raw($select),'>=',$d[0])
                   ->whereMonth(DB::raw($select),'>=',$d[1])
                   ->whereYear(DB::raw($select),'<=',$h[0])
                   ->whereMonth(DB::raw($select),'<=',$h[1]);
        } 
        else if(is_array($recibido) && count($recibido) == 1 && !empty($recibido[0])){
          $m = explode('-',$recibido[0]);
          foreach($QS as &$q)
            $q = $q->whereYear(DB::raw($select),'=',$m[0])
                   ->whereMonth(DB::raw($select),'=',$m[1]);
        }
        else if(!is_array($recibido)){
          $m = explode('-',$recibido);
          foreach($QS as &$q)
            $q = $q->whereYear(DB::raw($select),'=',$m[0])
                   ->whereMonth(DB::raw($select),'=',$m[1]);
        }
      }
      else if(is_array($recibido) && count($recibido) >= 2){
        if(!is_null($recibido[0])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'>=',$recibido[0]);
        }
        if(!is_null($recibido[1])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'<=',$recibido[1]);
        }
      }
      else if(is_array($recibido) && count($recibido) == 1){
        if(!is_null($recibido[0])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'=',$recibido[0]);
        }
      }
      else if(!is_array($recibido)){
        if(!is_null($recibido)){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'=',$recibido);
        }
      }
    }
    
    $sort_by = [
      'columna' => array_keys($v['default_order_by'])[0],
      'orden'   => array_values($v['default_order_by'])[0],
    ];
    
    if(!empty($request->sort_by) 
    && !empty($request->sort_by['columna']) 
    && !empty($request->sort_by['orden']) 
    && $cols->where(BO_ALIAS,$request->sort_by['columna'])->count() > 0){
      $col = $cols->where(BO_ALIAS,$request->sort_by['columna'])->first()[0];
      $sort_by['columna'] = DB::raw($col);
      $sort_by['orden'] = $request->sort_by['orden'];
    }
    
    $query = $QS[0];
    $count = $QS[1];
    
    $query = $query->orderBy($sort_by['columna'],$sort_by['orden']);
    
    $page_size = is_numeric($request->page_size)? intval($request->page_size) : 10;
    $page      = is_numeric($request->page)? intval($request->page) : 1;
    $OFFSET    = ($page-1)*$page_size;
    
    DB::statement('SET @@group_concat_max_len = 4294967295');//MAXUINT
    $select = $cols->map(function($c){
      return "{$c[BO_SELECT]} as `{$c[BO_ALIAS]}`";
    })->implode(', ');
    $data = $query->selectRaw(($v['precols'] ?? '').' '.$select);
    
    if($para_descargar === false || $para_descargar == 'PAGINA'){
      $data = $data->skip($OFFSET)->take($page_size);
    }
    
    $data = $data->get()->map(function($r,$rk) use ($request){
      return collect($r)->map(function($cv,$ck) use ($request){
        return $this->postprocess($request->vista,$ck,$cv);
      });
    });
    
    if($para_descargar !== false){
      return collect([$cols->pluck(1)->toArray()])->merge(
        $data->map(function($r){
          return $r->values();
        })
      );
    }
    
    $count = $count->first();
    $count = is_null($count)? 0 : $count->count;
    
    return [
      'current_page' => $page,
      'per_page'     => $page_size,
      'from'         => $OFFSET+1,
      'to'           => $page*$page_size,
      'data'         => $data,
      'total'        => $count,
      'last_page'    => ceil($count/$page_size)
    ];
  }
  
  private function postprocess($vista,$col,$val){
    $col = collect($this->vistas[$vista]['cols'])->where(BO_ALIAS,$col)->first();
    $tipo = $col[BO_TIPO] ?? null;
    if(!is_null($col) && $tipo == 'input_vals_list'){
      $vals  = explode(',',$val);
      $lista = self::colapsarListaDeNumerosAscendentes($vals);
      $count = count($vals);
      $val   = "[$count] $lista";
    }
    return self::val_format($col[BO_FMT] ?? null,$val);
  }
  
  private function postprocess_param($vista,$col,$val){
    $col = collect($this->vistas[$vista]['cols'])->where(BO_ALIAS,$col)->first();
    $tipo = $col[BO_TIPO] ?? null;
    if(!is_null($col) && $tipo == 'input_vals_list'){
      return self::expandirListaDeRangosSeparadaPorComas($val[0]);
    }
    return $val;
  }
  
  public function descargar(Request $request){
    $data = $this->buscar($request,$request->completo == '1'? 'COMPLETO' : 'PAGINA')->toArray();
    
    $f = fopen('php://memory', 'r+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    foreach ($data as $item) {
      fputcsv($f, $item,',','"',"\\");
    }
    rewind($f);
        
    return stream_get_contents($f);
  }
  
  private static function colapsarListaDeNumerosAscendentes(array $lista = null){
    $lista  = $lista ?? [];//null guard
    $rangos = [];
    $r      = [];
    $r_to_str = function($r){ return ($r[0] == $r[1])? $r[0] : "{$r[0]}-{$r[1]}"; };
    
    while(true){
      $val = array_shift($lista);
      if(is_null($val)) break;
      
      if(count($r) >= 2){
        if($r[1] == $val || ($r[1]+1) == $val){
          $r[1] = $val;
          continue;
        }
        $rangos[] = $r_to_str($r);
      }
           
      $r = [$val,$val];
    }
    
    if(count($r) >= 2){
      $rangos[] = $r_to_str($r);
    }
    
    return implode(', ',$rangos);
  }
  
  private static function expandirListaDeRangosSeparadaPorComas($lista_comas){
    if($lista_comas == '') return [];
    $lista_con_rangos = explode(',',$lista_comas);
    $lista_final = [];
    foreach($lista_con_rangos as $v){
      $v = trim($v);
      if(ctype_digit($v)){
        $lista_final[] = intval($v);
        continue;
      }
      $rango = explode('-',$v);
      if(count($rango) != 2) return false;
      
      $v1 = trim($rango[0]);$v2 = trim($rango[1]);
      if(!ctype_digit($v1) || !ctype_digit($v2)) return false;
      $v1 = intval($v1);$v2 = intval($v2);
      $min = min($v1,$v2);
      $max = max($v1,$v2);
      for($i = $min;$i <= $max;$i++){
        $lista_final[] = $i;
      }
    }
    return $lista_final;
  }
  
  private static function val_format($tipo,$val){
    if(is_null($val) || $val == '' || (is_numeric($val) && is_nan($val)))
      return '';
    switch($tipo){
      case 'integer':
        return intval($val);
      case 'numeric':
        return number_format($val,2,',','.');
      case 'numeric3d':
        return number_format($val,3,',','.');
    }
    return $val;
  }
}
