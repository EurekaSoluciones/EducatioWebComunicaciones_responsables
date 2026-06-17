<?php

namespace App\EureLib;

use Illuminate\Support\Str;

class InitialAvatar
{
  private const PALETTES = [
    ['bg' => '#1D4ED8', 'fg' => '#FFFFFF'],
    ['bg' => '#0F766E', 'fg' => '#FFFFFF'],
    ['bg' => '#7C2D12', 'fg' => '#FFFFFF'],
    ['bg' => '#A21CAF', 'fg' => '#FFFFFF'],
    ['bg' => '#BE123C', 'fg' => '#FFFFFF'],
    ['bg' => '#047857', 'fg' => '#FFFFFF'],
    ['bg' => '#4338CA', 'fg' => '#FFFFFF'],
    ['bg' => '#B45309', 'fg' => '#FFFFFF'],
    ['bg' => '#0369A1', 'fg' => '#FFFFFF'],
    ['bg' => '#4D7C0F', 'fg' => '#FFFFFF'],
    ['bg' => '#6D28D9', 'fg' => '#FFFFFF'],
    ['bg' => '#C2410C', 'fg' => '#FFFFFF'],
  ];

  public static function url(string $name, int $size = 512): string
  {
    return route('avatar.initials', [
      'name' => $name,
      'size' => $size,
    ]);
  }

  public static function svg(string $name, int $size = 512): string
  {
    $safeSize = max(32, min($size, 1024));
    $safeName = trim($name) !== '' ? trim($name) : '?';
    $palette = self::palette($safeName);
    $initials = self::initials($safeName);
    $fontSize = $safeSize * (Str::length($initials) > 1 ? 0.42 : 0.48);
    $escapedInitials = e($initials);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$safeSize}" height="{$safeSize}" viewBox="0 0 {$safeSize} {$safeSize}" role="img" aria-label="{$escapedInitials}">
  <rect width="100%" height="100%" fill="{$palette['bg']}"/>
  <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="{$palette['fg']}" font-family="Arial, Helvetica, sans-serif" font-size="{$fontSize}" font-weight="700">{$escapedInitials}</text>
</svg>
SVG;
  }

  public static function initials(string $name): string
  {
    $normalized = Str::of($name)
      ->replace(['+', '_', '-', '.'], ' ')
      ->squish();

    if ($normalized->isEmpty()) {
      return '?';
    }

    $parts = collect(explode(' ', (string) $normalized))
      ->filter(fn ($part) => trim($part) !== '')
      ->values();

    if ($parts->count() === 1) {
      return Str::upper(Str::substr($parts->first(), 0, 2));
    }

    return Str::upper(
      Str::substr($parts->first(), 0, 1).
      Str::substr($parts->last(), 0, 1)
    );
  }

  private static function palette(string $name): array
  {
    $hash = crc32(Str::lower(Str::ascii($name)));

    return self::PALETTES[$hash % count(self::PALETTES)];
  }
}
