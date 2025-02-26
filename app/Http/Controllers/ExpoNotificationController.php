<?php

namespace App\Http\Controllers;

use App\Models\NotificacionPush;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpoNotificationController extends Controller
{
    //

  public function api_marcarMostrado()
  {
    $user = auth('sanctum')->user();

    if ($user == null)
    {
      return response()->json(['message' => 'Usuario no encontrado'], 401);
    }

    NotificacionPush::DeResponsable($user->responsable)
      ->SinMostrar()
      ->update(['fhMostrado' => Carbon::now()]);

    return response()->json(['message' => 'Notificaciones marcadas como mostradas']);
  }
}
