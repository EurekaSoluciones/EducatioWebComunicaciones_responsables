<?php

namespace App\Http\Controllers\Auth;

use App\EureLib\EureFunctions;
use App\Http\Controllers\Controller;
use App\Models\ExpoToken;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EureAuthController extends Controller
{
  public function login(Request $request, EurekaTheme $themes)
  {
    if ($request->user()) {
      return redirect()->route('home');
    }

    $theme = $themes->current();
    $client = $theme['clientId'];
    $logo = "assets/C/{$client}/institucional/LoginLogo.png";
    $background = match ($client) {
      'ifesnqn', 'ifesplottier' => "assets/C/{$client}/institucional/LoginCarrusel03.jpg",
      'culturalnqn', 'culturalcentenario' => 'assets/images/fondoLoginCultural.png',
      default => "assets/C/{$client}/institucional/LoginBackground.jpeg",
    };

    return Inertia::render('auth/Login', [
      'theme' => $theme,
      'institution' => config('eureka.institution'),
      'logoUrl' => is_file(public_path($logo)) ? asset($logo) : null,
      'backgroundUrl' => is_file(public_path($background)) ? asset($background) : null,
      'authenticateUrl' => route('authenticate'),
      'csrfToken' => csrf_token(),
      'oldLogin' => $request->old('login', ''),
    ]);
  }

  public function logout(Request $request)
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
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
      'credentials' => 'El nombre de Usuario o contraseña es incorrecto.',
    ])->withInput($request->only('login'));
  }

  public function passwordUpdate(Request $request, FamilyPortal $portal)
  {
    $responsable = $portal->responsable($request->user());
    // Keep the web password policy; never flash passwords back into the session.
    $validator = Validator::make($request->all(), [
      'currentPassword' => 'required|string',
      'newPassword' => 'required|string',
      'confirmPassword' => 'required|string|same:newPassword',
    ], [
      'currentPassword.required' => 'Ingresá tu contraseña actual.',
      'newPassword.required' => 'Ingresá la nueva contraseña.',
      'confirmPassword.required' => 'Repetí la nueva contraseña.',
      'confirmPassword.same' => 'Las contraseñas nuevas no coinciden.',
      '*.string' => 'Ingresá una contraseña válida.',
    ]);

    // Si la validación falla, redirige de nuevo al formulario con los errores
    if ($validator->fails()) {
      return redirect()->route('auth.password')->withErrors($validator);
    }

    // Validar la contraseña actual
    if (!Hash::check($request->currentPassword, auth()->user()->password)) {
      return redirect()->route('auth.password')->withErrors(['currentPassword' => 'La contraseña actual no es correcta.']);
    }

    // Si la validación es exitosa, actualiza la contraseña en la base de datos
    $user = auth()->user();
    $user->password = Hash::make($request->newPassword);
    $user->save();

    Auth::logoutOtherDevices($request->newPassword);

    // Redirige a la página de éxito o donde sea necesario
    return redirect()->route('responsables.show', $responsable)->with('success', '¡Contraseña cambiada con éxito!');
  }

  public function api_login(Request $request)
  {
    // Validamos las credenciales del usuario
    $credentials = $request->validate([
      'login' => 'required',       // Se asume que 'login' es el campo de nombre de usuario
      'password' => 'required',
    ]);

    // Intentamos autenticar al usuario
    if (Auth::attempt(['login' => $request->login, 'password' => $request->password])) {
      // Si la autenticación es exitosa, obtenemos al usuario y generamos un token
      $user = Auth::user();

      if ($user->tipo != 'Responsable') {
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

  public function api_logout(Request $request)
  {
    $user = $request->user();
    $expoPushToken = $request->input('expoPushToken');
    $expoTokensEliminados = 0;

    if ($expoPushToken != null && $expoPushToken !== '') {
      $expoTokensEliminados =
        ExpoToken
          ::where('user_id', $user->id)
          ->where('expo_push_token', $expoPushToken)
          ->delete();
    }

    $currentToken = $user->currentAccessToken();

    if ($currentToken != null) {
      $currentToken->delete();
    }

    return response()->json([
      'message' => 'Logout successful',
      'expoTokensEliminados' => $expoTokensEliminados,
    ]);
  }

  public function api_passwordUpdate(Request $request)
  {
    $user = $request->user();

    // Validaciones
    $request->validate([
      'current_password' => ['required'],
      'new_password' => ['required', 'min:6', 'confirmed'],
    ]);

    // Verificar si la contraseña actual es correcta
    if (!Hash::check($request->current_password, $user->password)) {
      throw ValidationException::withMessages([
        'current_password' => ['La contraseña actual es incorrecta.'],
      ]);
    }

    // Actualizar la contraseña
    $user->password = Hash::make($request->new_password);
    $user->save();

    $currentToken = $user->currentAccessToken();

    if ($currentToken != null) {
      $user->tokens()->where('id', '!=', $currentToken->id)->delete();
    } else {
      Auth::guard('web')->logoutOtherDevices($request->new_password);
    }

    return response()->json(['message' => 'Contraseña actualizada correctamente'], 200);
  }

  //    return back()->withErrors([
  //      'credentials' => 'No nos coincide el usuario con la contraseña.',
  //    ])->onlyInput('email');

}
