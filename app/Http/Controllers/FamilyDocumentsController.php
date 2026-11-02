<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Adjunto;
use App\Models\Alumno;
use App\Models\Responsable;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FamilyDocumentsController extends FamilySentController
{
    private function authorizeStudent(Request $request, Alumno $student, FamilyPortal $portal): Responsable
    {
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $student), 403);

        return $responsible;
    }

    public function index(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $this->authorizeStudent($request, $alumno, $portal);
        $reports = EducatioCommFunctions::Documentos_Obtener($alumno);

        return Inertia::render('family/Documents', $this->page($request, $alumno, $portal, $themes) + [
            'documents' => [
                'studentId' => (string) $alumno->id,
                'notice' => $request->session()->get('mensajeDocumento'),
                'newAttachmentUrl' => route('alumnos.adjunto.create', $alumno),
                'reports' => collect($reports)->map(fn ($report) => [
                    'id' => (string) $report->id_doc, 'name' => (string) $report->descripcion,
                    'enabled' => (bool) $report->habilitado,
                    'message' => $report->habilitado ? null : (string) $report->mensaje,
                    'url' => $report->habilitado ? route('informes.descargarDocumento', [$alumno, $report->id_doc]) : null,
                ])->values()->all(),
                'attachments' => $alumno->adjuntos()->whereNotNull('filename')->where('filename', '<>', '')
                    ->reorder('id', 'desc')->get(['id', 'filename', 'originalFilename', 'created_at'])
                    ->map(fn ($file) => [
                        'id' => (string) $file->id, 'name' => $file->originalFilename ?: basename($file->filename),
                        'url' => '/storage/'.$file->filename,
                        'extension' => strtolower(pathinfo($file->filename, PATHINFO_EXTENSION)),
                        'date' => $file->created_at?->toIso8601String(),
                    ])->all(),
            ],
        ]);
    }

    private function draftKey(Request $request, string $token): string
    {
        return 'family_documents.'.$request->user()->id.'.'.$token;
    }

    private function token(Request $request, Alumno $student): string
    {
        $token = $request->validate(['tempId' => ['required', 'uuid']])['tempId'];
        abort_unless((string) $request->session()->get($this->draftKey($request, $token)) === (string) $student->id, 403);

        return $token;
    }

    private function completed(Alumno $student, string $token): bool
    {
        return Adjunto::where('entity', 'alumno')->where('entityId', $student->id)->where('tempId', $token)->exists();
    }

    public function create(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes)
    {
        $this->authorizeStudent($request, $alumno, $portal);
        if (! $request->has('tempId')) {
            $token = (string) Str::uuid();
            $request->session()->put($this->draftKey($request, $token), (string) $alumno->id);

            return redirect()->route('alumnos.adjunto.create', [$alumno, 'tempId' => $token]);
        }
        $token = $this->token($request, $alumno);
        if ($this->completed($alumno, $token)) {
            return redirect()->route('alumnos.show', $alumno);
        }

        return Inertia::render('family/StudentAttachments', $this->page($request, $alumno, $portal, $themes) + [
            'upload' => [
                'studentId' => (string) $alumno->id, 'tempId' => $token,
                'submitUrl' => route('alumnos.adjunto.store', $alumno),
                'backUrl' => route('alumnos.show', $alumno),
            ],
        ]);
    }

    public function store(Request $request, Alumno $alumno, FamilyPortal $portal)
    {
        $responsible = $this->authorizeStudent($request, $alumno, $portal);
        $token = $this->token($request, $alumno);
        $written = [];
        try {
            DB::transaction(function () use ($request, $alumno, $responsible, $token, &$written) {
                Responsable::whereKey($responsible->id)->lockForUpdate()->firstOrFail();
                // Retry after a lost response must not duplicate the batch.
                if ($this->completed($alumno, $token)) {
                    return;
                }
                $request->validate([
                    'fileCount' => ['required', 'integer', 'min:1'],
                    'files' => ['required', 'array', 'min:1'],
                    'files.*' => ['required', 'file', 'max:12288', 'mimes:jpeg,jpg,png,gif,pdf,doc,docx,rtf,xls,xlsx', 'extensions:jpeg,jpg,png,gif,pdf,doc,docx,rtf,xls,xlsx'],
                ], [
                    'files.required' => 'Seleccioná al menos un archivo.',
                    'files.*.max' => 'Cada archivo puede pesar hasta 12 MB.',
                    'files.*.mimes' => 'Usá imágenes JPG, PNG o GIF, PDF, Word, Excel o RTF.',
                    'files.*.extensions' => 'Usá imágenes JPG, PNG o GIF, PDF, Word, Excel o RTF.',
                ]);
                if (count($request->file('files')) !== (int) $request->input('fileCount')) {
                    throw ValidationException::withMessages(['files' => 'No llegaron todos los archivos. Probá guardar menos archivos juntos.']);
                }
                foreach ($request->file('files') as $file) {
                    $filename = 'CommAdj_'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                    // Only these newly generated filenames may be cleaned up on failure.
                    $written[] = $filename;
                    if (! Storage::disk('public')->put($filename, $file->get())) {
                        throw ValidationException::withMessages(['files' => 'No se pudieron guardar los archivos. Reintentá.']);
                    }
                    $attachment = new Adjunto;
                    $attachment->entity = 'alumno';
                    $attachment->entityId = $alumno->id;
                    $attachment->tempId = $token;
                    $attachment->filename = $filename;
                    $attachment->originalFilename = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 200);
                    $attachment->save();
                }
            });
        } catch (Throwable $exception) {
            foreach ($written as $filename) {
                try {
                    Storage::disk('public')->delete($filename);
                } catch (Throwable $cleanup) {
                    report($cleanup);
                }
            }
            throw $exception;
        }
        $request->session()->flash('success', 'Los archivos se agregaron al legajo.');

        return response()->json(['redirectUrl' => route('alumnos.show', $alumno)]);
    }
}
