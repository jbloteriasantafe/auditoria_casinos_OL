<?php

Route::get('/aadd', '\AADD\Http\Controllers\AaddController@index')
    ->middleware('tiene_permiso');
