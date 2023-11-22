<?php

namespace App\Http\Controllers\Auth;

use App\EureLib\EureFunctions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EureAuthController extends Controller
{
  public function login()
  {
    return view('auth.login');
  }

  public function logout()
  {
    Auth::logout();
    return redirect('login'); // Redirige a la página de inicio u otra página deseada después del cierre de sesión
  }

  public function authenticate(Request $request): RedirectResponse
  {
    $credentials = $request->validate([
      'login' => ['required'],
      'password' => ['required']
    ]);

//    dd($credentials);

//    $B= Auth::attempt($credentials);

    $B = Auth::attempt(['login' => $request->login, 'password' => $request->password, 'tipo' => 'Responsable']);

    if ($B) {


      $request->session()->regenerate();

      return redirect()->intended('/');
    }

    //
    return back()->withErrors([
      'credentials' => 'No nos coincide el usuario con la contraseña.',
    ])->withInput();

  }

  public function password()
  {
    return view('auth.password');
  }

  public function passwordUpdate(Request $request)
  {
    // Validación de datos
    $validator = Validator::make($request->all(), [
      'currentPassword' => 'required',
      'newPassword' => 'required', // Puedes ajustar la longitud mínima según tus requisitos
      'confirmPassword' => 'required|same:newPassword',
    ]);

    // Si la validación falla, redirige de nuevo al formulario con los errores
    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator)->withInput();
    }

    // Validar la contraseña actual
    if (!Hash::check($request->currentPassword, auth()->user()->password)) {
      return redirect()->back()->withErrors(['currentPassword' => 'La contraseña actual no es correcta'])->withInput();
    }

    // Si la validación es exitosa, actualiza la contraseña en la base de datos
    $user = auth()->user();
    $user->password = Hash::make($request->newPassword);
    $user->save();

    // Redirige a la página de éxito o donde sea necesario
    return redirect()->route('home')->with('success', '¡Contraseña cambiada con éxito!');
  }

//    return back()->withErrors([
//      'credentials' => 'No nos coincide el usuario con la contraseña.',
//    ])->onlyInput('email');

}
