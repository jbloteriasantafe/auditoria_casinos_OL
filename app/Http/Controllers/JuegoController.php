<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\GliSoft;
use App\TipoMoneda;
use App\CategoriaJuego;
use App\Laboratorio;
use App\EstadoJuego;
use App\LogJuego;
use App\Plataforma;
use Validator;
use View;
use Dompdf\Dompdf;
use App\Http\Controllers\CacheController;

function csvstr(array $fields): string
{
  $f = fopen('php://memory', 'r+');
  if (fputcsv($f, $fields) === false) {
    return false;
  }
  rewind($f);
  $csv_line = stream_get_contents($f);
  return rtrim($csv_line);
}

class JuegoController extends Controller
{
  private static $atributos = [];

  private static $instance;

  public static function getInstancia()
  {
    if (!isset(self::$instance)) {
      self::$instance = new JuegoController();
    }
    return self::$instance;
  }

  public function buscarTodo($id = null)
  {
    $uc = UsuarioController::getInstancia();
    $uc->agregarSeccionReciente('Juegos', 'juegos');
    $usuario = $uc->quienSoy()['usuario'];
    $plataformas = $usuario->plataformas;
    $proveedores = DB::table('juego')->select('proveedor')
      ->whereNull('deleted_at')->distinct()
      ->orderBy('proveedor', 'asc')->get()->pluck('proveedor')->toArray();
    return view(
      'seccionJuegos',
      [
        'certificados' => GliSoftController::getInstancia()->gliSoftsPorPlataformas($plataformas),
        'monedas' => TipoMoneda::all(),
        'categoria_juego' => CategoriaJuego::all(),
        'estado_juego' => EstadoJuego::all(),
        'plataformas' => $plataformas,
        'proveedores' => $proveedores,
        'laboratorios' => Laboratorio::all()
      ]
    );
  }

  public function obtenerJuego($id)
  {
    $juego = Juego::find($id);
    if (is_null($juego)) {
      return response()->json(['acceso' => ['']], 422);
    }

    return [
      'juego' => $juego,
      'certificados' => $juego->gliSoft,
      'plataformas' => DB::table('plataforma_tiene_juego')->join('plataforma', 'plataforma.id_plataforma', '=', 'plataforma_tiene_juego.id_plataforma')
        ->where('id_juego', $id)->get()
    ];
  }

  public function obtenerLogs($id)
  {
    //Empiezo con el actual... antes no se logeaba cuando hacia guardarJuego... mala mia, saldran duplicados (?)
    $juego = $this->obtenerJuego($id);
    $logs = $juego['juego']->logs()->orderBy('updated_at', 'desc')->get();
    $juego['juego'] = $juego['juego']->toArray();
    $juego['juego']['updated_at'] = 'ACTUAL ' . $juego['juego']['updated_at'];
    $ret = [$juego];
    foreach ($logs as &$l) {
      $ret[] = [
        'juego' => $l,
        'certificados' => $l->gliSoft,
        'plataformas' =>
          DB::table('plataforma_tiene_juego_log_norm')->join('plataforma', 'plataforma.id_plataforma', '=', 'plataforma_tiene_juego_log_norm.id_plataforma')
            ->where('id_juego_log_norm', $l->id_juego_log_norm)->get(),
        'usuario' => $l->usuario,
      ];
    }
    return $ret;
  }

  private function crear_o_modificar_juego($id_juego, $motivo, $params, $plataformas_estado, $certificados)
  {
    $juego = $id_juego !== null ? Juego::find($id_juego) : (new Juego);
    $log = new LogJuego;
    $attrs = [
      'nombre_juego',
      'cod_juego',
      'denominacion_juego',
      'porcentaje_devolucion',
      'escritorio',
      'movil',
      'codigo_operador',
      'proveedor',
      'id_tipo_moneda',
      'id_categoria_juego'
    ];
    foreach ($attrs as $attr) {
      $juego->{$attr} = $params[$attr];
      $log->{$attr} = $params[$attr];//Se guarda todo lo que mando en un log nuevo siempre
    }

    $juego->touch();//Fuerza cambio en update_at
    $juego->save();

    $log->id_juego = $juego->id_juego;
    $log->motivo = $motivo;
    $log->created_at = $juego->updated_at;
    $log->updated_at = $juego->updated_at;
    $log->deleted_at = null;
    $log->id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    $log->save();

    $log_anterior = LogJuego::where('id_juego', $juego->id_juego)->whereNull('deleted_at')
      ->where('id_juego_log_norm', '<>', $log->id_juego_log_norm)
      ->orderBy('updated_at', 'desc')->take(1)
      ->get()->first();

    if (!is_null($log_anterior)) {
      $log_anterior->deleted_at = $juego->updated_at;
      $log_anterior->save();
    }

    $juego->plataformas()->sync($plataformas_estado);
    $log->plataformas()->sync($plataformas_estado);

    foreach ($juego->gliSoft as $gli) {
      $juego->gliSoft()->detach($gli->id_gli_soft);
    }

    if (!empty($certificados)) {
      $juego->setearGliSofts($certificados, True);
      $log->setearGliSofts($certificados, True);
    }

    $juego->save();
    $juegoSecundario = Juego::on('gestion_notas_mysql')->find($juego->id_juego);
    if($juegoSecundario === null){
      $juegoSecundario = new Juego;
      $juegoSecundario->setConnection('gestion_notas_mysql');
    }
    
    foreach($attrs as $attr){
      $juegoSecundario->{$attr} = $params[$attr];
    }
    $juegoSecundario->id_juego   = $juego->id_juego;
    $juegoSecundario->created_at = $juego->created_at;
    $juegoSecundario->updated_at = $juego->updated_at;
    $juegoSecundario->deleted_at = $juego->deleted_at;
    $juegoSecundario->save();
    $log->save();

    CacheController::getInstancia()->invalidarDependientes(['juego']);

    return [$juego, $log];
  }

