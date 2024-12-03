<?php

namespace App\Http\Controllers\API;

use App\EureLib\EureFunctions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EureAuthController extends Controller
{
  public function login(Request $request)
  {
    // Validamos las credenciales del usuario
    $credentials = $request->validate([
      'login' => 'required',       // Se asume que 'login' es el campo de nombre de usuario
      'password' => 'required',
    ]);

    // Intentamos autenticar al usuario
    if (Auth::attempt(['login' => $request->login, 'password' => $request->password]))
    {
      // Si la autenticación es exitosa, obtenemos al usuario y generamos un token
      $user = Auth::user();

      if($user->tipo != 'Responsable') {
        return response()->json([
          'message' => 'Invalid user type'
        ], 401);
      }

      $token = $user->createToken('API Token')->plainTextToken;

      // Retornamos el token en la respuesta
      return response()->json([
        'token' => $token,
        'message' => 'Login successful'
      ]);
    }

    // Si las credenciales no son válidas
    return response()->json([
      'message' => 'Invalid credentials'
    ], 401);
  }

}
