<?php

namespace AADD\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AaddController extends Controller
{
    public function index()
    {
        return view()->file(base_path('aadd/Views/index.blade.php'));
    }
}
