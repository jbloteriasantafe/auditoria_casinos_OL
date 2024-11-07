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
    ->select('beneficio_mensual.beneficio')
    ->where('fecha','=',$request->año_mes)
    ->where('id_plataforma','=',$id_plataforma->valor)
    ->orderBy('id_beneficio_mensual','desc')->first();
        
    if($bm === null) return null;
    
    return $bm->beneficio;
  });
});
