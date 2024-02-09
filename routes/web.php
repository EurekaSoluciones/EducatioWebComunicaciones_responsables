<?php


use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\EureAuthController;
use App\Http\Controllers\ComunicacionController;
use App\Http\Controllers\CuentaCorrienteController;
use App\Http\Controllers\DummyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ResponsableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
})->middleware('auth')->name('home');;

// RutaS de autenticación
Route::get('/login', [EureAuthController::class,'login'])->name('login');
Route::get('/logout', [EureAuthController::class,'logout'])->name('logout');
Route::post('/logout', [EureAuthController::class,'logout'])->name('logout');


Route::post('/authenticate', [EureAuthController::class, 'authenticate'])->name('authenticate');

Route::group(['middleware' => 'auth'], function () {

  Route::get('/', [HomeController::class,'index'])->name('home');
  Route::get('/home', [HomeController::class,'index']);

  Route::get('/password', [EureAuthController::class,'password'])->name('auth.password');
  Route::post('/usuarios/password/update', [EureAuthController::class, 'passwordUpdate'])->name('auth.password.update');



  Route::get('notifications/show', [NotificationController::class, 'show'])->name('notificationes.show');
  Route::get('notifications/get', [NotificationController::class, 'get'])->name('notificationes.get');


  Route::get('/resp/logged', [ResponsableController::class, 'showLogged'])->name('responsables.showLogged');
  Route::get('/resp/{responsable}', [ResponsableController::class, 'show'])->name('responsables.show');
  Route::get('/resp/{responsable}/edit', [ResponsableController::class, 'edit'])->name('responsables.edit');
  Route::patch('/resp/{user}/edit', [ResponsableController::class, 'update'])->name('responsables.update');

  Route::get('/alumno/{alumno}', [AlumnoController::class, 'show'])->name('alumnos.show')->middleware('auth');
  Route::get('/alumno/{alumno}/editPic', [AlumnoController::class, 'editPic'])->name('alumnos.editPic');
  Route::patch('/alumno/{alumno}/editPic', [AlumnoController::class, 'updatePic'])->name('alumnos.updatePic');

  Route::post('/upload-image', [AdjuntoController::class, 'fileStore']);

  Route::get('/comunicaciones/{comunicacion}/alumno/{alumno}', [ComunicacionController::class, 'show'])->name('comunicaciones.show');
  Route::get('/comunicaciones/{alumno}', [ComunicacionController::class, 'indexA'])->name('comunicaciones.indexA');
  Route::post('/comunicaciones/{alumno}/filtrado', [ComunicacionController::class, 'indexAFiltered'])->name('comunicaciones.indexAFiltered');

  Route::get('/pagos/{alumno}', [CuentaCorrienteController::class, 'pagosA'])->name('pagos.indexA');
  Route::get('/pagos/{cod_recibo}/descargar', [CuentaCorrienteController::class, 'descargarPago'])->name('pagos.descargar');

  Route::get('/cc/{alumno}', [CuentaCorrienteController::class, 'indexA'])->name('cc.indexA');

  Route::get('/notas/{alumno}', [NotaController::class, 'indexA'])->name('notas.indexA');

  Route::get('/informes/{alumno}', [InformeController::class, 'indexA'])->name('informes.indexA');

  Route::get('/asistencias/{alumno}', [AsistenciaController::class, 'indexA'])->name('asistencias.indexA');

});

Route::get('/dummy', [DummyController::class, 'index'])->middleware('auth');
Route::get('/dummy2', [DummyController::class, 'index2']);
Route::get('/dummy3', [DummyController::class, 'index3'])->name('dummy3');
Route::get('/dummy4', [DummyController::class, 'dropzone'])->name('dummy4');
Route::get('/dummy5', [DummyController::class, 'index5'])->name('dummy5');
Route::get('/dummy/{any}', [DummyController::class, 'show', 'any']);

//Route::get('/{any}', [\App\Http\Controllers\AdminGeneralController::class, 'show', 'any']);

