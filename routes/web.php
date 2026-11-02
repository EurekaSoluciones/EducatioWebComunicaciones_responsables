<?php


use App\Http\Controllers\AdjuntoController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AvatarController;
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
use Carbon\CarbonImmutable;
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

Route::get('/hello-world', [DummyController::class, 'hello_world']);

Route::get('/avatar/initials', [AvatarController::class, 'initials'])->name('avatar.initials');

Route::get('/pi', function () {
    $deadline = CarbonImmutable::create(2026, 4, 27, 14, 0, 0, 'America/Argentina/Buenos_Aires');

    if (now('America/Argentina/Buenos_Aires')->greaterThanOrEqualTo($deadline)) {
        abort(404);
    }

    ob_start();
    phpinfo();

    return response(ob_get_clean(), 200)
        ->header('Content-Type', 'text/html; charset=UTF-8');
});



// RutaS de autenticación
Route::get('/login', [EureAuthController::class, 'login'])
    ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('login');
Route::get('/logout', [EureAuthController::class, 'logout'])->name('logout');
Route::post('/logout', [EureAuthController::class, 'logout'])->name('logout');


Route::post('/authenticate', [EureAuthController::class, 'authenticate'])->name('authenticate');

Route::get('/demo/eureka', \App\Http\Controllers\EurekaDemoController::class)
    ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)
    ->name('demo.eureka');

Route::get('/demo/eureka/alternativa', [\App\Http\Controllers\EurekaDemoController::class, 'alternative'])
    ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)
    ->name('demo.eureka.alternative');

