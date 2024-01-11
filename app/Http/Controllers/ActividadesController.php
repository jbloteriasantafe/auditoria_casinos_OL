<?php

namespace App\Http\Controllers;

class ActividadesController
{
  public static function cambiarEstado($fecha,$tags_api,$estado,$contenido){	
    set_time_limit(30);
    $ch = curl_init();
    $ip_port = env('ACTIVIDADES_IP', '127.0.0.1').':'.env('ACTIVIDADES_PORT','8001');
    curl_setopt($ch, CURLOPT_URL, "http://$ip_port/api/actividades/cambiarEstado");
    curl_setopt($ch, CURLOPT_POST, 1);
    $user_name = \App\Http\Controllers\UsuarioController::getInstancia()->quienSoy()['usuario']->user_name;
    $postfields = compact('fecha','tags_api','user_name');
    $postfields['nuevos_datos'] = compact('estado','contenido');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    //curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_PROXY, NULL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'API-Token: '.env('ACTIVIDADES_API_TOKEN',''),
    ]);
    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'result' => json_decode($result ?? null,true)];
  }
}
