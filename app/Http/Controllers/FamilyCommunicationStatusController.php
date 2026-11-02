<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\ComunicacionDestinatario;
use App\Models\ComunicacionE;
use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyCommunicationStatusController extends Controller
{
    public function __invoke(Request $request, Alumno $alumno, string $kind, FamilyPortal $portal): JsonResponse
    {
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $alumno), 403);
        if ($kind === 'received') {
            $states = ComunicacionDestinatario::where('Cod_Responsable', $responsible->id)->where('Cod_Alumno', $alumno->id)
                ->select('comunicacion_id')->selectRaw('MAX(CASE WHEN leido = 0 THEN 1 ELSE 0 END) as unread_count')
                ->groupBy('comunicacion_id')->get()->map(fn ($row) => [
                    'id' => (string) $row->comunicacion_id, 'unread' => $row->unread_count > 0,
                ])->all();
        } else {
            $states = ComunicacionE::deResponsable($responsible)->deAlumno($alumno)
                ->get(['id', 'fhLeido', 'fhRespuesta', 'fhRespuestaLeida'])->map(fn ($row) => [
                    'id' => (string) $row->id,
                    'readAt' => $row->fhLeido?->toIso8601String(),
                    'answeredAt' => $row->fhRespuesta?->toIso8601String(),
                    'replyUnread' => $row->fhRespuesta !== null && $row->fhRespuestaLeida === null,
                ])->all();
        }

        return response()->json(['userId' => (string) $request->user()->id, 'states' => $states])
            ->header('Cache-Control', 'private, no-store');
    }
}
