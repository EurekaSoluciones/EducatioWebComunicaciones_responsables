<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SuperviellePublicationStore
{
    public function begin(array $attempt): void
    {
        // Insert before contacting the bank; an existing durable attempt must not be republished.
        DB::table('web_supervielle_publications')->insert([
            'attempt_id' => $attempt['id'], 'hash' => null,
            'client' => (string) config('eureka.client_id'), 'agreement' => (string) config('supervielle.agreement'),
            'student_id' => (string) $attempt['student_id'], 'amount' => $attempt['amount'],
            'due_date' => $attempt['due_date'], 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function complete(string $attemptId, string $hash): void
    {
        $updated = DB::table('web_supervielle_publications')->where('attempt_id', $attemptId)
            ->whereNull('hash')->update(['hash' => strtolower($hash), 'updated_at' => now()]);
        if ($updated !== 1) {
            throw new RuntimeException('No se pudo asociar la publicación al intento.');
        }
    }
}
