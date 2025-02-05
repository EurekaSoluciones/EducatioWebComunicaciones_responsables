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

      $nombreArchivo = 'AVAlumno_' . date('ymdHis') . '_' . rand(100, 999) . '.png';
      Storage::disk('public')->put($nombreArchivo, $data);

      //  Storage::disk('public')->put($nombreArchivo, file_get_contents($archivo));

      $alumno->web->avatarImg = $nombreArchivo;
      $alumno->web->save();
    }

    return redirect()->route('alumnos.show', compact('alumno'))->with('success', 'Perfil Actualizado.');
  }

  public function api_update_foto(Request $request)
  {
    $request->validate([
      'Cod_Alumno' => 'required',
      'foto' => 'required|image|mimes:jpg,png,jpeg|max:2048',
    ]);

    $user = Auth::user();
    $alumno= Alumno::where('Cod_Alumno', $request->Cod_Alumno)->first();

    if ($alumno == null)
      return response()->json(['message' => 'Alumno no encontrado'], 401);

    $responsable= Responsable::where('Cod_Responsable', $user->Cod_Responsable)->first();

    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    $nombreArchivo = 'AVAlumno_' . date('ymdHis') . '_' . rand(100, 999) . '.png';

    $path = $request->file('foto')->storeAs('', $nombreArchivo, 'public');

    // Guardar la nueva ruta en la base de datos
    $alumno->web->avatarImg = $nombreArchivo;
    $alumno->web->save();


    return response()->json([
      'message' => 'Foto de perfil actualizada',
      'foto_url' => asset("storage/$path")
    ]);
  }

  public function api_update_foto_remover(Request $request)
  {
    $user = Auth::user();
    $alumno= Alumno::where('Cod_Alumno', $request->Cod_Alumno)->first();

    if ($alumno == null)
      return response()->json(['message' => 'Alumno no encontrado'], 401);

    $responsable= Responsable::where('Cod_Responsable', $user->Cod_Responsable)->first();

    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    $alumno->web->avatarImg = '';
    $alumno->web->save();


    return response()->json([
      'message' => 'Foto de perfil actualizada',
    ]);
  }


}
