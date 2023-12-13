<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use App\Usuario;
use App\ActividadTarea;
use App\ActividadTareaGrupo;
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
  
  private $estados = ['ABIERTO','ESPERANDO RESPUESTA','HECHO','CERRADO SIN SOLUCIÓN','CERRADO'];
  private $estados_sin_completar = ['ABIERTO','ESPERANDO RESPUESTA'];
  private $estados_completados = ['HECHO','CERRADO SIN SOLUCIÓN','CERRADO'];
  
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
    $estados = $this->estados;
    $estados_completados = $this->estados_completados;
    $estados_sin_completar = $this->estados_sin_completar;
    return view('Actividades.index',compact(
      'usuario','casinos','roles','estados',
      'estados_completados','estados_sin_completar'
    ));
  }
  
  public function buscar(Request $request){
    Validator::make($request->all(), [
      'desde' => 'required|string|date',
      'hasta' => 'required|string|date',
      'mostrar_sin_completar' => 'required|bool',
    ], [
      'required' => 'El valor es requerido',
      'string'   => 'El valor tiene que ser una cadena',
      'date'     => 'La fecha tiene que tener formato yyyy-mm-dd',
      'bool'     => 'El valor tiene que ser 0 o 1',
    ], [])
    ->validate();
    
    $q = DB::table('actividad_tarea as at')
    ->select('at.numero','at.fecha','at.estado','at.titulo','at.roles',
    DB::raw('at.padre_numero IS NULL as es_actividad'),
    DB::raw('at.estado IN ("'.implode('","',$this->estados_completados).'") as finalizado'),
    'at.color_texto','at.color_fondo','at.color_borde')
    ->whereNull('at.deleted_at')
    ->where('at.fecha','<=',$request->hasta)
    ->orderBy('at.fecha','asc');
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    if($request->mostrar_sin_completar){
      $q = $q->where(function($q) use (&$request){
        return $q->whereIn('at.estado',$this->estados_sin_completar)
        ->orWhere('at.fecha','>=',$request->desde);
      });
    }
    else{
      $q = $q->where('at.fecha','>=',$request->desde);
    }
    
    $roles;
    if($usuario->es_superusuario){
      $roles = Rol::all()->pluck('id_rol');
    }
    else{
      $roles = $usuario->roles->pluck('id_rol');
    }
        
    $ats = $q->get()->filter(function(&$at) use (&$roles){
      return true;
      $at->roles = json_decode($at->roles ?? '[]',true);
      if(count($roles->intersect($at->roles)) == 0)
        return false;
      return true;
    })
    ->transform(function(&$at){
      unset($at->roles);
      return $at;
    });
    
    return $ats;
  }
  
  public function obtener(Request $request,int $numero){
    Validator::make(compact('numero'), [
      'numero' => 'required|integer|exists:actividad_tarea,numero,deleted_at,NULL',
    ], [
      'required' => 'El valor es requerido',
      'integer'   => 'El valor tiene que ser un numero',
      'exits'     => 'No existe',
    ], [])
    ->validate();
    
    $datos = DB::table('actividad_tarea as at')
    ->select('at.*')
    ->where('at.numero','=',$numero)
    ->orderBy(DB::raw('at.deleted_at IS NULL'),'desc')//primero el que esta vigente
    ->orderBy('at.deleted_at','desc')//despues los demas en ordenes descendente de eliminacion
    ->get()
    ->map(function(&$at){
      $at->roles = json_decode($at->roles ?? '[]',true);
      $at->adjuntos = json_decode($at->adjuntos ?? '[]',true);
      return $at;
    });
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $roles;
    if($usuario->es_superusuario){
      $roles = Rol::all()->pluck('id_rol');
    }
    else{
      $roles = $usuario->roles->pluck('id_rol');
    }
        
    if(is_null($datos->first()) || count($roles->intersect($datos->first()->roles)) == 0){
      return response()->json(['numero' => ['No existe o no tiene acceso']],422);
    }
    
    return $datos;
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
  
  private function generarNumero($tabla){
    $posible_numero = null;
    while(is_null($posible_numero)){
      $posible_numero = rand();
      $existe = DB::table($tabla)
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
    $at->created_at  = $at_anterior->created_at;;
    $at->modified_at = $at_anterior->modified_at;
    $at->deleted_at  = $at_anterior->deleted_at;
    return $at;
  }
  
  public function guardar(Request $R){
    $at_anterior = null;
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($R->all(), [
      'numero' => 'nullable|integer',//si es nulo esta creando una actividad
      'titulo' => 'required|string',
      'fecha' => 'required|date',
      'estado' => 'required|string|in:'.implode(',',$this->estados),
      'generar_tareas' => 'required|bool',
      'cada_cuanto' => 'required_if:generar_tareas,1|nullable|integer|min:1',
      'tipo_repeticion' => 'required_if:generar_tareas,1|nullable|string|in:d,m',
      'hasta'    => 'required_if:generar_tareas,1|nullable|date|after:fecha',
      'contenido' => 'nullable|string',
      'adjuntos_viejos' => 'nullable|array', 
      'adjuntos_viejos.*' => 'nullable|integer',
      'roles' => 'nullable|array|min:1',
      'roles.*' => 'integer|exists:rol,id_rol',
      'color_fondo' => ['required','string','regex:/^#([0-9]|[A-F]){6}$/i'],
      'color_texto' => ['required','string','regex:/^#([0-9]|[A-F]){6}$/i'],
      'color_borde' => ['required','string','regex:/^#([0-9]|[A-F]){6}$/i'],
      'tags_api' => 'nullable|string',
    ], [
      'required' => 'El valor es requerido',
      'required_if' => 'El valor es requerido',
      'integer' => 'El valor tiene que ser un numero',
      'min' => 'El valor es menor al valor limite',
      'after' => 'El valor es menor que el de referencia',
      'in' => 'El valor no se encuentra dentro de los valores esperados',
    ], [])
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
          return $validator->errors()->add('roles','El valor es requerido');
        }
      }
      else{
        if(isset($data['generar_tareas']) && $data['generar_tareas']){
          return $validator->errors()->add('generar_tareas','No puede generar tareas para una tarea');
        }
        if($data['fecha'] !== ($at_anterior->fecha ?? null)){
          return $validator->errors()->add('fecha','No puede alterar la fecha de una tarea');
        }
        if($data['titulo'] !== ($at_anterior->titulo ?? null)){
          return $validator->errors()->add('titulo','No puede alterar el titulo de una tarea');
        }
      }
    })->validate();
    
    return DB::transaction(function() use (&$R,&$at_anterior,&$usuario){     
      $at = null;
      $timestamp = (new DateTime())->format('Y-m-d H:i:s');
      if(empty($at_anterior)){
        $at = new ActividadTarea;
        $at->numero = $this->generarNumero('actividad_tarea');
        $at->created_at = $timestamp;
        $at->created_by = $usuario->user_name;
        $at->padre_numero = null;
        $at->cada_cuanto = null;
        $at->tipo_repeticion = null;
        $at->hasta = null;
      }
      else{
        $at = $this->clonar($at_anterior);
        $at_anterior->deleted_at = $timestamp;
        $at_anterior->deleted_by = $usuario->user_name;
        $at_anterior->save();
      }
      
      $at->modified_at = $timestamp;
      $at->modified_by = $usuario->user_name;
      
      $at->estado = $R->estado;
      $at->contenido = $R->contenido ?? '';
      $at->color_fondo = $R->color_fondo;
      $at->color_texto = $R->color_texto;
      $at->color_borde = $R->color_borde;
      $at->estado = $R->estado;
      
      if($usuario->es_superusuario){
        $at->tags_api = $R->tags_api ?? $at->tags_api ?? '';
      }
      
      if(is_null($at->padre_numero)){//Es actividad
        $at->fecha  = $R->fecha;
        $at->titulo = $R->titulo;
        
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
              $this->borrarTarea($t,$usuario->user_name,$at->modified_at);
              $this->descolgarTarea($newt,$usuario->user_name,$at->modified_at);
            }
            else{//Encontro, creo una tarea nueva con esa fecha pero todos los datos iguales
              $newt = $this->clonar($t);
              $this->borrarTarea($t,$usuario->user_name,$at->modified_at);
              $closest = $tareas_nuevas[$closestidx];
              $newt->fecha       = $closest->fecha;
              $newt->modified_at = $closest->modified_at;
              $newt->modified_by = $closest->modified_by;
              $newt->save();
              
              $fechas_movidas[$newt->fecha] = true;
            }
          }
          else{//Esta sin tocar, simplemente la borro para insertarle una tarea nueva correcta
            $this->borrarTarea($t,$usuario->user_name,$at->modified_at);
          }
        }
      
        //Para tareas nuevas que antes no estaban
        foreach($tareas_nuevas as $nt){
          //Y las fecha no fue ocupada
          if(($fechas_movidas[$nt->fecha] ?? false))
            continue;
          //Le creo una tarea           
          $nt->numero = $this->generarNumero('actividad_tarea');
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
      $user_name = UsuarioController::getInstancia()->quienSoy()['usuario']->user_name;
      
      $actividad->deleted_at = $timestamp;
      $actividad->deleted_by = $user_name;
      $actividad->save();
      
      foreach($actividad->tareas as $t){
        if($t->dirty){
          $tdescolgado = $this->clonar($t);
          $this->borrarTarea($t,$user_name,$timestamp);
          $this->descolgarTarea($tdescolgado,$user_name,$timestamp);
        }
        else{
          $this->borrarTarea($t,$user_name,$timestamp);
        }
      }
      
      return 1;
    });
  }
  
  private function descolgarTarea(&$t,$user_name,$timestamp){
    $t->modified_at = $timestamp;
    $t->modified_by = $user_name;
    $t->padre_numero_original = $t->padre_numero;
    $t->padre_numero = null;
    $t->save();
  }
  
  private function borrarTarea($t,$user_name,$timestamp){
    $t->deleted_at = $timestamp;
    $t->deleted_by = $user_name;
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
  
  public function cambiarEstado(Request $request){
    $actividad_tarea = null;
    $timestamp = (new DateTime())->format('Y-m-d H:i:s');
    $estados = implode(',',$this->estados);
    $validator = Validator::make($request->all(), [
      'fecha'    => 'required|date',
      'tags_api' => 'required|string',
      'user_name' => 'required|string|exists:usuario,user_name,deleted_at,NULL',
      'nuevos_datos' => 'required|array',
      'nuevos_datos.estado' => 'nullable|string|in:'.$estados,
      'nuevos_datos.contenido' => 'nullable|string',
    ], [
      'required' => 'El valor es requerido',
      'date' => 'Tiene que ser una fecha',
      'integer' => 'El valor tiene que ser un número entero',
      'exists' => 'El valor no es valido',
      'in' => 'El estado tiene que estar dentro de los siguientes valores: '.$estados,
    ], [])
    ->after(function ($validator) use (&$actividad_tarea){
      if($validator->errors()->any()) return;
      
      $api_token = AuthenticationController::getInstancia()->obtenerAPIToken();
      $metadata = json_decode($api_token->metadata,true);
      if(!($metadata['puede_cambiar_estados_actividades'] ?? false)){
        return $validator->errors()->add('privilegios','No tiene los permisos');
      }
      
      $data = $validator->getData();
      
      $actividad_tarea = ActividadTarea::where('fecha','=',$data['fecha'])
      ->whereNull('deleted_at')
      ->where('tags_api','=',$data['tags_api'])
      ->orderBy('created_at','desc')->first();
      
      if(is_null($actividad_tarea)){
        return $validator->errors()->add('actividad_tarea','No existe una actividad o tarea con esos parametros');
      }
      
      //@TODO validar usuario tiene rol para acceder?
    });
    
    if($validator->errors()->any()) return response()->json($validator->errors(),422);
    
    return DB::transaction(function() use (&$request,&$actividad_tarea,&$timestamp){
      $user_name = UsuarioController::getInstancia()->quienSoy()['usuario']->user_name;
      
      $nuevo = $this->clonar($actividad_tarea);
      $actividad_tarea->deleted_at = $timestamp;
      $actividad_tarea->deleted_by = $user_name;
      $actividad_tarea->save();
      
      $nuevo->estado = $request->nuevos_datos['estado'] ?? $nuevo->estado;
      $nuevo->contenido = $request->nuevos_datos['contenido'] ?? $nuevo->contenido;
      $nuevo->modified_at = $timestamp;
      $nuevo->modified_by = $user_name;
      $nuevo->save();
      return 1;
    });
  }
  
  public function grupos_buscar(Request $request){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($request->all(), [], [], [])
    ->after(function($validator) use (&$usuario){
      if($validator->errors()->any()) return;
      if(!$usuario->es_superusuario){
        return $validator->errors()->add('privilegios','No tiene acceso');
      }
    })->validate();
    
    return DB::table('actividad_tarea_grupo as g')
    //saco la coma de inicio y fin
    ->select('g.numero','g.nombre',DB::raw('SUBSTRING(g.usuarios,2,LENGTH(g.usuarios)-2) as usuarios'))
    ->whereNull('g.deleted_at')
    ->orderBy('g.nombre','asc')
    ->get();
  }
  
  public function grupos_guardar(Request $request){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($request->all(), [
      'numero' => 'nullable|integer|exists:actividad_tarea_grupo,numero,deleted_at,NULL',
      'nombre' => 'required|string',
      'usuarios' => 'required|string',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor no existe',
    ], [])->after(function($validator) use (&$usuario){
      if($validator->errors()->any()) return;
      if(!$usuario->es_superusuario){
        return $validator->errors()->add('privilegios','No tiene acceso');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$usuario){
      $grupo_viejo = ActividadTareaGrupo::where('numero','=',$request->numero ?? null)
      ->whereNull('deleted_at')->first();
      $timestamp = (new DateTime())->format('Y-m-d H:i:s');
            
      $grupo;
      if(is_null($grupo_viejo)){
        $grupo = new ActividadTareaGrupo;
        $grupo->numero = $this->generarNumero('actividad_tarea_grupo');
        $grupo->created_at = $timestamp;
        $grupo->created_by = $usuario->user_name;
      }
      else{
        $grupo = $this->clonar($grupo_viejo);
        
        $grupo_viejo->deleted_at = $timestamp;
        $grupo_viejo->deleted_by = $usuario->user_name;
        $grupo_viejo->save();
      }
      
      $grupo->modified_at = $timestamp;
      $grupo->modified_by = $usuario->user_name;
      $grupo->nombre = $request->nombre;
      $grupo->usuarios = ','.implode(
        ',',
        array_map(
          function($u){return trim($u);},
          explode(',',$request->usuarios)
        )
      ).',';
      
      $grupo->save();
      return 1;
    });
  }
  
  public function grupos_borrar(Request $request){
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($request->all(), [
      'numero' => 'nullable|integer|exists:actividad_tarea_grupo,numero,deleted_at,NULL',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor no existe',
    ], [])->after(function($validator) use (&$usuario){
      if($validator->errors()->any()) return;
      if(!$usuario->es_superusuario){
        return $validator->errors()->add('privilegios','No tiene acceso');
      }
    })->validate();
    
    $grupo = ActividadTareaGrupo::where('numero','=',$request->numero)
    ->whereNull('deleted_at')->first();
    
    $grupo->deleted_at =  (new DateTime())->format('Y-m-d H:i:s');
    $grupo->deleted_by = $usuario->user_name;
    $grupo->save();
    return 1;
  }
}
