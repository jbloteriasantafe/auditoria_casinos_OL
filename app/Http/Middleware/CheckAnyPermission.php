<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;

class CheckAnyPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$permisos)
    {
      $AC = AuthenticationController::getInstancia();
      $id_usuario = $AC->obtenerIdUsuario();

      if(is_null($id_usuario)) return $this->errorOut($request);

      if(empty($permisos)) return $next($request);

      foreach($permisos as $permiso){
        if($AC->usuarioTienePermiso($id_usuario,$permiso)){
          return $next($request);
        }
      }
      return $this->errorOut($request);
    }

    private function errorOut($request){
      $url_to_redirect = 'inicio';
      if($request->ajax()){
        return response()->json(['mensaje' => 'No tiene los permisos encesarios para realizar dicha acción.','url' => $url_to_redirect],
                                351,[['Content-Type','application/json']]);
      }
      dump(__FILE__.' '.__LINE__);
      return redirect($url_to_redirect);
    }
}
