<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use Illuminate\Http\Request;
use mysql_xdevapi\CrudOperationBindable;

class HomeController extends Controller
{
    //
  public function index()
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    $carteleraG = EureFunctions::getCarteleraGeneral();


    if ($carteleraG != null)
    {
      if
      (
        $responsable->web_user->fh_visualizacion_cartelera_general == null ||
        $responsable->web_user->fh_visualizacion_cartelera_general < $carteleraG->updated_at
      )
        return redirect()->route('carteleras.show', $carteleraG);
    }

    return view('home.index', compact('responsable', 'carteleraG'));
  }
}
