<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;

class CheckSessionTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $tiempo_sesion = 14400;// segundos

    public function handle($request, Closure $next)
    {
      $ahora = date_create();
      $ultima_actividad = $request->session()->has('last_activity_time') ? $request->session()->get('last_activity_time') : null;
      if($ultima_actividad != null && date_diff($ahora,$ultima_actividad)->format('%s') > $this->tiempo_sesion && $request->path() != 'login'){
        AuthenticationController::getInstancia()->logout($request);
        $request->session()->put('redirect',$request->path());
        if($request->ajax()){
          return response()->json(['mensaje' => 'Su sesión ha expirado, ingrese de nuevo al sistema','url' => '/'],351,[['Content-Type', 'application/json']]);
        }
        else{
          return redirect('/');
        }
      }
      else{ // se actualiza el tiempo de la ultima actividad
        $request->session()->put('last_activity_time',$ahora);
        return $next($request);
      }
    }
}
