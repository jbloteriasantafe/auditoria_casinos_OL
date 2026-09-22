<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Juego;
use App\GliSoft;
use Validator;

//Importador masivo de rectificaciones: reemplaza el certificado GLI de juegos que YA existen.
//A diferencia de la carga masiva de JuegoController, aca el juego TIENE que estar cargado
//y lo unico que se modifica es su certificado.
class RectificacionCertificadosController extends Controller
{
  private static $instance;

  private static $atributos = [
    'motivo' => 'motivo',
    'rectificacion.*.cod_juego' => 'código del juego',
    'rectificacion.*.nro_archivo' => 'código del certificado',
    'rectificacion.*.id_laboratorio' => 'laboratorio',
    'rectificacion.*.certificado' => 'certificado'
  ];

  private static $errores = [
    'required' => 'El valor es requerido',
    'string' => 'El valor tiene que ser una cadena de caracteres',
    'integer' => 'El valor no es un numero',
    'array' => 'El valor es invalido',
    'min' => 'No hay nada para rectificar',
    'max' => 'El valor es demasiado largo',
    'regex' => 'El valor tiene formato invalido',
    'exists' => 'El valor no existe',
    'mimes' => 'Tiene que ser un archivo PDF'
  ];

  public static function getInstancia()
  {
    if (!isset(self::$instance)) {
      self::$instance = new RectificacionCertificadosController();
    }
    return self::$instance;
  }

  //Lee el ANEXO A reusando el parser de la carga masiva de juegos y lo proyecta a lo
  //unico que importa para una rectificacion, agregandole el estado actual de cada juego.
  public function parsearRectificacion(Request $request)
  {
    Validator::make($request->all(), [
      'archivo' => 'required|file'
    ], self::$errores, self::$atributos)->validate();

    $parseado = JuegoController::getInstancia()->parsearArchivo($request);

    return [
      'id_plataforma' => $parseado['id_plataforma'],
      'mensaje' => $parseado['mensaje'],
      'filas' => $this->proyectarColumnasRectificacion($parseado['juegos'])
    ];
  }

  //parsearArchivo devuelve la fila 0 con los nombres de columna y el resto con los datos
  private function proyectarColumnasRectificacion($juegos)
  {
    if (empty($juegos))
      return [];

    $columnas_idx = array_flip($juegos[0]);
    $filas = [];

    for ($jidx = 1; $jidx < count($juegos); $jidx++) {
      $j = $juegos[$jidx];

      $cod_juego = trim($j[$columnas_idx['cod_juego']] ?? '');
      if ($cod_juego === '')//Filas vacias al final de la hoja
        continue;

      $fila = [
        'cod_juego' => $cod_juego,
        'nombre_anexo' => $j[$columnas_idx['nombre_juego']] ?? '',
        'nro_archivo' => trim($j[$columnas_idx['nro_archivo']] ?? ''),
        'id_laboratorio' => $j[$columnas_idx['id_laboratorio']] ?? '',
      ];

      $filas[] = array_merge($fila, $this->estadoActualDelJuego($cod_juego), [
        'gli_existente' => $this->estadoDelCertificado($fila['nro_archivo'])
      ]);
    }

    return $filas;
  }

  //Datos del juego tal como esta hoy, para que el auditor vea el "antes" en la grilla
  private function estadoActualDelJuego($cod_juego)
  {
    $juegos = Juego::where('cod_juego', $cod_juego)->whereNull('deleted_at')->get();

    if ($juegos->count() != 1) {
      return [
        'id_juego' => null,
        'nombre_juego' => null,
        'certificados_actuales' => [],
        'juegos_encontrados' => $juegos->count()
      ];
    }

    $juego = $juegos->first();
    $certificados = [];
    foreach ($juego->gliSoft as $gli) {
      $certificados[] = ['id_gli_soft' => $gli->id_gli_soft, 'nro_archivo' => $gli->nro_archivo];
    }

    return [
      'id_juego' => $juego->id_juego,
      'nombre_juego' => $juego->nombre_juego,
      'certificados_actuales' => $certificados,
      'juegos_encontrados' => 1
    ];
  }

  //Si el certificado ya existe no hace falta volver a pedir el PDF ni el laboratorio
  private function estadoDelCertificado($nro_archivo)
  {
    if (empty($nro_archivo))
      return null;

    $gli = GliSoft::where('nro_archivo', $nro_archivo)->whereNull('deleted_at')->first();
    if (is_null($gli))
      return null;

    return [
      'id_gli_soft' => $gli->id_gli_soft,
      'id_laboratorio' => $gli->id_laboratorio,
      'tiene_archivo' => !is_null($gli->id_archivo)
    ];
  }

