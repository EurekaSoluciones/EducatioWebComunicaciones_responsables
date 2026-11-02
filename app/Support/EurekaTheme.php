<?php

namespace App\Support;

use JsonException;
use RuntimeException;

class EurekaTheme
{
    public const COLORS = [
        'primary', 'primary-hover', 'primary-text', 'on-primary', 'accent', 'on-accent',
        'page', 'surface', 'surface-muted', 'text', 'muted', 'border', 'ring',
        'header', 'on-header', 'toolbar', 'sidebar', 'on-sidebar', 'sidebar-muted',
        'sidebar-active', 'sidebar-active-text', 'success', 'success-soft',
        'sidebar-footer', 'sidebar-footer-text', 'sidebar-footer-muted', 'sidebar-footer-border',
        'warning', 'warning-soft', 'danger', 'danger-soft', 'info', 'info-soft',
        'overlay', 'shadow',
    ];

    public function current(): array
    {
        $client = config('eureka.client_id');

        if (! is_string($client) || ! preg_match('/\A[a-z0-9_-]+\z/', $client)) {
            throw new RuntimeException('EURE_CLIENTE_ID no identifica un tema válido.');
        }

        $path = resource_path("themes/{$client}.json");

        if (! is_file($path)) {
            throw new RuntimeException("Falta el tema completo resources/themes/{$client}.json.");
        }

        try {
            $theme = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("El tema de {$client} no contiene JSON válido.", previous: $exception);
        }

        $this->validate($theme);

        return ['clientId' => $client, ...$theme];
    }

    public function validate(mixed $theme): void
    {
        if (! is_array($theme) || ! is_string($theme['name'] ?? null)
            || trim($theme['name']) === '' || ! is_array($theme['colors'] ?? null)) {
            throw new RuntimeException('El tema necesita un nombre y su paleta completa.');
        }

        if (array_diff(self::COLORS, array_keys($theme['colors']))
            || array_diff(array_keys($theme['colors']), self::COLORS)) {
            throw new RuntimeException('El tema debe definir todos los colores del contrato Eureka, sin herencia.');
        }

        foreach ($theme['colors'] as $name => $value) {
            if (! is_string($value) || ! preg_match('/\A#[0-9a-fA-F]{6}\z/', $value)) {
                throw new RuntimeException("El color {$name} debe tener formato #RRGGBB.");
            }
        }
    }
}
