<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AlumnoController extends Controller
{
  public function show(Alumno $alumno)
  {
    // Obtengo el Responsable

    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');


  //  dd($responsable);

    return view('alumnos.show', compact('alumno', 'responsable'));
  }

  public function editPic(Alumno $alumno)
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    //  dd($responsable);

    return view('alumnos.editPic', compact('alumno', 'responsable'));
  }


  public function updatePic(Request $request, Alumno $alumno)
  {
//    dd($request->all());

    if ($request->chSinImagen == 'on')
    {
      $alumno->web->avatarImg = '';
      $alumno->web->save();
    }
    else
    {
      // La imagen viene cropeada ahora
      $data = $request->input('cropped_image');

      list($type, $data) = explode(';', $data);
      list(, $data)      = explode(',', $data);
      $data = base64_decode($data);

      $nombreArchivo = 'AVAlumno_' . date('ymdHis') . '_' . rand(100, 999);
      Storage::disk('public')->put($nombreArchivo, $data);

      //  Storage::disk('public')->put($nombreArchivo, file_get_contents($archivo));

      $alumno->web->avatarImg = $nombreArchivo;
      $alumno->web->save();
    }

    return redirect()->route('alumnos.show', compact('alumno'))->with('success', 'Perfil Actualizado.');
  }
}
