<?php

namespace AADD\Dto;

class ArchivoImportadoDTO implements \JsonSerializable
{
    public $nombre_archivo;
    public $plataforma;
    public $ruta;
    public $estado;
    public $mensaje;

    public function __construct($nombre_archivo, $plataforma, $estado, $ruta = null, $mensaje = null)
    {
        $this->nombre_archivo = $nombre_archivo;
        $this->plataforma = $plataforma;
        $this->estado = $estado;
        $this->ruta = $ruta;
        $this->mensaje = $mensaje;
    }

    public static function crearExito($nombre_archivo, $plataforma, $ruta, $mensaje)
    {
        return new self($nombre_archivo, $plataforma, 'exito', $ruta, $mensaje);
    }

    public static function crearError($nombre_archivo, $plataforma, $mensaje)
    {
        return new self($nombre_archivo, $plataforma, 'error', null, $mensaje);
    }

    public function jsonSerialize()
    {
        return [
            'nombre_archivo' => $this->nombre_archivo,
            'plataforma' => $this->plataforma,
            'estado' => $this->estado,
            'ruta' => $this->ruta,
            'mensaje' => $this->mensaje
        ];
    }
}
