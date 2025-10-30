<?php
namespace App\Http\Controllers\AuditoriaEventos;

use App\Http\Controllers\Controller;


class AuditoriaEventosController extends Controller
{
    public function index()
    {
        return view('AuditoriaEventos.indexAuditoriaEventos');
    }
}