<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyAttendanceController extends FamilySentController
{
    public function index(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $alumno), 403);

        // Keep the legacy procedure, weights and rolling month/seven-day calculations.
        $attendance = EducatioCommFunctions::Inasistencias_Obtener($alumno);

        return Inertia::render('family/Attendance', $this->page($request, $alumno, $portal, $themes) + [
            'attendance' => [
                'studentId' => (string) $alumno->id,
                'schoolYear' => (string) EureFunctions::al(),
                'success' => $request->session()->get('success'),
                'totals' => [
                    'year' => (float) $attendance->cantidadTotal,
                    'month' => (float) $attendance->cantidadMes,
                    'week' => (float) $attendance->cantidadSemana,
                ],
                'rows' => collect($attendance->lista)->values()->map(fn ($row, $index) => [
                    // The procedure does not require a stable ID; preserve every row, including duplicates.
                    'id' => (string) $index,
                    'date' => $row->fechaCarbon->toIso8601String(),
                    'dateLabel' => $row->fechaCarbon->format('d/m/Y'),
                    'type' => (string) ($row->descripcion ?? ''),
                    'weight' => (float) $row->imputar,
                    'notes' => (string) ($row->observaciones ?? ''),
                ])->all(),
            ],
        ]);
    }
}
