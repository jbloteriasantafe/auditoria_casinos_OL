<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Plataforma;

class Usuario extends Model
{
    use Notifiable;
    use SoftDeletes;
    protected $connection = 'mysql';//correo.santafe.gov.ar:587
    protected $table = 'usuario';
    public $timestamps = false;
    protected $primaryKey = 'id_usuario';
    protected $visible = array('id_usuario','user_name','nombre','email', 'dni' ,'ultimos_visitados');
    protected $hidden = array('imagen','password','token');
    protected $appends = array('plataformas','es_superusuario','es_controlador','elimina_cya','es_administrador','es_fiscalizador','es_control','es_despacho','es_casino_ae');

    public function getPlataformasAttribute(){
      $plataformas = [];
      foreach($this->casinos as $c)
        foreach($c->plataformas as $p) $plataformas[] = $p->id_plataforma;
      return Plataforma::whereIn('id_plataforma',$plataformas)->get();
    }
    //en cierres y aperturas de mesas
    public function getEliminaCyaAttribute(){
      $roles = $this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->get();
      foreach ($roles as $rol) {
        foreach ($rol->permisos as $p) {
          if($p->descripcion == 'm_eliminar_cierres_y_aperturas'){
            return true;
          }
        }
      }
      return false;
    }

    public function getEsSuperusuarioAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',1)->get()) > 0);
    }

    public function getEsAdministradorAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',2)->get()) > 0);
    }

    public function getEsControlAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',4)->get()) > 0);
    }

    public function getEsControladorAttribute(){
      return $this->es_administrador || $this->es_superusuario || $this->es_control;
    }

    public function getEsFiscalizadorAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',3)->get()) > 0);
    }

    public function getEsDespachoAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',6)->get()) > 0);
    }

    public function getEsCasinoAeAttribute(){
      return (count($this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol')->where('rol.id_rol','=',9)->get()) > 0);
    }
    
    public function roles(){
	     return $this->belongsToMany('App\Rol','usuario_tiene_rol','id_usuario','id_rol');
    }
    public function casinos(){
      return $this->belongsToMany('App\Casino','usuario_tiene_casino','id_usuario','id_casino');
    }
    public function logs(){
      return $this->hasMany('App\Log','id_usuario','id_usuario');
    }

    public function secciones_recientes(){
      return $this->hasMany('App\SecRecientes','id_usuario','id_usuario')->orderBy('orden','asc');
    }

    public static function boot(){
      parent::boot();
      Usuario::observe(Observers\ParametrizedObserver::class);
    }

    //si el usuario forma parte del casino $id_casino devuelve verdadero
    public function usuarioTieneCasino($id_casino){
      $bandera=false;
      foreach($this->casinos as $casino){
          if($casino->id_casino == $id_casino){
              $bandera=true;
          }
      }
      return $bandera;
    }

    public function lastNotifications()
    {
      return $this->notifications()
                      ->orderBy('id', 'desc')->take(10)->get();
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_usuario;
    }

}
