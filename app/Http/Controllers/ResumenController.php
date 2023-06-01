<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

class ResumenController extends Controller
{
  private static $instance = null;
  
  public static function getInstancia() {
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  private static $pjug_attrs = ['apuesta_efectivo','apuesta_bono','apuesta','premio_efectivo','premio_bono','premio','beneficio_efectivo','beneficio_bono','beneficio'];
  
  public function generarResumenMensualProducidoJugadores($id_plataforma,$id_tipo_moneda,$fecha_mes){
    $sum_attrs = array_map(function($s){return "SUM(dpj.$s) as $s";},self::$pjug_attrs);
    $sum_attrs = implode(',',$sum_attrs);
    $attrs     = implode(',',self::$pjug_attrs);
    
    $primer_dia_mes = date('01-m-Y',strtotime($fecha_mes));
    
    $pdo = DB::connection('mysql')->getPdo();
    
    $pdo->prepare('DELETE FROM resumen_mensual_producido_jugadores
    WHERE id_plataforma = :id_plataforma AND id_tipo_moneda = :id_tipo_moneda
    AND   desde = :primer_dia_mes1 AND hasta = LAST_DAY(:primer_dia_mes2)')
    ->execute([
      'id_plataforma' => $id_plataforma,'id_tipo_moneda' => $id_tipo_moneda,
      'primer_dia_mes1' => $primer_dia_mes,'primer_dia_mes2' => $primer_dia_mes,
    ]);
    
    $pdo->prepare("INSERT INTO resumen_mensual_producido_jugadores
    (id_plataforma,id_tipo_moneda,desde,hasta,jugador,$attrs)
    SELECT pj.id_plataforma,pj.id_tipo_moneda,:primer_dia_mes1,LAST_DAY(:primer_dia_mes2),dpj.jugador,$sum_attrs
    FROM producido_jugadores as pj
    JOIN detalle_producido_jugadores as dpj ON dpj.id_producido_jugadores = pj.id_producido_jugadores
    WHERE pj.id_plataforma = :id_plataforma AND pj.id_tipo_moneda = :id_tipo_moneda AND pj.fecha BETWEEN :primer_dia_mes3 AND LAST_DAY(:primer_dia_mes4)
    GROUP BY pj.id_plataforma,pj.id_tipo_moneda,periodo.desde,periodo.hasta,dpj.jugador")
    ->execute([
      'primer_dia_mes1' => $primer_dia_mes,'primer_dia_mes2' => $primer_dia_mes,
      'primer_dia_mes3' => $primer_dia_mes,'primer_dia_mes4' => $primer_dia_mes,
      'id_plataforma' => $id_plataforma,'id_tipo_moneda' => $id_tipo_moneda
    ]);
    
    return 1;
  }
  
  public function regenerarResumenesMensualesProducidosJugadores(){
    $u = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if(!$u->es_superusuario) return 0;
    
    return DB::transaction(function(){
      $pdo = DB::connection('mysql')->getPdo();
      
      $pdo->prepare('DROP TABLE IF EXISTS resumen_mensual_producido_jugadores')
      ->execute();
      
      $pdo->prepare('CREATE TABLE resumen_mensual_producido_jugadores (
        id_plataforma INT NOT NULL,
        id_tipo_moneda INT NOT NULL,
        desde DATE NOT NULL,
        hasta DATE NOT NULL,
        jugador VARCHAR(16) NOT NULL,
        apuesta_efectivo DECIMAL(15,2) NOT NULL,
        apuesta_bono DECIMAL(15,2) NOT NULL,
        apuesta DECIMAL(15,2) NOT NULL,
        premio_efectivo DECIMAL(15,2) NOT NULL,
        premio_bono DECIMAL(15,2) NOT NULL,
        premio DECIMAL(15,2) NOT NULL,
        beneficio_efectivo DECIMAL(15,2) NOT NULL,
        beneficio_bono DECIMAL(15,2) NOT NULL,
        beneficio DECIMAL(15,2) NOT NULL,
        PRIMARY KEY (`id_plataforma`, `id_tipo_moneda`, `desde`, `hasta`, `jugador`),
        CONSTRAINT `fk_r_m_prod_jug_plat` FOREIGN KEY (`id_plataforma`) REFERENCES `plataforma` (`id_plataforma`),
        CONSTRAINT `fk_r_m_prod_jug_tipomon` FOREIGN KEY (`id_tipo_moneda`) REFERENCES `tipo_moneda` (`id_tipo_moneda`)
      )')->execute();
      
      $bms = \App\BeneficioMensual::where('validado','=',1)
      ->orderBy('fecha','asc')
      ->orderBy('id_plataforma','asc')
      ->orderBy('id_tipo_moneda','asc')->get();
      
      foreach($bms as $bm){
        $this->generarResumenMensualProducidoJugadores(
          $bm->id_plataforma,$bm->id_tipo_moneda,$bm->fecha
        );
      }
      
      return 1;
    });
  }
}
