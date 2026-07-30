<?php

// Landing page
Route::get('/aadd', '\AADD\Http\Controllers\AaddController@index')
    ->middleware('tiene_permiso');

// Importación de archivos
Route::get('/aadd/importaciones', '\AADD\Http\Controllers\ImportacionController@index')
    ->middleware('tiene_permiso');

Route::get('/aadd/importaciones/tabla', '\AADD\Http\Controllers\ImportacionController@tabla')
    ->middleware('tiene_permiso');

Route::post('/aadd/importaciones/importar', '\AADD\Http\Controllers\ImportacionController@importar')
    ->middleware('tiene_permiso');

Route::delete('/aadd/importaciones/{id}', '\AADD\Http\Controllers\ImportacionController@eliminar')
    ->middleware('tiene_permiso');

// Beneficio Diario (Informes Visuales y AJAX)
Route::get('/aadd/beneficio-diario', '\AADD\Http\Controllers\BeneficioDiarioController@index')
    ->middleware('tiene_permiso');

Route::get('/aadd/beneficio-diario/tabla', '\AADD\Http\Controllers\BeneficioDiarioController@tabla')
    ->middleware('tiene_permiso');

Route::get('/aadd/beneficio-diario/acumulado-mensual', '\AADD\Http\Controllers\BeneficioDiarioController@acumuladoMensual')
    ->middleware('tiene_permiso');

// Comparación de beneficios por plataforma (vista consolidada de todas las plataformas)
Route::get('/aadd/datos-rapidos', '\AADD\Http\Controllers\DatosRapidosController@index')
    ->middleware('tiene_permiso');

Route::get('/aadd/datos-rapidos/tabla', '\AADD\Http\Controllers\DatosRapidosController@tabla')
    ->middleware('tiene_permiso');

Route::get('/aadd/datos-rapidos/acumulado-mensual', '\AADD\Http\Controllers\DatosRapidosController@acumuladoMensual')
    ->middleware('tiene_permiso');

// Emisión extensible de informes PDF y Excel
Route::get('/aadd/informes/pdf/{reporte}', '\AADD\Http\Controllers\InformesController@exportarPdf')
    ->middleware('tiene_permiso');

Route::get('/aadd/informes/excel/{reporte}', '\AADD\Http\Controllers\InformesController@exportarExcel')
    ->middleware('tiene_permiso');