  public function guardarJuego(Request $request)
  {
    Validator::make($request->all(), [
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable', 'regex:/^\d?\w(.|-|_|\d|\w)*$/', 'max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'proveedor' => 'nullable|string|max:100',
      'plataformas' => 'required|array',
      'plataformas.*.id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
      'plataformas.*.id_estado_juego' => 'nullable|integer|exists:estado_juego,id_estado_juego',
    ], array(), self::$atributos)->after(function ($validator) {
      $data = $validator->getData();
      if ($data['movil'] == 0 && $data['escritorio'] == 0) {
        $validator->errors()->add('tipos', 'validation.required');
      }
      if ($validator->errors()->any())
        return;

      $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas;
      $plataformas_usuario = [];
      foreach ($plataformas as $p) {
        $plataformas_usuario[] = $p->id_plataforma;
      }

      foreach ($data['plataformas'] as $p) {
        if (!in_array($p['id_plataforma'], $plataformas_usuario)) {
          $validator->errors()->add('id_juego', 'El usuario no puede acceder a este juego');
          break;
        }
      }

      if (!is_null($data['cod_juego'])) {
        //El codigo del juego es unico
        $juegos_mismo_codigo = DB::table('juego as j')
          ->where('j.cod_juego', $data['cod_juego'])
          ->whereNull('j.deleted_at');
        if ($juegos_mismo_codigo->count() > 0) {
          $validator->errors()->add('cod_juego', 'validation.unique');
        }
      }
    })->validate();

    return DB::transaction(function () use ($request) {
      $plataformas_estado = [];
      foreach ($request->plataformas as $p) {
        if (!is_null($p['id_estado_juego'])) {
          $plataformas_estado[$p['id_plataforma']] = ['id_estado_juego' => $p['id_estado_juego']];
        }
      }

      $ret = $this->crear_o_modificar_juego(null, $request->motivo ?? '', $request->all(), $plataformas_estado, $request->certificados ?? [])[0];

      return ['juego' => $ret[0]];
    });
  }

  public function modificarJuego(Request $request)
  {
    $plataformas_usuario = [];
    Validator::make($request->all(), [
      'id_juego' => 'required|integer|exists:juego,id_juego',
      'nombre_juego' => 'required|max:100',
      'cod_juego' => ['nullable', 'regex:/^\d?\w(.|-|_|\d|\w)*$/', 'max:100'],
      'id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'certificados.*' => 'nullable',
      'certificados.*.id_gli_soft' => 'nullable',
      'denominacion_juego' => 'required|numeric|between:0,100',
      'porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'id_tipo_moneda' => 'required|integer|exists:tipo_moneda,id_tipo_moneda',
      'motivo' => 'nullable|string|max:256',
      'movil' => 'nullable|boolean',
      'escritorio' => 'nullable|boolean',
      'codigo_operador' => 'nullable|string|max:100',
      'proveedor' => 'nullable|string|max:100',
      'plataformas' => 'required|array',
      'plataformas.*.id_plataforma' => 'required|integer|exists:plataforma,id_plataforma',
      'plataformas.*.id_estado_juego' => 'nullable|integer|exists:estado_juego,id_estado_juego',
    ], array(), self::$atributos)->after(function ($validator) use (&$plataformas_usuario) {
      $data = $validator->getData();
      if ($data['movil'] == 0 && $data['escritorio'] == 0) {
        $validator->errors()->add('tipos', 'validation.required');
      }
      if ($validator->errors()->any())
        return;


      $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas;
      foreach ($plataformas as $p) {
        $plataformas_usuario[] = $p->id_plataforma;
      }

      foreach ($data['plataformas'] as $p) {
        if (!in_array($p['id_plataforma'], $plataformas_usuario)) {
          $validator->errors()->add('id_juego', 'El usuario no puede acceder a este juego');
          break;
        }
      }

      if (!is_null($data['cod_juego'])) {
        //El codigo del juego es unico
        $juegos_mismo_codigo = DB::table('juego as j')
          ->where('j.cod_juego', $data['cod_juego'])
          ->where('j.id_juego', '<>', $data['id_juego'])
          ->whereNull('j.deleted_at');
        if ($juegos_mismo_codigo->count() > 0) {
          $validator->errors()->add('cod_juego', 'validation.unique');
        }
      }
    })->validate();

    return DB::transaction(function () use ($request, $plataformas_usuario) {
      $plataformas_estado = [];
      foreach ($request->plataformas as $p) {
        if (!is_null($p['id_estado_juego'])) {
          $plataformas_estado[$p['id_plataforma']] = ['id_estado_juego' => $p['id_estado_juego']];
        }
      } {
        $plataforma_tiene_juego = DB::table('plataforma_tiene_juego')
          ->where('id_juego', $request->id_juego)
          ->get()->keyBy('id_plataforma');

        foreach ($plataforma_tiene_juego as $pj) {//Mantengo las relaciones que el usuario no puede acceder
          if (!in_array($pj->id_plataforma, $plataformas_usuario)) {
            $plataformas_estado[$pj->id_plataforma] = $pj->id_estado_juego;
          }
        }
      }

      $ret = $this->crear_o_modificar_juego($request->id_juego, $request->motivo ?? '', $request->all(), $plataformas_estado, $request->certificados ?? []);
      return ['juego' => $ret[0]];
    });
  }

