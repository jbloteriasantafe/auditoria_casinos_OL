<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoriaJuego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'categoria_juego';
  protected $primaryKey = 'id_categoria_juego';
  protected $visible = array('id_categoria_juego','nombre','nombre_lower');
  public $timestamps = false;

  public function juegos(){
    return $this->hasMany('App\Juego','id_categoria_juego','id_categoria_juego');
  }
}
