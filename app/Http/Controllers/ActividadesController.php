<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use App\Usuario;
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
    return view('Actividades.index',compact('usuario','casinos'));
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
    
    $user_cache = [];//para evitar golpear tanto la bd
    $actividades_tareas = $this->GET_BD()
    ->filter(function(&$at) use ($request,&$user_cache){
      if($at['fecha'] >= $request->desde && $at['fecha'] <= $request->hasta){
        if(array_key_exists($at['created_by'],$user_cache)){
          $at['user_created'] = $user_cache[$at['created_by']];
        }
        else{
          $at['user_created'] = Usuario::withTrashed()->find($at['created_by'])->nombre;
          $user_cache[$at['created_by']] = $at['user_created'];
        }
        if(array_key_exists($at['modified_by'],$user_cache)){
          $at['user_modified'] = $user_cache[$at['created_by']];
        }
        else{
          $at['user_modified'] = Usuario::withTrashed()->find($at['modified_by'])->nombre;
          $user_cache[$at['modified_by']] = $at['user_modified'];
        }
        return true;
      }
      return false;
    });
      
    //TODO: ordenar por fecha y modified_at
    return $actividades_tareas->sortByDesc('modified_at')->groupBy('numero');
  }
  
  private function generarTareas($act){
    $es_numero = function($i){return $i == intval($i);};
    $tareas = [];
    
    if(empty($act['hasta']) || empty($act['repetir']))
      return $tareas;
    
    for($f=$act['fecha'];$f<=$act['hasta'];$f=date('Y-m-d',strtotime($f.' +1 day'))){
      //No genero para la misma fecha
      if($f == $act['fecha']) continue;
            
      $diario  = $act['repetir'] == 'd';
      $semanal = $act['repetir'] == 'w' && ($this->diff_dates_days($f,$act['fecha']) % 7) == 0;
      $mensual = $act['repetir'] == 'm' && $es_numero($this->diff_dates_logical_months($f,$act['fecha']));
      $crear_tarea = $diario || $semanal || $mensual;
      
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
    
    Validator::make($R->all(), [
      'numero' => 'nullable|integer',//si es nulo esta creando una actividad
      'titulo' => 'required|string',
      'fecha' => 'required|date',
      'estado' => 'required|string',
      'generar_tareas' => 'nullable',
      'repetir'  => 'required_with:es_tarea|nullable|string',
      'hasta'    => 'required_with:es_tarea|nullable|date',
      'contenido' => 'nullable|string',
      'adjuntos_viejos' => 'nullable|array', 
      'adjuntos_viejos.*' => 'nullable|integer',
    ], ['required' => 'El valor es requerido','required_with' => 'El valor es requerido'], [])
    ->after(function ($validator) use (&$actividades_tareas,&$at_anterior){
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
    })->validate();
    
    return DB::transaction(function() use (&$R,&$actividades_tareas,&$at_anterior){
      $at = [];
      {//Manejo de atributos;
        $f_sobreescribir = function(&$at,$datos,$attrs){
          foreach($attrs as $a) $at[$a] = $datos[$a] ?? $at[$a] ?? null;
        };
        
        $f_sobreescribir($at,$at_anterior,['numero','parent','created_by','created_at']);
        
        $Rall = $R->all();
        {
          $attrs_actividad_y_tarea = ['estado','contenido','color_fondo','color_texto','color_borde'];
          $f_sobreescribir($at,$at_anterior,$attrs_actividad_y_tarea);
          $f_sobreescribir($at,$Rall,$attrs_actividad_y_tarea);
        }
        {
          $attrs_actividad = ['fecha','titulo'];
          $f_sobreescribir($at,$at_anterior,$attrs_actividad);
          if(is_null($at['parent'] ?? null)){//Es actividad
            $f_sobreescribir($at,$Rall,$attrs_actividad);
            
            $attrs_generar_tareas = ['repetir','hasta'];
            $f_sobreescribir($at,[],$attrs_generar_tareas);
            if(($Rall['generar_tareas'] ?? false)){
              $f_sobreescribir($at,$at_anterior,$attrs_generar_tareas);
              $f_sobreescribir($at,$Rall,$attrs_generar_tareas);
            }
          }
          else{
            $at['dirty'] = true;//Modifico una tarea @HACK: chequear atributos
          }
        }
        
        $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
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
      $at['id_actividad_tarea'] = count($actividades_tareas);
      $actividades_tareas[$at['id_actividad_tarea']] = $at;
      if(!empty($at_anterior))
        $actividades_tareas[$at_anterior['id_actividad_tarea']] = $at_anterior;
        
      if(is_null($at['parent'])){//Generar tareas para las actividades
        $tareas_nuevas = collect($this->generarTareas($at))
        ->sortByDesc('fecha');
        
        $tareas_bd = $actividades_tareas->filter(function(&$t) use (&$at){
          return $t['parent'] == $at['numero'] && is_null($t['deleted_at']);
        })
        ->sortByDesc('fecha');
        
        {//Simplemente borro las que no estan sucias
          $not_dirty = $tareas_bd->filter(function(&$t){
            return !($t['dirty'] ?? false);
          });
          foreach($not_dirty as &$ot){
            $ot['deleted_at'] = $at['modified_at'];
            $actividades_tareas[$ot['id_actividad_tarea']] = $ot;
          }
        }
        
        $fechas_movidas = [];
        {//Las que estan sucias trato de moverlas a alguna de las neuvas fechas
          $dirty = $tareas_bd->filter(function(&$t){
            return ($t['dirty'] ?? false);
          });
          foreach($dirty as &$ot){
            $closestidx = $this->matchearTarea($ot['fecha'],$tareas_nuevas,$fechas_movidas);
            //No encontro, lo "descuelgo", pasandolo a actividad.
            if(is_null($closestidx)){
              $ot['parent_original'] = $ot['parent'];
              $ot['parent'] = null;
              $actividades_tareas[$ot['id_actividad_tarea']] = $ot;
              continue;
            }
            
            //Encontro, creo una tarea nueva con esa fecha pero todos los datos iguales
            $nt = unserialize(serialize($ot));
            
            $ot['deleted_at'] = $at['modified_at'];
            $actividades_tareas[$ot['id_actividad_tarea']] = $ot;
            
            $nt['fecha'] = $tareas_nuevas[$closestidx]['fecha'];
            $nt['modified_at'] = $tareas_nuevas[$closestidx]['modified_at'];
            $nt['modified_by'] = $tareas_nuevas[$closestidx]['modified_by'];
            $nt['id_actividad_tarea'] = count($actividades_tareas);
            $actividades_tareas->push($nt);
            
            $fechas_movidas[$tareas_nuevas[$closestidx]['fecha']] = true;
          }
        }
        
        //Para tareas nuevas que antes no estaban
        foreach($tareas_nuevas as $nt){
          //Y las fecha no fue ocupada
          if(($fechas_movidas[$nt['fecha']] ?? false))
            continue;
          //Le creo una tarea           
          $nt['id_actividad_tarea'] = count($actividades_tareas);
          $nt['numero'] = $this->generarNumeroActividad($actividades_tareas);
          $actividades_tareas->push($nt);
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
    
    foreach($actividades_tareas as $idx => &$at){
      if($at['numero'] == $numero && is_null($at['deleted_at'])){
        $at['deleted_at'] = (new DateTime())->format('Y-m-d H:i:s');
        break;
      }
    }
    
    $this->SET_BD($actividades_tareas);
    return $actividades_tareas;
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
