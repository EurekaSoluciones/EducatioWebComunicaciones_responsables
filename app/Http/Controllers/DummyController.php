<?php

namespace App\Http\Controllers;


use App\EureLib\EureFunctions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class DummyController extends Controller
{
  public function hello_world()
  {
    return 'Hello World' . env('EURE_AL') . '  PERO LTPM ' . env('EURE_DEFAULT_AVATAR_PROVIDER_RESPONSABLES');

  }

    //
  public function index()
  {
    $sa= Carbon::now();

    $a= $sa;

    return view('dummy.index', compact('a'));
  }

  public function show($slug)
  {
    return view('dummy.' . $slug);
  }

  public function index2()
  {
    dd(Str::slug('Presupuesto ESET + SDP-2023_230719_194604.pdf'));

    return view('dummy.index2');
  }
  public function index3()
  {
    return view('dummy.index3');
  }

  public function index5()
  {
 dd(EureFunctions::getLoggedResponsableAttribute());



    return view('dummy.index5');
  }

}
