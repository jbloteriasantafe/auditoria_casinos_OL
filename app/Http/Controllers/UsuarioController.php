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
    Validator::make($request->all(), [
      'usuario' => ['required' , 'max:45' , 'unique:usuario,user_name'] ,
      'email' => ['required' , 'max:45' , 'unique:usuario,email'],
      'contraseña' => ['required', 'max:45'],
      'nombre' => ['required'],
      'imagen' => ['nullable', 'image'],
      'plataformas' => 'required'
     ])->after(function ($validator){
          //validar que descripcion no exista
        $email =$validator->getData()['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $validator->errors()->add('email', 'Formato de email inválido.');
        }
        $user = $this->quienSoy()['usuario'];
        $plats = array();
        foreach ($user->plataformas as $p) {
          $plats[]=$p->id_plataforma;
        }
        foreach ($validator->getData()['plataformas'] as $p) {
          if(!in_array($p,$plats)){
            $validator->errors()->add('id_plataforma', 'FAIL.');
            break;
          }
        }
    })->validate();

    $usuario= new Usuario;
    $usuario->nombre = $request->nombre;
    $usuario->user_name = $request->usuario;
    $usuario->password= $request->contraseña;
    $usuario->email = $request->email;
    if($request->imagen != null){
      $usuario->imagen = base64_encode(file_get_contents($request->imagen->getRealPath()));
    }
    $usuario->save();

    if(!empty($request->roles)){
      $usuario->roles()->sync($request->roles);
    }
    if(!empty($request->plataformas)){
      //falta validar que sea de al menos una plataforma
      $usuario->plataformas()->sync($request->plataformas);
    }
    $usuario->save();
    return ['usuario' => $usuario];
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

  public function modificarUsuario(Request $request){
    $this->validate($request, [
      'nombre' => ['required','max:45', Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      'user_name' => ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      'email' =>  ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')]
    ], ['user_name.required' => 'El campo Nombre de usuario no puede estar vacio']);

    $usuario = Usuario::find($request->id_usuario);
    $usuario->nombre = $request->nombre;
    $usuario->user_name = $request->user_name;
    $usuario->email = $request->email;
    $usuario->save();
    $usuario->roles()->sync($request->roles);
    $usuario->plataformas()->sync($request->plataformas);
    return ['usuario' => $usuario];
  }

  public function eliminarUsuario(Request $request){
    $usuario = Usuario::find($request->id);
    $usuario->roles()->detach();
    $usuario->plataformas()->detach();
    $usuario->delete();
    return ['usuario' => $usuario];
  }

  public function buscarUsuarios(Request $request){
    $nombre = (empty($request->nombre)) ? '%' : '%'.$request->nombre.'%';
    $usuario = (empty($request->usuario)) ? '%' : '%'.$request->usuario.'%';
    $email = (empty($request->email)) ? '%' : '%'.$request->email.'%';

    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $plats = array();
    foreach ($user->plataformas as $p) {
      $plats[]=$p->id_plataforma;
    }

    $resultado=DB::table('usuario')
    ->select('usuario.*')
    ->join('usuario_tiene_plataforma','usuario_tiene_plataforma.id_usuario','=','usuario.id_usuario')
    ->where([['usuario.nombre','like',$nombre],['usuario.user_name','like',$usuario],['usuario.email','like',$email]])
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
  public function buscarUsuario($id){
    $usuario=Usuario::find($id);
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'plataformas' => $usuario->plataformas];
  }
  //en la seccion usuarios (ajaxUsuarios.js)
  public function buscarUsuarioSecUsuarios($id){
    $usuario=Usuario::find($id);
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'plataformas' => $usuario->plataformas,
            'superusuario' => $this->quienSoy()['usuario']->es_superusuario];
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

  public function reestablecerContraseña(Request $request){
    $validator=Validator::make($request->all(), [
      'id_usuario' => ['required'  , 'exists:usuario,id_usuario'] ,
     ])->after(function ($validator){

      });
    $validator->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->password = $usuario->dni;
    $usuario->save();

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
