<?php

namespace App\Support;

use App\EureLib\EducatioCommFunctions;
use App\Models\Alumno;
use App\Models\AlumnoWeb;
use App\Models\Cartelera;
use App\Models\Comunicacion;
use App\Models\ComunicacionE;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FamilyPortal
{
    public static function receivedUrl(Alumno $student): string
    {
        // Entry links choose the initial period; the listing itself has no implicit filters.
        return route('comunicaciones.indexA', [
            'alumno' => $student,
            'desde' => now()->startOfYear()->subMonths(2)->toDateString(),
        ]);
    }

    public function responsable(User $user): Responsable
    {
        abort_unless($user->tipo === 'Responsable', 403, 'Este portal es para responsables.');
        $responsable = $user->responsable;
        abort_unless($responsable, 403, 'Tu usuario no tiene un responsable vinculado.');

        return $responsable;
    }

    public function generalBoard(): ?Cartelera
    {
        return Cartelera::where('tipo', 'GENERAL')->where('activa', true)->first();
    }

    public function mustReadBoard(User $user, ?Cartelera $board): bool
    {
        return $board !== null && ($user->fh_visualizacion_cartelera_general === null
            || $user->fh_visualizacion_cartelera_general < $board->updated_at);
    }

    public function boardPreviews(): array
    {
        return Cartelera::where('activa', true)->orderBy('id')
            ->get(['id', 'nombre', 'cartelera'])->map(fn ($board) => [
                'id' => (string) $board->id,
                'title' => $board->nombre,
                'html' => (string) $board->cartelera,
                'url' => route('carteleras.show', $board),
            ])->all();
    }

    public function unread(Responsable $responsable, ?Collection $students = null): array
    {
        $students ??= $responsable->alumnos;
        $byStudent = $students->map(fn (Alumno $student) => [
            'studentId' => (string) $student->id,
            'received' => Comunicacion::NoLeidosPorAlumno(true, $responsable, $student)->count(),
            'replies' => ComunicacionE::deResponsable($responsable)->deAlumno($student)->RespuestaSinLeer()->count(),
        ])->values();

        return [
            // A communication addressed to siblings counts once in the family total.
            'received' => Comunicacion::NoLeidos(true, $responsable)->count(),
            'replies' => $byStudent->sum('replies'),
            'byStudent' => $byStudent->all(),
        ];
    }

    public function home(User $user, Responsable $responsable, ?Cartelera $board, ?Alumno $profileStudent = null): array
    {
        $students = $responsable->alumnos;
        // The existing profile permits related students who are no longer active.
        if ($profileStudent && ! $students->contains('Cod_Alumno', $profileStudent->id)) {
            abort_unless(in_array($responsable->id, [$profileStudent->Responsable1, $profileStudent->Responsable2]), 403);
            $students->push($profileStudent);
        }
        $students->loadMissing(['grupo.ECurso', 'grupo.EDivision', 'grupo.ETurno', 'datos',
            'EResponsable1', 'EResponsable2', 'ETipoResponsable1', 'ETipoResponsable2']);

        // Legacy photo getters create records. Only read existing images here.
        $images = AlumnoWeb::whereIn('Cod_Alumno', $students->modelKeys())->pluck('avatarImg', 'Cod_Alumno');
        $guardianIds = $students->flatMap(fn (Alumno $student) => [$student->Responsable1, $student->Responsable2])
            ->filter()->unique()->values();
        $guardianImages = User::where('tipo', 'Responsable')->whereIn('Cod_Responsable', $guardianIds)
            ->get(['Cod_Responsable', 'avatarImg'])->keyBy('Cod_Responsable');

        return [
            'user' => [
                'id' => (string) $user->id,
                'name' => trim($responsable->nombreYApellido),
                'firstName' => $responsable->Nombre,
                'avatar' => $this->image($user->avatarImg),
                'profileUrl' => route('responsables.showLogged'),
                'editUrl' => route('responsables.edit', $responsable),
                'passwordUrl' => route('auth.password'),
            ],
            'students' => $students->map(function (Alumno $student) use ($images, $guardianImages) {
                $guardians = collect([1, 2])->map(function (int $slot) use ($student, $guardianImages) {
                    $guardian = $student->{'EResponsable'.$slot};
                    if (! $guardian) {
                        return null;
                    }

                    return [
                        'id' => (string) $guardian->id,
                        'name' => trim($guardian->nombreYApellido),
                        'role' => $student->{'ETipoResponsable'.$slot.'Descripcion'},
                        'avatar' => $this->image($guardianImages->get($guardian->id)?->avatarImg),
                        'url' => route('responsables.show', $guardian),
                    ];
                })->filter()->values()->all();

                return [
                    'id' => (string) $student->id,
                    'name' => trim($student->nombreYApellido),
                    'avatar' => $this->image($images->get($student->id)),
                    'course' => $student->grupo?->ECurso?->Descripcion ?? '',
                    'division' => $student->grupo?->EDivision?->Descripcion ?? '',
                    'shift' => $student->grupo?->ETurno?->Descripcion ?? '',
                    'academicContact' => $student->datos?->responsableAcademico ?? '',
                    'guardians' => $guardians,
                    'enrollment' => EducatioCommFunctions::MensajeBloqueoRematriculacion($student),
                    'links' => [
                        'profile' => route('alumnos.show', $student),
                        'photo' => route('alumnos.editPic', $student),
                        'received' => self::receivedUrl($student),
                        'sent' => route('comunicaciones.e.indexA', $student),
                        'payments' => route('pagos.indexA', $student),
                        'account' => route('cc.indexA', $student),
                        'documents' => route('informes.indexDocumentos', $student),
                        'attendance' => route('asistencias.indexA', $student),
                        'enrollment' => route('alumnos.rematriculacion', $student),
                    ],
                ];
            })->values()->all(),
            'labels' => [
                'payments' => EducatioCommFunctions::MENU_leyenda_pagos(),
                'account' => EducatioCommFunctions::MENU_leyenda_cuentaCorriente(),
                'documents' => EducatioCommFunctions::MENU_leyenda_Boletin(),
            ],
            'boards' => Cartelera::where('activa', true)->get(['id', 'nombre'])->map(fn ($item) => [
                'id' => (string) $item->id, 'name' => $item->nombre, 'url' => route('carteleras.show', $item),
            ])->all(),
            'board' => $board ? [
                'title' => $board->nombre,
                'html' => (string) $board->cartelera,
                'url' => route('carteleras.show', $board),
            ] : null,
            'notifications' => $this->unread($responsable, $students),
        ];
    }

    private function image(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
