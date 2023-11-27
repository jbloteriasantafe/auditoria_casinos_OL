<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use App\Usuario;
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
  
  private $BD_ARCHIVO      = 'actividades_tareas.json';
  private $ABS_BD_ARCHIVO  = null;
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
    $this->ABS_BD_ARCHIVO  = Storage::getDriver()->getAdapter()->getPathPrefix();
    $this->ABS_ADJ_CARPETA = $this->ABS_BD_ARCHIVO;
    $this->ABS_BD_ARCHIVO .= '/'.$this->BD_ARCHIVO;
    $this->ABS_ADJ_CARPETA .= '/'.$this->ADJ_CARPETA;
    
    if(!Storage::exists($this->BD_ARCHIVO)){
      $this->SET_BD([]);
    }
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
  
  private function GET_BD(){
    return collect(json_decode(Storage::get($this->BD_ARCHIVO),true));
  }
  private function SET_BD($BD){
    Storage::put($this->BD_ARCHIVO,json_encode($BD));
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
    
    $estados_sin_completar = [
      'ABIERTO' => true,
      'ESPERANDO RESPUESTA' => true
    ];
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $roles = $usuario->roles->keyBy('id_rol');
    $es_superusuario = $usuario->es_superusuario;
        
    $user_cache = [];//para evitar golpear tanto la bd
    $actividades_tareas = $this->GET_BD()
    ->sortBy('fecha')
    ->groupBy('numero')
    ->filter(function(&$ats) use (&$request,$estados_sin_completar,$roles,$usuario){
      foreach($ats as $at){
        if(is_null($at['deleted_at'])){
          if($request->mostrar_sin_completar ?? false){
            return $at['fecha'] <= $request->hasta 
            && (//Si esta sin completar o cae dentro de la ventana
              ($estados_sin_completar[$at['estado']] ?? false)
              || $at['fecha'] >= $request->desde
            );
          }
          else{
            return $at['fecha'] >= $request->desde && $at['fecha'] <= $request->hasta;
          }
        }
      }
      return false;
    })
    ->filter(function(&$ats) use ($roles,$es_superusuario){
      if($es_superusuario){
        return true;
      }
      foreach($ats->where('deleted_at',null) as $at){
        foreach(($at['roles'] ?? []) as $r){
          if(!is_null($roles[intval($r)] ?? null)){
            return true;
          }
        }
      }
      return false;
    })
    ->map(function(&$ats){
      return $ats->sortByDesc('modified_at')->values();
    });
    
    return $actividades_tareas;
  }
  
  private function generarTareas($act){
    $tareas = [];
    
    if(empty($act['hasta']) || empty($act['tipo_repeticion']) || empty($act['cada_cuanto']))
      return $tareas;
    
    for($f=$act['fecha'];$f<=$act['hasta'];$f=date('Y-m-d',strtotime($f.' +1 day'))){
      //No genero para la misma fecha
      if($f == $act['fecha']) continue;
         
      $crear_tarea = false;
      if($act['tipo_repeticion'] == 'd'){
        $diff = $this->diff_dates_days($f,$act['fecha']);
        $crear_tarea = $diff > 0 && ($diff % $act['cada_cuanto'])==0;
      }
      else if ($act['tipo_repeticion'] == 'm'){
        $diff = $this->diff_dates_logical_months($f,$act['fecha']);
        $crear_tarea = $diff > 0 && ($diff-floor($diff))==0 && ($diff % $act['cada_cuanto'])==0;
      }
      else{
        throw new \Exception("Tipo de repeticion {$act['tipo_repeticion']} no soportada");
      }
      
      if(!$crear_tarea) continue;
      
      $t = unserialize(serialize($act));
      unset($t['id_actividad_tarea']);
            
      $t['parent']  = $act['numero'];
      $t['fecha']   = $f;
      $t['repetir'] = null;
      $t['hasta']   = null;
      
      $tareas[] = $t;
    }
    
    return $tareas;
  }
  
  private function generarNumeroActividad($actividades_tareas){
    $posible_numero = null;
    while(is_null($posible_numero)){
      $posible_numero = rand();
      foreach($actividades_tareas as $aux){
        if($aux['numero'] == $posible_numero){
          $posible_numero = null;
          break;
        }
      }
    }
    return $posible_numero;
  }
  
  public function guardar(Request $R){
    $actividades_tareas = $this->GET_BD();
    $at_anterior = [];
    
    $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($R->all(), [
      'numero' => 'nullable|integer',//si es nulo esta creando una actividad
      'titulo' => 'required|string',
      'fecha' => 'required|date',
      'estado' => 'required|string',
      'generar_tareas' => 'nullable',
      'cada_cuanto' => 'required_with:generar_tareas|nullable|integer|min:0',
      'tipo_repeticion' => 'required_with:generar_tareas|nullable|string|in:d,m',
      'hasta'    => 'required_with:generar_tareas|nullable|date',
      'cambiar_tareas' => 'required_with:generar_tareas|nullable|bool',
      'contenido' => 'nullable|string',
      'adjuntos_viejos' => 'nullable|array', 
      'adjuntos_viejos.*' => 'nullable|integer',
      'roles' => 'required|array|min:1',
      'roles.*' => 'integer|exists:rol,id_rol'
    ], ['required' => 'El valor es requerido','required_with' => 'El valor es requerido'], [])
    ->after(function ($validator) use (&$actividades_tareas,&$at_anterior,&$usuario){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(isset($data['numero'])){
        foreach($actividades_tareas as $idx => $aux){
          if($aux['numero'] == $data['numero'] && is_null($aux['deleted_at'])){
            $at_anterior = $aux;
            break;
          }
        }
        if(empty($at_anterior)){
          return $validator->errors()->add('numero','No existe la actividad');
        }
      }
      if(!$usuario->es_superusuario &&
        ( 
          $usuario->roles()->whereIn('rol.id_rol',$data['roles'])->count() 
          != 
          count($data['roles'])
        )
      ){
        return $validator->errors()->add('roles','No tiene los privilegios');
      }
    })->validate();
    
    
    return DB::transaction(function() use (&$R,&$actividades_tareas,&$at_anterior,&$usuario){
      $roles = $usuario->roles->keyBy('id_rol');
      $es_superusuario = $usuario->es_superusuario;
      
      $at = [];
      $Rall = $R->all();
      $es_actividad = empty($at_anterior) || is_null($at_anterior['parent']);
      $cambiar_tareas = $es_actividad && ($Rall['cambiar_tareas'] ?? false);
      {//Manejo de atributos;
        $f_sobreescribir = function(&$at,$datos,$attrs){
          foreach($attrs as $a) $at[$a] = $datos[$a] ?? $at[$a] ?? null;
        };
        
        $f_sobreescribir($at,$at_anterior,['numero','parent','created_by','created_at']);
        
        {
          $attrs_actividad_y_tarea = ['estado','contenido','color_fondo','color_texto','color_borde'];
          $f_sobreescribir($at,$at_anterior,$attrs_actividad_y_tarea);
          $f_sobreescribir($at,$Rall,$attrs_actividad_y_tarea);
        }
        {
          $attrs_actividad = ['fecha','titulo'];
          $f_sobreescribir($at,$at_anterior,$attrs_actividad);
          if($es_actividad){
            $f_sobreescribir($at,$Rall,$attrs_actividad);
            
            {
              $strval = function($i){return $i.'';};
              $roles_anteriores = collect($at_anterior['roles'] ?? [])->map($strval);
              //Los enviados si o si le pertenecen al usuario porque son validados
              $roles_enviados = collect($Rall['roles'] ?? [])->map($strval);
              
              $roles_a_mantener = collect([]);
              if(!$es_superusuario){
                $roles_usuario = $roles->keys()->map($strval);
                $roles_a_mantener = $roles_anteriores->diff($roles_usuario);
              }
              
              $at['roles'] = $roles_enviados
              ->merge($roles_a_mantener)->unique()->values();
            }
            
            $attrs_generar_tareas = ['cada_cuanto','tipo_repeticion','hasta'];  
            $f_sobreescribir($at,$at_anterior,$attrs_generar_tareas);
            if($cambiar_tareas){
              $f_sobreescribir($at,$Rall,$attrs_generar_tareas);
            }
          }
          else{
            $at['roles'] = $at_anterior['roles'] ?? [];
            $at['dirty'] = true;//Modifico una tarea @HACK: chequear atributos
          }
        }
        
        {
          $attrs_superusuario = ['tags_api'];
          $f_sobreescribir($at,$at_anterior,$attrs_superusuario);
          if($usuario->es_superusuario){
            $f_sobreescribir($at,$Rall,$attrs_superusuario);
          }
        }
        
        $at['deleted_at']  = null;
        $at['modified_at'] = (new DateTime())->format('Y-m-d H:i:s');
        $at['modified_by'] = $usuario->id_usuario;
                
        if(empty($at_anterior)){//Nueva actividad
          $at['numero'] = $this->generarNumeroActividad($actividades_tareas);
          $at['created_at'] = $at['modified_at'];
          $at['created_by'] = $at['modified_by'];
        }
        else{
          $at_anterior['deleted_at'] = $at['modified_at'];
        }
      }
      
      {//Manejo de adjuntos
        {
          $adjuntos_anteriores = array_keys($at_anterior? $at_anterior['adjuntos'] : []);
          $adjuntos_viejos_enviados = $R->adjuntos_viejos ?? [];
          $at['adjuntos'] = [];
          foreach($adjuntos_anteriores as $adj_ant){
            if(in_array($adj_ant,$adjuntos_viejos_enviados)){
              $at['adjuntos'][$adj_ant] = $at_anterior['adjuntos'][$adj_ant];
            }
          }
        }
        
        $PATH_ARCHIVOS = $this->ADJ_CARPETA.'/'.$at['numero'];
        $ABS_PATH_ARCHIVOS = $this->ABS_ADJ_CARPETA.'/'.$at['numero'];
        
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
          $at['adjuntos'][$folder] = $filename;
          $folder+=1;
        }
      }
      
      //Guardo lo modificado
      $this->guardarActividadTarea($actividades_tareas,$at);
      if(!empty($at_anterior))
        $this->guardarActividadTarea($actividades_tareas,$at_anterior);
        
      if($cambiar_tareas){//Generar tareas para las actividades
        $tareas_nuevas = collect($this->generarTareas($at))
        ->sortByDesc('fecha');
        
        $tareas_bd = $actividades_tareas->filter(function(&$t) use (&$at){
          return $t['parent'] == $at['numero'] && is_null($t['deleted_at']);
        })
        ->sortByDesc('fecha');
        
        $fechas_movidas = [];
        //Las que estan sucias trato de moverlas a alguna de las nuevas fechas
        foreach($tareas_bd as $t){
          if($t['dirty'] ?? false){
            $closestidx = $this->matchearTarea($t['fecha'],$tareas_nuevas,$fechas_movidas);
            
            if(is_null($closestidx)){//No encontro, lo "descuelgo", pasandolo a actividad.
              $ret = $this->descolgarTarea($t,$usuario->id_usuario,$at['modified_at']);
              $tborrado = $ret[0];$tdescolgado = $ret[1];
              $this->guardarActividadTarea($actividades_tareas,$tborrado);
              $this->guardarActividadTarea($actividades_tareas,$tdescolgado);
            }
            else{//Encontro, creo una tarea nueva con esa fecha pero todos los datos iguales
              $tborrado = $this->borrarTarea($t,$usuario->id_usuario,$at['modified_at']);
              $this->guardarActividadTarea($actividades_tareas,$tborrado);
              
              $closest = $tareas_nuevas[$closestidx];
              $t['id_actividad_tarea'] = null;
              $t['fecha']       = $closest['fecha'];
              $t['modified_at'] = $closest['modified_at'];
              $t['modified_by'] = $closest['modified_by'];
              $this->guardarActividadTarea($actividades_tareas,$t);
              
              $fechas_movidas[$t['fecha']] = true;
            }
          }
          else{//Esta sin tocar, simplemente la borro para insertarle una tarea nueva correcta
            $tborrado = $this->borrarTarea($t,$usuario->id_usuario,$at['modified_at']);
            $this->guardarActividadTarea($actividades_tareas,$tborrado);
          }
        }
      
        //Para tareas nuevas que antes no estaban
        foreach($tareas_nuevas as $nt){
          //Y las fecha no fue ocupada
          if(($fechas_movidas[$nt['fecha']] ?? false))
            continue;
          //Le creo una tarea           
          $nt['numero'] = $this->generarNumeroActividad($actividades_tareas);
          $this->guardarActividadTarea($actividades_tareas,$nt);
        }
      }
        
      $this->SET_BD($actividades_tareas);
      
      return $at;
    });
  }
  
  private function matchearTarea($fecha,$tareas,$blacklist){
    $closestidx = null;
    $closestdiff = INF;//Bisect?
    foreach($tareas as $nidx => &$nt){
      $diff = abs($this->diff_dates_days($fecha,$nt['fecha']));
      if($diff < $closestdiff && ($blacklist[$nt['fecha']] ?? false) == false){
        $closestdiff = $diff;
        $closestidx = $nidx;
      }
    }
    return $closestidx;
  }
  
  public function borrar(Request $request,int $numero){
    $actividades_tareas = $this->GET_BD();
    
    $actividad = $actividades_tareas
    ->where('numero',$numero)
    ->where('parent',null)
    ->where('deleted_at',null)
    ->first();
    
    if(is_null($actividad)) return 0;
    
    $timestamp = (new DateTime())->format('Y-m-d H:i:s');    
    $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    
    $actividad['deleted_at'] = $timestamp;
    $actividad['deleted_by'] = $id_usuario;
    $actividades_tareas[$actividad['id_actividad_tarea']] = $actividad;
    
    $tareas = $actividades_tareas->where('parent',$numero)
    ->where('deleted_at',null);
    foreach($tareas as $t){
      if($t['dirty'] ?? false){
        $ret = $this->descolgarTarea($t,$id_usuario,$timestamp);
        $tborrado = $ret[0];$tdescolgado = $ret[1];
        $this->guardarActividadTarea($actividades_tareas,$tborrado);
        $this->guardarActividadTarea($actividades_tareas,$tdescolgado);
      }
      else{
        $tborrado = $this->borrarTarea($t,$id_usuario,$timestamp);
        $this->guardarActividadTarea($actividades_tareas,$tborrado);
      }
    }
    
    $this->SET_BD($actividades_tareas);
    return 1;
  }
  
  private function descolgarTarea(&$t,$id_usuario,$timestamp){
    $tborrado = $this->borrarTarea($t,$id_usuario,$timestamp);
    $tdescolgado = unserialize(serialize($t));//@HACK: hace falta clonar?
    $tdescolgado['modified_at'] = $timestamp;
    $tdescolgado['parent_original'] = $t['parent'];
    $tdescolgado['parent'] = null;
    $tdescolgado['id_actividad_tarea'] = null;
    return [$tborrado,$tdescolgado];
  }
  
  private function borrarTarea($t,$id_usuario,$timestamp,$clonar = true){
    $clonedt = unserialize(serialize($t));//@HACK: hace falta clonar?
    $clonedt['deleted_at'] = $timestamp;
    $clonedt['deleted_by'] = $id_usuario;
    return $clonedt;
  }
  
  private function guardarActividadTarea(&$actividades_tareas,$at){
    if(is_null($at['id_actividad_tarea'] ?? null)){
      $at['id_actividad_tarea'] = count($actividades_tareas);
    }
    $actividades_tareas[$at['id_actividad_tarea']] = $at;
  }
  
  public function archivo(Request $request,int $nro_ticket,int $nro_archivo){
    try{
      $f = Storage::files($this->ADJ_CARPETA()."/$nro_ticket/$nro_archivo")[0];
      return response()->download(
        Storage::getDriver()->getAdapter()->getPathPrefix().$f
      );
    }
    catch(Exception $e){
      return 'No existe el archivo';
    }
  }
}
