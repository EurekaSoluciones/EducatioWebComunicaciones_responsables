<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupervielleWebhookProcessor
{
    public function __construct(private SupervielleClient $client, private SuperviellePaymentPoster $poster) {}

    public function process(string $hash): bool
    {
        // Serialize callbacks for one publication, including the bank read, so snapshots cannot overtake each other.
        // No transaction retries: the future stored procedure must participate in this same transaction.
        return DB::transaction(function () use ($hash) {
            $publication = DB::table('web_supervielle_publications')->where('hash', strtolower($hash))
                ->where('client', 'isi')->where('agreement', (string) config('supervielle.agreement'))
                ->lockForUpdate()->first();
            if (! $publication) {
                return false;
            }
            $state = $this->client->publicationStatus($hash);
            $identifiers = $state['identificadores'] ?? null;
            if ((string) ($state['convenio'] ?? '') !== $publication->agreement
                || ! is_array($identifiers)
                || ! in_array('cod_alumno|'.$publication->student_id, $identifiers, true)
                || ! in_array('uuid|'.$publication->attempt_id, $identifiers, true)
                || $this->amount($state['importe1'] ?? null) !== $this->amount($publication->amount)
                || ! isset($state['pagos']) || ! is_array($state['pagos']) || ! array_is_list($state['pagos'])) {
                throw new RuntimeException('El estado del banco no corresponde a la publicación registrada.');
            }

            $seen = [];
            foreach ($state['pagos'] as $raw) {
                $payment = $this->payment($raw);
                if (isset($seen[$payment['payment_id']])) {
                    throw new RuntimeException('El banco devolvió identificadores de pago repetidos.');
                }
                $seen[$payment['payment_id']] = true;
                $this->record($publication, $payment);
            }

            return true;
        });
    }

    private function record(object $publication, array $payment): void
    {
        $identity = ['client' => 'isi', 'agreement' => $publication->agreement, 'payment_id' => $payment['payment_id']];
        $query = DB::table('web_supervielle_payments')->where($identity);
        $existing = (clone $query)->lockForUpdate()->first();
        if ($existing && ($existing->attempt_id !== $publication->attempt_id
            || ($existing->posted_at && ($this->amount($existing->amount) !== $payment['amount']
            || CarbonImmutable::parse($existing->paid_at)->format('Y-m-d H:i:s') !== $payment['paid_at']
            || (int) $existing->payment_method !== $payment['payment_method'])))) {
            throw new RuntimeException('El identificador del pago tiene datos inconsistentes.');
        }
        $review = (bool) ($existing->needs_review ?? false)
            || in_array($payment['bank_status'], [4, 5], true)
            || $payment['amount'] !== $this->amount($publication->amount);
        $postedAt = $existing->posted_at ?? null;
        if (! $postedAt && ! $review && in_array($payment['bank_status'], [1, 2], true)) {
            $this->poster->post($publication, $payment);
            $postedAt = now();
        }
        $values = array_merge($payment, [
            'attempt_id' => $publication->attempt_id, 'needs_review' => $review,
            'posted_at' => $postedAt, 'updated_at' => now(),
        ]);
        if ($existing) {
            $query->update($values);
        } else {
            DB::table('web_supervielle_payments')->insert(array_merge($identity, $values, ['created_at' => now()]));
        }
    }

    private function payment(mixed $raw): array
    {
        if (! is_array($raw)
            || ! preg_match('/\A[1-9][0-9]{0,18}\z/', (string) ($raw['id_pago'] ?? ''))
            || ! in_array($raw['id_estado_pago'] ?? null, [1, 2, 3, 4, 5], true)
            || ! in_array($raw['id_medio_pago'] ?? null, [1, 2, 3], true)) {
            throw new RuntimeException('Datos de pago incompletos o desconocidos.');
        }
        $date = $raw['fecha'] ?? '';
        if (! is_string($date) || ! preg_match('/\A(\d{4})-(\d{2})-(\d{2})T([01]\d|2[0-3]):([0-5]\d):([0-5]\d)(?:\.\d{1,6})?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)?\z/', $date, $parts)
            || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new RuntimeException('Fecha de pago inválida.');
        }

        return [
            'payment_id' => (string) $raw['id_pago'], 'bank_status' => $raw['id_estado_pago'],
            'payment_method' => $raw['id_medio_pago'], 'amount' => $this->amount($raw['importe'] ?? null),
            // The manual's timestamps have no offset; interpret those in the bank's local timezone.
            'paid_at' => CarbonImmutable::parse($date, 'America/Argentina/Buenos_Aires')
                ->setTimezone('America/Argentina/Buenos_Aires')->format('Y-m-d H:i:s'),
        ];
    }

    private function amount(mixed $value): string
    {
        if ((! is_string($value) && ! is_int($value) && ! is_float($value))
            || ! preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/', (string) $value)) {
            throw new RuntimeException('Importe inválido en el estado del banco.');
        }
        $parts = explode('.', (string) $value);
        $cents = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents <= 0) {
            throw new RuntimeException('El importe del pago debe ser positivo.');
        }

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
