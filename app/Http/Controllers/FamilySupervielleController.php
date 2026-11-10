<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Support\EurekaTheme;
use App\Support\FamilyPortal;
use App\Support\SupervielleClient;
use App\Support\SupervielleException;
use App\Support\SuperviellePublicationStore;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FamilySupervielleController extends FamilySentController
{
    private const ZONE = 'America/Argentina/Buenos_Aires';

    public function prepare(Request $request, FamilyPortal $portal): RedirectResponse
    {
        abort_unless(SupervielleClient::enabled(), 403);
        $data = $request->validate([
            'alumno' => ['required', 'integer'],
            'importe' => ['required', 'string', 'regex:/\A\d{1,10}(?:[.,]\d{1,2})?\z/'],
            'metodo' => ['required', 'in:supervielle'],
        ], [
            'importe.*' => 'Ingresá un importe mayor que cero, con hasta dos decimales y sin puntos de miles.',
            'metodo.*' => 'No recibimos un medio de pago válido. Volvé a seleccionar Pagar con Supervielle.',
        ]);
        $student = Alumno::findOrFail($data['alumno']);
        $responsible = $portal->responsable($request->user());
        abort_unless(EureFunctions::esResponsableDeAlumno($responsible, $student), 403);
        $parts = explode('.', str_replace(',', '.', $data['importe']));
        $cents = ((int) $parts[0]) * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents <= 0) {
            throw ValidationException::withMessages(['importe' => 'Ingresá un importe mayor que cero.']);
        }
        $amount = intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $due = CarbonImmutable::now(self::ZONE)->addDays(max(1, min(30, config('supervielle.expires_after_days'))))->startOfDay();
        // Reopening the same amount/student on this day reuses the attempt, including its outcome.
        $fingerprint = hash('sha256', implode('|', [$request->user()->id, $student->id, $amount, $due->toDateString()]));
        $indexKey = 'supervielle:'.config('eureka.client_id').':index:'.$fingerprint;
        try {
            $id = Cache::lock($indexKey.':lock', 10)->block(3, function () use ($indexKey, $request, $student, $responsible, $amount, $due) {
                $id = Cache::get($indexKey);
                if (! is_string($id) || ! Cache::has($this->key($id))) {
                    $id = (string) Str::uuid();
                    $attempt = [
                        'id' => $id, 'user_id' => (string) $request->user()->id,
                        'student_id' => (string) $student->id, 'responsible_id' => (string) $responsible->id,
                        'student_name' => trim($student->Nombre.' '.$student->Apellido),
                        'amount' => $amount, 'due_date' => $due->toDateString(),
                        'expires_at' => $due->addDays(2)->timestamp,
                        'state' => 'ready', 'error' => null,
                    ];
                    $this->save($attempt);
                    abort_unless(Cache::put($indexKey, $id, $due->addDays(2)), 503, 'No pudimos guardar la solicitud. Intentá más tarde.');
                }

                return $id;
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['importe' => 'Ya estamos preparando esta solicitud. Intentá nuevamente en unos instantes.']);
        }

        return redirect()->route('family.supervielle.show', $id);
    }

    public function confirmation(Request $request, string $attempt, FamilyPortal $portal, EurekaTheme $themes): Response
    {
        [$data, $student] = $this->authorizedAttempt($request, $attempt, $portal);
        $expired = $this->expired($data);

        return Inertia::render('family/SuperviellePayment', $this->page($request, $student, $portal, $themes) + [
            'payment' => [
                'studentId' => (string) $student->id,
                'amount' => (float) $data['amount'],
                'dueDate' => $data['due_date'],
                'dueLabel' => CarbonImmutable::parse($data['due_date'], self::ZONE)->format('d/m/Y'),
                'submitUrl' => route('family.supervielle.publish', $attempt),
                'backUrl' => route('cc.indexA', $student),
                'canContinue' => ! $expired && empty($data['error']) && in_array($data['state'], ['ready', 'published'], true),
                'published' => $data['state'] === 'published',
                'error' => $expired ? 'Esta solicitud venció. Volvé a la cuenta corriente para revisar el importe y preparar una nueva.'
                    : ($data['state'] === 'publishing' ? 'La solicitud está en proceso. Esperá unos instantes y recargá esta página antes de continuar.' : $data['error']),
            ],
        ]);
    }

    public function publish(Request $request, string $attempt, FamilyPortal $portal, SupervielleClient $client): RedirectResponse
    {
        $this->authorizedAttempt($request, $attempt, $portal);
        $lock = Cache::lock($this->key($attempt).':lock', 4 * (max(1, min(120, config('supervielle.timeout'))) + 10) + 30);
        if (! $lock->get()) {
            return redirect()->route('family.supervielle.show', $attempt);
        }
        try {
            [$data] = $this->authorizedAttempt($request, $attempt, $portal);
            if ($this->expired($data) || ! empty($data['error'])) {
                return redirect()->route('family.supervielle.show', $attempt);
            }
            if ($data['state'] === 'published') {
                return redirect()->away($data['url']);
            }
            if ($data['state'] !== 'ready') {
                return redirect()->route('family.supervielle.show', $attempt);
            }
            // Persist before sending: a lost response/crashed worker must not trigger a second publication.
            $data['state'] = 'publishing';
            $this->save($data);
            try {
                if (config('supervielle.webhook_enabled')) {
                    app(SuperviellePublicationStore::class)->begin($data);
                }
                $result = $client->publish($data);
                if (config('supervielle.webhook_enabled')) {
                    app(SuperviellePublicationStore::class)->complete($data['id'], $result['hash']);
                }
            } catch (SupervielleException $exception) {
                $data['state'] = $exception->uncertain ? 'uncertain' : 'ready';
                $data['error'] = $exception->getMessage();
                $this->save($data);

                return redirect()->route('family.supervielle.show', $attempt);
            }
            $data = array_merge($data, $result, ['state' => 'published', 'error' => null]);
            $this->save($data);

            return redirect()->away($data['url']);
        } finally {
            $lock->release();
        }
    }

    private function authorizedAttempt(Request $request, string $id, FamilyPortal $portal): array
    {
        abort_unless(SupervielleClient::enabled(), 403);
        $data = Cache::get($this->key($id));
        abort_unless(is_array($data), 410, 'La solicitud ya no está disponible. Volvé a la cuenta corriente.');
        abort_unless($data['user_id'] === (string) $request->user()->id, 403);
        $student = Alumno::findOrFail($data['student_id']);
        abort_unless(EureFunctions::esResponsableDeAlumno($portal->responsable($request->user()), $student), 403);

        return [$data, $student];
    }

    private function expired(array $attempt): bool
    {
        $today = CarbonImmutable::now(self::ZONE)->toDateString();

        // Existing links can be opened on their due day; new publications require a future date.
        return $attempt['state'] === 'published' ? $attempt['due_date'] < $today : $attempt['due_date'] <= $today;
    }

    private function key(string $id): string
    {
        return 'supervielle:'.config('eureka.client_id').':attempt:'.$id;
    }

    private function save(array $attempt): void
    {
        abort_unless(Cache::put($this->key($attempt['id']), $attempt, CarbonImmutable::createFromTimestamp($attempt['expires_at'])),
            503, 'No pudimos guardar el estado de la solicitud. Comunicate con la institución antes de generar otra.');
    }
}
