<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\GliSoft;
use App\Archivo;
use App\Casino;
use App\Plataforma;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\Laboratorio;

use Validator;

class GliSoftController extends Controller
{
  private static $atributos = [
    'nro_certificado' => 'Nro certificado',
    'observaciones' => 'Niveles de Progresivo',
    'file' => 'Archivo GLI',
  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new GliSoftController();
      }
      return self::$instance;
  }

  public function buscarTodo($id = null){
      $uc = UsuarioController::getInstancia();
      $uc->agregarSeccionReciente('Certificados Software' , 'certificadoSoft');
      $user = $uc->quienSoy()['usuario'];
      $plats = [];
      foreach($user->plataformas as $p){
        $plats[] = $p->id_plataforma;
      }
      //Ordenar por nombre ascendiente ignorando mayusculas
      $query = Juego::select('juego.id_juego')
      ->leftjoin('plataforma_tiene_juego as pj','juego.id_juego','=','pj.id_juego')
      ->whereIn('pj.id_plataforma',$plats)
      ->orWhereNull('pj.id_plataforma')
      ->groupBy('juego.id_juego')
      ->orderBy('juego.nombre_juego','ASC')
      ->get();
      //Hay juegos con el mismo nombre, los agrupo
      $juegosarr = [];
      //formato juegosarr = {'juego1' => [j1,j2],'juego2' => [j3],...}
      foreach($query as $q){
        $j = Juego::find($q->id_juego);
        $lista = "";
        $plataformas_juego = $j->plataformas()->orderBy('codigo')->get();
        foreach($plataformas_juego as $idx => $p){
          if($idx!=0) $lista = $lista . ', ';
          $lista = $lista . $p->codigo;
        }
        $nombre = $j->nombre_juego . ' ‣ ' . $lista;
        if(!isset($juegosarr[$nombre])){
          $juegosarr[$nombre] = [];
        }
        $juegosarr[$nombre][] = $j;
      }
      return view('seccionGLISoft' , 
      ['plataformas' => $user->plataformas,
      'juegos' => $juegosarr]);
  }

  public function obtenerGliSoft(Request $request,$id){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];

    Validator::make($request->all(),
    [],[],self::$atributos)->after(function ($validator) use ($id,$user){
      if(is_null($id)){
        $validator->errors()->add('certificado', 'No existe el certificado.');
        return;
      }
      $GLI = GliSoft::find($id); 
      if(is_null($GLI)){
        $validator->errors()->add('certificado', 'No existe el certificado.');
        return;
      }
    });

    $glisoft = GliSoft::find($id);

    if(!empty($glisoft->archivo)){
      $nombre_archivo = $glisoft->archivo->nombre_archivo;
      //Saca el tamaño approx de una string encodeada en base64
      $size=(int) (strlen(rtrim($glisoft->archivo->archivo, '=')) * 3 / 4);
    }else{
      $nombre_archivo = null;
      $size = 0;
    }

    $plats = [];
    foreach($user->plataformas as $p){
      $plats[] = $p->id_plataforma;
    }
    $juegos = array();
    foreach ($glisoft->juegos as $juego) {
      $visible = $juego->plataformas()->whereIn('plataforma.id_plataforma',$plats)->count() > 0;
      $sin_plats = $juego->plataformas()->count() == 0;
      if($sin_plats || $visible){
        $juegos[]= ['juego'=> $juego, 
        'plataformas' => $juego->plataformas];
      }
    }
    $plataformas_certificado = DB::table('gli_soft')
    ->select('plataforma.id_plataforma','plataforma.nombre')
    ->join('juego_glisoft','juego_glisoft.id_gli_soft','=','gli_soft.id_gli_soft')
    ->join('plataforma_tiene_juego','plataforma_tiene_juego.id_juego','=','juego_glisoft.id_juego')
    ->join('plataforma','plataforma.id_plataforma','=','plataforma_tiene_juego.id_plataforma')
    ->join('juego','juego.id_juego','=','juego_glisoft.id_juego')
    ->whereNull('juego.deleted_at')
    ->where('gli_soft.id_gli_soft',$id)
    ->distinct()->get();
    return ['glisoft' => $glisoft ,
            'expedientes' => $glisoft->expedientes,
            'nombre_archivo' => $nombre_archivo ,
            'juegos' => $juegos,
            'size' =>$size,
            'plataformas' => $plataformas_certificado,
            'laboratorio' => $glisoft->laboratorio];
  }

  public function leerArchivoGliSoft(Request $request,$id){
    $rqst = $request->all();
    $rqst['id_gli_soft'] = $id;
    $archivo = null;
    $fail = Validator::make($rqst,
    [
      'id_gli_soft' => 'required|integer|exists:gli_soft,id_gli_soft'
    ],[],self::$atributos)->after(function ($validator) use (&$archivo){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $archivo = GliSoft::find($data['id_gli_soft'])->archivo; 
      if(is_null($archivo)){
        $validator->errors()->add('archivo', 'No existe el archivo.');
        return;
      }
    })->fails();
    if($fail) return "Certificado o archivo inexistente";
    return Response::make(base64_decode($archivo->archivo), 200, [ 'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="'. $archivo->nombre_archivo  . '"']);
  }

  //METODO QUE RESPONDEN A GUARDAR
  public function guardarGliSoft(Request $request){
    $tipo_lab = 'sin';
    Validator::make($request->all(), [
      'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:gli_soft,nro_archivo'],
      'observaciones' => 'nullable|string',
      'file' => 'sometimes|mimes:pdf',
      'expedientes' => 'nullable',
      'juegos' => 'nullable|string',
      'laboratorio' => 'required',
      'laboratorio.id_laboratorio' => 'required|integer',
      'laboratorio.codigo' => 'nullable|string|max:64',
      'laboratorio.denominacion' => 'nullable|string|max:64',
      'laboratorio.pais' => 'nullable|string|max:64',
      'laboratorio.url' => 'nullable|string|max:64',
      'laboratorio.nota' => 'nullable|string|max:200',
    ], array(), self::$atributos)->after(function ($validator) use (&$tipo_lab){
        $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $plats = [];
        foreach($user->plataformas as $p){
          $plats[] = $p->id_plataforma;
        }
        $data = $validator->getData();
        if(isset($data['juegos'])){
          $juegos = explode(",",$data['juegos']);
          foreach($juegos as $j){
            if(is_null(Juego::find($j))) continue;

            $tiene_plataforma = DB::table('plataforma_tiene_juego')
            ->where('id_juego',$j)->count();

            $accesibles = DB::table('plataforma_tiene_juego as pj')
            ->whereIn('pj.id_plataforma',$plats)
            ->where('id_juego',$j)->count();
            if($tiene_plataforma > 0 && $accesibles == 0){
              $validator->errors()->add($j, 'No puede acceder a ese juego');
            }
          }
        }

        $lab = $data['laboratorio'];
        $id_lab = $lab['id_laboratorio'];
        $codigo = $lab['codigo'];
        $denominacion = $lab['denominacion'];

        if($id_lab != '0'){
          $tipo_lab = 'seteando/modificando';
          if(is_null(Laboratorio::find($id_lab))){
            $validator->errors()->add('laboratorio.id_laboratorio','No existe tal laboratorio');
            return;
          }
        }
        else if(isset($codigo)) $tipo_lab = 'nuevo'; 

        if($tipo_lab != 'sin'){
          $ya_existe = Laboratorio::where([
            ['codigo','=',$codigo],
            ['id_laboratorio','<>',$id_lab]
          ])->count() > 0;
          if($ya_existe){
            $validator->errors()->add('laboratorio.codigo','Ya existe un laboratorio con ese codigo');
          }
          $ya_existe = Laboratorio::where([
            ['denominacion','=',$denominacion],
            ['id_laboratorio','<>',$id_lab]
          ])->count() > 0;
          if($ya_existe){
            $validator->errors()->add('laboratorio.denominacion','Ya existe un laboratorio con esa denominacion');
          }
        }
    })->validate();

    $GLI = null;
    $nombre_archivo = null;

    DB::transaction(function() use($GLI,$nombre_archivo,$request,$tipo_lab){
      $GLI=new GliSoft;
      $GLI->nro_archivo =$request->nro_certificado;
      $GLI->observaciones=$request->observaciones;
  
      if($request->file != null){
        $file=$request->file;
        $archivo=new Archivo;
        $data=base64_encode(file_get_contents($file->getRealPath()));
        $nombre_archivo=$file->getClientOriginalName();
        $archivo->nombre_archivo=$nombre_archivo;
        $archivo->archivo=$data;
        $archivo->save();
        $GLI->archivo()->associate($archivo->id_archivo);
      }
  
      $GLI->save();
  
      if(!empty($request->expedientes)){
        $expedientesReq = explode(',',$request->expedientes);
      }else{
        $expedientesReq=null;
      }
      if($expedientesReq != null){
        foreach ($expedientesReq as $exp) {
          if($this->noEstabaEnLista($exp,$GLI->expedientes)){
            $GLI->expedientes()->attach($exp);
          }
        }
      }
      if(isset($request->juegos)){
        $juegos=explode("," , $request->juegos);
        JuegoController::getInstancia()->asociarGLI($juegos , $GLI->id_gli_soft);
      }
  
      $GLI->save();
  
      //obtengo solo el nombre del archivo para devolverlo a la vista
      if(!empty($GLI->archivo)){
        $nombre_archivo = $GLI->archivo->nombre_archivo;
      }

      if($tipo_lab == 'sin'){
        $GLI->id_laboratorio = null;
      }
      else if($tipo_lab == 'seteando/modificando' || $tipo_lab == 'nuevo'){
        $lab = $request->laboratorio;
        $labBD = null;
        if($tipo_lab == 'seteando/modificando') $labBD = Laboratorio::find($lab['id_laboratorio']);
        else $labBD = new Laboratorio;
        $labBD->codigo = $lab['codigo'];
        $labBD->denominacion = $lab['denominacion'];
        $labBD->pais = $lab['pais'];
        $labBD->url = $lab['url'];
        $labBD->nota = $lab['nota'];
        $labBD->save();
        $GLI->id_laboratorio = $labBD->id_laboratorio;
      }
      $GLI->save();
    });
    
    return ['gli_soft' => $GLI,  'nombre_archivo' =>$nombre_archivo];
  }

  public function buscarGliSofts(Request $request){
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $plats_ids = [];
    foreach($user->plataformas as $p) $plats_ids[] = $p->id_plataforma;

    $reglas = array();
    if(!empty($request->certificado)){
      $reglas[]=['gli_soft.nro_archivo' , 'like' , '%' .  $request->certificado . '%'];
    }
    if(!empty($request->nombre_archivo)){
      $reglas[]=['archivo.nombre_archivo' , 'like' , '%' . $request->nombre_archivo . '%' ];
    }
    if(isset($request->id_juego)){
      $reglas[]=['juego_glisoft.id_juego' , '=' , $request->id_juego];
    }
    if(isset($request->nro_exp_org)){
      $reglas[]=['expediente.nro_exp_org','like',$request->nro_exp_org.'%'];
    }
    if(isset($request->nro_exp_interno)){
      $reglas[]=['expediente.nro_exp_org','like',$request->nro_exp_interno.'%'];
    }
    if(isset($request->nro_exp_control)){
      $reglas[]=['expediente.nro_exp_control','=',$request->nro_exp_control];
    }
    
    $sort_by = $request->sort_by;
    $resultados=DB::table('gli_soft')
    ->select('gli_soft.*', 'archivo.nombre_archivo')
    ->leftJoin('archivo' , 'archivo.id_archivo' , '=' , 'gli_soft.id_archivo')
    ->leftJoin('juego_glisoft','juego_glisoft.id_gli_soft','=','gli_soft.id_gli_soft')
    ->leftJoin('plataforma_tiene_juego','plataforma_tiene_juego.id_juego','=','juego_glisoft.id_juego')
    ->leftJoin('expediente_tiene_gli_sw','expediente_tiene_gli_sw.id_gli_soft','=','gli_soft.id_gli_soft')
    ->leftJoin('expediente','expediente.id_expediente','=','expediente_tiene_gli_sw.id_Expediente')
    ->where($reglas);
    if($request->id_plataforma == 0){
      $resultados = $resultados->where(function ($q) use($plats_ids){
        $q->whereIn('plataforma_tiene_juego.id_plataforma',$plats_ids)->orWhereNull('plataforma_tiene_juego.id_plataforma');
      });
    }
    else if($request->id_plataforma == -1){
      $resultados = $resultados->whereNull('plataforma_tiene_juego.id_plataforma');
    }
    else if($request->id_plataforma > 0){
      $resultados = $resultados->whereIn('plataforma_tiene_juego.id_plataforma',$plats_ids)->where('plataforma_tiene_juego.id_plataforma','=',$request->id_plataforma);
    }

    $resultados=$resultados->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    });

    //Elimino duplicados y pagino.
    $resultados = $resultados->groupBy('gli_soft.id_gli_soft')->paginate($request->page_size);
    return ['resultados' => $resultados];
  }

  public function modificarGliSoft(Request $request){
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $plats = [];
      foreach($user->plataformas as $p){
        $plats[] = $p->id_plataforma;
      }
      $tipo_lab = 'sin';
      Validator::make($request->all(), [
        'id_gli_soft' => 'required|exists:gli_soft,id_gli_soft',
        'nro_certificado' => ['required','regex:/^\d?\w(.|-|_|\d|\w)*$/','unique:gli_soft,nro_archivo,'.$request->id_gli_soft.',id_gli_soft'],
        'observaciones' => 'nullable|string',
        'file' => 'sometimes|mimes:pdf',
        'expedientes' => 'nullable',
        'juegos' => 'nullable|string',
        'laboratorio' => 'required',
        'laboratorio.id_laboratorio' => 'required|integer',
        'laboratorio.codigo' => 'nullable|string|max:64',
        'laboratorio.denominacion' => 'nullable|string|max:64',
        'laboratorio.pais' => 'nullable|string|max:64',
        'laboratorio.url' => 'nullable|string|max:64',
        'laboratorio.nota' => 'nullable|string|max:200',
      ])->after(function ($validator) use ($user,$plats,&$tipo_lab){
        $data = $validator->getData();
        //Verifico que pueda ver el certificado
        $GLI = GliSoft::find($data['id_gli_soft']);
        if(isset($data['juegos'])){
          $juegos = explode(",",$data['juegos']);
          foreach($juegos as $j){
            if(is_null(Juego::find($j))) continue;

            $tiene_plataforma = DB::table('plataforma_tiene_juego')
            ->where('id_juego',$j)->count();

            $accesibles = DB::table('plataforma_tiene_juego as pj')
            ->whereIn('pj.id_plataforma',$plats)
            ->where('id_juego',$j)->count();
            if($tiene_plataforma > 0 && $accesibles == 0){
              $validator->errors()->add($j, 'No puede acceder a ese juego');
            }
          }
        }

        $lab = $data['laboratorio'];
        $id_lab = $lab['id_laboratorio'];
        $codigo = $lab['codigo'];
        $denominacion = $lab['denominacion'];

        if($id_lab != '0'){
          $tipo_lab = 'seteando/modificando';
          if(is_null(Laboratorio::find($id_lab))){
            $validator->errors()->add('laboratorio.id_laboratorio','No existe tal laboratorio');
            return;
          }
        }
        else if(isset($codigo)) $tipo_lab = 'nuevo'; 

        if($tipo_lab != 'sin'){
          $ya_existe = Laboratorio::where([
            ['codigo','=',$codigo],
            ['id_laboratorio','<>',$id_lab]
          ])->count() > 0;
          if($ya_existe){
            $validator->errors()->add('laboratorio.codigo','Ya existe un laboratorio con ese codigo');
          }
          $ya_existe = Laboratorio::where([
            ['denominacion','=',$denominacion],
            ['id_laboratorio','<>',$id_lab]
          ])->count() > 0;
          if($ya_existe){
            $validator->errors()->add('laboratorio.denominacion','Ya existe un laboratorio con esa denominacion');
          }
        }
      })->validate();

      $GLI = null;
      $nombre_archivo = null;
      DB::transaction(function() use($request,$plats,$GLI,$nombre_archivo,$tipo_lab){
        $GLI=GliSoft::find($request->id_gli_soft);
        $GLI->nro_archivo =$request->nro_certificado;
        $GLI->observaciones=$request->observaciones;
  
        if(!empty($request->expedientes)){
          $expedientesReq = explode(',',$request->expedientes);
        }else{
          $expedientesReq=null;
        }
        if(isset($GLI->expedientes)){
          foreach ($GLI->expedientes as $expediente) {
            if($this->noEstaEnLista($expediente ,  $expedientesReq )){
              $GLI->expedientes()->detach($expediente->id_expediente);
            }
          }
        }
  
        if($request->expedientes != null){
          for($i=0; $i<count(  $expedientesReq); $i++){
            if($this->noEstabaEnLista(  $expedientesReq[$i],$GLI->expedientes)){
              $GLI->expedientes()->attach(  $expedientesReq[$i]);
            }
          }
        }

        $juegos = [];
        if(!empty($request->juegos)){
          $juegos=explode("," , $request->juegos);
        }

        $plataformas_a_ignorar = Plataforma::whereNotIn('plataforma.id_plataforma',$plats)->get();
        $aux = [];
        foreach($plataformas_a_ignorar as $p) $aux[] = $p->id_plataforma;
        JuegoController::getInstancia()->asociarGLI($juegos , $GLI->id_gli_soft, $aux);

        $GLI->save();
  
        if(!empty($request->file)){
            if($GLI->archivo != null){
              $archivoAnterior=$GLI->archivo;
              $GLI->archivo()->dissociate();
              $GLI->save();
              $archivoAnterior->delete();
            }
  
            $file=$request->file;
            $archivo=new Archivo;
            $archivo->nombre_archivo=$file->getClientOriginalName();
            $data=base64_encode(file_get_contents($file->getRealPath()));
            $archivo->archivo=$data;
            $archivo->save();
            $GLI->archivo()->associate($archivo->id_archivo);
            $GLI->save();
  
        }else{
            if($request->borrado == "true"){
              $archivoAnterior=$GLI->archivo;
              $GLI->archivo()->dissociate();
              $GLI->save();
              $archivoAnterior->delete();
            }
        }
  
        $GLI->save();
        $GLI=GliSoft::find($request->id_gli_soft);
        if(!empty($GLI->archivo)){
          $nombre_archivo = $GLI->archivo->nombre_archivo;
        }

        if($tipo_lab == 'sin'){
          $GLI->id_laboratorio = null;
        }
        else if($tipo_lab == 'seteando/modificando' || $tipo_lab == 'nuevo'){
          $lab = $request->laboratorio;
          $labBD = null;
          if($tipo_lab == 'seteando/modificando') $labBD = Laboratorio::find($lab['id_laboratorio']);
          else $labBD = new Laboratorio;
          $labBD->codigo = $lab['codigo'];
          $labBD->denominacion = $lab['denominacion'];
          $labBD->pais = $lab['pais'];
          $labBD->url = $lab['url'];
          $labBD->nota = $lab['nota'];
          $labBD->save();
          $GLI->id_laboratorio = $labBD->id_laboratorio;
        }
        $GLI->save();
      });

      return ['gli_soft' => $GLI , 'nombre_archivo' => $nombre_archivo ];
  }

  private function noEstaEnLista($expediente ,$expedientes ){//expediente posta ,expedientes del request
    for($i=0; $i<count($expedientes); $i++){
      if($expedientes[$i] == $expediente->id_expediente){
        return false; //si esta en lista
      }
    }
    return true;
  }
  
  private function noEstabaEnLista($expediente ,$expedientes ){//expediente del request ,expedientes del gli
    if(isset($expedientes)){
      foreach ($expedientes as $exp) {
        if($exp->id_expediente == $expediente){
          return false; //si esta en lista
        }
      }
    }
    return true;
  }

  public function eliminarGLI(Request $request,$id){
    $GLI=GliSoft::find($id);
    $se_borro = false;
    if(is_null($GLI)) return ['gli' => null,'se_borro' => false];

    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $plataformas = $user->plataformas;
    $pats = array();
    foreach($plataformas as $p){
      $plats [] = $p->id_plataforma;
    }

    $juegos_accesibles = DB::table('gli_soft as gl')->select('j.id_juego')
    ->join('juego_glisoft as jgl','jgl.id_gli_soft','=','gl.id_gli_soft')
    ->join('juego as j','j.id_juego','=','jgl.id_juego')
    ->join('plataforma_tiene_juego as pj','pj.id_juego','=','j.id_juego')
    ->where('gl.id_gli_soft',$GLI->id_gli_soft)
    ->whereIn('pj.id_plataforma',$plats)->get();

    DB::transaction(function() use ($GLI,$juegos_accesibles,&$se_borro){
      foreach($juegos_accesibles as $j){
        $juego = Juego::withTrashed()->find($j->id_juego);
        $juego->gliSoft()->detach($GLI->id_gli_soft);
        $juego->save();
      }
      $GLI->save();
      if($GLI->juegos()->count() == 0){//Si el GLI no tiene mas juegos, lo borro
        $GLI->expedientes()->sync([]);
        $archivo = $GLI->archivo;
        $GLI->archivo()->dissociate();
        $GLI->save();
        $GLI->delete();
        if(!empty($archivo)){
          $archivo->delete();
        }
        $se_borro = true;
      }
    });

    return ['gli' => $GLI,'se_borro' => $se_borro];
  }

  public function gliSoftsPorPlataformas($plataformas){
    if(is_null($plataformas)) return [];
    $plats_ids = [];
    foreach($plataformas as $p) $plats_ids[] = $p->id_plataforma;

    $gli_softs = DB::table('gli_soft as gl')->select('gl.id_gli_soft')
    ->join('juego_glisoft as jgl','jgl.id_gli_soft','=','gl.id_gli_soft')
    ->join('plataforma_tiene_juego as pj','pj.id_juego','=','jgl.id_juego')
    ->whereIn('pj.id_plataforma',$plats_ids)
    ->groupBy('gl.id_gli_soft')
    ->get();
    $ret = [];
    foreach($gli_softs as $gl){
      $ret[]=GliSoft::find($gl->id_gli_soft);
    }
    $gli_softs_sin_plat = DB::table('gli_soft as gl')->select('gl.id_gli_soft')
    ->leftjoin('juego_glisoft as jgl','jgl.id_gli_soft','=','gl.id_gli_soft')
    ->leftjoin('plataforma_tiene_juego as pj','pj.id_juego','=','jgl.id_juego')
    ->whereNull('pj.id_plataforma')
    ->groupBy('gl.id_gli_soft')
    ->get();
    foreach($gli_softs_sin_plat as $gl){
      $ret[]=GliSoft::find($gl->id_gli_soft);
    }
    return $ret;
  }

  public function buscarLabs($codigo = ""){
    return ['laboratorios' => Laboratorio::where('codigo','like',$codigo.'%')->get()];
  }
  public function obtenerLab($id_laboratorio){
    return Laboratorio::find($id_laboratorio);
  }
}
