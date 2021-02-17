<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PermisoObserver extends Observers\ParametrizedObserver
{
  public function __construct(){
    parent::__construct('descripcion');
  }
}

class Permiso extends Model
{
    protected $connection = 'mysql';
    protected $table = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;
    protected $visible = array('id_permiso', 'descripcion');

    public function roles(){
      return $this->belongsToMany('App\Rol','rol_tiene_permiso','id_permiso','id_rol');
    }

    public static function boot(){
      parent::boot();
      Permiso::observe(PermisoObserver::class);
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_permiso;
    }
}
