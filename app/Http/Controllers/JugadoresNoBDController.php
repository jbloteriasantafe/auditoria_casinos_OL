<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\ImportacionEstadoJugador;
use App\ProducidoJugadores;
use App\Http\Controllers\UsuarioController;

class JugadoresNoBDController extends Controller
{
  static private $select_jugadores_bd = "SELECT distinct iej.id_plataforma,dj.codigo as jugador
  FROM importacion_estado_jugador as iej
  JOIN estado_jugador AS ej 
    ON  ej.id_importacion_estado_jugador = iej.id_importacion_estado_jugador
    AND ej.es_ultimo_estado_del_jugador = 1
  JOIN datos_jugador AS dj 
    ON dj.id_datos_jugador = ej.id_datos_jugador";
  static private $select_jugadores_prod = "SELECT distinct pj.id_plataforma,dpj.jugador
  FROM producido_jugadores AS pj
  JOIN detalle_producido_jugadores as dpj 
    ON dpj.id_producido_jugadores = pj.id_producido_jugadores";
  
  function __construct(){
    if(!Schema::hasTable('jugadores_produciendo_no_en_bd')){
      Schema::dropIfExists('jugadores_produciendo_no_en_bd');
      DB::statement('CREATE TABLE jugadores_produciendo_no_en_bd(
       id_plataforma INT,
       jugador VARCHAR(16),
       PRIMARY KEY(id_plataforma,jugador)
      )');
    }
  }
  
  public function agregueProducido($id = null){
    //Me fijo si estan en la BD, si no estan los agrego (estan produciendo pero no estan en la BD)
    
    //Limito los que agrego como faltantes a ese id_producido_jugadores
    $pj = is_null($id)? null : ProducidoJugadores::find($id);
    $where_pj  = '1';
    $where_iej = '1';
    if(!is_null($pj)){
      $idp = $pj->id_plataforma;
      $where_pj  = "pj.id_producido_jugadores = $id";
      $where_iej = "iej.id_plataforma = $idp";
    }
    
    $select_jugadores_bd   = self::$select_jugadores_bd;
    $select_jugadores_prod = self::$select_jugadores_prod;
    //Agrego los que estan en detalles producidos sin estar en la tabla de JPNBD y que no estan en la BD
    DB::statement("INSERT INTO jugadores_produciendo_no_en_bd (id_plataforma,jugador)
    $select_jugadores_prod
    LEFT JOIN jugadores_produciendo_no_en_bd AS jnobd 
      ON  jnobd.id_plataforma = pj.id_plataforma 
      AND jnobd.jugador       = dpj.jugador
    WHERE $where_pj 
    AND jnobd.id_plataforma IS NULL
    AND (pj.id_plataforma,dpj.jugador) NOT IN (
      $select_jugadores_bd
      WHERE $where_iej
      ORDER BY iej.id_plataforma ASC, dj.codigo ASC
    )
    ORDER BY pj.id_plataforma ASC,dpj.jugador ASC");
  }
  
  public function agregueJugadores($id){
    //Si agrego jugadores, los borro de "jugadores produciendo que no estan en la bd"
    
    //Limito los que borro porque estan en la BD a ese id_importacion_estado_jugador
    $idp = ImportacionEstadoJugador::find($id)->id_plataforma;
    $select_jugadores_bd = self::$select_jugadores_bd;
    //Saco los que fueron importados en la tabla de jugadores
    DB::statement("DELETE FROM jugadores_produciendo_no_en_bd
    WHERE jugadores_produciendo_no_en_bd.id_plataforma = $idp
    AND (jugadores_produciendo_no_en_bd.id_plataforma,jugadores_produciendo_no_en_bd.jugador) IN (
      $select_jugadores_bd
      WHERE iej.id_importacion_estado_jugador = $id
      ORDER BY iej.id_plataforma ASC, dj.codigo ASC
    )");
  }
  
  public function borrarProducido($id){ 
    //Me fijo en los jugadores, si no aparece en otro producido, los
    //borro de "los jugadores produciendo que no estan en la BD"
    $idp = ProducidoJugadores::find($id)->id_plataforma;
    $select_jugadores_prod = self::$select_jugadores_prod;
    DB::statement("DELETE FROM jugadores_produciendo_no_en_bd
    WHERE jugadores_produciendo_no_en_bd.id_plataforma = $idp
    AND (jugadores_produciendo_no_en_bd.id_plataforma,jugadores_produciendo_no_en_bd.jugador) NOT IN (
      $select_jugadores_prod
      WHERE pj.id_producido_jugadores <> $id AND pj.id_plataforma = $idp
      ORDER BY pj.id_plataforma ASC,dpj.jugador ASC
    )");
  }
  
  public function borrarJugadores($id){
    //Los agrego a "los jugadores produciendo que no estan en la BD"
    //Si no esta en la BD sacando el que voy a borrar (jbd2.id_plataforma IS NULL)
    //Si no esta en la tabla (jnobd.is_plataforma IS NULL),
    //Si aparecen en algun producido (IN ...),
    $select_jugadores_bd   = self::$select_jugadores_bd;
    $select_jugadores_prod = self::$select_jugadores_prod;
    $idp = ImportacionEstadoJugador::find($id)->id_plataforma;
    DB::statement("INSERT INTO jugadores_produciendo_no_en_bd (id_plataforma,jugador)
    SELECT distinct jbd.id_plataforma,jbd.jugador
    FROM (
      $select_jugadores_bd
      WHERE iej.id_importacion_estado_jugador = $id
      ORDER BY iej.id_plataforma ASC, dj.codigo ASC
    ) AS jbd
    LEFT JOIN (
      $select_jugadores_bd
      WHERE iej.id_importacion_estado_jugador <> $id
      AND   iej.id_plataforma = $idp
      ORDER BY iej.id_plataforma ASC, dj.codigo ASC
    ) AS jbd2
      ON  jbd2.id_plataforma = jbd.id_plataforma
      AND jbd2.jugador       = jbd.jugador
    LEFT JOIN jugadores_produciendo_no_en_bd as jnobd
      ON  jnobd.id_plataforma = jbd.id_plataforma 
      AND jnobd.jugador       = jbd.jugador
    WHERE  jbd2.id_plataforma IS NULL
    AND jnobd.id_plataforma IS NULL
    AND (jbd.id_plataforma,jbd.jugador) IN (
      $select_jugadores_prod
      WHERE pj.id_plataforma = $idp
      ORDER BY pj.id_plataforma ASC,dpj.jugador ASC
    )");
  }

  //Argumentos optimizan si se borro/agrego uno en particular, en vez de chequear todos
  public function actualizarTablaJugadoresNoEnBD(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$usuario->es_superusuario) return response()->json("No tiene los permisos",422);
    
    DB::transaction(function(){
      DB::statement('TRUNCATE TABLE jugadores_produciendo_no_en_bd');
      //Si lo llamo sin ID, agrega todos los jugadores produciendo que no estan en la BD
      $this->agregueProducido();
    });
    return 1;
  }
}
