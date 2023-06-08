<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Plataforma;
use App\Juego;

class InformeContableController extends Controller
{
  public function informeContableJuego($id_plataforma = null,$modo = null,$codigo = null){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Informe Contable Juegos/Jugadores' , 'informeContableJuego');

    $mostrar = null;
    if(!is_null($id_plataforma) && !is_null($modo) && !is_null($codigo)){
      $busqueda = [];
      if($modo == 'juego'){
        $busqueda = $this->obtenerJuegoPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      else if($modo == 'jugador'){
        $busqueda = $this->obtenerJugadorPlataforma($id_plataforma,$codigo)['busqueda'];
      }
      $plataforma = Plataforma::find($id_plataforma);
      if(count($busqueda) > 0 && $busqueda[0]->codigo == $codigo && !is_null($plataforma)){
        $mostrar['id_plataforma'] = $id_plataforma;
        $mostrar['codigo_plat']   = $plataforma->codigo;
        $mostrar['modo']          = $modo;
        $mostrar['codigo']        = $codigo;
        $mostrar['id']            = $busqueda[0]->id;
      }
    }
    return view('informe_juego', ['plataformas' => $usuario->plataformas,'mostrar' => $mostrar]);
  }

  public function obtenerJugadorPlataforma($id_plataforma,$jugador=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $jugadores = DB::table('producido_jugadores as p')
    ->join('detalle_producido_jugadores as dp','dp.id_producido_jugadores','=','p.id_producido_jugadores')
    ->selectRaw('-1 as id, dp.jugador as codigo')->distinct()
    ->whereIn('p.id_plataforma',$plats)
    ->where('dp.jugador','LIKE',$jugador.'%')
    ->orderBy('codigo','asc');

    if($id_plataforma != "0") $jugadores = $jugadores->where('p.id_plataforma',$id_plataforma);

    return ['busqueda' => $jugadores->get()];
  }

  public function obtenerJuegoPlataforma($id_plataforma,$cod_juego=""){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = [];
    foreach($usuario->plataformas as $p) $plats[] = $p->id_plataforma;

    $j = DB::table('plataforma_tiene_juego as pj')
    ->select('j.id_juego as id','j.cod_juego as codigo')
    ->join('juego as j','j.id_juego','=','pj.id_juego')
    ->whereIn('pj.id_plataforma',$plats)
    ->whereNull('j.deleted_at')
    ->where('j.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j = $j->where('pj.id_plataforma',$id_plataforma);

    $j_no_en_bd = DB::table('detalle_producido_juego as dp')
    ->selectRaw('-1 as id, dp.cod_juego as codigo')->distinct()
    ->whereNull('dp.id_juego')
    ->whereIn('dp.id_plataforma',$plats)
    ->where('dp.cod_juego','LIKE',$cod_juego.'%')->orderBy('codigo','asc');

    if($id_plataforma != "0") $j_no_en_bd = $j_no_en_bd->where('dp.id_plataforma',$id_plataforma);

    return ['busqueda' => $j->union($j_no_en_bd)->orderBy('codigo','asc')->get()];
  }

  public function obtenerInformeDeJuego($id_juego){
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

