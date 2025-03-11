<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\CarteleraController;
use App\Http\Controllers\Auth\EureAuthController;
use App\Http\Controllers\ComunicacionController;
use App\Http\Controllers\ComunicacionEController;
use App\Http\Controllers\CuentaCorrienteController;
use App\Http\Controllers\ExpoNotificationController;
use App\Http\Controllers\ResponsableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/hello-world', function () {
  return response()->json(['message' => 'Hello World' . env('EURE_CLIENTE_ID') . 'asdsad']);
});


Route::post('login', [EureAuthController::class, 'api_login']);

Route::group(['middleware' => 'auth:sanctum'], function ()
{
  Route::get('/hello-world-auth', function () {
    return response()->json(['message' => 'Hello World!']);
  });

  Route::get('/auth-check', [APIController::class, 'auth_check']);
  Route::post('/info-responsable', [APIController::class, 'info_responsable']);


  Route::get('/carteleras', [CarteleraController::class, 'carteleras']);

  Route::post('/hello-world-auth-post', [APIController::class, 'hello_world_auth_post']);

  Route::post('/comunicaciones/responder',  [ComunicacionController::class, 'api_responderComunicacion']);
  Route::post('/comunicaciones/e/marcar-respuesta-leida',  [ComunicacionEController::class, 'api_marcarRespuestaLeida']);
  Route::post('/comunicaciones/e/store',  [ComunicacionEController::class, 'api_store']);

  Route::get('/pagos/{cod_recibo}',  [CuentaCorrienteController::class, 'api_descargarPago']);

  Route::post('/passw', [EureAuthController::class, 'apipasswordUpdate']);

  Route::post('/password-update',  [EureAuthController::class, 'api_passwordUpdate']);

  Route::post('/responsable-update',  [ResponsableController::class, 'api_update']);
  Route::post('/alumno-update-foto',  [AlumnoController::class, 'api_update_foto']);
  Route::post('/alumno-update-foto-remover',  [AlumnoController::class, 'api_update_foto_remover']);

  Route::get('/expo-notificaciones/marcar-mostradas',  [ExpoNotificationController::class, 'api_marcarMostrado']);
});

Route::get('/al',  [\App\Http\Controllers\DummyController::class, 'al']);
