<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cartelera;
use Illuminate\Http\Request;

class CarteleraController extends Controller
{
  public function carteleras()
  {
    $carteleras= Cartelera::where('activa', true)->get();

    return $carteleras;
  }
}