  public function validarRectificacion(Request $request)
  {
    return $this->validarRectificacion_arr($request->all());
  }

  private function validarRectificacion_arr(array $request)
  {
    Validator::make($request, [
      'motivo' => 'required|string|max:256',
      'rectificacion' => 'required|array|min:1',
      'rectificacion.*.cod_juego' => 'required|string|max:100',
      'rectificacion.*.nro_archivo' => ['required', 'string', 'max:150', 'regex:/^\d?\w(.|-|_|\d|\w)*$/'],
      'rectificacion.*.id_laboratorio' => 'nullable|integer|exists:laboratorio,id_laboratorio',
      'rectificacion.*.certificado' => 'nullable|mimes:pdf',
      //El modal valida antes de subir los PDF (asi no choca contra max_file_uploads),
      //y avisa con esta marca que el archivo lo va a mandar en el paso siguiente.
      //guardarRectificacion la descarta, asi que ahi se exige el archivo de verdad.
      'rectificacion.*.tiene_certificado' => 'nullable|boolean'
    ], self::$errores, self::$atributos)->after(function ($validator) {
      if ($validator->errors()->any())
        return;

      $data = $validator->getData();
      $filas = $data['rectificacion'] ?? [];

      $plataformas_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']
        ->plataformas->pluck('id_plataforma')->toArray();

      $vistos = [];
      $data_certificados = [];

      foreach ($filas as $fidx => $f) {
        //A diferencia de la carga masiva de juegos, aca el juego TIENE que existir
        $juegos = Juego::where('cod_juego', $f['cod_juego'])->whereNull('deleted_at')->get();

        if ($juegos->count() == 0) {
          $validator->errors()->add("rectificacion.$fidx.cod_juego", 'El juego no esta cargado');
        } else if ($juegos->count() > 1) {
          //No hay UNIQUE en juego.cod_juego, asi que no se puede elegir uno al azar
          $validator->errors()->add("rectificacion.$fidx.cod_juego", 'Hay mas de un juego con ese codigo');
        } else {
          $juego = $juegos->first();

          $tiene_plataforma = $juego->plataformas()->count();
          $accesibles = $juego->plataformas()
            ->whereIn('plataforma.id_plataforma', $plataformas_usuario)->count();
          if ($tiene_plataforma > 0 && $accesibles == 0) {
            $validator->errors()->add("rectificacion.$fidx.cod_juego", 'No puede acceder a ese juego');
          }
        }

        if (isset($vistos[$f['cod_juego']])) {
          $validator->errors()->add("rectificacion.$fidx.cod_juego", 'El juego aparece mas de una vez');
        }
        $vistos[$f['cod_juego']] = $fidx;

        //Varios juegos pueden compartir un certificado, pero entonces tienen que
        //traer el mismo laboratorio y el mismo PDF
        $nro_archivo = $f['nro_archivo'];
        $id_laboratorio = $f['id_laboratorio'] ?? null;
        $certificado = $f['certificado'] ?? null;

        $data_certificados[$nro_archivo] = $data_certificados[$nro_archivo] ?? [
          'id_laboratorio' => null,
          'certificado' => null,
          'hay_certificado' => false,
          'filas' => []
        ];
        $data_certificados[$nro_archivo]['filas'][] = $fidx;

        if (empty($data_certificados[$nro_archivo]['id_laboratorio'])) {
          $data_certificados[$nro_archivo]['id_laboratorio'] = $id_laboratorio;
        }
        if (empty($data_certificados[$nro_archivo]['certificado'])) {
          $data_certificados[$nro_archivo]['certificado'] = $certificado;
        }
        if (!empty($certificado) || !empty($f['tiene_certificado'])) {
          $data_certificados[$nro_archivo]['hay_certificado'] = true;
        }

        if ($data_certificados[$nro_archivo]['id_laboratorio'] != $id_laboratorio && !empty($id_laboratorio)) {
          $validator->errors()->add("rectificacion.$fidx.id_laboratorio", 'Conflicto de laboratorios');
        }
        if ($data_certificados[$nro_archivo]['certificado'] != $certificado && !empty($certificado)) {
          $validator->errors()->add("rectificacion.$fidx.certificado", 'Conflicto de archivos certificados');
        }
      }

      //El laboratorio y el PDF solo se exigen cuando el certificado todavia no existe
      foreach ($data_certificados as $nro_archivo => $dc) {
        $gli = GliSoft::where('nro_archivo', $nro_archivo)->whereNull('deleted_at')->get();

        if ($gli->count() > 1) {
          foreach ($dc['filas'] as $fidx) {
            $validator->errors()->add("rectificacion.$fidx.nro_archivo", 'Hay mas de un certificado con ese codigo');
          }
          continue;
        }

        $existente = $gli->first();

        if (is_null($existente) && empty($dc['id_laboratorio'])) {
          foreach ($dc['filas'] as $fidx) {
            $validator->errors()->add("rectificacion.$fidx.id_laboratorio", 'El valor es requerido');
          }
        }

        $le_falta_archivo = is_null($existente) || is_null($existente->id_archivo);
        if ($le_falta_archivo && !$dc['hay_certificado']) {
          foreach ($dc['filas'] as $fidx) {
            $validator->errors()->add("rectificacion.$fidx.certificado", 'El valor es requerido');
          }
        }
      }

      return;
    })->validate();

    return 1;
  }

