<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SupervielleClient
{
    public static function enabled(): bool
    {
        return (bool) config('supervielle.enabled');
    }

    // Explicit CLI diagnostic: authenticate only, never publish a debt or return the token.
    public function diagnoseAuthentication(): void
    {
        $this->validateConfiguration();
        $this->token();
    }

    public function publish(array $attempt): array
    {
        $this->validateConfiguration();
        $token = $this->token();
        $body = [
            'tipo_publicacion' => 'Deuda',
            'convenio' => (int) config('supervielle.agreement'),
            'modalidad' => 'Cerrada',
            'importe1' => (float) $attempt['amount'],
            'fecha_vencimiento1' => $attempt['due_date'],
            'nombre' => $attempt['student_name'],
            'descripcion' => 'Pago de cuenta corriente - '.$attempt['student_name'],
            'identificadores' => [
                'cod_alumno|'.$attempt['student_id'],
                'cod_responsable|'.$attempt['responsible_id'],
                'uuid|'.$attempt['id'],
            ],
            // No mail/celular: publishing must not send bank emails or SMS.
            // Omit id_publicacion so the bank assigns its numeric identifier.
        ];

        for ($try = 0; $try < 2; $try++) {
            try {
                $response = $this->http()->withToken($token)->withHeaders(array_filter([
                    'X-Ibm-Client-Id' => config('supervielle.ibm_client_id'),
                    'X-Forwarded-Host' => config('supervielle.forwarded_host'),
                    'X-Forwarded-For' => config('supervielle.forwarded_for'),
                ]))->post(config('supervielle.publication_url'), $body);
            } catch (Throwable) {
                // A timeout is not proof that the publication failed. Never retry it automatically.
                throw $this->uncertain();
            }

            if ($response->status() === 401 && $try === 0) {
                $token = $this->token();

                continue;
            }
            if (in_array($response->status(), [400, 401, 403, 404, 422, 429], true)) {
                throw new SupervielleException('Supervielle no pudo generar el enlace. Revisá el importe o intentá más tarde. Si persiste, comunicate con la institución.');
            }
            if ($response->status() !== 201) {
                throw $this->uncertain();
            }

            $data = $response->json();
            $hash = is_array($data) ? ($data['hash'] ?? null) : null;
            $url = is_array($data) ? ($data['link_de_pago'] ?? null) : null;
            if (! is_string($hash) || ! Str::isUuid($hash) || ! is_string($url)
                || ! empty($data['errores']) || ! $this->validCheckoutUrl(trim($url), $hash)) {
                throw $this->uncertain();
            }

            return ['hash' => $hash, 'url' => trim($url)];
        }

        throw $this->uncertain();
    }

    public function publicationStatus(string $hash): array
    {
        if (! Str::isUuid($hash)) {
            throw new SupervielleException('Identificador de publicación inválido.');
        }
        $this->validateConfiguration();
        for ($try = 0; $try < 2; $try++) {
            $token = $this->token(config('supervielle.consult_scope'));
            try {
                $response = $this->http()->withToken($token)->withHeaders(array_filter([
                    'X-Ibm-Client-Id' => config('supervielle.ibm_client_id'),
                    'X-Forwarded-Host' => config('supervielle.forwarded_host'),
                    'X-Forwarded-For' => config('supervielle.forwarded_for'),
                ]))->get(rtrim(config('supervielle.publication_url'), '/').'/'.$hash.'/estado');
            } catch (Throwable) {
                throw new SupervielleException('No pudimos consultar el estado en Supervielle.');
            }
            if ($response->status() === 401 && $try === 0) {
                continue;
            }
            $data = json_decode($response->body(), true, 512, JSON_BIGINT_AS_STRING);
            if ($response->status() !== 200 || ! is_array($data) || ! empty($data['errores'])) {
                throw new SupervielleException('Supervielle no devolvió un estado válido.');
            }

            return $data;
        }
        throw new SupervielleException('No pudimos consultar el estado en Supervielle.');
    }

    private function token(?string $scope = null): string
    {
        $diagnostics = ['stage' => 'token'];
        try {
            $response = $this->http()->asForm()->post(config('supervielle.token_url'), [
                'username' => config('supervielle.username'),
                'password' => config('supervielle.password'),
                'client_id' => config('supervielle.client_id'),
                'client_secret' => config('supervielle.client_secret'),
                'grant_type' => 'password',
                'scope' => $scope ?? config('supervielle.scope'),
            ]);
            $diagnostics['http_status'] = $response->status();
            $oauthError = $response->json('error');
            if (in_array($oauthError, ['invalid_grant', 'invalid_client', 'invalid_scope', 'invalid_request', 'unauthorized_client', 'unsupported_grant_type', 'access_denied', 'server_error', 'temporarily_unavailable'], true)) {
                $diagnostics['oauth_error'] = $oauthError;
            }
            $token = $response->json('access_token');
            if ($response->status() === 200 && is_string($token) && trim($token) !== '') {
                return $token;
            }
            if ($response->status() === 200) {
                $diagnostics['reason'] = 'missing_access_token';
            }
        } catch (Throwable $exception) {
            // Record only the numeric transport code; raw exceptions can contain secrets.
            $diagnostics['exception_type'] = get_class($exception);
            if (preg_match('/cURL error (\d+):/', $exception->getMessage(), $matches)) {
                $diagnostics['curl_code'] = (int) $matches[1];
            }
        }

        Log::warning('Supervielle: fallo al obtener el token', $diagnostics);

        throw new SupervielleException('No pudimos conectar con Supervielle. Intentá más tarde.', false, $diagnostics);
    }

    private function http(): PendingRequest
    {
        $options = ['allow_redirects' => false];
        if (filled(config('supervielle.ca_bundle'))) {
            $options['verify'] = config('supervielle.ca_bundle');
        }
        if (filled(config('supervielle.certificate'))) {
            $options['cert'] = config('supervielle.certificate');
            $options['ssl_key'] = filled(config('supervielle.private_key_password'))
                ? [config('supervielle.private_key'), config('supervielle.private_key_password')]
                : config('supervielle.private_key');
        }

        return Http::acceptJson()->connectTimeout(10)
            ->timeout(max(1, min(120, config('supervielle.timeout'))))->withOptions($options);
    }

    private function validateConfiguration(): void
    {
        $caBundle = config('supervielle.ca_bundle');
        if (filled($caBundle) && (! is_string($caBundle) || ! is_file($caBundle) || ! is_readable($caBundle))) {
            throw new SupervielleException('No se pudo leer el archivo de autoridades certificadoras configurado para Supervielle.');
        }
        foreach (['token_url', 'publication_url', 'username', 'password', 'client_id', 'client_secret', 'ibm_client_id', 'agreement'] as $key) {
            if (blank(config('supervielle.'.$key))) {
                throw new SupervielleException('El pago con Supervielle todavía no está disponible. Comunicate con la institución.');
            }
        }
        foreach (['token_url', 'publication_url'] as $key) {
            $url = config('supervielle.'.$key);
            $localTest = app()->environment('testing') && parse_url($url, PHP_URL_HOST) === '127.0.0.1';
            if (! filter_var($url, FILTER_VALIDATE_URL) || (! $localTest && parse_url($url, PHP_URL_SCHEME) !== 'https')) {
                throw new SupervielleException('La conexión de Supervielle no está configurada correctamente.');
            }
        }
        if (! ctype_digit((string) config('supervielle.agreement')) || (int) config('supervielle.agreement') <= 0
            || config('supervielle.publication_type') !== 'Deuda' || config('supervielle.modality') !== 'Cerrada') {
            throw new SupervielleException('El convenio de Supervielle no está configurado correctamente.');
        }
        if (filled(config('supervielle.certificate')) || filled(config('supervielle.private_key'))) {
            foreach (['certificate', 'private_key'] as $key) {
                if (! is_string(config('supervielle.'.$key)) || ! is_readable(config('supervielle.'.$key))) {
                    throw new SupervielleException('La conexión segura de Supervielle no está configurada correctamente.');
                }
            }
        }
    }

    private function validCheckoutUrl(string $url, string $hash): bool
    {
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) && is_array($parts)
            && ($parts['scheme'] ?? '') === 'https'
            && in_array(strtolower($parts['host'] ?? ''), config('supervielle.checkout_hosts'), true)
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment'])
            && (! isset($parts['port']) || $parts['port'] === 443)
            && ($parts['path'] ?? '') === '/publicacion/'.$hash;
    }

    private function uncertain(): SupervielleException
    {
        return new SupervielleException('No pudimos confirmar la generación del enlace. Para evitar duplicados, no volveremos a enviar esta solicitud. Comunicate con la institución antes de generar otra.', true);
    }
}
