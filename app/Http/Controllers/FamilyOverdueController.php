<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\Models\Alumno;
use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyOverdueController extends Controller
{
    public function __invoke(Request $request, FamilyPortal $portal): JsonResponse
    {
        $students = $portal->responsable($request->user())->alumnos;
        $balances = $students->map(function (Alumno $student) {
            $month = $today = $overdue = 0;
            $next = null;
            // Reuse the account's existing due-date and credit-offset rules.
            EducatioCommFunctions::CC_Obtener($student, $month, $today, $overdue, $next);

            return ['studentId' => (string) $student->id, 'hasOverdue' => $overdue > 0];
        })->values()->all();

        return response()->json(['students' => $balances], 200, ['Cache-Control' => 'no-store, private']);
    }
}