  public function eliminarJuego($id)
  {
    $juego = Juego::find($id);
    if (is_null($juego))
      return ['juego' => null];
    $juego->delete();

    $juegoSecundario = $juego->replicate();
    $juegoSecundario->setConnection('gestion_notas_mysql');

    $juegoSecundario = Juego::on('gestion_notas_mysql')->find($id);
    if ($juegoSecundario) {
      $juegoSecundario->delete();
    }
    return ['juego' => $juego];
  }

  public function buscarJuegos(Request $request)
  {
    $reglas = array();
    if (!empty($request->nombreJuego)) {
      $reglas[] = ['juego.nombre_juego', 'like', '%' . $request->nombreJuego . '%'];
    }
    if (!empty($request->cod_juego) && $request->cod_juego != '-') {
      $reglas[] = ['juego.cod_juego', 'like', '%' . $request->cod_juego . '%'];
    }
    if (!empty($request->proveedor) && $request->proveedor != '-') {//Si manda 1 guion significa sin proveedor
      //Tengo que hacer esto porque no tiene validacion de regex cuando se guarda, puede mandar solo guiones
      //Si manda n+1 guiones, significa n guiones
      $proveedor = $request->proveedor;
      if (substr_count($request->proveedor, "-") == count($request->proveedor))
        $proveedor = substr($proveedor, 1);
      $reglas[] = ['juego.proveedor', 'like', '%' . $proveedor . '%'];
    }
    if (!empty($request->id_plataforma)) {
      $reglas[] = ['plataforma_tiene_juego.id_plataforma', '=', $request->id_plataforma];
    }
    if (!empty($request->id_categoria_juego)) {
      $reglas[] = ['juego.id_categoria_juego', '=', $request->id_categoria_juego];
    }
    if (!empty($request->sistema)) {
      $escritorio = $request->sistema == "1";
      $movil = $request->sistema == "2";
      $escritorio_y_movil = $request->sistema == "3";
      $reglas[] = ['juego.escritorio', '=', $escritorio || $escritorio_y_movil];
      $reglas[] = ['juego.movil', '=', $movil || $escritorio_y_movil];
    }
    if (!is_null($request->pdev_menor)) {
      $reglas[] = ['juego.porcentaje_devolucion', '>=', $request->pdev_menor];
    }
    if (!is_null($request->pdev_mayor)) {
      $reglas[] = ['juego.porcentaje_devolucion', '<=', $request->pdev_mayor];
    }

    $sort_by = $request->sort_by;

    $resultados = DB::table('juego')
      ->selectRaw("juego.*,GROUP_CONCAT(DISTINCT(IFNULL(gli_soft.nro_archivo, '-')) separator ', ') as certificados")
      ->leftjoin('juego_glisoft as jgl', 'jgl.id_juego', '=', 'juego.id_juego')
      ->leftjoin('gli_soft', function ($j) {
        return $j->on('gli_soft.id_gli_soft', '=', 'jgl.id_gli_soft')->wherenull('gli_soft.deleted_at');
      })
      ->leftjoin('plataforma_tiene_juego', 'plataforma_tiene_juego.id_juego', '=', 'juego.id_juego')
      ->leftjoin('plataforma_tiene_casino', 'plataforma_tiene_juego.id_plataforma', '=', 'plataforma_tiene_casino.id_plataforma')
      ->when($sort_by, function ($query) use ($sort_by) {
        return $query->orderBy($sort_by['columna'], $sort_by['orden']);
      })
      ->whereNull('juego.deleted_at')
      ->where($reglas);

    $plataformas_usuario = [];
    foreach (UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas as $p) {
      $plataformas_usuario[] = $p->id_plataforma;
    }

    if (!empty($request->id_estado_juego)) {
      $resultados = $resultados->where('plataforma_tiene_juego.id_estado_juego', '=', $request->id_estado_juego);
      $resultados = $resultados->whereIn('plataforma_tiene_juego.id_plataforma', $plataformas_usuario);
    }

    if ($request->cod_juego == '-')
      $resultados = $resultados->whereNull('juego.cod_juego');
    if ($request->proveedor == '-')
      $resultados = $resultados->whereNull('juego.proveedor');

    if (!empty($request->certificado)) {
      if (trim($request->certificado) == '-') {//Si me envia un gion, significa sin certificado
        $resultados = $resultados->whereNull('gli_soft.id_gli_soft');
      } else {
        $codigos = explode(',', $request->certificado);
        foreach ($codigos as &$c)
          $c = trim($c);

        $resultados = $resultados->where(function ($query) use ($codigos) {
          foreach ($codigos as $idx => $c) {
            if ($idx == 0)
              $query->where('gli_soft.nro_archivo', 'like', '%' . $c . '%');
            else
              $query->orWhere('gli_soft.nro_archivo', 'like', '%' . $c . '%');
          }
        });
      }
    }

    $resultados = $resultados->groupBy('juego.id_juego');
    $resultados = $resultados->orderBy('juego.id_juego', 'desc');
    $resultados = $resultados->paginate($request->page_size);
    $resultados = $resultados->toArray();

    $resultados['data'] = array_map(function ($v) use ($plataformas_usuario) {
      $juego = Juego::find($v->id_juego);
      $plats = [];
      foreach ($juego->plataformas as $p) {
        if (in_array($p->id_plataforma, $plataformas_usuario))
          $plats[] = $p->codigo . ": " . EstadoJuego::find($p->pivot->id_estado_juego)->codigo;
      }
      $v->estado = implode(", ", $plats);
      return $v;
    }, $resultados['data']);
    return $resultados;
  }

