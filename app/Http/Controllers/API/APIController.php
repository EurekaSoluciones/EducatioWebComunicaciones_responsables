<?php

namespace App\Http\Controllers\API;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Http\Controllers\Controller;
use App\Models\Cartelera;
use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\ComunicacionE;
use App\Models\ExpoToken;
use App\Models\NotificacionPush;
use App\Models\UsuarioGrupo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Nette\Utils\Random;

class APIController extends Controller
{
  public function info_responsable(Request $request)
  {
//$all= $request->all();
//$ex= $request->expoPushToken;

    $user = auth('sanctum')->user();

    // Un santy
    if ($user == null)
    {
      return response()->json(['message' => 'Usuario no encontrado'], 401);
    }

    // Estas variables no se usar después. Es para levantar las lazys. Si no, no se devuelen
    $responsable = $user->responsable;

    $user->avatarImg = $user->avatar_image_withDefaults4API();
    $user->bgImg = $user->background_image();

    $alumnos = $responsable->alumnos;

    foreach ($alumnos as $alumno)
    {
      $comunicaciones = Comunicacion::Alumno($alumno)->ParaResponsable($responsable)->orderBy('id', 'desc')->get();
      $alumno->comunicaciones = $comunicaciones;

      $comunicacionesE= ComunicacionE::DeAlumno($alumno)->DeResponsable($responsable)->orderBy('id', 'desc')->get();
      $alumno->comunicacionesE= $comunicacionesE;

      $grupo = $alumno->grupo;
      $web = $alumno->web;
//      $web->avatar_img= $alumno->avatar_image_withDefaults4API();
      $web->avatar_img = $alumno->avatar_image_withDefaults();

      if ($alumno->Responsable1 != null)
      {
        $R1 = $alumno->EResponsable1()->first();
        $R1->avatar_img = $R1->webuser->avatar_image_withDefaults4API();
        $R1->web_user = $R1->webuser;
        $alumno->Responsable1 = $R1;

        $alumno->Tipo_Responsable1 = $alumno->ETipoResponsable1()->first();
      }

      if ($alumno->Responsable2 != null)
      {
        $R2 = $alumno->EResponsable2()->first();
        $R2->avatar_img = $R2->webuser->avatar_image_withDefaults4API();
        $R2->web_user = $R2->webuser;
        $alumno->Responsable2 = $R2;

        $alumno->Tipo_Responsable2 = $alumno->ETipoResponsable2()->first();
      }

//      dd($alumno->Responsable11);

      $eCurso = $grupo->ECurso;
      $eDivision = $grupo->EDivision;
      $eTurno = $grupo->ETurno;
      $ePlan = $grupo->EPlan;

      foreach ($comunicaciones as $comunicacion)
      {
        $dummy_comunicacion_remitente = $comunicacion->remitente;
        $comunicacion->remitente->avatar_img = $comunicacion->remitente->avatar_image_withDefaults4API();

        $comunicacion_destinatario =
          ComunicacionDestinatario
            ::where('comunicacion_id', $comunicacion->id)
            ->where('Cod_Alumno', $alumno->id)
            ->where('Cod_Responsable', $responsable->id)
            ->first();

        $comunicacion->leido = (bool)$comunicacion_destinatario->leido;
        $comunicacion->leidoInfo = $comunicacion_destinatario;

        $comunicacion->leidoInfo->fhLeido4Humans = $comunicacion->leidoInfo->fhLeido != null
          ? $comunicacion->leidoInfo->fhLeido->diffForHumans()
          : null;

        $comunicacion->leidoInfo->fhRespuesta4Humans = $comunicacion->leidoInfo->fhRespuesta
          ? $comunicacion->leidoInfo->fhRespuesta->diffForHumans()
          : null;

        $comunicacion->leidoInfo->fhReaccion4Humans = $comunicacion->leidoInfo->fhReaccion
          ? $comunicacion->leidoInfo->fhReaccion->diffForHumans()
          : null;

        $dummy_comunicacion_respuesta_adjuntos = $comunicacion->leidoInfo->adjuntos;
        $comunicacion->leidoInfo->adjuntos->each(function ($adjunto) {
          $adjunto->url = url("/storage/$adjunto->filename");
          $adjunto->filetypeicon = url(EureFunctions::getIconByFileType($adjunto->filename));
        });

        // Esto es para forzar a que lo cargue
        $dummy_comunicacion_tipo = $comunicacion->tipo;
        $dummy_comunicacion_tipo_respuesta = $comunicacion->tipo_respuesta;

        $dummy_comunicacion_adjuntos = $comunicacion->adjuntos;

        $comunicacion->adjuntos->each(function ($adjunto) {
          $adjunto->url = url("/storage/$adjunto->filename");
          $adjunto->filetypeicon = url(EureFunctions::getIconByFileType($adjunto->filename));
        });

        $comunicacion->msg = ''; // no lo mando mas

      }

      foreach ($comunicacionesE as $comunicacionE)
      {
        $comunicacionE->msg= "";
        $comunicacionE->hello= "world";
        $comunicacionE->destinatario= $comunicacionE->destinatario_web_user;
        $comunicacionE->destinatario->avatar_img = $comunicacionE->destinatario->avatar_image_withDefaults4API();
        $dummy_comunicacionE_adjuntos = $comunicacionE->adjuntos;
        $comunicacionE->adjuntos->each(function ($adjunto) {
          $adjunto->url = url("/storage/$adjunto->filename");
          $adjunto->filetypeicon = url(EureFunctions::getIconByFileType($adjunto->filename));
        });
//        $comunicacionE->remitente->web_user = $comunicacionE->remitente->webuser;
      }



      $alumno->cc = EducatioCommFunctions::CC_Obtener($alumno, $venceEsteMes, $venceHoy, $deudaVencida, $proximoVencimiento);

      $alumno->pagos = EducatioCommFunctions::Pagos_Obtener($alumno, null, null);

      $alumno->inasistencias = EducatioCommFunctions::Inasistencias_Obtener($alumno);

      $alumno->documentos= EducatioCommFunctions::Documentos_Obtener($alumno);

      $alumno->comunicacionesE_destinatarios= UsuarioGrupo::DeGrupo($alumno->grupo)->get();

      foreach ($alumno->comunicacionesE_destinatarios as $dest)
      {
        $dest->usuario = $dest->usuario;

        if ($dest->usuario != null) {
          $dest->usuario->avatar_img = $dest->usuario->avatar_image_withDefaults4API();
        }
      }

      //$alumno->Nombre= rand(1,1000);

      //dd($alumno->inasistencias);
    }

    // Fin de alumnos
    // Notificaciones.
    $notificacionesSinMostrar =
      NotificacionPush
        ::DeResponsable($responsable)
        ->SinMostrar()
        ->NoDescartado()
        ->Ultimos60Dias()
        ->get();

    $notificacionesMostradas=
      NotificacionPush
        ::DeResponsable($responsable)
        ->Mostrados()
        ->NoDescartado()
        ->Ultimos30Dias()
        ->get();

    $notificaciones= $notificacionesSinMostrar->merge($notificacionesMostradas);

    $notificaciones= $notificaciones->sortByDesc('created_at');

    $user->hmNotificacionesSinMostrar = $notificacionesSinMostrar->count();
    $user->notificaciones = $notificaciones->values()->toArray();
    $user->carteleras = Cartelera::where('activa', true)
      ->orderBy('nombre')
      ->get(['id', 'nombre']);

    // Bueno, tengo que guardar el token en la tabla de tokenss
    $EXP= $request->expoPushToken;

    if ($EXP != null)
    {
      $pushLogContext = $this->buildPushTokenLogContext($request, $user, $EXP);

      $expoTokenExistente =
        ExpoToken
          ::where('user_id', $user->id)
          ->where('expo_push_token', $EXP)
          ->exists();


      if (!$expoTokenExistente)
      {
        // Podria pasar otra cosa. que el token exista pero con otro usuario. Esto pasaria muy poco. dale copilot
        $tokensPrevios = ExpoToken::where('expo_push_token', $EXP)->get(['id', 'user_id']);

        if ($tokensPrevios->isNotEmpty()) {
          Log::channel('push')->warning('Reasignando Expo push token existente', $pushLogContext + [
            'token_record_ids_previos' => $tokensPrevios->pluck('id')->values()->all(),
            'user_ids_previos' => $tokensPrevios->pluck('user_id')->values()->all(),
          ]);
        }

        ExpoToken::where('expo_push_token', $EXP)->delete();

        ExpoToken::create([
          'user_id' => $user->id,
          'expo_push_token' => $EXP,
        ]);

        Log::channel('push')->info('Expo push token registrado', $pushLogContext + [
          'accion' => 'created',
          'tokens_previos_eliminados' => $tokensPrevios->count(),
        ]);
      } else {
        Log::channel('push')->info('Expo push token ya registrado para el usuario', $pushLogContext + [
          'accion' => 'existing',
        ]);
      }
    } else {
      Log::channel('push')->info('info_responsable sin expoPushToken', [
        'cliente_id' => EureFunctions::cliente_id(),
        'request_host' => $request->getHost(),
        'user_id' => $user->id,
        'responsable_id' => $responsable->id ?? null,
      ]);
    }


    $infoResponsable =
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
      'infoFH' => Carbon::now(),
      //    'grupo' => $grupo,
    ], 200);


  }

  public function hello_world_auth_post()
  {
    return "DUDE";
  }

  public function auth_check()
  {
    return response()->json(['message' => 'Token OK'], 200);
  }

  private function buildPushTokenLogContext(Request $request, $user, string $expoPushToken): array
  {
    return [
      'cliente_id' => EureFunctions::cliente_id(),
      'request_host' => $request->getHost(),
      'user_id' => $user->id,
      'responsable_id' => $user->responsable->id ?? null,
      'expo_push_token_masked' => $this->maskExpoPushToken($expoPushToken),
      'expo_push_token_hash' => hash('sha256', $expoPushToken),
    ];
  }

  private function maskExpoPushToken(?string $expoPushToken): ?string
  {
    if ($expoPushToken == null || $expoPushToken === '') {
      return $expoPushToken;
    }

    $length = strlen($expoPushToken);

    if ($length <= 12) {
      return $expoPushToken;
    }

    return substr($expoPushToken, 0, 8).'...'.substr($expoPushToken, -4);
  }
}

