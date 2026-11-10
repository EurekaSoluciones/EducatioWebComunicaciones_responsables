<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use App\Support\SupervielleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class FamilyFinanceController extends FamilySentController
{
    private function authorizeStudent(Request $request, Alumno $student, FamilyPortal $portal): void
    {
        abort_unless(EureFunctions::esResponsableDeAlumno($portal->responsable($request->user()), $student), 403);
    }

    public function index(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $this->authorizeStudent($request, $alumno, $portal);
        $month = $today = $overdue = 0;
        $next = Carbon::create(2050, 1, 1, 12);
        $items = EducatioCommFunctions::CC_Obtener($alumno, $month, $today, $overdue, $next);
        $now = EureFunctions::hoy();
        $methods = [];
        if (SupervielleClient::enabled()) {
            $methods[] = ['id' => 'supervielle', 'name' => 'Supervielle', 'logo' => null];
        }
        if (config('payments.pagostic')) {
            $methods[] = ['id' => 'pagostic', 'name' => 'PagosTIC', 'logo' => asset('assets/images/PTIC.png')];
        }
        if (config('payments.mercadopago')) {
            $methods[] = ['id' => 'mercadopago', 'name' => 'Mercado Pago', 'logo' => asset('assets/images/MP_ImagoTipo.png')];
        }

        return Inertia::render('family/Account', $this->page($request, $alumno, $portal, $themes) + [
            'account' => [
                'studentId' => (string) $alumno->id, 'success' => $request->session()->get('success'),
                'totals' => ['month' => (float) ($month + $today), 'today' => (float) $today, 'overdue' => (float) $overdue],
                'monthNotice' => $next >= Carbon::create(2049, 1, 1) ? 'No hay vencimientos en este mes'
                    : ($next > $now ? 'Tenés tiempo para pagar hasta el '.$next->format('d/m/Y') : 'Consultá los vencimientos en el detalle.'),
                'payment' => [
                    'url' => route('cc.pagar'), 'methods' => $methods,
                    'error' => $request->session()->get('errors')?->first('importe')
                        ?: $request->session()->get('errors')?->first('metodo'),
                    // No currency symbol or grouping: the existing POST parser expects a decimal amount.
                    'suggestedAmount' => (string) $request->old('importe', number_format($month + $overdue + $today, 2, ',', '')),
                ],
                'rows' => collect($items)->values()->map(function ($item, $index) use ($now) {
                    $end = $item->Fecha_Venc->copy()->endOfMonth();
                    $start = $item->Fecha_Venc->copy()->startOfMonth();
                    // Exact precedence from the Blade. Do not reinterpret negative balances or month-end rules.
                    $state = $item->Saldo == 0 ? 'paid'
                        : ($end < $now ? 'overdue'
                            : ($item->Fecha_Venc->isSameDay($now) || $end->isSameDay($now) ? 'today'
                                : ($start > $now && $end < $now ? 'month' : 'upcoming')));

                    return [
                        'id' => (string) $index, 'reference' => (string) ($item->cod_factura ?? ''),
                        'description' => (string) $item->Descripcion, 'installment' => (string) $item->Nro_Cuota,
                        'date' => $item->Fecha_Venc->toIso8601String(), 'dateLabel' => $item->Fecha_Venc->format('d/m/Y'),
                        'amount' => $item->Monto, 'balance' => $item->Saldo, 'state' => $state,
                    ];
                })->all(),
            ],
        ]);
    }

    public function payments(Request $request, Alumno $alumno, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        $this->authorizeStudent($request, $alumno, $portal);
        $items = EducatioCommFunctions::Pagos_Obtener($alumno, null, null);

        return Inertia::render('family/Payments', $this->page($request, $alumno, $portal, $themes) + [
            'payments' => [
                'studentId' => (string) $alumno->id, 'success' => $request->session()->get('success'),
                'rows' => collect($items)->values()->map(fn ($item, $index) => [
                    'id' => (string) $index, 'reference' => (string) $item->Nro_Comprobante,
                    'method' => (string) $item->Forma_Pago, 'amount' => $item->Total,
                    'date' => $item->Fecha_Pago?->toIso8601String(), 'dateLabel' => $item->Fecha_Pago?->format('d/m/Y'),
                    'pending' => (bool) $item->Pendiente,
                    // Legacy links the receipt number even when pending; only the PDF button is conditional.
                    'url' => route('pagos.descargar', $item->cod_recibo),
                ])->all(),
            ],
        ]);
    }
}
