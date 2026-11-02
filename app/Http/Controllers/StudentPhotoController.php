<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\AlumnoWeb;
use App\Support\EurekaTheme;
use App\Support\FamilyPhotoUpload;
use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentPhotoController extends Controller
{
    public function edit(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $responsable = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render('family/StudentPhoto', [
            'portal' => $portal->home($request->user(), $responsable, null, $alumno),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'photo' => ['studentId' => (string) $alumno->id, 'updateUrl' => route('alumnos.updatePic', $alumno)],
        ]);
    }

    public function update(Request $request, Alumno $alumno, FamilyPortal $portal): JsonResponse|RedirectResponse
    {
        $responsable = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);

        $remove = app(FamilyPhotoUpload::class)->save($request, 'AVAlumno_', function (bool $remove, ?string $filename) use ($alumno) {
            $web = AlumnoWeb::find($alumno->id);
            if (! $web && $remove) {
                return;
            }
            if (! $web) {
                $web = new AlumnoWeb;
                $web->Cod_Alumno = $alumno->id;
                $web->bgImg = '';
            }
            $web->avatarImg = $remove ? '' : $filename;
            $web->save();
        });

        $request->session()->flash('success', $remove ? 'Se quitó la foto del alumno.' : 'Foto del alumno actualizada.');
        $url = route('alumnos.show', $alumno);

        return $request->expectsJson() ? response()->json(['redirectUrl' => $url]) : redirect($url);
    }
}
