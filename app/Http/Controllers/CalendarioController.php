<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use View;
use App\Evento;
use App\Usuario;
use App\Rol;
use App\TipoEvento;
use App\Plataforma;
use App\Notifications\CalendarioEvento;


class CalendarioController extends Controller
{
  private static $instance;

  private static $atributos = [ ];

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new CalendarioController();
    }
    return self::$instance;
  }

  public function verMes($mes , $anio){
    $eventos = Evento::whereMonth('fecha_inicio',$mes)->whereYear('fecha_inicio',$anio)->get();
    $arreglo_eventos= array();
    foreach ($eventos as $evento) {
      $aux = new \stdClass();
      $aux->evento = $evento;
      $aux->tipo_evento = $evento->tipo_evento;
      $arreglo_eventos[]= $aux;
    }
    return ["eventos" => $arreglo_eventos];
  }

/*Retorna todos los eventos del mes actual*/
  public function buscarEventos(){
    $hoyY = date('Y');
    $hoyM = date('m');
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = array();

    foreach($usuario->plataformas as $p) {
      $plats[] = $p->id_plataforma;
    }
    //@TODO: Adaptar eventos para plataformas
    $eventos = DB::table('evento')
                  ->select('evento.*','tipo_evento.descripcion as tipo_evento', 'tipo_evento.color_back as fondo','tipo_evento.color_text as texto')
                  ->join('tipo_evento','tipo_evento.id_tipo_evento','=','evento.id_tipo_evento')
                  ->whereMonth('fecha_inicio',$hoyM)
                  ->whereYear('fecha_inicio',$hoyY)
                  ->whereIn('evento.id_plataforma', $plats)
                  ->get();
    return ['eventos'=>$eventos];
  }

  public function getOpciones(){
    return['roles' => Rol::all(),
     'plataformas' => Plataforma::all(),
      'tipos_eventos' => TipoEvento::all()];
  }

  public function crearEvento(Request $request){
    //falta validar las cosas :))
    Validator::make($request->all(), [
        'inicio' => 'required|date',
        'fin' => 'required|date',
        'titulo' => 'required',
        'descripcion' => 'required',
        'id_plataforma' => 'required',
        'id_tipo_evento' => 'required',
        'id_rol' =>'required',
        'desde' => '',
        'hasta' => ''
    ], array(), self::$atributos)->after(function ($validator){})->validate();

    $evento = new Evento;
    $evento->fecha_inicio = $request->inicio;
    $evento->fecha_fin = $request->fin;
    $evento->hora_inicio = $request->desde;
    $evento->hora_fin = $request->hasta;
    $evento->titulo = $request->titulo;
    $evento->descripcion = $request->descripcion;
    $evento->plataforma()->associate($request->id_plataforma);
    $evento->tipo_evento()->associate($request->id_tipo_evento);
    $evento->save();

    $usuarios = UsuarioController::getInstancia()->obtenerUsuariosRol($request->id_plataforma, $request->id_rol);
    foreach ($usuarios as $user){
      $u = Usuario::find($user->id_usuario);
      $u->notify(new CalendarioEvento($evento));
    }

    return ['evento' => $evento,'tipo' => TipoEvento::find($request->id_tipo_evento)];

  }

  public function modificarEvento(Request $request){
    $ev = Evento::find($request['id']);
    $ev->fecha_inicio = $request['inicio'];
    $ev->fecha_fin = $request['fin'];
    $ev->save();
    return ['evento' => $ev];
  }

  public function getEvento($id){
    $ev = Evento::find($id);
    return ['evento'=> $ev, 'plataforma' => $ev->plataforma, 'tipo_evento' => $ev->tipo_evento];
  }

  public function eliminarEvento($id){
    Evento::destroy($id);
    return 1;
  }

  public function crearTipoEvento(Request $request){
    Validator::make($request->all(), [
        'descripcion' => 'required|unique:tipo_evento,descripcion',
        'fondo' => 'required|unique:tipo_evento,color_back',
        'texto' => 'required|string'
    ], array(), self::$atributos)->after(function ($validator){})->validate();
    $tipo = new TipoEvento;
    $tipo->descripcion = $request->descripcion;
    $tipo->color_back = $request->fondo;
    $tipo->color_text= $request->texto;
    $tipo->save();
    return $tipo;
  }
}
