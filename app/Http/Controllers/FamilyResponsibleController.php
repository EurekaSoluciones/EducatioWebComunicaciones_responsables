<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use App\Models\User;
use App\Support\EurekaTheme;
use App\Support\FamilyPhotoUpload;
use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyResponsibleController extends Controller
{
    public function showLogged(Request $request, FamilyPortal $portal): RedirectResponse
    {
        return redirect()->route('responsables.show', $portal->responsable($request->user()));
    }

    public function show(Request $request, Responsable $responsable, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        return $this->page($request, $responsable, $portal, $themes, 'family/ResponsibleProfile');
    }

    public function edit(Request $request, Responsable $responsable, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        return $this->page($request, $responsable, $portal, $themes, 'family/ResponsiblePhoto');
    }

    public function password(Request $request, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        return $this->page($request, $portal->responsable($request->user()), $portal, $themes, 'family/Password')
            ->with('passwordUpdateUrl', route('auth.password.update'));
    }

    private function page(Request $request, Responsable $responsable, FamilyPortal $portal, EurekaTheme $themes, string $component): Response
    {
        $user = $request->user();
        $own = $portal->responsable($user);
        abort_unless((string) $own->id === (string) $responsable->id, 403);
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render($component, [
            'portal' => $portal->home($user, $responsable, null),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'profile' => [
                'responsibleId' => (string) $responsable->id,
                'login' => (string) $user->login,
                'email' => (string) $responsable->Email,
                'phone' => (string) $responsable->Telefono,
                'mobile' => (string) $responsable->Celular,
                'dni' => (string) $responsable->dni,
                'studentPlans' => $responsable->alumnos->mapWithKeys(fn ($student) => [
                    (string) $student->id => (string) $student->datos?->plan,
                ])->all(),
                'url' => route('responsables.show', $responsable),
                'photoUrl' => route('responsables.edit', $responsable),
                'updatePhotoUrl' => route('responsables.update', $user),
                'success' => $request->session()->get('success'),
            ],
        ]);
    }

    public function update(Request $request, User $user, FamilyPortal $portal, FamilyPhotoUpload $photos): JsonResponse|RedirectResponse
    {
        $responsable = $portal->responsable($request->user());
        abort_unless((string) $user->id === (string) $request->user()->id
            && (string) $user->Cod_Responsable === (string) $responsable->id, 403);
        $remove = $photos->save($request, 'AVResp_', function (bool $remove, ?string $filename) use ($user) {
            $user->avatarImg = $remove ? '' : $filename;
            $user->save();
        });
        $request->session()->flash('success', $remove ? 'Se quitó tu foto de perfil.' : 'Tu foto de perfil se actualizó.');
        $url = route('responsables.show', $responsable);

        return $request->expectsJson() ? response()->json(['redirectUrl' => $url]) : redirect($url);
    }
}
