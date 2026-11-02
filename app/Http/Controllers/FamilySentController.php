<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\ComunicacionE;
use App\Models\User;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FamilySentController extends Controller
{
    // The legacy recipient relation chooses a different foreign key per row. Eager loading
    // that relation for mixed recipients would join every row using the same key.
    private function recipients(Collection $messages): array
    {
        $people = [];
        foreach (['office' => 'cod_usuario', 'teacher' => 'Cod_Profesor'] as $kind => $column) {
            $ids = $messages->filter(fn ($message) => $this->recipientKind($message) === $kind)
                ->pluck('Cod_Usuario')->filter(fn ($id) => $id !== null)->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }
            $users = User::whereIn($column, $ids)->orderBy('id')
                ->get(['id', $column, 'nombres', 'apellidos', 'avatarImg', 'desc', 'tipo']);
            foreach ($users as $user) {
                $people[$kind.':'.$user->{$column}] ??= [
                    'name' => trim($user->NombreCompleto) ?: 'Institución',
                    'avatar' => filled($user->avatarImg) ? Storage::disk('public')->url($user->avatarImg) : null,
                    'description' => trim($user->desc ?? '') ?: null,
                    'role' => match ($user->tipo) {
                        'Profe' => 'Docente', 'Secretaria' => 'Secretaría', 'Admin' => 'Administración', default => null,
                    },
                ];
            }
        }

        return $people;
    }

    private function recipientKind(ComunicacionE $message): string
    {
        return $message->tipo === null || $message->tipo === 'Secretaria' ? 'office' : 'teacher';
    }

    private function message(ComunicacionE $message, array $people): array
    {
        return [
            'id' => (string) $message->id,
            'number' => $message->FormattedId,
            'subject' => filled($message->asunto) ? $message->asunto : 'Sin asunto',
            'dateTime' => $message->created_at?->toIso8601String(),
            'readAt' => $message->fhLeido?->toIso8601String(),
            'answeredAt' => $message->fhRespuesta?->toIso8601String(),
            'replyUnread' => $message->fhRespuesta !== null && $message->fhRespuestaLeida === null,
            'url' => route('comunicaciones.e.show', $message),
            'recipient' => $people[$this->recipientKind($message).':'.$message->Cod_Usuario]
                ?? ['name' => 'Destinatario no disponible', 'avatar' => null, 'description' => null, 'role' => null],
        ];
    }

    protected function page(Request $request, Alumno $student, FamilyPortal $portal, EurekaTheme $themes): array
    {
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return [
            'portal' => $portal->home($request->user(), $portal->responsable($request->user()), null, $student),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'), 'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'), 'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
        ];
    }

    public function index(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $responsable = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);
        // Like Blade/DataTables: all own messages for this student, with no implicit dates.
        // No bodies, responses or read writes are needed for this list.
        $messages = ComunicacionE::deAlumno($alumno)->deResponsable($responsable)->orderByDesc('id')
            ->get(['id', 'Cod_Usuario', 'tipo', 'asunto', 'created_at', 'fhLeido', 'fhRespuesta', 'fhRespuestaLeida']);
        $people = $this->recipients($messages);

        return Inertia::render('family/Sent', $this->page($request, $alumno, $portal, $themes) + [
            'sent' => [
                'studentId' => (string) $alumno->id,
                'url' => route('comunicaciones.e.indexA', $alumno),
                'createUrl' => route('comunicaciones.e.create', $alumno),
                'success' => $request->session()->get('success'),
                'messages' => $messages->map(fn ($message) => $this->message($message, $people))->all(),
                'statusUrl' => route('family.communications.status', [$alumno, 'sent']),
            ],
        ]);
    }

    public function show(Request $request, ComunicacionE $comunicacione, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $responsable = $portal->responsable($request->user());
        abort_unless((string) $comunicacione->Cod_Responsable === (string) $responsable->id, 403);
        $alumno = $comunicacione->alumno;
        abort_unless($alumno && EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);

        // Opening a response acknowledges it, as before. A conditional update keeps the
        // first read time stable and never changes the institution's own read marker.
        if ($comunicacione->fhRespuesta !== null && $comunicacione->fhRespuestaLeida === null) {
            ComunicacionE::whereKey($comunicacione->id)->where('Cod_Responsable', $responsable->id)
                ->where('Cod_Alumno', $alumno->id)->whereNotNull('fhRespuesta')->whereNull('fhRespuestaLeida')
                ->update(['fhRespuestaLeida' => now()]);
            $comunicacione->refresh();
        }
        $people = $this->recipients(collect([$comunicacione]));

        return Inertia::render('family/SentDetail', $this->page($request, $alumno, $portal, $themes) + [
            'sent' => [
                'studentId' => (string) $alumno->id,
                'url' => route('comunicaciones.e.indexA', $alumno),
                'createUrl' => route('comunicaciones.e.create', $alumno),
                'message' => $this->message($comunicacione, $people) + [
                    'html' => (string) $comunicacione->msg,
                    // The old response is plain text, not HTML.
                    'reply' => $comunicacione->fhRespuesta !== null ? (string) $comunicacione->respuesta : null,
                    'attachments' => $comunicacione->adjuntos()->whereNotNull('filename')->where('filename', '<>', '')
                        ->get(['id', 'filename', 'originalFilename'])->map(fn ($file) => [
                            'id' => (string) $file->id,
                            'name' => $file->originalFilename ?: basename($file->filename),
                            'url' => Storage::disk('public')->url($file->filename),
                        ])->all(),
                ],
            ],
        ]);
    }
}
