<?php

// Deterministic extraction of the existing local sprite sheets; no new artwork/downloads.
$root = dirname(__DIR__);
$source = file_get_contents($root.'/public/vendor/tam-emoji/js/config.js');
$read = function (string $name) use ($source): array {
    if (! preg_match('/Config\.'.preg_quote($name, '/').'\s*=\s*(.*?)(?=\r?\nConfig\.|\z)/s', $source, $match)) {
        throw new RuntimeException("Missing $name");
    }

    return json_decode(rtrim(trim($match[1]), ';'), true, 512, JSON_THROW_ON_ERROR);
};
$emoji = $read('Emoji');
$categories = $read('EmojiCategories');
$dimensions = $read('EmojiCategorySpritesheetDimens');
$out = $root.'/public/assets/eure/emoji/v1';
if (! is_dir($out)) {
    mkdir($out, 0777, true);
}
if (! is_dir($root.'/resources/emoji')) {
    mkdir($root.'/resources/emoji', 0777, true);
}
$catalog = [];
foreach ($categories as $category => $ids) {
    $sheet = imagecreatefrompng($root."/public/vendor/tam-emoji/img/emoji_spritesheet_$category.png");
    [$rows, $columns] = $dimensions[$category];
    $size = intdiv(imagesx($sheet), $columns);
    if ($size * $columns !== imagesx($sheet) || $size * $rows !== imagesy($sheet)) {
        throw new RuntimeException('Unexpected sprite dimensions');
    }
    foreach ($ids as $index => $id) {
        if (! preg_match('/^[a-f0-9-]+$/', $id)) {
            throw new RuntimeException('Invalid ID');
        }
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagecopy($image, $sheet, 0, 0, ($index % $columns) * $size, intdiv($index, $columns) * $size, $size, $size);
        imagepng($image, "$out/$id.png", 9);
        $catalog[] = ['id' => $id, 'category' => $category, 'unicode' => $emoji[$id][0], 'aliases' => $emoji[$id][1]];
    }
}
file_put_contents($root.'/resources/emoji/catalog.json', json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo count($catalog)." emojis extracted from the existing catalog.\n";
