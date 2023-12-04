<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use App\Usuario;
use App\ActividadTarea;
use App\Rol;
use Validator;
use Storage;
use File;
use Exception;
use DateTime;

class ActividadesController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new self();
      }
      return self::$instance;
  }
  
  private $ADJ_CARPETA     = 'adjuntos_actividades';
  private $ABS_ADJ_CARPETA = null;
    
  private function diff_dates_days(string $yyyymmdd1,string $yyyymmdd2){
    return (strtotime($yyyymmdd1)-strtotime($yyyymmdd2))/60/60/24;
  }
  
  private function diff_dates_logical_months(string $yyyymmdd1,string $yyyymmdd2,string $sep='-'){
    $Y=0;$M=1;$D=2;
    $to_int = function(string $s): int  {return intval($s);};
    $f1 = array_map($to_int,explode('-',$yyyymmdd1));
    $f2 = array_map($to_int,explode('-',$yyyymmdd2));
    $months_passed1 = $f1[$Y]*12+$f1[$M];
    $months_passed2 = $f2[$Y]*12+$f2[$M];
    $m_diff         = $months_passed1-$months_passed2;
    
    $sign = function(int $i): int {return $i>0? +1 : ($i < 0? -1 : 0);};
    $d_diff = $sign($f1[$D]-$f2[$D])/2;
    return $m_diff + $d_diff;
  }
  
  public function __construct(){
    $this->ABS_ADJ_CARPETA = Storage::getDriver()->getAdapter()->getPathPrefix().'/'.$this->ADJ_CARPETA;
    
    if(!Storage::exists($this->ADJ_CARPETA)){
      Storage::makeDirectory($this->ADJ_CARPETA);
    }
    else if(Storage::exists($this->ADJ_CARPETA) && !File::isDirectory($this->ABS_ADJ_CARPETA)){
      throw new Exception('Error, no se puede crear carpeta de adjuntos porque ya existe un archivo con el mismo nombre');
    }
    
    /*
    foreach([
      ['2022-06-03','2022-05-03',1],
      ['2022-06-03','2022-05-02',1.5],
      ['2022-06-03','2022-05-04',0.5],
      ['2022-05-03','2022-06-03',-1],
      ['2022-05-03','2022-06-02',-0.5],
      ['2022-05-03','2022-06-04',-1.5],
      ['2023-06-03','2022-05-03',13],
      ['2023-06-03','2022-05-02',13.5],
      ['2023-06-03','2022-05-04',12.5],
      ['2022-06-03','2023-05-03',-11],
      ['2022-06-03','2023-05-02',-10.5],
      ['2022-06-03','2023-05-04',-11.5]
    ] as $vals){
      $rslt = diff_dates_logical_months($vals[0],$vals[1]);
      if($rslt != $vals[2])
        throw new Exception("diff_dates_logical_months('{$vals[0]}','{$vals[1]}') expected {$vals[2]} got $rslt");
    }*/
  }
  
  public function index(Request $request){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos = $usuario->casinos;
    $roles = $usuario->roles;
    if($roles->where('descripcion','SUPERUSUARIO')->count() > 0){
      $roles = Rol::all();
    }
    return view('Actividades.index',compact('usuario','casinos','roles'));
  }
  
  public function buscar(Request $request){
     Validator::make($request->all(), [
      'desde' => 'required|string|date',
      'hasta' => 'required|string|date',
    ], [
      'required' => 'El valor es requerido',
      'string'   => 'El valor tiene que ser una cadena',
      'date'     => 'La fecha tiene que tener formato yyyy-mm-dd',
    ], [])
    ->validate();
    
    $q = DB::table('actividad_tarea as at')
    ->select('at.*','u_c.nombre as user_created','u_m.nombre as user_modified',DB::raw('NULL as user_deleted'))
    ->join('usuario as u_c','u_c.id_usuario','=','at.created_by')
    ->join('usuario as u_m','u_m.id_usuario','=','at.modified_by')
    ->whereNull('at.deleted_at')
    ->where('at.fecha','<=',$request->hasta)
    ->orderBy('at.fecha','asc');
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$usuario->es_superusuario){
      $q = $q->whereIn('at.roles',$usuario->roles->keyBy('id_rol')->keys());
    }
    
    if($request->mostrar_sin_completar ?? false){
      $estados_sin_completar = ['ABIERTO','ESPERANDO RESPUESTA'];
      $q = $q->whereIn('at.estado',$estados_sin_completar);
    }
    else{
      $q = $q->where('at.fecha','>=',$request->desde);
    }
    
    $ats = $q->get()->groupBy('numero');
    
    $ats_to_join = DB::table('actividad_tarea as at')
    ->select('at.*','u_c.nombre as user_created','u_m.nombre as user_modified','u_d.nombre as user_deleted')
    ->join('usuario as u_c','u_c.id_usuario','=','at.created_by')
    ->join('usuario as u_m','u_m.id_usuario','=','at.modified_by')
    ->join('usuario as u_d','u_d.id_usuario','=','at.deleted_by')
    ->whereNotNull('at.deleted_at')
    ->whereIn('at.numero',$ats->keys())
    ->orderBy('at.modified_at','desc')
    ->get()->groupBy('numero');
    
    $ats = $ats->map(function($at,$numero) use (&$ats_to_join){
      return $at->merge($ats_to_join[$numero] ?? []);
    })->map(function(&$at){
      return $at->map(function(&$a){
        $a->roles = json_decode($a->roles,true);
        $a->adjuntos = json_decode($a->adjuntos,true);
        return $a;
      });
    });
    
    return $ats;
  }
  
  private function generarTareas($act){
    if(empty($act->hasta) || empty($act->tipo_repeticion) || empty($act->cada_cuanto))
      return [];
    
    $tareas = [];
    for($f=$act->fecha;$f<=$act->hasta;$f=date('Y-m-d',strtotime($f.' +1 day'))){
      //No genero para la misma fecha
      if($f == $act->fecha) continue;
         
      $crear_tarea = false;
      if($act->tipo_repeticion == 'd'){
        $diff = $this->diff_dates_days($f,$act->fecha);
        $crear_tarea = $diff > 0 && ($diff % $act->cada_cuanto)==0;
      }
      else if ($act->tipo_repeticion == 'm'){
        $diff = $this->diff_dates_logical_months($f,$act->fecha);
        $crear_tarea = $diff > 0 && ($diff-floor($diff))==0 && ($diff % $act->cada_cuanto)==0;
      }
      else{
        throw new \Exception("Tipo de repeticion {$act->tipo_repeticion} no soportada");
      }
      
      if(!$crear_tarea) continue;
      
      $t = $this->clonar($act);
      $t->numero = null;
      $t->padre_numero = $act->numero;
      $t->fecha = $f;
      $t->cada_cuanto = null;
      $t->tipo_repeticion = null;
      $t->hasta = null;
      
      $tareas[] = $t;
    }
    
    return $tareas;
  }
  
  private function generarNumeroActividad(){
    $posible_numero = null;
    while(is_null($posible_numero)){
      $posible_numero = rand();
      $existe = DB::table('actividad_tarea')
      ->where('numero','=',$posible_numero)
      ->count() > 0;
      if($existe){
        $posible_numero = null;
      }
    }
    return $posible_numero;
  }
  
  private function clonar($at_anterior){
    $at = $at_anterior->replicate();
    $at->created_at  = $at_anterior->created_at;
    $at->created_by  = $at_anterior->created_by;
    $at->modified_by = $at_anterior->modified_by;
    $at->modified_at = $at_anterior->modified_at;
    $at->deleted_at  = $at_anterior->deleted_at;
    $at->deleted_by  = $at_anterior->deleted_by;
    return $at;
  }
  
  public function guardar(Request $R){
    $at_anterior = null;
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($R->all(), [
      'numero' => 'nullable|integer',//si es nulo esta creando una actividad
      'titulo' => 'required|string',
      'fecha' => 'required|date',
      'estado' => 'required|string',
      'generar_tareas' => 'required|bool',
      'cada_cuanto' => 'required_if:generar_tareas,1|nullable|integer|min:1',
      'tipo_repeticion' => 'required_if:generar_tareas,1|nullable|string|in:d,m',
      'hasta'    => 'required_if:generar_tareas,1|nullable|date',
      'contenido' => 'nullable|string',
      'adjuntos_viejos' => 'nullable|array', 
      'adjuntos_viejos.*' => 'nullable|integer',
      'roles' => 'nullable|array|min:1',
      'roles.*' => 'integer|exists:rol,id_rol'
    ], ['required' => 'El valor es requerido','required_if' => 'El valor es requerido'], [])
    ->after(function ($validator) use (&$at_anterior,&$usuario){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(isset($data['numero'])){
        $at_anterior = ActividadTarea::where('numero','=',$data['numero'])
        ->whereNull('deleted_at')
        ->first();
        
        if(empty($at_anterior)){
          return $validator->errors()->add('numero','No existe la actividad');
        }
      }
      
      $es_actividad = !isset($data['numero']) 
                    || is_null($data['numero']) 
                    || is_null($at_anterior) 
                    || is_null($at_anterior->padre_numero);
      
      if(!$usuario->es_superusuario &&
        ( 
          $usuario->roles()->whereIn('rol.id_rol',$data['roles'])->count() 
          != 
          count($data['roles'])
        )
      ){
        return $validator->errors()->add('roles','No tiene los privilegios');
      }
      
      if($es_actividad){
        if(!isset($data['roles']) || empty($data['roles'])){
          return $validator->errors()->add('roles[]','El valor es requerido');
        }
      }
      else{
        if(isset($data['generar_tareas']) && $data['generar_tareas']){
          return $validator->errors()->add('generar_tareas','No puede generar tareas para una tarea');
        }
      }
    })->validate();
    
    return DB::transaction(function() use (&$R,&$at_anterior,&$usuario){     
      $at = null;
      $timestamp = (new DateTime())->format('Y-m-d H:i:s');
      if(empty($at_anterior)){
        $at = new ActividadTarea;
        $at->numero = $this->generarNumeroActividad();
        $at->created_at = $timestamp;
        $at->created_by = $usuario->id_usuario;
        $at->padre_numero = null;
        $at->cada_cuanto = null;
        $at->tipo_repeticion = null;
        $at->hasta = null;
      }
      else{
        $at = $this->clonar($at_anterior);
        $at_anterior->deleted_at = $timestamp;
        $at_anterior->deleted_by = $usuario->id_usuario;
        $at_anterior->save();
      }
      
      $at->modified_at = $timestamp;
      $at->modified_by = $usuario->id_usuario;
      
      $at->estado = $R->estado ?? $at->estado ?? null;
      $at->contenido = $R->contenido ?? $at->contenido ?? '';
      $at->color_fondo = $R->color_fondo ?? $at->color_fondo ?? null;
      $at->color_texto = $R->color_texto ?? $at->color_texto ?? null;
      $at->color_borde = $R->color_borde ?? $at->color_borde ?? null;
      $at->estado = $R->estado ?? $at->estado ?? null;
      
      if($usuario->es_superusuario){
        $at->tags_api = $R->tags_api ?? $at->tags_api ?? '';
      }
      
      if(is_null($at->padre_numero)){//Es actividad
        $at->fecha = $R->fecha ?? $at->fecha ?? null;
        $at->titulo = $R->titulo ?? $at->titulo ?? null;
        
        {
          $strval = function($i){return $i.'';};
          $roles_anteriores = collect(json_decode($at_anterior->roles ?? '[]',true))->map($strval);
          //Los enviados si o si le pertenecen al usuario porque son validados
          $roles_enviados = collect($R->roles ?? [])->map($strval);
          
          $roles_a_mantener = collect([]);
          if(!$usuario->es_superusuario){
            $roles_usuario = $usuario->roles->pluck('id_rol')->unique()->values()
            ->map($strval);
            $roles_a_mantener = $roles_anteriores->diff($roles_usuario);
          }
          
          $at->roles = json_encode(
            $roles_enviados->merge($roles_a_mantener)->unique()->values()->toArray()
          );
        }
      }
      else{
        $at->dirty = true;//Modifico una tarea @HACK: chequear atributos
      }
      
      
      {//Manejo de adjuntos
        $adjuntos = [];
        {
          $adjuntos_anteriores = array_keys($at_anterior? json_decode($at_anterior->adjuntos,true) : []);
          $adjuntos_viejos_enviados = $R->adjuntos_viejos ?? [];
          foreach($adjuntos_anteriores as $adj_ant){
            if(in_array($adj_ant,$adjuntos_viejos_enviados)){
              $adjuntos[$adj_ant] = $at_anterior->adjuntos[$adj_ant];
            }
          }
        }
        
        $PATH_ARCHIVOS = $this->ADJ_CARPETA.'/'.$at->numero;
        $ABS_PATH_ARCHIVOS = $this->ABS_ADJ_CARPETA.'/'.$at->numero;
        
        $folder;
        try{
          $folder = count(scandir($ABS_PATH_ARCHIVOS))-2;//'.' y '..'
        }
        catch(Exception $e){
          $folder = 0;
        }
        
        foreach($R->file('adjuntos') ?? [] as $adj){
          $filename = $adj->getClientOriginalName();
          $adj->storeAs(
            $PATH_ARCHIVOS.'/'.$folder,$filename
          );
          $adjuntos[$folder] = $filename;
          $folder+=1;
        }
        
        $at->adjuntos = json_encode($adjuntos);
      }
      
      if($R->generar_tareas){
        $at->cada_cuanto = $R->cada_cuanto;
        $at->tipo_repeticion = $R->tipo_repeticion;
        $at->hasta = $R->hasta;
      }
      
      //Guardo lo modificado
      $at->save();
        
      if($R->generar_tareas){//Generar tareas para las actividades
        $at->cada_cuanto = $R->cada_cuanto;
        $at->tipo_repeticion = $R->tipo_repeticion;
        $at->hasta = $R->hasta;
        $at->save();
        
        $tareas_nuevas = collect($this->generarTareas($at))
        ->sortByDesc('fecha');
        
        $tareas_bd = ActividadTarea::whereNull('deleted_at')
        ->where('padre_numero','=',$at->numero)
        ->orderBy('fecha','desc')->get();
        
        $fechas_movidas = [];
        //Las que estan sucias trato de moverlas a alguna de las nuevas fechas
        foreach($tareas_bd as $t){
          if($t->dirty){
            $closestidx = $this->matchearTarea($t->fecha,$tareas_nuevas,$fechas_movidas);
            if(is_null($closestidx)){//No encontro, lo "descuelgo", pasandolo a actividad.
              $newt = $this->clonar($t);
              $this->borrarTarea($t,$usuario->id_usuario,$at->modified_at);
              $this->descolgarTarea($newt,$usuario->id_usuario,$at->modified_at);
            }
            else{//Encontro, creo una tarea nueva con esa fecha pero todos los datos iguales
              $newt = $this->clonar($t);
              $this->borrarTarea($t,$usuario->id_usuario,$at->modified_at);
              $closest = $tareas_nuevas[$closestidx];
              $newt->fecha       = $closest->fecha;
              $newt->modified_at = $closest->modified_at;
              $newt->modified_by = $closest->modified_by;
              $newt->save();
              
              $fechas_movidas[$newt->fecha] = true;
            }
          }
          else{//Esta sin tocar, simplemente la borro para insertarle una tarea nueva correcta
            $this->borrarTarea($t,$usuario->id_usuario,$at->modified_at);
          }
        }
      
        //Para tareas nuevas que antes no estaban
        foreach($tareas_nuevas as $nt){
          //Y las fecha no fue ocupada
          if(($fechas_movidas[$nt->fecha] ?? false))
            continue;
          //Le creo una tarea           
          $nt->numero = $this->generarNumeroActividad();
          $nt->save();
        }
      }
        
      return $at;
    });
  }
  
  private function matchearTarea($fecha,$tareas,$blacklist){
    $closestidx = null;
    $closestdiff = INF;//Bisect?
    foreach($tareas as $nidx => &$nt){
      $diff = abs($this->diff_dates_days($fecha,$nt->fecha));
      if($diff < $closestdiff && ($blacklist[$nt->fecha] ?? false) == false){
        $closestdiff = $diff;
        $closestidx = $nidx;
      }
    }
    return $closestidx;
  }
  
  public function borrar(Request $request,int $numero){
    DB::transaction(function() use ($numero){
      $actividad = ActividadTarea::where('numero',$numero)
      ->whereNull('padre_numero')->whereNull('deleted_at')->first();
      
      if(is_null($actividad)) return 0;
      
      $timestamp = (new DateTime())->format('Y-m-d H:i:s');    
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $actividad->deleted_at = $timestamp;
      $actividad->deleted_by = $id_usuario;
      $actividad->save();
      
      foreach($actividad->tareas as $t){
        if($t->dirty){
          $tdescolgado = $this->clonar($t);
          $this->borrarTarea($t,$id_usuario,$timestamp);
          $this->descolgarTarea($t,$id_usuario,$timestamp);
        }
        else{
          $this->borrarTarea($t,$id_usuario,$timestamp);
        }
      }
      
      return 1;
    });
  }
  
  private function descolgarTarea(&$t,$id_usuario,$timestamp){
    $t->modified_at = $timestamp;
    $t->modified_by = $id_usuario;
    $t->padre_numero = null;
    $t->padre_numero_original = $t->padre_numero;
    $t->save();
  }
  
  private function borrarTarea($t,$id_usuario,$timestamp){
    $t->deleted_at = $timestamp;
    $t->deleted_by = $id_usuario;
    $t->save();
  }
  
  public function archivo(Request $request,int $nro_ticket,int $nro_archivo){
    try{
      $f = Storage::files($this->ADJ_CARPETA."/$nro_ticket/$nro_archivo")[0];
      
      return response()->download(
        Storage::getDriver()->getAdapter()->getPathPrefix().$f
      );
    }
    catch(Exception $e){
      return 'No existe el archivo';
    }
  }
}
