<?php

namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_encuesta';
  protected $primaryKey = 'id_encuesta';
  protected $visible = array('id_encuesta','id_juego_preferido','id_frecuencia_asistencia',
                              'veces','tiempo_jugado',
                              'club_jugadores', 'juego_responsable', 'autocontrol_juego',
                              'decision_ae',  'recibir_informacion',
                              'medio_recibir_informacion', 'id_autoexcluido',
                              'como_asiste', 'observacion'
                              );
  protected $fillable = ['id_juego_preferido','id_frecuencia_asistencia',
                              'veces','tiempo_jugado',
                              'club_jugadores', 'juego_responsable', 'autocontrol_juego',
                              'decision_ae',  'recibir_informacion',
                              'medio_recibir_informacion', 'id_autoexcluido',
                              'como_asiste', 'observacion'];

  public $timestamps = false;
}
