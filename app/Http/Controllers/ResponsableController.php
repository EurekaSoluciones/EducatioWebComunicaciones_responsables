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

    return view('responsables.show', compact('user', 'responsable'));
  }

  public function showLogged()
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    return redirect()->route('responsables.show', ['responsable' => $responsable]);
  }

  public function edit(Responsable $responsable)
  {
    $user = User::where('Cod_Responsable', $responsable->id)->first();

    return view('responsables.edit', compact('user'));

  }
  public function update(Request $request, User $user)
  {
    if ($request->chSinImagen == 'on')
    {
      $user->avatarImg = '';
      $user->save();
    }
    else
    {
      if ($request->hasFile('imagenAvatar'))
      {
        $validator = Validator::make($request->all(), [
          'imagenAvatar' => 'required|image',
        ]);

        if ($validator->fails()) {
          // La validación falló
          return redirect()->back()->withErrors($validator)->withInput();
        }

        $archivo = $request->file('imagenAvatar');
        $nombreArchivo = 'AVResp_' . date('ymdHis') . '_' . $archivo->getClientOriginalName();
        Storage::disk('public')->put($nombreArchivo, file_get_contents($archivo));

        $user->avatarImg = $nombreArchivo;
        $user->save();
      }
    }

    return redirect()->route('responsables.show', ['responsable' => $user->responsable])->with('success', 'Perfil Actualizado.');

  }
}
