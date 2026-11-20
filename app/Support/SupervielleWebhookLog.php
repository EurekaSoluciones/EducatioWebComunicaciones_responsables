<?php

namespace App\Support;

use Illuminate\Http\Request;

class SupervielleWebhookLog
{
    public static function requestContext(Request $request): array
    {
        $raw = $request->getContent();
        $body = [];
        if (strlen($raw) > 16384) {
            $body = '[omitted: body exceeds 16 KiB]';
        } elseif ($raw !== '') {
            if ($request->isJson()) {
                $body = json_decode($raw, true);
                if (! is_array($body)) {
                    $body = '[omitted: unstructured or invalid JSON]';
                }
            } elseif ($request->request->count() > 0) {
                $body = $request->request->all();
            } else {
                $body = '[omitted: unstructured body]';
            }
        }

        return [
            'ip' => $request->ip(),
            // Only diagnostic headers; never credentials, cookies or Referer URLs.
            'headers' => self::sanitize([
                'user-agent' => $request->userAgent(),
                'content-type' => $request->header('content-type'),
                'content-length' => $request->header('content-length'),
            ]),
            'query' => strlen((string) $request->server('QUERY_STRING')) > 16384
                ? '[omitted: query exceeds 16 KiB]' : self::sanitize($request->query()),
            'body' => self::sanitize($body),
            'body_bytes' => strlen($raw),
            'files_count' => count($request->allFiles()),
        ];
    }

    private static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if (is_array($value)) {
            if ($depth >= 5) {
                return '[omitted: depth limit]';
            }
            $result = [];
            foreach (array_slice($value, 0, 50, true) as $key => $item) {
                $safeKey = mb_substr((string) $key, 0, 128);
                $result[$safeKey] = preg_match('/password|passwd|secret|token|auth|cookie|api.?key|credential|clave|contrase|tarjeta|card|cvv|cvc|pan$|mail|celular/i', (string) $key)
                    ? '[REDACTED]' : self::sanitize($item, $depth + 1);
            }
            if (count($value) > 50) {
                $result['_truncated'] = true;
            }

            return $result;
        }
        if (is_string($value)) {
            return mb_strlen($value) > 2048 ? mb_substr($value, 0, 2048).'[truncated]' : $value;
        }

        return $value;
    }
}
