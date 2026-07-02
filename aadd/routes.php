<?php

Route::get('/aadd', '\AADD\Http\Controllers\AaddController@index')
    ->middleware('tiene_permiso');

Route::get('/aadd/importaciones', '\AADD\Http\Controllers\AaddController@importaciones')
    ->middleware('tiene_permiso');

Route::post('/aadd/importar', '\AADD\Http\Controllers\AaddController@importar')
    ->middleware('tiene_permiso');

Route::get('/aadd/cantidad-archivos', '\AADD\Http\Controllers\AaddController@cantidadArchivos')
    ->middleware('tiene_permiso');

