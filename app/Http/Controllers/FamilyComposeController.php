<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\ComunicacionTipoEnum;
use App\EureLib\EureFunctions;
use App\Models\Adjunto;
use App\Models\Alumno;
use App\Models\ComunicacionE;
use App\Models\Responsable;
use App\Models\User;
use App\Models\UsuarioGrupo;
use App\Support\EurekaTheme;
use App\Support\FamilyEmojiCatalog;
use App\Support\FamilyMessageHtml;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class FamilyComposeController extends FamilySentController
{
    private function authorizeStudent(Request $request, Alumno $student, FamilyPortal $portal): Responsable
    {
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $student), 403);

        return $responsible;
    }

    private function recipients(Alumno $student): array
    {
        // The legacy scope silently returns every group when the student has none.
        // There is no authorized recipient in that case.
        if (! $student->Cod_Grupo) {
            return [];
        }
        $memberships = UsuarioGrupo::where('Cod_Grupo', $student->Cod_Grupo)->get(['tipo', 'cod_usuario']);
        $recipients = [];
        foreach (['Profe' => 'Cod_Profesor', 'Secretaria' => 'cod_usuario'] as $type => $column) {
            $codes = $memberships->where('tipo', $type)->pluck('cod_usuario')->filter(fn ($value) => $value !== null)->unique();
            if ($codes->isEmpty()) {
                continue;
            }
            foreach (User::whereIn($column, $codes)->orderBy('id')->get(['id', $column, 'nombres', 'apellidos', 'desc', 'avatarImg']) as $user) {
                $key = $type.'|'.$user->{$column};
                $recipients[$key] ??= [
                    'id' => $key, 'name' => trim($user->NombreCompleto) ?: 'Institución',
                    'description' => trim($user->desc ?? ''), 'role' => $type === 'Profe' ? 'Docente' : 'Secretaría',
                    'avatar' => filled($user->avatarImg) ? Storage::disk('public')->url($user->avatarImg) : null,
                ];
            }
        }

        return array_values($recipients);
    }

    private function draftKey(Request $request, string $token): string
    {
        return 'family_compose.'.$request->user()->id.'.'.$token;
    }

    private function token(Request $request, Alumno $student): string
    {
        $token = $request->validate(['tempId' => ['required', 'uuid']])['tempId'];
        abort_unless((string) $request->session()->get($this->draftKey($request, $token)) === (string) $student->id, 403);

        return $token;
    }

    private function receipt(string $token): ?Adjunto
    {
        return Adjunto::where('entity', 'comunicacione_envio')->where('tempId', $token)->first();
    }

    private function fileData(Adjunto $file): array
    {
        return [
            'id' => (string) $file->id, 'name' => $file->originalFilename,
            'url' => $this->imagePath($file), 'image' => $file->entity === 'comunicacione_imagen',
            // Restore drafts written before images used root-relative paths.
            'previousUrl' => Storage::disk('public')->url($file->filename),
        ];
    }

    private function imagePath(Adjunto $file): string
    {
        return '/storage/'.$file->filename;
    }

    public function create(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes)
    {
        $this->authorizeStudent($request, $alumno, $portal);
        if (! $request->has('tempId')) {
            $token = (string) Str::uuid();
            $request->session()->put($this->draftKey($request, $token), (string) $alumno->id);

            return redirect()->route('comunicaciones.e.create', [$alumno, 'tempId' => $token]);
        }
        $token = $this->token($request, $alumno);
        if ($receipt = $this->receipt($token)) {
            return redirect()->route('comunicaciones.e.show', $receipt->entityId);
        }

        return Inertia::render('family/Compose', $this->page($request, $alumno, $portal, $themes) + [
            'compose' => [
                'studentId' => (string) $alumno->id, 'tempId' => $token, 'recipients' => $this->recipients($alumno),
                'emojiBaseUrl' => '/'.FamilyEmojiCatalog::PATH,
                'submitUrl' => route('comunicaciones.e.store', $alumno),
                'uploadUrl' => route('family.compose.upload', $alumno),
                'listUrl' => route('comunicaciones.e.indexA', $alumno),
                'files' => Adjunto::whereIn('entity', ['comunicacione', 'comunicacione_imagen'])->whereNull('entityId')->where('tempId', $token)->get()->map(fn ($file) => $this->fileData($file))->all(),
            ],
        ]);
    }

    public function store(Request $request, Alumno $alumno, FamilyPortal $portal, FamilyMessageHtml $html)
    {
        $responsible = $this->authorizeStudent($request, $alumno, $portal);
        $token = $this->token($request, $alumno);
        $id = DB::transaction(function () use ($request, $alumno, $responsible, $token, $html) {
            // Serialize every operation on this responsible's drafts, including uploads.
            // The receipt survives a lost HTTP response and does not require a schema change.
            Responsable::whereKey($responsible->id)->lockForUpdate()->firstOrFail();
            if ($receipt = $this->receipt($token)) {
                return $receipt->entityId;
            }
            $data = $request->validate([
                'destinatario' => ['required', 'string', Rule::in(array_column($this->recipients($alumno), 'id'))],
                'asunto' => ['required', 'string', 'max:255'], 'msg' => ['required', 'string', 'max:200000'],
            ]);
            $images = Adjunto::where('entity', 'comunicacione_imagen')->whereNull('entityId')->where('tempId', $token)->get();
            $message = new ComunicacionE;
            [$message->tipo, $message->Cod_Usuario] = explode('|', $data['destinatario']);
            $message->tipo_id = ComunicacionTipoEnum::Entrante->value;
            $message->Cod_Responsable = $responsible->id;
            $message->Cod_Alumno = $alumno->id;
            $message->asunto = trim($data['asunto']);
            $message->msg = $html->clean(
                $data['msg'],
                $images->map(fn ($file) => $this->imagePath($file))->all(),
                FamilyEmojiCatalog::images('/'.FamilyEmojiCatalog::PATH),
                $images->mapWithKeys(fn ($file) => [Storage::disk('public')->url($file->filename) => $this->imagePath($file)])->all(),
            );
            $message->save();
            Adjunto::whereIn('entity', ['comunicacione', 'comunicacione_imagen'])->where('tempId', $token)->whereNull('entityId')->update(['entityId' => $message->id]);
            $receipt = new Adjunto;
            $receipt->entity = 'comunicacione_envio';
            $receipt->tempId = $token;
            $receipt->entityId = $message->id;
            $receipt->filename = '';
            $receipt->originalFilename = 'Constancia de envío';
            $receipt->save();

            return $message->id;
        });
        $request->session()->flash('success', 'Tu mensaje fue enviado.');

        return response()->json(['redirectUrl' => route('comunicaciones.e.show', $id)]);
    }

    public function upload(Request $request, Alumno $alumno, FamilyPortal $portal)
    {
        $responsible = $this->authorizeStudent($request, $alumno, $portal);
        $token = $this->token($request, $alumno);
        $request->validate(['kind' => ['required', Rule::in(['file', 'image'])], 'file' => ['required', 'file', 'max:12288', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,rtf,xls,xlsx', 'extensions:jpeg,jpg,png,gif,webp,pdf,doc,docx,rtf,xls,xlsx']]);
        $image = $request->input('kind') === 'image';
        if ($image) {
            $request->validate(['file' => ['image', 'mimes:jpeg,png,gif,webp', 'dimensions:max_width=8000,max_height=8000']]);
        }
        $file = $request->file('file');
        $filename = ($image ? 'CommEImg_' : 'CommEAdj_').Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        try {
            $attachment = DB::transaction(function () use ($responsible, $token, $file, $filename, $image) {
                Responsable::whereKey($responsible->id)->lockForUpdate()->firstOrFail();
                abort_if($this->receipt($token), 409, 'Este mensaje ya fue enviado.');
                if (! Storage::disk('public')->put($filename, $file->get())) {
                    throw ValidationException::withMessages(['file' => 'No se pudo guardar el archivo. Intentá nuevamente.']);
                }
                $attachment = new Adjunto;
                $attachment->entity = $image ? 'comunicacione_imagen' : 'comunicacione';
                $attachment->entityId = null;
                $attachment->tempId = $token;
                $attachment->filename = $filename;
                $attachment->originalFilename = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 200);
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

        return response()->json($this->fileData($attachment), 201);
    }

    public function removeUpload(Request $request, Alumno $alumno, Adjunto $adjunto, FamilyPortal $portal)
    {
        $responsible = $this->authorizeStudent($request, $alumno, $portal);
        $token = $this->token($request, $alumno);
        DB::transaction(function () use ($responsible, $token, $adjunto) {
            Responsable::whereKey($responsible->id)->lockForUpdate()->firstOrFail();
            abort_if($this->receipt($token), 409, 'Este mensaje ya fue enviado.');
            $file = Adjunto::whereKey($adjunto->id)->whereIn('entity', ['comunicacione', 'comunicacione_imagen'])->whereNull('entityId')->where('tempId', $token)->firstOrFail();
            if (! Storage::disk('public')->delete($file->filename)) {
                throw ValidationException::withMessages(['file' => 'No se pudo quitar el archivo.']);
            }
            $file->delete();
        });

        return response()->json(['removed' => true]);
    }
}
