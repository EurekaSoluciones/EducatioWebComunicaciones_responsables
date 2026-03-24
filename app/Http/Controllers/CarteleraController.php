<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Cartelera;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CarteleraController extends Controller
{
  public function show(Cartelera $cartelera)
  {
    if (!$cartelera->activa)
      abort(400, 'Cartelera inactiva');

    if ($cartelera->tipo == 'GENERAL')
      return $this->show_cartelera_general($cartelera);

    return view('carteleras.show', compact('cartelera'));
  }



  public function show_cartelera_general(Cartelera $cartelera)
  {
    if (!$cartelera->tipo == 'GENERAL')
      abort(400, 'No es general');

    $usuario = EureFunctions::loggedUser();
    $usuario->fh_visualizacion_cartelera_general = Carbon::now();
    $usuario->save();

    return view('carteleras.show', compact('cartelera'));
  }

  public function show_cartelera_general_st()
  {
    $carteleraG = Cartelera::where('tipo', 'GENERAL')->where('activa', true)->first();

    if ($carteleraG != null)
      return $this->show_cartelera_general($carteleraG);
  }

  public function appshow(Cartelera $cartelera)
  {
    if (!$cartelera->activa)
      abort(400, 'Cartelera inactiva');

    return view('carteleras.appshow', compact('cartelera'));
  }


}
