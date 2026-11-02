<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Models\MateriaProfeGrupo;
use App\Models\Responsable;
use App\Models\User;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FamilyReceivedController extends Controller
{
    private function received(Responsable $responsable, Alumno $alumno): Builder
    {
        // Both conditions must match the SAME recipient row (including siblings).
        return Comunicacion::whereHas('comunicacionDestinatarios', fn ($query) => $query
            ->where('Cod_Responsable', $responsable->id)->where('Cod_Alumno', $alumno->id));
    }

    /**
     * Plain-text opening of a communication, to fill the subject column in the list.
     * Markup, entities and runs of whitespace collapse away; an empty result is normal
     * when the message starts with an image, and the list simply shows nothing there.
     */
    private function preview(?string $html): string
    {
        // strip_tags drops the tags but keeps what is between them, so script and style
        // contents go first. The second pass covers an opener left unclosed by the cut.
        $markup = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', ' ', (string) $html) ?? '';
        $markup = preg_replace('#<(script|style)\b[^>]*>.*$#is', ' ', $markup) ?? '';
        $text = html_entity_decode(strip_tags($markup), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{a0}", ' ', $text)) ?? '');

        return mb_strimwidth($text, 0, 180, '…');
    }

    public function index(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $user = $request->user();
        $responsable = $portal->responsable($user);
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);
        // Inertia serializes GET booleans as "true"/"false"; Laravel's boolean rule accepts 1/0.
        // Normalize only those exact strings so malformed values still fail validation.
        foreach (['noLeidos', 'filtrado'] as $field) {
            if (in_array($request->input($field), ['true', 'false'], true)) {
                $request->merge([$field => $request->input($field) === 'true']);
            }
        }
        $input = $request->validate([
            'remitente' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'noLeidos' => ['nullable', 'boolean'],
            'filtrado' => ['nullable', 'boolean'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'desde.date_format' => 'Revisá la fecha inicial.',
            'hasta.date_format' => 'Revisá la fecha final.',
        ]);
        $filters = [
            'remitente' => (string) ($input['remitente'] ?? ''),
            'desde' => $input['desde'] ?? '',
            'hasta' => $input['hasta'] ?? '',
            'noLeidos' => $request->boolean('noLeidos'),
        ];
        $filters['filtrado'] = $filters['remitente'] !== ''
            || $filters['desde'] !== '' || $filters['hasta'] !== '' || $filters['noLeidos'];
        $base = $this->received($responsable, $alumno);
        // Sender choices remain stable when applying other filters, and expose only this recipient's senders.
        $senders = User::whereIn('id', (clone $base)->select('usuario_id'))
            ->orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);
        $query = clone $base;
        if ($filters['remitente'] !== '') {
            $query->where('usuario_id', $filters['remitente']);
        }
        if ($filters['desde'] !== '') {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['desde']));
        }
        if ($filters['hasta'] !== '') {
            $query->where('created_at', '<', CarbonImmutable::createFromFormat('!Y-m-d', $filters['hasta'])->addDay());
        }
        $unread = fn ($recipient) => $recipient->where('Cod_Responsable', $responsable->id)
            ->where('Cod_Alumno', $alumno->id)->where('leido', false);
        if ($filters['noLeidos']) {
            $query->whereHas('comunicacionDestinatarios', $unread);
        }
        $query->orderByDesc('id');
        // Only the opening of the body travels, so a long message (or an embedded image) never
        // weighs on the list. SQL Server needs the cast; SUBSTR covers SQLite and MySQL.
        $opening = $query->getConnection()->getDriverName() === 'sqlsrv'
            ? 'SUBSTRING(CAST(msg AS NVARCHAR(MAX)), 1, 1000)'
            : 'SUBSTR(msg, 1, 1000)';
        $messages = $query->select(['id', 'asunto', 'usuario_id', 'tipo_id', 'created_at'])
            ->selectRaw("$opening as msg_opening")
            ->with(['remitente:id,nombres,apellidos,avatarImg,desc,tipo,Cod_Profesor', 'tipo:id,descripcion'])
            // A scalar count also works on SQL Server, unlike SELECT EXISTS (...).
            ->withCount(['comunicacionDestinatarios as unread_count' => $unread])
            ->withCount(['adjuntos' => fn ($attachments) => $attachments->whereNotNull('filename')->where('filename', '<>', '')])
            // Match the original DataTable: load the complete filtered set once.
            // Search, sorting and pagination are performed locally by React.
            ->get();
        // Batch only the visible teachers' subjects for this student's group, never other groups.
        $teacherIds = $messages->pluck('remitente')->filter(fn ($sender) => $sender?->tipo === 'Profe')
            ->pluck('Cod_Profesor')->filter()->unique()->values();
        $subjectsByTeacher = collect();
        if ($alumno->Cod_Grupo && $teacherIds->isNotEmpty()) {
            $subjectsByTeacher = MateriaProfeGrupo::where('Cod_Grupo', $alumno->Cod_Grupo)
                ->whereIn('Cod_Profesor', $teacherIds)
                ->with('materia:Cod_Materia,Descripcion')
                ->get(['Cod_Profesor', 'Cod_Materia'])
                ->groupBy('Cod_Profesor')
                ->map(fn ($assignments) => $assignments->pluck('materia.Descripcion')
                    ->map(fn ($name) => trim($name ?? ''))->filter()->unique()->sort()->values()->all());
        }
        $theme = $themes->current();
        $logoPath = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render('family/Received', [
            'portal' => $portal->home($user, $responsable, null, $alumno),
            'theme' => $theme,
            'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logoPath)) ? asset($logoPath) : null,
            'homeUrl' => route('home'),
            'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'),
            'loginUrl' => route('login'),
            'csrfToken' => csrf_token(),
            'received' => [
                'studentId' => (string) $alumno->id,
                'url' => route('comunicaciones.indexA', $alumno),
                'clearUrl' => route('comunicaciones.indexA', $alumno),
                'statusUrl' => route('family.communications.status', [$alumno, 'received']),
                'filters' => $filters,
                'success' => $request->session()->get('success'),
                'senders' => $senders->map(fn ($sender) => ['id' => (string) $sender->id, 'name' => trim($sender->NombreCompleto) ?: 'Institución'])->all(),
                'messages' => $messages->map(fn ($message) => [
                    'id' => (string) $message->id,
                    'number' => $message->FormattedId,
                    'subject' => filled($message->asunto) ? $message->asunto : 'Sin asunto',
                    'preview' => $this->preview($message->msg_opening),
                    'type' => $message->tipo?->descripcion ?? '',
                    'dateTime' => $message->created_at?->toIso8601String(),
                    'unread' => $message->unread_count > 0,
                    'attachmentCount' => $message->adjuntos_count,
                    'url' => route('comunicaciones.show', [$message, $alumno]),
                    'sender' => [
                        'name' => trim($message->remitente?->NombreCompleto ?? '') ?: 'Institución',
                        'avatar' => filled($message->remitente?->avatarImg) ? Storage::disk('public')->url($message->remitente->avatarImg) : null,
                        'description' => trim($message->remitente?->desc ?? '') ?: null,
                        'role' => match ($message->remitente?->tipo) {
                            'Profe' => 'Docente',
                            'Secretaria' => 'Secretaría',
                            'Admin' => 'Administración',
                            'Responsable' => 'Responsable',
                            default => null,
                        },
                        'subjects' => $message->remitente?->tipo === 'Profe'
                            ? $subjectsByTeacher->get($message->remitente->Cod_Profesor, []) : [],
                    ],
                ])->all(),
            ],
        ]);
    }

    public function legacyFilters(Request $request, Alumno $alumno, FamilyPortal $portal): RedirectResponse
    {
        $responsable = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsable, $alumno), 403);
        $input = $request->validate([
            'remitente' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:d/m/Y'],
            'hasta' => ['nullable', 'date_format:d/m/Y'],
            'noLeidos' => ['nullable', Rule::in(['on', '1', '0'])],
        ]);
        if (! $request->filled('remitente') && ! $request->filled('desde') && ! $request->filled('hasta') && ! $request->boolean('noLeidos')) {
            return redirect()->route('comunicaciones.indexA', $alumno);
        }

        return redirect()->route('comunicaciones.indexA', [
            'alumno' => $alumno,
            'remitente' => $input['remitente'] ?? '',
            'desde' => empty($input['desde']) ? '' : CarbonImmutable::createFromFormat('!d/m/Y', $input['desde'])->toDateString(),
            'hasta' => empty($input['hasta']) ? '' : CarbonImmutable::createFromFormat('!d/m/Y', $input['hasta'])->toDateString(),
            'noLeidos' => $request->boolean('noLeidos') ? 1 : 0,
            'filtrado' => 1,
        ]);
    }
}
