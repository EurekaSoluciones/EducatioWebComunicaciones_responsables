<?php


use App\Http\Controllers\AdjuntoController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\EureAuthController;
use App\Http\Controllers\CarteleraController;
use App\Http\Controllers\ComunicacionController;
use App\Http\Controllers\ComunicacionEController;
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

Route::get('/hello-world', [DummyController::class,'hello_world']);



// RutaS de autenticación
Route::get('/login', [EureAuthController::class,'login'])->name('login');
Route::get('/logout', [EureAuthController::class,'logout'])->name('logout');
Route::post('/logout', [EureAuthController::class,'logout'])->name('logout');


Route::post('/authenticate', [EureAuthController::class, 'authenticate'])->name('authenticate');

Route::group(['middleware' => 'auth'], function () {

  Route::get('/', [HomeController::class,'index']);
  Route::get('/home', [HomeController::class,'index'])->name('home');

  Route::get('/password', [EureAuthController::class,'password'])->name('auth.password');
  Route::post('/usuarios/password/update', [EureAuthController::class, 'passwordUpdate'])->name('auth.password.update');

  Route::get('notifications/show', [NotificationController::class, 'show'])->name('notificationes.show');
  Route::get('notifications/get', [NotificationController::class, 'get'])->name('notificationes.get');


  Route::get('/resp/logged', [ResponsableController::class, 'showLogged'])->name('responsables.showLogged');
  Route::get('/resp/{responsable}', [ResponsableController::class, 'show'])->name('responsables.show');
  Route::get('/resp/{responsable}/edit', [ResponsableController::class, 'edit'])->name('responsables.edit');
  Route::post('/resp/{user}/edit', [ResponsableController::class, 'update'])->name('responsables.update');

  Route::get('/alumno/{alumno}', [AlumnoController::class, 'show'])->name('alumnos.show')->middleware('auth');
  Route::get('/alumno/{alumno}/editPic', [AlumnoController::class, 'editPic'])->name('alumnos.editPic');
  Route::patch('/alumno/{alumno}/editPic', [AlumnoController::class, 'updatePic'])->name('alumnos.updatePic');

  Route::post('/upload-image', [AdjuntoController::class, 'fileStore']);

  Route::get('/comunicaciones/{comunicacion}/alumno/{alumno}', [ComunicacionController::class, 'show'])->name('comunicaciones.show');
  Route::get('/comunicaciones/{alumno}', [ComunicacionController::class, 'indexA'])->name('comunicaciones.indexA');
  Route::post('/comunicaciones/{alumno}/filtrado', [ComunicacionController::class, 'indexAFiltered'])->name('comunicaciones.indexAFiltered');
  Route::post('comunicaciones/{comunicaciondestinatario}/respuestalibre', [ComunicacionController::class, 'storeRespuestaLibre'])->name('comunicaciones.respuestas.libres.store');
  Route::post('comunicaciones/{comunicaciondestinatario}/respuestafija', [ComunicacionController::class, 'storeRespuestaFija'])->name('comunicaciones.respuestas.fijas.store');

  Route::get('/comunicaciones/e/{alumno}', [ComunicacionEController::class, 'indexA'])->name('comunicaciones.e.indexA');
  Route::get('/comunicaciones/e/show/{comunicacione}', [ComunicacionEController::class, 'show'])->name('comunicaciones.e.show');
  Route::get('/comunicaciones/e/{alumno}/create', [ComunicacionEController::class, 'createA'])->name('comunicaciones.e.create');
  Route::post('/comunicaciones/e/{alumno}/store', [ComunicacionEController::class, 'store'])->name('comunicaciones.e.store');

  Route::post('/uploads/comunicaciones/e/imagenes', [AdjuntoController::class, 'storeImagenComunicacione']);
  Route::post('/uploads/comunicaciones/e/adjuntos', [AdjuntoController::class, 'storeAdjuntoComunicacione'])->name('uploads.comunicaciones.e.adjuntos.store');
  Route::post('/uploads/adjuntos/e/delete/', [AdjuntoController::class, 'destroyAdjunto'])->name('uploads.adjuntos.delete');

  Route::get('/pagos/{alumno}', [CuentaCorrienteController::class, 'pagosA'])->name('pagos.indexA');
  Route::get('/pagos/{cod_recibo}/descargar', [CuentaCorrienteController::class, 'descargarPago'])->name('pagos.descargar');

  Route::get('/cc/{alumno}', [CuentaCorrienteController::class, 'indexA'])->name('cc.indexA');
  Route::post('/cc/pagar', [CuentaCorrienteController::class, 'pagar'])->name('cc.pagar');

 // Notas lo sacamos
 // Route::get('/notas/{alumno}', [NotaController::class, 'indexA'])->name('notas.indexA');

  Route::get('/informes/{alumno}', [InformeController::class, 'indexA'])->name('informes.indexA');
  Route::get('/informes/{alumno}/duco', [InformeController::class, 'descargarDUCO'])->name('informes.descargarDUCO');
  Route::get('/informes/{alumno}/informeConceptual', [InformeController::class, 'informeConceptual'])->name('informes.conceptual.descargar');
  Route::get('/informes/{alumno}/boletin', [InformeController::class, 'descargarBoletin'])->name('informes.descargarBoletin');


  Route::get('/carteleras/GENERAL', [CarteleraController::class, 'show_cartelera_general_st'])->name('carteleras.general.show');
  Route::get('/carteleras/{cartelera}', [CarteleraController::class, 'show'])->name('carteleras.show');


  Route::get('/asistencias/{alumno}', [AsistenciaController::class, 'indexA'])->name('asistencias.indexA');


});



Route::get('/comunicaciones/app/{comunicacion}/{responsable}/{token}', [ComunicacionController::class, 'appshow']);
Route::get('/comunicaciones/e/app/{comunicacionE}/{responsable}/{token}', [ComunicacionEController::class, 'appshow']);
//Route::get('/pagos/app/{cod_recibo}/{token}', [CuentaCorrienteController::class, 'appDescargarPago']);

Route::get('/dummy', [DummyController::class, 'index'])->middleware('auth');
Route::get('/dummy2', [DummyController::class, 'index2']);
Route::get('/dummy3', [DummyController::class, 'index3'])->name('dummy3');
Route::get('/dummy4', [DummyController::class, 'dropzone'])->name('dummy4');
Route::get('/dummy5', [DummyController::class, 'index5'])->name('dummy5');
Route::get('/dummy/al',  [DummyController::class, 'al']);
Route::get('/dummy/{any}', [DummyController::class, 'show', 'any']);



//Route::get('/{any}', [\App\Http\Controllers\AdminGeneralController::class, 'show', 'any']);