  public function guardarRectificacion(Request $request)
  {
    $R = $request->all();

    //Aca el PDF tiene que venir de verdad: se descarta la marca que usa la validacion
    //previa para que nadie pueda guardar un certificado nuevo sin su archivo
    foreach (($R['rectificacion'] ?? []) as $fidx => $f) {
      unset($R['rectificacion'][$fidx]['tiene_certificado']);
    }

    $this->validarRectificacion_arr($R);

    return DB::transaction(function () use ($R) {
      $filas = $R['rectificacion'];
      $motivo = $R['motivo'];

      //FASE 1: un GLI por nro_archivo. Si varios juegos comparten certificado
      //el PDF se guarda una sola vez.
      $certificados = [];
      foreach ($filas as $f) {
        $nro_archivo = $f['nro_archivo'];
        if (isset($certificados[$nro_archivo]))
          continue;

        $gli = GliSoft::where('nro_archivo', $nro_archivo)->whereNull('deleted_at')->first();
        if (is_null($gli)) {
          $gli = new GliSoft;
          $gli->nro_archivo = $nro_archivo;
        }
        if (!empty($f['id_laboratorio'])) {
          $gli->id_laboratorio = $f['id_laboratorio'];
        }
        $gli->save();

        if (!empty($f['certificado'])) {
          $archivo_anterior = $gli->archivo;
          try {
            GliSoftController::getInstancia()->guardarArchivo($gli, $f['certificado']);
          } finally {
            //guardarArchivo las apaga y no las restaura si falla
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
          }
          //Recien ahora se puede borrar: gli_soft.id_archivo ya apunta al nuevo
          if (!is_null($archivo_anterior)) {
            $archivo_anterior->delete();
          }
        }

        $certificados[$nro_archivo] = $gli;
      }

      //FASE 2: el reemplazo en si, que deja el log en juego_log_norm
      $JC = JuegoController::getInstancia();
      $resultados = [];
      foreach ($filas as $f) {
        $juego = Juego::where('cod_juego', $f['cod_juego'])->whereNull('deleted_at')->first();
        $gli = $certificados[$f['nro_archivo']];

        if ($this->yaTieneSoloEseCertificado($juego, $gli)) {
          $resultados[] = [
            'cod_juego' => $f['cod_juego'],
            'id_gli_soft' => $gli->id_gli_soft,
            'estado' => 'SIN CAMBIOS'
          ];
          continue;
        }

        $JC->reemplazarCertificadoDeJuego($juego->id_juego, $motivo, $gli->id_gli_soft);
        $resultados[] = [
          'cod_juego' => $f['cod_juego'],
          'id_gli_soft' => $gli->id_gli_soft,
          'estado' => 'COMPLETADO'
        ];
      }

      return ['resultados' => $resultados, 'certificados' => array_values($certificados)];
    });
  }

  //Si ya quedo como tiene que quedar no se genera un log al pedo
  private function yaTieneSoloEseCertificado($juego, $gli)
  {
    $actuales = $juego->gliSoft->pluck('id_gli_soft')->toArray();
    return count($actuales) == 1 && $actuales[0] == $gli->id_gli_soft;
  }
}
