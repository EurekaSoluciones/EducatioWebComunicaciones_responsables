<?php

namespace App\Http\Controllers\API;

use App\EureLib\EducatioCommFunctions;
use App\Http\Controllers\Controller;
use App\Models\ComunicacionDestinatario;
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

    $user->avatarImg= $user->avatar_image_withDefaults4API();
    $user->bgImg= $user->background_image();

    $alumnos = $responsable->alumnos;

    foreach ($alumnos as $alumno)
    {
      $comunicaciones = $alumno->comunicaciones;
      $grupo = $alumno->grupo;
      $web= $alumno->web;
//      $web->avatar_img= $alumno->avatar_image_withDefaults4API();
      $web->avatar_img= $alumno->avatar_image_withDefaults();

      if ($alumno->Responsable1 != null)
      {
        $R1 = $alumno->EResponsable1()->first();
        $R1->avatar_img= $R1->webuser->avatar_image_withDefaults4API();
        $R1->web_user = $R1->webuser;
        $alumno->Responsable1 = $R1;

        $alumno->Tipo_Responsable1 = $alumno->ETipoResponsable1()->first();
      }

      if ($alumno->Responsable2 != null)
      {
        $R2= $alumno->EResponsable2()->first();
        $R2->avatar_img= $R2->webuser->avatar_image_withDefaults4API();
        $R2->web_user = $R2->webuser;
        $alumno->Responsable2 = $R2;

        $alumno->Tipo_Responsable2 = $alumno->ETipoResponsable2()->first();
      }

//      dd($alumno->Responsable11);

      $eCurso= $grupo->ECurso;
      $eDivision= $grupo->EDivision;
      $eTurno= $grupo->ETurno;
      $ePlan= $grupo->EPlan;

      foreach ($comunicaciones as $comunicacion)
      {
        $dummy_comunicacion_remitente= $comunicacion->remitente;
        $comunicacion->remitente->avatar_img= $comunicacion->remitente->avatar_image_withDefaults4API();

        $comunicacion_destinatario=
          ComunicacionDestinatario
            ::where('comunicacion_id', $comunicacion->id)
            ->where('Cod_Alumno', $alumno->id)
            ->where('Cod_Responsable', $responsable->id)
            ->first();

        $comunicacion->leido= (bool) $comunicacion_destinatario->leido;
        $comunicacion->leidoInfo= $comunicacion_destinatario;

        $comunicacion->leidoInfo->fhLeido4Humans = $comunicacion->leidoInfo->fhLeido != null
          ? $comunicacion->leidoInfo->fhLeido->diffForHumans()
          : null;

        $comunicacion->leidoInfo->fhRespuesta4Humans = $comunicacion->leidoInfo->fhRespuesta
          ? $comunicacion->leidoInfo->fhRespuesta->diffForHumans()
          : null;

        $comunicacion->leidoInfo->fhReaccion4Humans = $comunicacion->leidoInfo->fhReaccion
          ? $comunicacion->leidoInfo->fhReaccion->diffForHumans()
          : null;




        $dummy_comunicacion_tipo= $comunicacion->tipo;
        $dummy_comunicacion_tipo_respuesta= $comunicacion->tipo_respuesta;
        $dummy_comunicacion_adjuntos= $comunicacion->adjuntos;

      }

      $alumno->cc= EducatioCommFunctions::CC_Obtener($alumno, $venceEsteMes, $venceHoy, $deudaVencida, $proximoVencimiento);

      $alumno->pagos= EducatioCommFunctions::Pagos_Obtener($alumno, null, null);

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
