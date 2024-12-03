<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class APIController extends Controller
{
  public function info_responsable(Request $request)
  {
    $user = auth('sanctum')->user();

    // Un santy
    if ($user == null) {
      return response()->json(['message' => 'Usuario no encontrado'], 401);
    }

    // Estas variables no se usar después. Es para levantar las lazys. Si no, no se devuelen
    $responsable = $user->responsable;

    $user->avatarImg= $user->avatar_image_withDefaults();
    $user->bgImg= $user->background_image();

    $alumnos = $responsable->alumnos;

    foreach ($alumnos as $alumno)
    {
      $comunicaciones = $alumno->comunicaciones;
      $grupo = $alumno->grupo;
      $web= $alumno->web;
      $web->avatar_img= $alumno->avatar_image_withDefaults();

      $eCurso= $grupo->ECurso;
      $eDivision= $grupo->EDivision;
      $eTurno= $grupo->ETurno;
      $ePlan= $grupo->EPlan;

      foreach ($comunicaciones as $comunicacion)
      {
        $dummy_comunicacion_remitente= $comunicacion->remitente;
        $comunicacion->remitente->avatar_img= $comunicacion->remitente->avatar_image_withDefaults();

        $dummy_comunicacion_tipo= $comunicacion->tipo;
        $dummy_comunicacion_tipo_respuesta= $comunicacion->tipo_respuesta;
        $dummy_comunicacion_adjuntos= $comunicacion->adjuntos;

      }

    }


    $infoResponsable=
      [
        'user' => $user,
//        'grupo' => $grupo,
        'responsable' => $responsable,
        'alumnos' => $responsable->alumnos,

      ];




    // Primero los estudiantes
//    return response()->json([
//      'message' => 'OK',
//      'info' => $infoResponsable,
//    ], 200);



    return response()->json([
      'message' => 'OK',
      'info' => $user,
  //    'grupo' => $grupo,
    ], 200);



  }
}
