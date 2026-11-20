<?php

namespace App\Http\Controllers;

use App\Support\SupervielleWebhookLog;
use App\Support\SupervielleWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SupervielleWebhookController extends Controller
{
    public function __invoke(Request $request, ?string $hash = null): JsonResponse
    {
        $receiptId = (string) Str::uuid();
        $logOnly = (bool) config('supervielle.webhook_log_only');
        $rejection = null;
        if (! $request->isMethod('GET')) {
            $rejection = [405, 'Método no permitido.'];
        } elseif (config('eureka.client_id') !== 'isi') {
            $rejection = [404, 'Receptor no disponible para esta institución.'];
        } elseif (! config('supervielle.webhook_enabled')) {
            $rejection = [503, 'Receptor no habilitado.'];
        } elseif (! $logOnly && ! Str::isUuid($hash ?? '')) {
            $rejection = [400, 'Hash inválido.'];
        }
        try {
            // Record arrivals before returning a rejection or attempting payment processing.
            Log::channel('supervielle_webhook')->info('Solicitud recibida; pago no verificado ni imputado', [
                'receipt_id' => $receiptId,
                'received_at' => now()->toIso8601String(),
                'mode' => $logOnly ? 'log_only' : 'processing',
                'client' => config('eureka.client_id'),
                'method' => $request->method(),
                'hash' => $hash === null ? null : mb_substr($hash, 0, 2048),
                'hash_truncated' => $hash !== null && mb_strlen($hash) > 2048,
                'http_status' => $rejection[0] ?? ($logOnly ? 200 : null),
                'result' => $rejection[1] ?? ($logOnly ? 'received_only' : 'pending_processing'),
                'payment_verified' => false,
                'payment_posted' => false,
            ] + SupervielleWebhookLog::requestContext($request));
            if ($rejection !== null) {
                return $this->reply(['status' => 'error', 'message' => $rejection[1], 'receipt_id' => $receiptId], $rejection[0]);
            }
            if ($logOnly) {
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
            Log::warning('Supervielle: notificación no procesada', ['receipt_id' => $receiptId, 'exception_type' => get_class($exception)]);

            return $this->reply(['status' => 'error', 'message' => 'No se pudo procesar la notificación. Reintentar.'], 503);
        }
    }

    private function reply(array $body, int $status): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store, private']);
    }
}
