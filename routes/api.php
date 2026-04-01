<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\DB;

class VerificarPermisoObtenerBruto {
  public function handle($request,$next){
    $api_token = AuthenticationController::getInstancia()->obtenerAPIToken();
      
    if(is_null($api_token) || !($api_token->metadata['puede_obtener_bruto'] ?? true)){
      return response()->json(['privilegios' => 'No puede realizar la acción'],422);
    }
      
    return $next($request);
  }
};

Route::prefix('auditoria')->group(function(){
  Route::get('plataformas',function(){
    $plataformas = DB::table('plataforma')
    ->select('id_plataforma','nombre','codigo')
    ->get();
    return response()->json($plataformas);
  });

  Route::get('juegos',function(Request $request){
    if(empty($request->plataforma)) return response()->json(['plataforma' => 'Falta'],422);

    $query = DB::table('juego as j')
    ->select('j.id_juego','j.nombre_juego','j.cod_juego','j.porcentaje_devolucion',
             'j.escritorio','j.movil','cj.nombre as categoria')
    ->join('plataforma_tiene_juego as ptj','ptj.id_juego','=','j.id_juego')
    ->leftJoin('estado_juego as ej','ej.id_estado_juego','=','ptj.id_estado_juego')
    ->leftJoin('categoria_juego as cj','cj.id_categoria_juego','=','j.id_categoria_juego')
    ->where('ptj.id_plataforma',$request->plataforma)
    ->where(function($q){ $q->where('ej.nombre','Activo')->orWhereNull('ptj.id_estado_juego'); })
    ->whereNull('j.deleted_at');

    if(!empty($request->busqueda)){
      $query->where(function($q) use ($request){
        $q->where('j.nombre_juego','LIKE','%'.$request->busqueda.'%')
          ->orWhere('j.cod_juego','LIKE','%'.$request->busqueda.'%');
      });
    }

    return response()->json($query->limit(20)->get());
  });
});

Route::group(['middleware' => ['check_API_token',VerificarPermisoObtenerBruto::class]],function(){
  Route::post('bruto',function(Request $request){
    if(empty($request->año_mes)) return response()->json(['año_mes' => 'Falta'],423);
    if(empty($request->id_casino)) return response()->json(['id_casino' => 'Falta'],424);
    
    $id_plataforma = DB::table('plataforma_tiene_casino')
    ->select('id_plataforma as valor')
    ->where('id_casino',$request->id_casino)
    ->first();
    
    if($id_plataforma === null) return response()->json(['id_casino' => 'Falta en tabla'],425);
    
    $bm = DB::table('beneficio_mensual')
    ->select('beneficio')
    ->where('fecha','=',$request->año_mes)
    ->where('id_plataforma','=',$id_plataforma->valor)
    ->orderBy('id_beneficio_mensual','desc')->first();
    
    $bmp = DB::table('beneficio_mensual_poker')
    ->select('utilidad')
    ->where('fecha','=',$request->año_mes)
    ->where('id_plataforma','=',$id_plataforma->valor)
    ->orderBy('id_beneficio_mensual_poker','desc')->first();
        
    if($bm === null && $bmp === null) return null;
    
    return bcadd($bm === null? '0' : $bm->beneficio,$bmp === null? '0' : $bmp->utilidad,2);
  });
});
