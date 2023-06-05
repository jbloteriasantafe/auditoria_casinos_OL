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
  private static $pjug_type = 'DECIMAL(20,2)';
  
  public function generarResumenMensualProducidoJugadores($id_plataforma,$id_tipo_moneda,$fecha_mes){
    $sum_attrs = array_map(function($s){return "SUM(dpj.$s) as $s";},self::$pjug_attrs);
    $sum_attrs = implode(',',$sum_attrs);
    $attrs     = implode(',',self::$pjug_attrs);
    
    $primer_dia_mes = date('Y-m-01',strtotime($fecha_mes));
    
    $pdo = DB::connection('mysql')->getPdo();
    
    $pdo->prepare('DELETE FROM resumen_mensual_producido_jugadores
    WHERE id_plataforma = :id_plataforma AND id_tipo_moneda = :id_tipo_moneda
    AND   aniomes = :primer_dia_mes')
    ->execute([
      'id_plataforma' => $id_plataforma,'id_tipo_moneda' => $id_tipo_moneda,
      'primer_dia_mes' => $primer_dia_mes
    ]);
    
    $pdo->prepare("INSERT INTO resumen_mensual_producido_jugadores
    (id_plataforma,id_tipo_moneda,aniomes,jugador,$attrs)
    SELECT pj.id_plataforma,pj.id_tipo_moneda,:primer_dia_mes1,dpj.jugador,$sum_attrs
    FROM producido_jugadores as pj
    JOIN detalle_producido_jugadores as dpj ON dpj.id_producido_jugadores = pj.id_producido_jugadores
    WHERE pj.id_plataforma = :id_plataforma AND pj.id_tipo_moneda = :id_tipo_moneda 
    AND pj.fecha BETWEEN :primer_dia_mes2 AND LAST_DAY(:primer_dia_mes3)
    GROUP BY pj.id_plataforma,pj.id_tipo_moneda,dpj.jugador")
    ->execute([
      'primer_dia_mes1' => $primer_dia_mes,'primer_dia_mes2' => $primer_dia_mes,
      'primer_dia_mes3' => $primer_dia_mes,
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
      
      $attrs = implode(',',array_map(
        function($s){return $s.' '.self::$pjug_type.' NOT NULL';},
        self::$pjug_attrs
      ));
      
      $pdo->prepare("CREATE TABLE resumen_mensual_producido_jugadores (
        id_plataforma INT NOT NULL,
        id_tipo_moneda INT NOT NULL,
        aniomes DATE NOT NULL,
        jugador VARCHAR(16) NOT NULL,
        $attrs,
        PRIMARY KEY (`id_plataforma`, `id_tipo_moneda`, `aniomes`, `jugador`),
        UNIQUE KEY `unq_rmpjug1` (`id_plataforma`,`id_tipo_moneda`,`jugador`,`aniomes`),
        CONSTRAINT `fk_r_m_prod_jug_plat` FOREIGN KEY (`id_plataforma`) REFERENCES `plataforma` (`id_plataforma`),
        CONSTRAINT `fk_r_m_prod_jug_tipomon` FOREIGN KEY (`id_tipo_moneda`) REFERENCES `tipo_moneda` (`id_tipo_moneda`)
      )")->execute();
      
      $primer_dia_mes = 'DATE(CONCAT(YEAR(fecha),'-',MONTH(fecha),'-',1))';
      $pjs = \App\ProducidoJugadores::orderBy('aniomes','asc')
      ->select('id_plataforma','id_tipo_moneda',DB::raw("$primer_dia_mes as aniomes"))->distinct()
      ->get();
      
      foreach($pjs as $p){
        $this->generarResumenMensualProducidoJugadores(
          $p->id_plataforma,$p->id_tipo_moneda,$p->aniomes
        );
      }
      
      return 1;
    });
  }
}
