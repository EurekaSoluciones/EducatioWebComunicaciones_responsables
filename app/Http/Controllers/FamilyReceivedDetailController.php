<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Adjunto;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\MateriaProfeGrupo;
use App\Models\User;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FamilyReceivedDetailController extends Controller
{
    private function authorizeRecipient(Request $request, ComunicacionDestinatario $recipient, FamilyPortal $portal): void
    {
        $responsible = $portal->responsable($request->user());
        abort_unless((string) $recipient->Cod_Responsable === (string) $responsible->id, 403);
        $student = Alumno::find($recipient->Cod_Alumno);
        abort_unless($student && EureFunctions::esResponsableDeAlumno($responsible, $student), 403);
    }

    private function options(Comunicacion $message): array
    {
        return collect(explode(';', (string) $message->listas_respuestas_fijas))->map(fn ($option) => trim($option))
            ->filter(fn ($option) => $option !== '')->unique()->values()->all();
    }

    private function files($files): array
    {
        return $files->filter(fn ($file) => filled($file->filename))->map(fn ($file) => [
            'id' => (string) $file->id, 'name' => $file->originalFilename ?: basename($file->filename),
            'url' => Storage::disk('public')->url($file->filename),
        ])->values()->all();
    }

    private function draftKey(Request $request, ComunicacionDestinatario $recipient): string
    {
        return 'family_reply_drafts.'.$request->user()->id.'.'.$recipient->id;
    }

    private function checkDraft(Request $request, ComunicacionDestinatario $recipient): string
    {
        $input = $request->validate(['tempId' => ['required', 'uuid']]);
        $token = $request->session()->get($this->draftKey($request, $recipient));
        abort_unless(is_string($token) && hash_equals($token, $input['tempId']), 403);

        return $token;
    }

    private function canReply(ComunicacionDestinatario $recipient, int $mode): void
    {
        abort_unless((int) $recipient->comunicacion?->tipo_respuesta_id === $mode, 403);
        if ($recipient->fhRespuesta !== null || filled($recipient->respuesta)) {
            throw ValidationException::withMessages(['respuesta' => 'Esta comunicación ya tiene una respuesta. Recargá la página para verla.']);
        }
    }

    public function show(Request $request, Comunicacion $comunicacion, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $alumno), 403);
        $recipient = $comunicacion->comunicacionDestinatarios()->where('Cod_Responsable', $responsible->id)
            ->where('Cod_Alumno', $alumno->id)->first();
        abort_unless($recipient, 403);
        // Keep the first reading time. Never write the legacy derived `leido` column.
        ComunicacionDestinatario::whereKey($recipient->id)->whereNull('fhLeido')->update(['fhLeido' => now()]);
        $recipient->refresh();
        $comunicacion->loadMissing(['remitente:id,nombres,apellidos,avatarImg,desc,tipo,Cod_Profesor', 'tipo:id,descripcion']);
        $sender = $comunicacion->remitente;
        $subjects = [];
        if ($sender?->tipo === 'Profe' && $sender->Cod_Profesor && $alumno->Cod_Grupo) {
            $subjects = MateriaProfeGrupo::where('Cod_Profesor', $sender->Cod_Profesor)->where('Cod_Grupo', $alumno->Cod_Grupo)
                ->with('materia:Cod_Materia,Descripcion')->get()->pluck('materia.Descripcion')->filter()->unique()->values()->all();
        }
        $mode = (int) $comunicacion->tipo_respuesta_id;
        $answered = $recipient->fhRespuesta !== null || filled($recipient->respuesta);
        $draft = null;
        if ($mode === 2 && ! $answered) {
            $draft = $request->session()->get($this->draftKey($request, $recipient));
            if (! $draft) {
                $draft = (string) Str::uuid();
                $request->session()->put($this->draftKey($request, $recipient), $draft);
            }
        }
        // Preserve the legacy reading panel for this student, never recipients of other students.
        $readers = $comunicacion->comunicacionDestinatarios()->where('Cod_Alumno', $alumno->id)
            ->with('responsable')->get();
        $photos = User::where('tipo', 'Responsable')->whereIn('Cod_Responsable', $readers->pluck('Cod_Responsable'))
            ->pluck('avatarImg', 'Cod_Responsable');
        $theme = $themes->current();
        $logo = "assets/C/{$theme['clientId']}/institucional/Logo.png";

        return Inertia::render('family/ReceivedDetail', [
            'portal' => $portal->home($request->user(), $responsible, null, $alumno),
            'theme' => $theme, 'institution' => config('eureka.institution'),
            'institutionLogoUrl' => is_file(public_path($logo)) ? asset($logo) : null,
            'homeUrl' => route('home'), 'logoutUrl' => route('logout'),
            'notificationsUrl' => route('family.notifications'), 'loginUrl' => route('login'), 'csrfToken' => csrf_token(),
            'received' => [
                'studentId' => (string) $alumno->id, 'listUrl' => route('comunicaciones.indexA', $alumno),
                'readConfirmed' => $recipient->fhLeido !== null,
                'success' => $request->session()->get('success'),
                'message' => [
                    'id' => (string) $comunicacion->id, 'number' => $comunicacion->FormattedId,
                    'subject' => $comunicacion->asunto ?: 'Sin asunto', 'html' => (string) $comunicacion->msg,
                    'type' => $comunicacion->tipo?->descripcion ?? '', 'dateTime' => $comunicacion->created_at?->toIso8601String(),
                    'audience' => match ($comunicacion->templateDestinatarios) {
                        'CURSO_COMPLETO' => 'Todo el curso', 'TODOS' => 'Toda la institución', default => $alumno->nombreYApellido,
                    },
                    'groups' => $comunicacion->templateDestinatarios === 'TODOS'
                        ? collect($comunicacion->gruposSeleccion)->map(fn ($value) => trim($value))->filter()->unique()->values()->all() : [],
                    'tags' => collect(explode(';', (string) $comunicacion->etiquetas))->map(fn ($value) => trim($value))->filter()->unique()->values()->all(),
                    'sender' => [
                        'name' => trim($sender?->NombreCompleto ?? '') ?: 'Institución',
                        'avatar' => filled($sender?->avatarImg) ? Storage::disk('public')->url($sender->avatarImg) : null,
                        'description' => trim($sender?->desc ?? '') ?: null, 'subjects' => $subjects,
                        'role' => match ($sender?->tipo) {
                            'Profe' => 'Docente', 'Secretaria' => 'Secretaría', 'Admin' => 'Administración',
                            'Responsable' => 'Responsable', default => null,
                        },
                    ],
                    'attachments' => $this->files($comunicacion->adjuntos()->get()),
                    'readers' => $readers->map(fn ($reader) => [
                        'id' => (string) $reader->id, 'name' => trim($reader->responsable?->nombreYApellido ?? '') ?: 'Responsable',
                        'avatar' => filled($photos->get($reader->Cod_Responsable)) ? Storage::disk('public')->url($photos->get($reader->Cod_Responsable)) : null,
                        'readAt' => $reader->fhLeido?->toIso8601String(),
                    ])->all(),
                ],
                'reply' => [
                    'mode' => $mode, 'answered' => $answered, 'text' => (string) $recipient->respuesta,
                    'dateTime' => $recipient->fhRespuesta?->toIso8601String(), 'options' => $mode === 3 ? $this->options($comunicacion) : [],
                    'attachments' => $this->files($recipient->adjuntos()->get()),
                    'draftFiles' => $draft ? $this->files(Adjunto::where('entity', 'comunicaciond')->whereNull('entityId')->where('tempId', $draft)->get()) : [],
                    'tempId' => $draft,
                    'submitUrl' => in_array($mode, [2, 3]) ? route($mode === 2 ? 'comunicaciones.respuestas.libres.store' : 'comunicaciones.respuestas.fijas.store', $recipient) : null,
                    'uploadUrl' => route('family.received.reply.upload', $recipient),
                ],
            ],
        ]);
    }

    public function free(Request $request, ComunicacionDestinatario $comunicaciondestinatario, FamilyPortal $portal): JsonResponse
    {
        return $this->reply($request, $comunicaciondestinatario, $portal, 2);
    }

    public function fixed(Request $request, ComunicacionDestinatario $comunicaciondestinatario, FamilyPortal $portal): JsonResponse
    {
        return $this->reply($request, $comunicaciondestinatario, $portal, 3);
    }

    private function reply(Request $request, ComunicacionDestinatario $recipient, FamilyPortal $portal, int $mode): JsonResponse
    {
        $this->authorizeRecipient($request, $recipient, $portal);
        $this->canReply($recipient, $mode);
        $input = $request->validate(['respuesta' => ['required', 'string', ...($mode === 3 ? [Rule::in($this->options($recipient->comunicacion))] : [])]]);
        $token = $mode === 2 ? $this->checkDraft($request, $recipient) : null;
        DB::transaction(function () use ($recipient, $mode, $input, $token) {
            $locked = ComunicacionDestinatario::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
            $this->canReply($locked, $mode);
            $locked->respuesta = $input['respuesta'];
            $locked->fhRespuesta = now();
            $locked->save();
            if ($token) {
                Adjunto::where('entity', 'comunicaciond')->whereNull('entityId')->where('tempId', $token)->update(['entityId' => $locked->id]);
            }
        });
        $request->session()->forget($this->draftKey($request, $recipient));
        $request->session()->flash('success', 'Tu respuesta fue enviada.');

        return response()->json(['redirectUrl' => route('comunicaciones.show', [$recipient->comunicacion_id, $recipient->Cod_Alumno])]);
    }

    public function upload(Request $request, ComunicacionDestinatario $comunicaciondestinatario, FamilyPortal $portal): JsonResponse
    {
        $recipient = $comunicaciondestinatario;
        $this->authorizeRecipient($request, $recipient, $portal);
        $this->canReply($recipient, 2);
        $token = $this->checkDraft($request, $recipient);
        $request->validate(['file' => ['required', 'file', 'max:12288', 'mimes:jpeg,jpg,png,gif,pdf,doc,docx,rtf,xls,xlsx', 'extensions:jpeg,jpg,png,gif,pdf,doc,docx,rtf,xls,xlsx']]);
        $file = $request->file('file');
        $filename = 'CommRespAdj_'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        try {
            $attachment = DB::transaction(function () use ($recipient, $file, $filename, $token) {
                $locked = ComunicacionDestinatario::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
                $this->canReply($locked, 2);
                if (! Storage::disk('public')->put($filename, $file->get())) {
                    throw ValidationException::withMessages(['file' => 'No se pudo guardar el archivo. Intentá nuevamente.']);
                }
                $attachment = new Adjunto;
                $attachment->entity = 'comunicaciond';
                $attachment->entityId = null;
                $attachment->tempId = $token;
                $attachment->filename = $filename;
                $attachment->originalFilename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $attachment->save();

                return $attachment;
            });
        } catch (Throwable $exception) {
            try {
                Storage::disk('public')->delete($filename);
            } catch (Throwable $cleanup) {
                report($cleanup);
            }
            throw $exception;
        }

        return response()->json($this->files(collect([$attachment]))[0], 201);
    }

    public function removeUpload(Request $request, ComunicacionDestinatario $comunicaciondestinatario, Adjunto $adjunto, FamilyPortal $portal): JsonResponse
    {
        $recipient = $comunicaciondestinatario;
        $this->authorizeRecipient($request, $recipient, $portal);
        $token = $this->checkDraft($request, $recipient);
        DB::transaction(function () use ($recipient, $adjunto, $token) {
            $locked = ComunicacionDestinatario::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
            $this->canReply($locked, 2);
            $file = Adjunto::whereKey($adjunto->id)->where('entity', 'comunicaciond')->whereNull('entityId')->where('tempId', $token)->firstOrFail();
            // Only an unsubmitted attachment from this session can be removed.
            if (! Storage::disk('public')->delete($file->filename)) {
                throw ValidationException::withMessages(['file' => 'No se pudo quitar el archivo. Intentá nuevamente.']);
            }
            $file->delete();
        });

        return response()->json(['removed' => true]);
    }
}
