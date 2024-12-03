<?php

use App\Http\Controllers\API\APIController;
use App\Http\Controllers\API\CarteleraController;
use App\Http\Controllers\API\EureAuthController;
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
  return response()->json(['message' => 'Hello World']);
});


Route::post('login', [EureAuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function ()
{
  Route::get('/hello-world-auth', function () {
    return response()->json(['message' => 'Hello World!']);
  });

  Route::get('/info-responsable', [APIController::class, 'info_responsable']);

  Route::get('/carteleras', [CarteleraController::class, 'carteleras']);

});