Route::group(['middleware' => ['auth', 'auth.session']], function () {

    Route::get('/', [HomeController::class, 'index'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::get('/home', [HomeController::class, 'index'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('home');
    Route::get('/familia/notificaciones', \App\Http\Controllers\FamilyNotificationController::class)
        ->name('family.notifications');
    Route::get('/familia/comunicaciones/{alumno}/estados/{kind}', \App\Http\Controllers\FamilyCommunicationStatusController::class)
        ->whereIn('kind', ['received', 'sent'])->name('family.communications.status');

    Route::get('/password', [\App\Http\Controllers\FamilyResponsibleController::class, 'password'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('auth.password');
    Route::post('/usuarios/password/update', [EureAuthController::class, 'passwordUpdate'])->name('auth.password.update');

    Route::get('notifications/show', [NotificationController::class, 'show'])->name('notificationes.show');
    Route::get('notifications/get', [NotificationController::class, 'get'])->name('notificationes.get');


    Route::get('/resp/logged', [\App\Http\Controllers\FamilyResponsibleController::class, 'showLogged'])->name('responsables.showLogged');
    Route::get('/resp/{responsable}', [\App\Http\Controllers\FamilyResponsibleController::class, 'show'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('responsables.show');
    Route::get('/resp/{responsable}/edit', [\App\Http\Controllers\FamilyResponsibleController::class, 'edit'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('responsables.edit');
    Route::post('/resp/{user}/edit', [\App\Http\Controllers\FamilyResponsibleController::class, 'update'])->name('responsables.update');

    Route::get('/alumno/{alumno}', \App\Http\Controllers\StudentProfileController::class)
        ->name('alumnos.show')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::get('/alumno/{alumno}/editPic', [\App\Http\Controllers\StudentPhotoController::class, 'edit'])
        ->name('alumnos.editPic')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::patch('/alumno/{alumno}/editPic', [\App\Http\Controllers\StudentPhotoController::class, 'update'])->name('alumnos.updatePic');
    Route::get('/alumnos/{alumno}/adjunto/create', [\App\Http\Controllers\FamilyDocumentsController::class, 'create'])
        ->name('alumnos.adjunto.create')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::post('/alumnos/{alumno}/adjunto/store', [\App\Http\Controllers\FamilyDocumentsController::class, 'store'])->name('alumnos.adjunto.store');
    Route::get('/alumno/{alumno}/rematriculacion', [AlumnoController::class, 'rematriculacion'])->name('alumnos.rematriculacion')->middleware('auth');
    Route::post('/alumno/{alumno}/rematriculacion', [AlumnoController::class, 'guardarRematriculacion'])->name('alumnos.rematriculacion.guardar')->middleware('auth');

    Route::post('/upload-image', [AdjuntoController::class, 'fileStore']);

    Route::get('/comunicaciones/{comunicacion}/alumno/{alumno}', [\App\Http\Controllers\FamilyReceivedDetailController::class, 'show'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.show');
    Route::get('/comunicaciones/{alumno}', [\App\Http\Controllers\FamilyReceivedController::class, 'index'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.indexA');
    Route::post('/comunicaciones/{alumno}/filtrado', [\App\Http\Controllers\FamilyReceivedController::class, 'legacyFilters'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.indexAFiltered');
    Route::post('comunicaciones/{comunicaciondestinatario}/respuestalibre', [\App\Http\Controllers\FamilyReceivedDetailController::class, 'free'])->name('comunicaciones.respuestas.libres.store');
    Route::post('comunicaciones/{comunicaciondestinatario}/respuestafija', [\App\Http\Controllers\FamilyReceivedDetailController::class, 'fixed'])->name('comunicaciones.respuestas.fijas.store');
    Route::post('comunicaciones/{comunicaciondestinatario}/respuesta/adjuntos', [\App\Http\Controllers\FamilyReceivedDetailController::class, 'upload'])->name('family.received.reply.upload');
    Route::delete('comunicaciones/{comunicaciondestinatario}/respuesta/adjuntos/{adjunto}', [\App\Http\Controllers\FamilyReceivedDetailController::class, 'removeUpload'])->name('family.received.reply.upload.remove');

    Route::get('/comunicaciones/e/{alumno}', [\App\Http\Controllers\FamilySentController::class, 'index'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.e.indexA');
    Route::get('/comunicaciones/e/show/{comunicacione}', [\App\Http\Controllers\FamilySentController::class, 'show'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.e.show');
    Route::get('/comunicaciones/e/{alumno}/create', [\App\Http\Controllers\FamilyComposeController::class, 'create'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('comunicaciones.e.create');
    Route::post('/comunicaciones/e/{alumno}/store', [\App\Http\Controllers\FamilyComposeController::class, 'store'])->name('comunicaciones.e.store');
    Route::post('/comunicaciones/e/{alumno}/adjuntos', [\App\Http\Controllers\FamilyComposeController::class, 'upload'])->name('family.compose.upload');
    Route::delete('/comunicaciones/e/{alumno}/adjuntos/{adjunto}', [\App\Http\Controllers\FamilyComposeController::class, 'removeUpload']);

    Route::post('/uploads/adjuntos/e/delete/', [AdjuntoController::class, 'destroyAdjunto'])->name('uploads.adjuntos.delete');
    // The student legajo is uploaded atomically through alumnos.adjunto.store.
    // Retire the unscoped legacy web upload routes; mobile endpoints stay separate.
    Route::get('/pagos/{alumno}', [\App\Http\Controllers\FamilyFinanceController::class, 'payments'])
        ->name('pagos.indexA')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::get('/pagos/{cod_recibo}/descargar', [CuentaCorrienteController::class, 'descargarPago'])->name('pagos.descargar');

    Route::get('/cc/{alumno}', [\App\Http\Controllers\FamilyFinanceController::class, 'index'])
        ->name('cc.indexA')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::post('/cc/pagar', [CuentaCorrienteController::class, 'pagar'])->name('cc.pagar');

    // Notas lo sacamos
    // Route::get('/notas/{alumno}', [NotaController::class, 'indexA'])->name('notas.indexA');

    // Route::get('/informes/{alumno}', [InformeController::class, 'indexA'])->name('informes.indexA');
    Route::get('/informes/{alumno}/duco', [InformeController::class, 'descargarDUCO'])->name('informes.descargarDUCO');
    Route::get('/informes/{alumno}/duco2', [InformeController::class, 'descargarDUCO2'])->name('informes.descargarDUCO2');
    Route::get('/informes/{alumno}/examenfinal', [InformeController::class, 'descargarExamenFinal'])->name('informes.descargarExamenFinal');
    Route::get('/informes/{alumno}/certificado', [InformeController::class, 'descargarCertificado'])->name('informes.descargarCertificado');

    Route::get('/informes/{alumno}/documentos', [\App\Http\Controllers\FamilyDocumentsController::class, 'index'])
        ->name('informes.indexDocumentos')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
    Route::get('/informes/{alumno}/documentos/{idDoc}', [InformeController::class, 'descargarDocumento'])->name('informes.descargarDocumento');

    Route::get('/informes/{alumno}/informeConceptual', [InformeController::class, 'informeConceptual'])->name('informes.conceptual.descargar');
    Route::get('/informes/{alumno}/boletin', [InformeController::class, 'descargarBoletin'])->name('informes.descargarBoletin');


    Route::get('/carteleras/GENERAL', [\App\Http\Controllers\FamilyBoardController::class, 'general'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('carteleras.general.show');
    Route::get('/carteleras/{cartelera}', [\App\Http\Controllers\FamilyBoardController::class, 'show'])
        ->middleware(\App\Http\Middleware\HandleEurekaRequests::class)->name('carteleras.show');


    Route::get('/asistencias/{alumno}', [\App\Http\Controllers\FamilyAttendanceController::class, 'index'])
        ->name('asistencias.indexA')->middleware(\App\Http\Middleware\HandleEurekaRequests::class);
});



Route::get('/comunicaciones/app/{comunicacion}/{responsable}/{token}', [ComunicacionController::class, 'appshow']);
Route::get('/comunicaciones/e/app/{comunicacionE}/{responsable}/{token}', [ComunicacionEController::class, 'appshow']);
Route::get('/carteleras/app/{cartelera}', [CarteleraController::class, 'appshow'])->name('carteleras.appshow');
//Route::get('/pagos/app/{cod_recibo}/{token}', [CuentaCorrienteController::class, 'appDescargarPago']);





Route::get('/dummy', [DummyController::class, 'index'])->middleware('auth');
Route::get('/dummy2', [DummyController::class, 'index2']);
Route::get('/dummy3', [DummyController::class, 'index3'])->name('dummy3');
Route::get('/dummy4', [DummyController::class, 'dropzone'])->name('dummy4');
Route::get('/dummy5', [DummyController::class, 'index5'])->name('dummy5');
Route::get('/dummy/al',  [DummyController::class, 'al']);
Route::get('/dummy/{any}', [DummyController::class, 'show', 'any']);



//Route::get('/{any}', [\App\Http\Controllers\AdminGeneralController::class, 'show', 'any']);
