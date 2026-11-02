<?php

namespace App\Http\Controllers;

use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request, FamilyPortal $portal, EurekaTheme $themes): Response|RedirectResponse
    {
        $user = $request->user();
        $responsable = $portal->responsable($user);
        $board = $portal->generalBoard();
        if ($portal->mustReadBoard($user, $board)) {
            return redirect()->route('carteleras.show', $board);
        }

        $result = $request->session()->get('resultadoRematriculacion');
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render('family/Home', [
            'portal' => $portal->home($user, $responsable, $board),
            'boardPreviews' => $portal->boardPreviews(),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'enrollmentResult' => is_array($result) ? [
                'studentId' => (string) ($result['alumnoId'] ?? ''),
                'type' => $result['tipo'] ?? '',
                'message' => $result['mensaje'] ?? '',
            ] : null,
        ]);
    }
}
