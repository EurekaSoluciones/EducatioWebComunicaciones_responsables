<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentProfileController extends Controller
{
    public function __invoke(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $user = $request->user();
        $responsable = $portal->responsable($user);
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403, 'Acceso no permitido');

        // Scope both recipient and student on the same destination row. No read markers are written.
        $communications = Comunicacion::whereIn('id', function ($query) use ($responsable, $alumno) {
            $query->from('web_comunicaciones_destinatarios')->select('comunicacion_id')
                ->where('Cod_Responsable', $responsable->id)->where('Cod_Alumno', $alumno->id);
        })->with('remitente:id,nombres,apellidos,avatarImg')
            ->orderByDesc('id')->limit(5)->get(['id', 'asunto', 'usuario_id', 'created_at']);

        $unreadIds = Comunicacion::NoLeidosPorAlumno(true, $responsable, $alumno)
            ->whereIn('id', $communications->modelKeys())->pluck('id');
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render('family/StudentProfile', [
            'portal' => $portal->home($user, $responsable, null, $alumno),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'profile' => [
                'studentId' => (string) $alumno->id,
                'newAttachmentUrl' => route('alumnos.adjunto.create', $alumno),
                'success' => $request->session()->get('success'),
                'communications' => $communications->map(fn ($communication) => [
                    'id' => (string) $communication->id,
                    'subject' => filled($communication->asunto) ? $communication->asunto : 'Sin asunto',
                    'url' => route('comunicaciones.show', [$communication, $alumno]),
                    'date' => $communication->created_at?->format('d/m/Y H:i'),
                    'dateHuman' => $communication->created_at?->locale('es')->diffForHumans(),
                    'dateTime' => $communication->created_at?->toIso8601String(),
                    'unread' => $unreadIds->contains($communication->id),
                    'sender' => [
                        'name' => trim($communication->remitente?->NombreCompleto ?? '') ?: 'Institución',
                        'avatar' => filled($communication->remitente?->avatarImg)
                            ? Storage::disk('public')->url($communication->remitente->avatarImg) : null,
                    ],
                ])->values()->all(),
                'attachments' => $alumno->adjuntos()->get(['id', 'filename', 'originalFilename'])
                    ->filter(fn ($attachment) => filled($attachment->filename))
                    ->map(fn ($attachment) => [
                        'id' => (string) $attachment->id,
                        'name' => $attachment->originalFilename ?: basename($attachment->filename),
                        'extension' => strtolower(pathinfo($attachment->filename, PATHINFO_EXTENSION)),
                        'url' => Storage::disk('public')->url($attachment->filename),
                    ])->values()->all(),
            ],
        ]);
    }
}
