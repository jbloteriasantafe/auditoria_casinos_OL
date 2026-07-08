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

// Beneficio Diario (Informes Visuales y AJAX)
Route::get('/aadd/beneficio-diario', '\AADD\Http\Controllers\BeneficioDiarioController@index')
    ->middleware('tiene_permiso');

Route::get('/aadd/beneficio-diario/tabla', '\AADD\Http\Controllers\BeneficioDiarioController@tabla')
    ->middleware('tiene_permiso');

Route::get('/aadd/beneficio-diario/acumulado-mensual', '\AADD\Http\Controllers\BeneficioDiarioController@acumuladoMensual')
    ->middleware('tiene_permiso');

// Emisión extensible de informes PDF
Route::get('/aadd/informes/pdf/{reporte}', '\AADD\Http\Controllers\InformesController@exportarPdf')
    ->middleware('tiene_permiso');
