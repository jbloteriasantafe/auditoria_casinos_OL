<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use App\Usuario;
use App\Rol;
use App\Plataforma;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class UsuarioController extends Controller
{
  private static $atributos = [
    'user_name' => 'Nombre Usuario',
    'password' => 'Contraseña'
  ];

  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new UsuarioController();
      }
      return self::$instance;
  }

  public function guardarUsuario(Request $request){
    $user = $this->quienSoy()['usuario'];
    $plats = [];
    foreach ($user->plataformas as $p) {
      $plats[]=$p->id_plataforma;
    }
    Validator::make($request->all(), [
      'id_usuario' => 'nullable|integer|exists:usuario,id_usuario',
      'nombre' => 'required|max:100|unique:usuario,nombre,'.$request->id_usuario.',id_usuario',
      'user_name' => 'required|max:45|unique:usuario,user_name,'.$request->id_usuario.',id_usuario',
      'email' =>  'required|max:70|unique:usuario,email,'.$request->id_usuario.',id_usuario',
      'password' => 'required_if:id_usuario,""|max:45',
      'imagen' => 'nullable|image',
      'plataformas' => 'required|array',
      'plataformas.*' => 'exists:plataforma,id_plataforma',
      'roles' => 'required|array',
      'roles.*' => 'exists:rol,id_rol',
    ], [
      'required' => 'No puede estar vacio','required_if' => 'No puede estar vacio','unique' => 'Tiene que ser único',
      'max' => 'Supera el limite'
    ])->after(function ($v) use ($plats){
      //validar que descripcion no exista
      if($v->errors()->any()) return;
      $email = $v->getData()['email'];
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $v->errors()->add('email', 'Formato de email inválido.');
      }
      foreach ($v->getData()['plataformas'] as $p) {
        if(!in_array($p,$plats)){
          $v->errors()->add('id_plataforma', 'No puede acceder a la plataforma.');
          break;
        }
      }
    })->validate();

    DB::transaction(function () use ($request){
      $usuario = null;
      if(!empty($request->id_usuario)){ 
        $usuario = Usuario::find($request->id_usuario);
      }
      else{
        $usuario = new Usuario;
        $usuario->password = $request->password; 
      }
      $usuario->nombre = $request->nombre;
      $usuario->user_name = $request->user_name;
      $usuario->email = $request->email;
      if($request->imagen != null){
        $usuario->imagen = base64_encode(file_get_contents($request->imagen->getRealPath()));
      }
      $usuario->save();
      $usuario->roles()->sync($request->roles);
      $usuario->plataformas()->sync($request->plataformas);
    });
    
    return 1;
  }

  public function modificarImagen(Request $request){
    Validator::make($request->all(),[
        'id_usuario' => 'required|exists:usuario,id_usuario',
        // 'imagen' => ['nullable','image'],
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);

    $imagen = $request->imagen;
    if(!empty($imagen)){
      list(, $imagen) = explode(';', $imagen);
      list(, $imagen) = explode(',', $imagen);
        $usuario->imagen = $imagen;
    }
    $usuario->save();

    return ['imagen' => $usuario->imagen];
  }

  public function modificarPassword(Request $request){
    Validator::make($request->all(),[
        'id_usuario' => 'required|exists:usuario,id_usuario',
        'password_actual' => 'required|max:45',
        'password_nuevo' => 'required|max:45|confirmed',
        'password_nuevo_confirmation' => 'required|max:45'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
      $usuario = Usuario::find(session('id_usuario'));
      if($usuario->password != $validator->getData()['password_actual']){
        $validator->errors()->add('password_incorrecta','La contraseña actual no coincide con la del usuario.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->password = $request->password_nuevo;
    $usuario->save();

    return $usuario;
  }

  public function modificarDatos(Request $request){
    Validator::make($request->all(),[
      'id_usuario' => 'required|exists:usuario,id_usuario',
      'user_name' => ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      'email' =>  ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')]
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->user_name = $request->user_name;
    $usuario->email = $request->email;
    $usuario->save();

    return $usuario;
  }

  public function eliminarUsuario($id_usuario){
    DB::transaction(function () use ($id_usuario){
      $usuario = Usuario::find($id_usuario);
      $usuario->roles()->detach();
      $usuario->plataformas()->detach();
      $usuario->delete();
    });
    return ['codigo' => 200];
  }

  public function buscarUsuarios(Request $request){
    $reglas = [];
    if(!empty($request->nombre)) $reglas[] = ['usuario.nombre','like','%'.$request->nombre.'%'];
    if(!empty($request->usuario)) $reglas[] = ['usuario.user_name','like','%'.$request->usuario.'%'];
    if(!empty($request->email)) $reglas[] = ['usuario.email','like','%'.$request->email.'%'];
    if(!empty($request->id_plataforma)) $reglas[] = ['usuario_tiene_plataforma.id_plataforma','=',$request->id_plataforma];
    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = array();
    foreach ($user->plataformas as $p) {
      $plats[]=$p->id_plataforma;
    }

    $resultado=DB::table('usuario')
    ->select('usuario.*')
    ->join('usuario_tiene_plataforma','usuario_tiene_plataforma.id_usuario','=','usuario.id_usuario')
    ->where($reglas)
    ->whereIn('usuario_tiene_plataforma.id_plataforma',$plats)
    ->whereNull('usuario.deleted_at')
    ->distinct('id_usuario')
    ->orderBy('user_name','asc')
    ->get();

    return ['usuarios' => $resultado];

  }

  public function buscarTodo(){
    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $plataformas = [];
    $roles = [];
    if($user->es_superusuario){
      $plataformas = Plataforma::all();
      $roles = Rol::all();
    }
    else{
      $plataformas = $user->plataformas;
      $roles = Rol::whereNotIn('id_rol',[1,5,6])->get();
    }

    $this->agregarSeccionReciente('Usuarios' ,'usuarios');
    return view('seccionUsuarios',  ['roles' => $roles , 'plataformas' => $plataformas]);
  }

  //sin la session iniciada usa esta funcion ----
  public function buscarUsuario($id_usuario){
    $usuario=Usuario::find($id_usuario);//TODO SACAR PASSWD
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'plataformas' => $usuario->plataformas];
  }

  public function configUsuario(){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $this->agregarSeccionReciente('Configuración de Cuenta', 'configCuenta');
    return view('seccionConfigCuenta')->with('usuario',$usuario);
  }

  public function leerImagenUsuario(){
    $file = Usuario::find(session('id_usuario'));
    $data = $file->imagen;
    return Response::make(base64_decode($data), 200, [ 'Content-Type' => 'image/jpeg',
                                                      'Content-Disposition' => 'inline; filename="lalaaa.jpeg"']);
  }

  public function tieneImagen(){
    $file = Usuario::find(session('id_usuario'));
    $data = $file->imagen;
    return $data != null;
  }

  public function reestablecerContraseña($id_usuario){
    Validator::make(["id_usuario" => $id_usuario], [
      'id_usuario' => ['required'  , 'exists:usuario,id_usuario'] ,
    ])->validate();

    DB::transaction(function () use ($id_usuario){
      $usuario = Usuario::find($id_usuario);
      $usuario->password = $usuario->dni;
      $usuario->save();
    });

    return ['codigo' => 200];
  }

  public function agregarSeccionReciente($seccion , $ruta){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $user = Usuario::find($usuario['usuario']->id_usuario);

    //si no tiene creadas las secciones_recientes
    if($user->secciones_recientes->count() == 0){
      for($i=1;$i<=4;$i++){
        $sec1 = new SecRecientes;
        $sec1->orden = $i;
        $sec1->seccion = $seccion;
        $sec1->ruta = $ruta;
        $sec1->usuario()->associate($user->id_usuario);
        $sec1->save();
        $seccion = null;
        $ruta = null;
      }
    }else{
      $secciones = $user->secciones_recientes;
      $secciones_nombres = [];
      foreach($secciones as $s) $secciones_nombres[] = $s->seccion;
      if(!in_array($seccion,$secciones_nombres)){//Evita repetidos
        for($i=3;$i>=1;$i--){
          $secciones[$i]->seccion = $secciones[$i-1]->seccion;
          $secciones[$i]->ruta    = $secciones[$i-1]->ruta;
          $secciones[$i]->save();
        }
        $secciones[0]->seccion = $seccion;
        $secciones[0]->ruta    = $ruta;
        $secciones[0]->save();
      }
    }
  }

  public function obtenerUsuariosRol($id_plataforma, $id_rol){
    $rta = DB::table('usuario')
                ->join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                ->join('usuario_tiene_plataforma','usuario.id_usuario','=','usuario_tiene_plataforma.id_usuario')
                ->whereIn('usuario_tiene_rol.id_rol',$id_rol)
                ->where('usuario_tiene_plataforma.id_plataforma','=', $id_plataforma)
                ->whereNull('usuario.deleted_at')
                ->get();
    return $rta;
  }

  public function quienSoy(){
    $usuario = $this->buscarUsuario(session('id_usuario'))['usuario'];
    return ['usuario' => $usuario];
  }
}
