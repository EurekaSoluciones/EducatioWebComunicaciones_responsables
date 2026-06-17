<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResponsableController extends Controller
{
  public function show(Responsable $responsable)
  {
    $user = User::where('Cod_Responsable', $responsable->id)->first();

    if($user->id != Auth::id())
      abort(403);

    return view('responsables.show', compact('responsable'));
  }

  public function showLogged()
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    return redirect()->route('responsables.show', ['responsable' => $responsable]);
  }

  public function edit(Responsable $responsable)
  {
    $user = User::where('Cod_Responsable', $responsable->id)->first();

    if($user->id != Auth::id())
      abort(403);

    return view('responsables.edit', compact('user'));

  }
  public function update(Request $request, User $user)
  {
//    dd($request->all());

    if ($request->chSinImagen == 'on')
    {
      $user->avatarImg = '';
      $user->save();
    }
    else
    {
      // La imagen viene cropeada ahora
      $data = $request->input('cropped_image');

      list($type, $data) = explode(';', $data);
      list(, $data)      = explode(',', $data);
      $data = base64_decode($data);

      $nombreArchivo = 'AVResp_' . date('ymdHis') . '_' . rand(100, 999) . '.png';
      Storage::disk('public')->put($nombreArchivo, $data);

    //  Storage::disk('public')->put($nombreArchivo, file_get_contents($archivo));

      $user->avatarImg = $nombreArchivo;
      $user->save();
      }

    return redirect()->route('responsables.show', ['responsable' => $user->responsable])->with('success', 'Perfil Actualizado.');

  }

  public function api_update(Request $request)
  {
   $request->validate([
      'foto' => 'required|image|mimes:jpg,png,jpeg|max:2048',
    ]);

    $user = Auth::user();

    $nombreArchivo = 'AVResp_' . date('ymdHis') . '_' . rand(100, 999) . '.png';

    $path = $request->file('foto')->storeAs('', $nombreArchivo, 'public');

    // Guardar la nueva ruta en la base de datos
    $user->avatarImg = $nombreArchivo;
    $user->save();



    return response()->json([
      'message' => 'Foto de perfil actualizada',
      'foto_url' => asset("storage/$path")
    ]);
  }



}