  public function asociarGLI($listaJuegos, $id_gli_soft, $mantener_los_de_plataformas = [])
  {
    $lista_limpia = [];
    foreach ($listaJuegos as $id_juego) {
      $juego = Juego::find($id_juego);
      if (is_null($juego))
        continue;
      $lista_limpia[] = $id_juego;
    }
    //Por si manda varias veces el mismo juego lo filtro
    $lista_limpia = array_unique($lista_limpia);
    $GLI = GliSoft::find($id_gli_soft);
    if ($GLI != null) {
      $mantenidos = [];
      foreach ($GLI->juegos as $j) {
        $mantener = $j->plataformas()->whereIn('plataforma.id_plataforma', $mantener_los_de_plataformas)->count() > 0;
        if ($mantener)
          $mantenidos[] = $j->id_juego;
      }
      $asociar = array_unique(array_merge($lista_limpia, $mantenidos));
      $GLI->setearJuegos([]);
      $GLI->setearJuegos($asociar, true);
      $GLI->save();
    }
  }

  public function generarDiferenciasEstadosJuegos(Request $request)
  {
    //Esto se puede pasar a usar una tabla temporal y hacerlo por SQL si demora mucho, no deberia porque pocas deberian reportar diferencias
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if ($user->plataformas()->where('plataforma.id_plataforma', $request->id_plataforma)->count() <= 0)
      return response()->json(["errores" => ["No puede acceder a la plataforma"]], 422);

    $resultado = [];
    $codigo_idx = false;
    $nombre_idx = false;
    $estado_idx = false;

    $query = DB::table('plataforma_tiene_juego')
      ->select('estado_juego.nombre')
      ->join('juego', 'juego.id_juego', '=', 'plataforma_tiene_juego.id_juego')
      ->join('estado_juego', 'estado_juego.id_estado_juego', '=', 'plataforma_tiene_juego.id_estado_juego')
      ->where('plataforma_tiene_juego.id_plataforma', '=', $request->id_plataforma);

    //Los que esperaba que estaban activos, inactivos, ausentes(-1)
    $resultado = ["No existe" => []];
    foreach (EstadoJuego::all() as $e) {
      $resultado[$e->nombre] = [];
    }

    if (($gestor = fopen($request->archivo->getRealPath(), "r")) !== FALSE) {
      if (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
        $codigo_idx = array_search("GameCode", $datos);
        $nombre_idx = array_search("GameName", $datos);
        $estado_idx = array_search("IsPublished", $datos);
        if ($codigo_idx === false || $nombre_idx === false || $estado_idx === false) {
          fclose($gestor);
          return response()->json(["errores" => ["Error en el formato del archivo."]], 422);
        }
      } else
        return response()->json(["errores" => ["Error en el formato del archivo."]], 422);

      while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
        $r = [];
        $r["juego"] = utf8_encode($datos[$nombre_idx]);//CCO viene con codificacion en latino... necesito encodearlo en utf8 para mostrarlo
        $cod_juego = $datos[$codigo_idx];
        $r["codigo"] = $cod_juego;
        $estado = strtoupper($datos[$estado_idx]);
        $estado_t = $estado == "TRUE" || $estado == "HABILITADO-ACTIVO";
        $estado_f = $estado == "FALSE" || $estado == "HABILITADO-INACTIVO";
        $r["estado_recibido"] = $estado_t ? "Activo" : ($estado_f ? "Inactivo" : $estado);
        $estado_esperado = (clone $query)->where('juego.cod_juego', '=', $cod_juego)->first();
        if (is_null($estado_esperado))
          $estado_esperado = "No existe";
        else
          $estado_esperado = $estado_esperado->nombre;
        if ($estado_esperado != $r["estado_recibido"])
          $resultado[$estado_esperado][] = $r;
      }
      fclose($gestor);
    }
    foreach ($resultado as &$v) {
      usort($v, function ($a, $b) {
        return strnatcmp($a["juego"], $b["juego"]) ?? strnatcmp($a["codigo"], $b["codigo"]);
      });
    }
    $plataforma = Plataforma::find($request->id_plataforma)->codigo;
    $view = View::make('planillaDiferenciasEstadosJuegos', compact('resultado', 'plataforma'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0, 0, 0));
    return base64_encode($dompdf->output());
  }

  public function juegos_csv()
  {
    $ret = DB::table('juego as j')
      ->selectRaw("
      j.nombre_juego,
      p.codigo as plataforma,
      CASE
          WHEN j.movil = 1 AND j.escritorio = 1 THEN 'Escritorio y Móvil'
          WHEN j.movil = 1 AND j.escritorio = 0 THEN 'Móvil'
          WHEN j.movil = 0 AND j.escritorio = 1 THEN 'Escritorio'
          ELSE NULL
      END as tecnologia,
      j.cod_juego as codigo,
      j.porcentaje_devolucion
    ")
      ->join('plataforma_tiene_juego as pj', 'pj.id_juego', '=', 'j.id_juego')
      ->join('plataforma as p', 'p.id_plataforma', '=', 'pj.id_plataforma')
      ->whereNull('j.deleted_at')
      ->orderBy('j.nombre_juego', 'asc')
      ->orderBy('p.codigo', 'asc')
      ->orderBy('tecnologia', 'asc')
      ->orderBy('j.cod_juego', 'asc')
      ->get()
      ->toArray();

    $lineas = [];
    foreach ($ret as $v) {
      $lineas[] = csvstr(array_keys((array) $v));
      break;
    }

    foreach ($ret as $v) {
      $lineas[] = csvstr((array) $v);
    }

    $ret = implode("\r\n", $lineas);
    $filename = 'juegos ' . date('Y-m-d h i s') . '.csv';
    return Response::make($ret, 200, [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'inline; filename="' . $filename . '"'
    ]);
  }

  private function convertirAnexo($datos)
  {
    $columnas = [
      'ID',
      'id_categoria_juego',
      'tecnologia',
      'cod_juego',
      'nombre_juego',
      'porcentaje_devolucion',
      'id_laboratorio',
      'nro_archivo',
      'Jurisdiccion',
      'Estado'
    ];
    $columnas_idx = array_flip($columnas);

    foreach ($columnas as $colidx => $col)
      $datos[0][$colidx] = $col;

    for ($rowidx = 0; $rowidx < count($datos); $rowidx++) {//Saco las columnas inutiles
      unset($datos[$rowidx][$columnas_idx['ID']]);
      unset($datos[$rowidx][$columnas_idx['Estado']]);
      unset($datos[$rowidx][$columnas_idx['Jurisdiccion']]);

      $datos[$rowidx] = array_values($datos[$rowidx]);
    }

    $columnas = $datos[0];
    $columnas_idx = array_flip($columnas); {//Transformaciones 1:1
      $categorias = CategoriaJuego::all();
      $laboratorios = Laboratorio::all();
      $CAT_IDX = $columnas_idx['id_categoria_juego'];
      $LAB_IDX = $columnas_idx['id_laboratorio'];
      $PJE_IDX = $columnas_idx['porcentaje_devolucion'];
      $REGEX_ES_PJE = '/^[0-9]{0,3}[1-9]\.[0-9]{0,3}%?$/';
      $REGEX_EN_PJE = '/^[0-9]{0,3}[1-9],[0-9]{0,3}%?$/';
      $REGEX_ES_DEC = '/^0,[0-9]{0,6}$/';
      $REGEX_EN_DEC = '/^0\.[0-9]{0,6}$/';

      for ($rowidx = 1; $rowidx < count($datos); $rowidx++) {
        //En las columnas tecnologia y codigo vienen CodigoDesktop y CodigoMovil
        //Hago esta unificacion para mas adelante
        $datos[$rowidx][$columnas_idx['cod_juego']] =
          $datos[$rowidx][$columnas_idx['tecnologia']]
          . '|' .
          $datos[$rowidx][$columnas_idx['cod_juego']];
        $datos[$rowidx][$columnas_idx['tecnologia']] = ''; {
          $pje = preg_replace('/[[:space:]]/', '', $datos[$rowidx][$PJE_IDX]);
          if (preg_match($REGEX_ES_PJE, $pje)) {
            $pje = str_replace('%', '', $pje);
            $pje = str_replace(',', '.', $pje);
          } else if (preg_match($REGEX_EN_PJE, $pje)) {
            $pje = str_replace('%', '', $pje);
          } else if (preg_match($REGEX_ES_DEC, $pje)) {
            $pje = bcmul(str_replace(',', '.', $pje), 100, 2);
          } else if (preg_match($REGEX_EN_DEC, $pje)) {
            $pje = bcmul($pje, 100, 4);
          } else {
            $pje = 'ERROR';
          }
          $datos[$rowidx][$PJE_IDX] = $pje;
        } {
          $closest_cat = null;
          $closest_cat_percent = -INF;
          foreach ($categorias as $cat) {
            $p = -INF;
            similar_text(strtolower($cat->nombre), strtolower($datos[$rowidx][$CAT_IDX]), $p);
            if ($p > $closest_cat_percent) {
              $closest_cat_percent = $p;
              $closest_cat = $cat->id_categoria_juego;
            }
          }

          if ($closest_cat !== null) {
            $datos[$rowidx][$CAT_IDX] = $closest_cat;
          } else {
            $datos[$rowidx][$CAT_IDX] = '';
          }
        } {
          $closest_lab = null;
          $closest_lab_percent = -INF;
          foreach ($laboratorios as $lab) {
            $p = -INF;
            similar_text(strtolower($lab->codigo), strtolower($datos[$rowidx][$LAB_IDX]), $p);
            if ($p > $closest_lab_percent) {
              $closest_lab_percent = $p;
              $closest_lab = $lab->id_laboratorio;
            }
          }

          if ($closest_lab !== null) {
            $datos[$rowidx][$LAB_IDX] = $closest_lab;
          } else {
            $datos[$rowidx][$LAB_IDX] = '';
          }
        }
      }
    }

    $id_plataforma = null; {//Transformacion 1->N
      $new_datos = [];
      $COD_IDX = $columnas_idx['cod_juego'];
      $TEC_IDX = $columnas_idx['tecnologia'];
      foreach ($datos as $jidx => $j) {
        if ($jidx == 0) {
          $new_datos[] = $j;
          continue;
        }

        $cods = explode('|', $j[$COD_IDX]);
        $cod_desktop = trim($cods[0] ?? '');
        $cod_mobile = trim($cods[1] ?? '');
        $tiene_desktop = !empty($cod_desktop);
        $tiene_mobile = !empty($cod_mobile);

        $tecnologia = '';
        if ($cod_desktop != $cod_mobile) {
          if ($tiene_desktop && $tiene_mobile) {
            //BPLAY manda los dos juntos en la misma fila (con distinto codigo) asi que sacamos la plataforma de ahi
            $id_plataforma = ($id_plataforma === null || $id_plataforma == 2) ? 2 : -1;//BPLAY
            $tecnologia = 'duplicar';
          } else if ($tiene_desktop xor $tiene_mobile) {
            //CCO manda uno por fila
            $id_plataforma = ($id_plataforma === null || $id_plataforma == 1) ? 1 : -1;//CCO
            $tecnologia = $tiene_desktop ? 'escritorio' : 'movil';
          }
        } else {//Si tiene el mismo codigo quiere decir que es para ambas tecnologias 
          $tecnologia = 'escritorio_y_movil';
        }

        if ($tecnologia == 'duplicar') {
          $dd = $j;
          $dd[$TEC_IDX] = 'escritorio';
          $dd[$COD_IDX] = $cod_desktop;

          $dm = $j;
          $dm[$TEC_IDX] = 'movil';
          $dm[$COD_IDX] = $cod_mobile;

          $new_datos[] = $dd;
          $new_datos[] = $dm;
        } else if ($tecnologia == 'escritorio') {
          $j[$TEC_IDX] = 'escritorio';
          $j[$COD_IDX] = $cod_desktop;
          $new_datos[] = $j;
        } else if ($tecnologia == 'movil') {
          $j[$TEC_IDX] = 'movil';
          $j[$COD_IDX] = $cod_mobile;
          $new_datos[] = $j;
        } else if ($tecnologia == 'escritorio_y_movil') {
          $j[$TEC_IDX] = 'escritorio_y_movil';
          $j[$COD_IDX] = $cod_desktop;//Cualquiera esta bien porque son el mismo
          $new_datos[] = $j;
        } else {
          throw new \Exception('Unreachable, tecnologia: ' . $tecnologia . ' codigos: ' . $j[$COD_IDX]);
        }
      }

      $datos = $new_datos;
    }

    return [$datos, $id_plataforma];
  }

  private function convertirDatosProveedor($datos)
  {
    if ($datos === null)
      return [null, null];
    $id_plataforma = 2;//Es BPLAY si tiene datos de proveedor

    $columnas = $datos[0];
    $columnas_idx = array_flip($columnas);

    $ret = [];
    $NJIDX = $columnas_idx['Nombre del juego'];
    $PIDX = $columnas_idx['Proveedor'];
    foreach ($datos as $didx => $d) {
      if ($didx == 0)
        continue;
      $ret[$d[$NJIDX]] = $d[$PIDX];
    }

    return [$ret, $id_plataforma];
  }

  public function parsearArchivo(Request $request)
  {
    $rmdir = function ($dir) use (&$rmdir) {//Borra recursivamente... cuidado con que se lo llama
      assert(substr($dir, 0, strlen(storage_path())) == storage_path());//Chequea que no se llame con un path raro
      if (is_dir($dir) === false)
        return false;
      $files = array_diff(scandir($dir), ['.', '..']);

      foreach ($files as $f) {
        $fpath = $dir . '/' . $f;
        if (is_dir($fpath)) {
          $rmdir($fpath);
        } else {
          unlink($fpath);
        }
      }

      return rmdir($dir);
    };

    $carpeta_storage = storage_path('juegosImportacion');
    if (!is_dir($carpeta_storage)) {
      mkdir($carpeta_storage);
    }

    $carpeta_storage .= '/' . uniqid();
    $rmdir($carpeta_storage);
    mkdir($carpeta_storage);

    $ret = ['id_plataforma' => null, 'juegos' => [], 'mensaje' => ''];

    $err_file = $carpeta_storage . '/log.err';
    $out_dir = $carpeta_storage . '/out';
    mkdir($out_dir);

    $excel = $request->archivo->getPathName();
    $outfile = $out_dir . '/out.csv';

    $output = [];
    $return_var = null;
    exec('ssconvert --export-file-per-sheet ' . escapeshellarg($excel) . ' ' . $outfile . ' 2> ' . $err_file, $output, $return_var);

    $clean = function () use ($err_file, $rmdir, $carpeta_storage) {
      try {
        unlink($err_file);
        $rmdir($carpeta_storage);
      } catch (\Exception $e) {
      }
    };

    if ($return_var != 0) {
      $ret['mensaje'] .= '<span>Code: ' . $return_var . '</span>';
      $ret['mensaje'] .= '<p>Output:<pre><code></code>' . implode("\r\n", $output) . '</pre></p>';
      $ret['mensaje'] .= '<p>Error:<pre><code></code>' . file_get_contents($err_file) . '</pre></p>';
      $clean();
      return $ret;
    }

    $candidatos = scandir($out_dir);
    $datos = null;
    $datos_proveedor = null;
    foreach ($candidatos as $cand) {
      if ($cand == '.' || $cand == '..')
        continue;
      $abs_cand = $out_dir . '/' . $cand;
      $fhandle = fopen($abs_cand, 'r');
      if ($fhandle === FALSE)
        continue;
      $linea = fgetcsv($fhandle);
      $es_anexo = count($linea) > 0 && $linea[0] == "ANEXO A";
      $es_proveedor = count($linea) > 0 && $linea[0] == "Proveedor";
      if ($es_anexo) {
        $datos = [$linea];
        while (($linea = fgetcsv($fhandle)) !== FALSE)
          $datos[] = $linea;
      } else if ($es_proveedor) {
        $datos_proveedor = [$linea];
        while (($linea = fgetcsv($fhandle)) !== FALSE)
          $datos_proveedor[] = $linea;
      }
      fclose($fhandle);
    }

    if ($datos === null) {
      $ret['mensaje'] = 'No se encontro al ANEXO A en el archivo';
      $clean();
      return $ret;
    }

    $datos = array_slice($datos, 2);//Saco el titulo y la precabecera

    $id_plataforma = null; {
      $aux = $this->convertirAnexo($datos);
      $datos = $aux[0];
      $id_plataforma = $aux[1];
    } {
      $aux = $this->convertirDatosProveedor($datos_proveedor);
      $datos_proveedor = $aux[0];
      $id_plataforma = ($id_plataforma == -1) ? $aux[1] : $id_plataforma;
      $id_plataforma = ($id_plataforma === null) ? -1 : $id_plataforma;
    } {//Le agrego el proveedor
      $nombre_juego_idx = array_search('nombre_juego', $datos[0] ?? []);
      if ($nombre_juego_idx !== FALSE)
        foreach ($datos as $didx => &$d) {
          if ($didx == 0) {
            $d[] = 'proveedor';
          } else {
            $d[] = $datos_proveedor[$d[$nombre_juego_idx]] ?? '';
          }
        }
    }

    $ret['id_plataforma'] = $id_plataforma == -1 ? null : $id_plataforma;//-1 quiere decir que hubo conflicto detectando
    $ret['juegos'] = $datos;

    $clean();
    return $ret;
  }

  private function validarCargaMasiva_arr(array $request)
  {
    Validator::make($request, [
      'id_plataforma' => 'required|exists:plataforma,id_plataforma',
      'juegos' => 'nullable|array',
      'juegos.*.id_categoria_juego' => 'required|integer|exists:categoria_juego,id_categoria_juego',
      'juegos.*.tecnologia' => 'required|string|in:escritorio,movil,escritorio_y_movil',
      'juegos.*.cod_juego' => 'required|string',
      'juegos.*.nombre_juego' => 'required|string',
      'juegos.*.porcentaje_devolucion' => 'required|numeric|between:0,99.99',
      'juegos.*.nro_archivo' => ['required', 'string', 'regex:/^\d?\w(.|-|_|\d|\w)*$/'],
      'juegos.*.nro_archivo' => ['required', 'string', 'regex:/^\d?\w(.|-|_|\d|\w)*$/'],
      'juegos.*.id_laboratorio' => 'nullable|integer|exists:laboratorio,id_laboratorio',
      'juegos.*.certificado' => 'nullable|mimes:pdf'
    ], [
      'required' => 'El valor es requerido',
      'numeric' => 'El valor tiene que ser numerico',
      'between' => 'El valor tiene que ser entre 0-99.99',
      'regex' => 'El valor tiene formato invalido',
      'exists' => 'El valor no existe',
      'in' => 'El valor no existe',
      'mimes' => 'Tiene que ser un archivo PDF'
    ], self::$atributos)->after(function ($validator) {
      if ($validator->errors()->any())
        return;
      $data = $validator->getData();

      $plataformas = UsuarioController::getInstancia()->quienSoy()['usuario']->plataformas->pluck('id_plataforma')->toArray();
      if (!in_array($data['id_plataforma'], $plataformas)) {
        return $validator->errors()->add('id_plataforma', 'El usuario no puede acceder a esa plataforma');
      }

      $data_certificados = [];
      foreach (($data['juegos'] ?? []) as $jidx => $j) {
        $ya_existe = DB::table('juego as j')
          ->where('j.cod_juego', $j['cod_juego'])
          ->whereNull('j.deleted_at')->count() > 0;
        if ($ya_existe) {
          $validator->errors()->add("juegos.$jidx.cod_juego", 'El juego ya esta cargado');
        }

        $id_laboratorio = $j['id_laboratorio'] ?? null;
        $certificado = $j['certificado'] ?? null;

        $data_certificados[$j['nro_archivo']] = $data_certificados[$j['nro_archivo']] ?? [
          'id_laboratorio' => null,
          'certificado' => null
        ];

        if (empty($data_certificados[$j['nro_archivo']]['id_laboratorio'])) {
          $data_certificados[$j['nro_archivo']]['id_laboratorio'] = $id_laboratorio;
        }
        if (empty($data_certificados[$j['nro_archivo']]['certificado'])) {
          $data_certificados[$j['nro_archivo']]['certificado'] = $certificado;
        }

        if ($data_certificados[$j['nro_archivo']]['id_laboratorio'] != $id_laboratorio && !empty($id_laboratorio)) {
          $validator->errors()->add("juegos.$jidx.id_laboratorio", 'Conflicto de laboratorios');
        }
        if ($data_certificados[$j['nro_archivo']]['certificado'] != $certificado && !empty($certificado)) {
          $validator->errors()->add("juegos.$jidx.certificado", 'Conflicto de archivos certificados');
        }
      }

      foreach ($data_certificados as $nro_archivo => $dc) {
        if (empty($dc['id_laboratorio'])) {
          foreach (($data['juegos'] ?? []) as $jidx => $j) {
            if ($j['nro_archivo'] != $nro_archivo)
              continue;
            $validator->errors()->add("juegos.$jidx.id_laboratorio", 'El valor es requerido');
          }
        }
      }

      return;
    })->validate();

    return 1;
  }

  public function validarCargaMasiva(Request $request)
  {
    return $this->validarCargaMasiva_arr($request->all());
  }

  public function guardarCargaMasiva(Request $request)
  {
    $R = $request->all();
    $this->validarCargaMasiva_arr($R);
    $R = collect($R);

    return DB::transaction(function () use ($R) {
      $certificados = [];
      foreach (($R['juegos'] ?? []) as $j) {
        if (
          empty($j['nro_archivo'])
          || isset($certificados[$j['nro_archivo']])
        ) {
          continue;
        }

        $gli = GliSoft::where('nro_archivo', $j['nro_archivo'])->whereNull('deleted_at')->first();
        if ($gli === null) {
          $gli = new GliSoft;
        }

        $gli->nro_archivo = $j['nro_archivo'];
        $gli->id_laboratorio = empty($j['id_laboratorio']) ? null : $j['id_laboratorio'];
        $gli->save();

        if (!empty($j['certificado'])) {
          GliSoftController::getInstancia()->guardarArchivo($gli, $j['certificado']);
        }

        $certificados[$gli->nro_archivo] = $gli;
      }

      $plataformas_estado = [];
      $plataformas_estado[$R['id_plataforma']] = [
        'id_estado_juego' => DB::table('estado_juego')->where('nombre', 'Activo')->first()->id_estado_juego
      ];

      foreach (($R['juegos'] ?? []) as $j) {
        $params = [
          'nombre_juego' => $j['nombre_juego'] ?? null,
          'cod_juego' => $j['cod_juego'] ?? null,
          'denominacion_juego' => 1,
          'porcentaje_devolucion' => $j['porcentaje_devolucion'] ?? null,
          'escritorio' => (($j['tecnologia'] == 'escritorio' || $j['tecnologia'] == 'escritorio_y_movil') + 0),
          'movil' => (($j['tecnologia'] == 'movil' || $j['tecnologia'] == 'escritorio_y_movil') + 0),
          'codigo_operador' => '',
          'proveedor' => $j['proveedor'] ?? '',
          'id_tipo_moneda' => 1,
          'id_categoria_juego' => $j['id_categoria_juego'] ?? null
        ];
        $cert = ['id_gli_soft' => $certificados[$j['nro_archivo']]->id_gli_soft];
        $this->crear_o_modificar_juego(null, 'Alta por Carga Masiva', $params, $plataformas_estado, [$cert]);
      }

      return ['certificados' => array_values($certificados)];
    });
  }
}
