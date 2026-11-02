<?php

namespace App\Http\Controllers;

use App\Models\Cartelera;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FamilyBoardController extends Controller
{
    public function general(Request $request, FamilyPortal $portal): RedirectResponse
    {
        $portal->responsable($request->user());
        $board = $portal->generalBoard();

        return $board ? redirect()->route('carteleras.show', $board) : redirect()->route('home');
    }

    public function show(Request $request, Cartelera $cartelera, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $user = $request->user();
        $responsable = $portal->responsable($user);
        abort_unless($cartelera->activa, 400, 'Cartelera inactiva');

        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";
        $data = [
            'portal' => $portal->home($user, $responsable, null),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'board' => [
                'id' => (string) $cartelera->id,
                'title' => $cartelera->nombre,
                'html' => (string) $cartelera->cartelera,
                'url' => route('carteleras.show', $cartelera),
                'createdAt' => $cartelera->created_at?->toIso8601String(),
                'updatedAt' => $cartelera->updated_at?->toIso8601String(),
                'attachments' => $cartelera->adjuntos()->get(['id', 'filename', 'originalFilename'])
                    ->filter(fn ($attachment) => filled($attachment->filename))
                    ->map(fn ($attachment) => [
                        'id' => (string) $attachment->id,
                        'name' => $attachment->originalFilename ?: basename($attachment->filename),
                        'extension' => strtolower(pathinfo($attachment->filename, PATHINFO_EXTENSION)),
                        'url' => Storage::disk('public')->url($attachment->filename),
                    ])->values()->all(),
            ],
        ];

        // Preserve the legacy acknowledgement only when opening the full general board.
        if ($cartelera->tipo === 'GENERAL') {
            $user->fh_visualizacion_cartelera_general = now();
            $user->save();
        }

        return Inertia::render('family/Board', $data);
    }
}
