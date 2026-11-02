<?php

namespace Tests\Unit;

use App\Support\FamilyEmojiCatalog;
use App\Support\FamilyMessageHtml;
use PHPUnit\Framework\TestCase;

class FamilyMessageHtmlTest extends TestCase
{
    public function test_safe_formatting_unicode_lists_tables_and_links_survive(): void
    {
        $html = '<h2>Consulta 😊</h2><p style="text-align:center;color:rgb(10, 20, 30);position:fixed">Texto <b>negrita</b> <i>cursiva</i> <u>subrayado</u></p><ul><li>Primero</li></ul><table><tr><td colspan="2">Celda</td></tr></table><font face="Georgia" size="4" color="#aa0000">Hola</font><a href="https://example.test/consulta?q=1&amp;a=2">Enlace</a>';
        $clean = (new FamilyMessageHtml)->clean($html, []);
        foreach (['Consulta 😊', '<b>negrita</b>', '<i>cursiva</i>', '<u>subrayado</u>', '<ul><li>Primero</li></ul>', 'colspan="2"', 'border="1"', 'face="Georgia"', 'size="4"', 'color:rgb(10, 20, 30)', 'rel="noopener noreferrer"'] as $expected) {
            $this->assertStringContainsString($expected, $clean);
        }
        $this->assertStringNotContainsString('position', $clean);
    }

    public function test_active_content_attributes_and_css_are_removed_before_legacy_rendering(): void
    {
        $clean = (new FamilyMessageHtml)->clean('<p onclick="alert(1)" id="clobber" style="background-color:url(https://example.test);color:expression(alert(1));font-size:16px">Seguro</p><script>alert(1)</script><style>*{display:none}</style><svg onload="alert(1)"></svg><iframe srcdoc="bad"></iframe><form><input name="x"></form><!-- bad --><a href="java&#115;cript:alert(1)">Enlace</a>', []);
        foreach (['onclick', 'clobber', 'url(', 'expression', 'script', 'svg', 'iframe', 'srcdoc', 'form', 'input', 'href=', '<!--'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $clean);
        }
        $this->assertStringContainsString('Seguro', $clean);
        $this->assertStringContainsString('font-size:16px', $clean);
    }

    public function test_only_uploaded_draft_images_are_accepted_and_size_is_responsive(): void
    {
        $clean = (new FamilyMessageHtml)->clean('<img src="/storage/own.png" onerror="alert(1)" srcset="other.png 2x" alt="Foto" style="width:50%">', ['/storage/own.png']);
        $this->assertStringContainsString('src="/storage/own.png"', $clean);
        $this->assertStringContainsString('max-width:100%', $clean);
        $this->assertStringContainsString('width:50%', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('srcset', $clean);
    }

    public function test_image_resize_widths_survive_sanitization_and_keep_mobile_constraints(): void
    {
        foreach (['25%', '50%', '100%', '250px'] as $width) {
            $clean = (new FamilyMessageHtml)->clean('<img src="/storage/own.png" style="width:'.$width.';height:auto;max-width:100%">', ['/storage/own.png']);
            $this->assertStringContainsString('style="width:'.$width.';', $clean);
            $this->assertStringContainsString('height:auto', $clean);
            $this->assertStringContainsString('max-width:100%', $clean);
        }
        $original = (new FamilyMessageHtml)->clean('<img src="/storage/own.png" style="height:auto;max-width:100%">', ['/storage/own.png']);
        $this->assertStringNotContainsString('style="width:', $original);
        $this->assertStringContainsString('max-width:100%', $original);
    }

    public function test_catalog_emojis_are_canonical_inline_images_and_survive_repeated_cleaning(): void
    {
        $base = 'https://familias.example/assets/eure/emoji/v1';
        $catalog = FamilyEmojiCatalog::images($base);
        $this->assertCount(845, $catalog);
        $html = '<p>Hola <img src="'.$base.'/1f604.png" width="900" onerror="bad()" style="position:fixed;width:900px" data-eure-emoji="fake">!</p>';
        $clean = (new FamilyMessageHtml)->clean($html, [], $catalog);
        $this->assertStringContainsString('alt="Sonrisa"', $clean);
        $this->assertStringContainsString('width="24" height="24"', $clean);
        $this->assertStringContainsString('data-eure-emoji="1f604"', $clean);
        $this->assertStringContainsString('vertical-align:-4px', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('900', $clean);
        $this->assertSame($clean, (new FamilyMessageHtml)->clean($clean, [], $catalog));
    }
}
