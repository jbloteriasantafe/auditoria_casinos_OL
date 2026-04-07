<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Plataforma;
use App\Juego;

class InformeContableController extends Controller
{
  public function informeContableJuego($id_plataforma = null,$tipo = null,$codigo = null){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable Juegos/Jugadores' , 'informeContableJuego');

    $mostrar = null;
    if(!is_null($id_plataforma) && !is_null($tipo) && !is_null($codigo)){
      $busqueda = [];
      if($tipo == 'juego'){
        $busqueda = $this->obtenerJuegoPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      else if($tipo == 'jugador'){
        $busqueda = $this->obtenerJugadorPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      $plataforma = Plataforma::find($id_plataforma);
      if(count($busqueda) > 0 && $busqueda[0]->codigo == $codigo && !is_null($plataforma)){
        $mostrar['id_plataforma'] = $id_plataforma;
        $mostrar['tipo']          = $modo;
        $mostrar['codigo']        = $codigo;
      }
    }
    return view('informe_juego', ['plataformas' => $usuario->plataformas,'mostrar' => $mostrar]);
  }

  public function obtenerJugadorPlataforma($id_plataforma,$jugador=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!in_array($id_plataforma,$usuario->plataformas->pluck('id_plataforma')->toArray())){
      return ['busqueda' => []];
    }
    
    if(!ctype_digit($id_plataforma)){
      return ['busqueda' => []];
    }
       
    $codigos = [];
    
    DB::table('producido_jugadores as p')
    ->select('p.id_producido_jugadores')
    ->where('p.id_plataforma',$id_plataforma)
    ->chunkById(1000,function($ids_producidos_jugadores) use (&$codigos){
      $chunk_codigos = DB::table('detalle_producido_jugadores as dp')
      ->selectRaw("dp.jugador")->distinct()
      ->whereIn('dp.id_producido_jugadores',$ids_producidos_jugadores->pluck('id_producido_jugadores'))
      ->orderBy('dp.jugador','asc')
      ->get();
      
      foreach($chunk_codigos as $ck){
        $codigos[$ck->jugador] = true;
      }
    },'id_producido_jugadores');
    
    ksort($codigos);
    $codigos = array_keys($codigos);
    $codigos = array_map(function($c) use ($id_plataforma){
      return ['plataforma_codigo' => ($id_plataforma.'|'.$c),'codigo' => $c];
    },$codigos);

    return ['busqueda' => $codigos];
  }

  public function obtenerJuegoPlataforma($id_plataforma,$cod_juego=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $j = DB::table('plataforma_tiene_juego as pj')
    ->select(DB::raw('CONCAT(pj.id_plataforma,"|",j.cod_juego) as plataforma_codigo'),'j.cod_juego as codigo')
    ->join('juego as j','j.id_juego','=','pj.id_juego')
    ->whereIn('pj.id_plataforma',$plats)
    ->whereNull('j.deleted_at')
    ->where('j.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j = $j->where('pj.id_plataforma',$id_plataforma);

    $j_no_en_bd = DB::table('detalle_producido_juego as dp')
    ->selectRaw('CONCAT(dp.id_plataforma,"|",dp.cod_juego) as plataforma_codigo, dp.cod_juego as codigo')->distinct()
    ->whereNull('dp.id_juego')
    ->whereIn('dp.id_plataforma',$plats)
    ->where('dp.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j_no_en_bd = $j_no_en_bd->where('dp.id_plataforma',$id_plataforma);

    return ['busqueda' => $j->union($j_no_en_bd)->orderBy('codigo','asc')->get()];
  }

  public function obtenerInformeDeJuego($id_plataforma,$cod_juego){
    $id_juegos = Juego::where('cod_juego',$cod_juego)->get();
    
    if(empty($id_juegos)) return null;
    
    $pj = DB::table('plataforma_tiene_juego')
    ->whereIn('id_juego',$id_juegos->pluck('id_juego'))
    ->where('id_plataforma',$id_plataforma)->first();
        
    if(empty($pj)) return null;
    
    $id_juego = $pj->id_juego;
    
    $juego = Juego::find($id_juego);

    $estados = DB::table('plataforma_tiene_juego as pj')
    ->select('pj.id_plataforma','p.codigo as plataforma','ej.nombre as estado')
    ->join('plataforma as p','p.id_plataforma','=','pj.id_plataforma')
    ->join('estado_juego as ej','ej.id_estado_juego','=','pj.id_estado_juego')
    ->where('pj.id_juego',$id_juego)->get();
    
    return ['juego' => $juego, 'categoria' => $juego->categoria_juego, 'moneda' => $juego->tipo_moneda, 'estados' => $estados];
  }

  public function obtenerProducidosDeJuego($id_plataforma,$cod_juego,$offset=0,$size=30){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $q = DB::table('detalle_producido as dp')
    ->join('producido as p','p.id_producido','=','dp.id_producido')
    ->join('tipo_moneda as m','m.id_tipo_moneda','=','p.id_tipo_moneda')
    ->where('p.id_plataforma',$id_plataforma)
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.cod_juego',$cod_juego);

    $producidos = (clone $q)
    ->select('p.fecha','m.descripcion as moneda','dp.categoria','dp.jugadores',
      'dp.apuesta_efectivo',  'dp.apuesta_bono',  'dp.apuesta',
       'dp.premio_efectivo',   'dp.premio_bono',   'dp.premio',
    'dp.beneficio_efectivo','dp.beneficio_bono','dp.beneficio')
    ->orderBy('p.fecha','desc');

    if($size > 0) $producidos = $producidos->skip($offset)->take($size);
 
    $total = (clone $q)
    ->selectRaw('COUNT(p.fecha) as cantidad,SUM(dp.apuesta) as apuesta,SUM(dp.premio) as premio,
    SUM(dp.beneficio) as beneficio,AVG(dp.premio)/AVG(dp.apuesta) as pdev')
    ->groupBy('dp.cod_juego')->get()->first();

    return ['producidos' => $producidos->get(), 'total' => $total];
  }

  public function obtenerProducidosDeJugador($id_plataforma,$jugador,$offset=0,$size=30){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $q = DB::table('detalle_producido_jugadores as dp')
    ->join('producido_jugadores as p','p.id_producido_jugadores','=','dp.id_producido_jugadores')
    ->join('tipo_moneda as m','m.id_tipo_moneda','=','p.id_tipo_moneda')
    ->where('p.id_plataforma',$id_plataforma)
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.jugador',$jugador);

    $producidos = (clone $q)
    ->select('p.fecha','m.descripcion as moneda','dp.juegos',
      'dp.apuesta_efectivo',  'dp.apuesta_bono',  'dp.apuesta',
       'dp.premio_efectivo',   'dp.premio_bono',   'dp.premio',
    'dp.beneficio_efectivo','dp.beneficio_bono','dp.beneficio')
    ->orderBy('p.fecha','desc');

    if($size > 0) $producidos = $producidos->skip($offset)->take($size);
 
    $total = (clone $q)
    ->selectRaw('COUNT(p.fecha) as cantidad,SUM(dp.apuesta) as apuesta,SUM(dp.premio) as premio,
    SUM(dp.beneficio) as beneficio,AVG(dp.premio)/AVG(dp.apuesta) as pdev')
    ->groupBy('dp.jugador')->get()->first();

    return ['producidos' => $producidos->get(), 'total' => $total];
  }
}

