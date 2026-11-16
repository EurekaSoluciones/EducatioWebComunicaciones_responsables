<?php

namespace App\Http\Controllers;

use App\Support\SupervielleWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SupervielleWebhookController extends Controller
{
    public function __invoke(Request $request, string $hash): JsonResponse
    {
        // Laravel also matches HEAD for GET routes: never process a probe as a payment notification.
        if (! $request->isMethod('GET')) {
            return $this->reply(['status' => 'error', 'message' => 'Método no permitido.'], 405);
        }
        if (config('eureka.client_id') !== 'isi') {
            return $this->reply(['status' => 'error'], 404);
        }
        if (! config('supervielle.webhook_enabled')) {
            return $this->reply(['status' => 'error', 'message' => 'Receptor no habilitado.'], 503);
        }
        if (! Str::isUuid($hash)) {
            return $this->reply(['status' => 'error', 'message' => 'Hash inválido.'], 400);
        }
        try {
            if (config('supervielle.webhook_log_only')) {
                $receiptId = (string) Str::uuid();
                // This records delivery only. The callback's sender and payment have not been verified.
                // Do not log query strings, cookies, authorization headers or arbitrary request bodies.
                Log::channel('supervielle_webhook')->info('Aviso recibido; pago no verificado ni imputado', [
                    'receipt_id' => $receiptId,
                    'received_at' => now()->toIso8601String(),
                    'mode' => 'log_only',
                    'client' => 'isi',
                    'method' => 'GET',
                    'hash' => strtolower($hash),
                    'payment_verified' => false,
                    'payment_posted' => false,
                ]);

                return $this->reply([
                    'status' => 'success',
                    'message' => 'Notificación recibida para pruebas. Sin imputación de pago.',
                    'receipt_id' => $receiptId,
                ], 200);
            }
            if (! app(SupervielleWebhookProcessor::class)->process($hash)) {
                return $this->reply(['status' => 'error', 'message' => 'Publicación no registrada.'], 400);
            }

            return $this->reply(['status' => 'success', 'message' => 'Solicitud procesada correctamente'], 200);
        } catch (Throwable $exception) {
            // No raw exception/HTTP bodies: they may contain credentials or database details.
            Log::warning('Supervielle: notificación no procesada', ['hash' => $hash, 'exception_type' => get_class($exception)]);

            return $this->reply(['status' => 'error', 'message' => 'No se pudo procesar la notificación. Reintentar.'], 503);
        }
    }

    private function reply(array $body, int $status): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store, private']);
    }
}
