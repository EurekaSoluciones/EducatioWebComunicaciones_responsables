<?php

namespace App\Support;

class FamilyEmojiCatalog
{
    public const PATH = 'assets/eure/emoji/v1';

    public const STYLE = 'display:inline-block;width:24px;height:24px;max-width:none;vertical-align:-4px';

    public static function images(string $baseUrl): array
    {
        static $catalog = null;
        static $labels = null;
        $root = dirname(__DIR__, 2);
        $catalog ??= json_decode(file_get_contents($root.'/resources/emoji/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
        $labels ??= json_decode(file_get_contents($root.'/resources/emoji/labels.es.json'), true, 512, JSON_THROW_ON_ERROR);
        $images = [];
        foreach ($catalog as $emoji) {
            $images[rtrim($baseUrl, '/').'/'.$emoji['id'].'.png'] = [
                'id' => $emoji['id'], 'label' => $labels[$emoji['aliases'][0]] ?? str_replace('_', ' ', $emoji['aliases'][0]),
            ];
        }

        return $images;
    }
}
