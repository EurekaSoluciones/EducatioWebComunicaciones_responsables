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


    return view('home.index', compact('responsable'));
  }
}
