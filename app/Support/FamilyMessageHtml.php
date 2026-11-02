<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Validation\ValidationException;

/** HTML authored by families, also rendered by legacy institutional clients. */
class FamilyMessageHtml
{
    public function clean(string $html, array $imageUrls, array $emojiImages = [], array $imageAliases = []): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            throw ValidationException::withMessages(['msg' => 'Escribí el mensaje.']);
        }
        $this->walk($body, $imageUrls, $emojiImages, $imageAliases);
        $text = preg_replace('/[\s\x{00a0}\x{200b}-\x{200d}\x{feff}]+/u', '', $body->textContent);
        if ($text === '' && $body->getElementsByTagName('img')->length === 0) {
            throw ValidationException::withMessages(['msg' => 'Escribí el mensaje o agregá una imagen.']);
        }

        return implode('', array_map(fn ($node) => $doc->saveHTML($node), iterator_to_array($body->childNodes)));
    }

    private function walk(DOMNode $parent, array $imageUrls, array $emojiImages, array $imageAliases): void
    {
        $allowed = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'span', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code', 'ul', 'ol', 'li', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'hr', 'sub', 'sup'];
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if (! $node instanceof DOMElement || in_array(strtolower($node->tagName), ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'button', 'textarea', 'select'])) {
                $parent->removeChild($node);

                continue;
            }
            $tag = strtolower($node->tagName);
            $this->walk($node, $imageUrls, $emojiImages, $imageAliases);
            if (! in_array($tag, $allowed)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }
            $attributes = [];
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $attributes[strtolower($attribute->name)] = $attribute->value;
                $node->removeAttributeNode($attribute);
            }
            if ($tag === 'a' && preg_match('~^(https?://|mailto:|tel:)[^\s\x00-\x20]+$~i', $attributes['href'] ?? '')) {
                $node->setAttribute('href', $attributes['href']);
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img') {
                // Only aliases built from this draft's own files are accepted.
                // Never strip arbitrary hosts and thereby authorize foreign images.
                $attributes['src'] = $imageAliases[$attributes['src'] ?? ''] ?? ($attributes['src'] ?? '');
                if ($emoji = $emojiImages[$attributes['src'] ?? ''] ?? null) {
                    $node->setAttribute('src', $attributes['src']);
                    $node->setAttribute('alt', $emoji['label']);
                    $node->setAttribute('data-eure-emoji', $emoji['id']);
                    $node->setAttribute('width', '24');
                    $node->setAttribute('height', '24');
                    $node->setAttribute('style', FamilyEmojiCatalog::STYLE);

                    continue;
                }
                if (! in_array($attributes['src'] ?? '', $imageUrls, true)) {
                    throw ValidationException::withMessages(['msg' => 'Una imagen no pertenece a este borrador. Pegala o subila nuevamente.']);
                }
                $node->setAttribute('src', $attributes['src']);
                $node->setAttribute('alt', mb_substr($attributes['alt'] ?? '', 0, 200));
            }
            if ($tag === 'font') {
                foreach (['color', 'face', 'size'] as $name) {
                    if (isset($attributes[$name]) && preg_match('/^[\w\s,#.\-]+$/u', $attributes[$name])) {
                        $node->setAttribute($name, mb_substr($attributes[$name], 0, 80));
                    }
                }
            }
            if ($tag === 'table') {
                $node->setAttribute('border', '1');
                $node->setAttribute('cellpadding', '7');
            }
            foreach (['colspan', 'rowspan', 'width', 'height'] as $name) {
                if (isset($attributes[$name]) && preg_match('/^\d{1,4}%?$/', $attributes[$name])) {
                    $node->setAttribute($name, $attributes[$name]);
                }
            }
            $styles = [];
            foreach (explode(';', $attributes['style'] ?? '') as $declaration) {
                $pair = explode(':', $declaration, 2);
                if (count($pair) !== 2) {
                    continue;
                }
                [$name, $value] = array_map('trim', $pair);
                if (in_array(strtolower($name), ['color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style', 'text-decoration', 'text-align', 'margin-left', 'width', 'height'])
                    && preg_match('/^[a-zA-Z0-9\s#.,()%\-]+$/', $value) && ! preg_match('/url|expression|var\s*\(/i', $value)) {
                    $styles[] = strtolower($name).':'.$value;
                }
            }
            if ($tag === 'img') {
                $styles[] = 'max-width:100%';
                $styles[] = 'height:auto';
            }
            if ($styles) {
                $node->setAttribute('style', implode(';', $styles));
            }
        }
    }
}
