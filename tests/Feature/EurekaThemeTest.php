<?php

namespace Tests\Feature;

use App\Support\EurekaTheme;
use RuntimeException;
use Tests\TestCase;

class EurekaThemeTest extends TestCase
{
    public function test_ifes_has_an_independent_theme_and_uses_its_existing_login_logo(): void
    {
        $this->withoutVite();
        config(['eureka.client_id' => 'ifesnqn', 'eureka.institution' => 'Colegio IFES Neuquén']);

        $theme = app(EurekaTheme::class)->current();
        $this->assertSame('ifesnqn', $theme['clientId']);
        $this->assertSame('IFES Neuquén', $theme['name']);
        $this->assertSame('#c4ad0f', $theme['colors']['primary']);
        $this->assertEqualsCanonicalizing(EurekaTheme::COLORS, array_keys($theme['colors']));
        $this->assertFileExists(public_path('assets/C/ifesnqn/institucional/Logo.png'));

        $this->get('/login')->assertOk()->assertViewHas('page', function ($page) {
            return $page['props']['theme']['clientId'] === 'ifesnqn'
                && $page['props']['institution'] === 'Colegio IFES Neuquén'
                && str_ends_with($page['props']['logoUrl'], '/assets/C/ifesnqn/institucional/LoginLogo.png');
        });
    }

    public function test_the_configured_client_has_a_complete_independent_theme(): void
    {
        $theme = app(EurekaTheme::class)->current();

        $this->assertSame('isi', $theme['clientId']);
        $this->assertSame('ISI College', $theme['name']);
        $this->assertEqualsCanonicalizing(EurekaTheme::COLORS, array_keys($theme['colors']));
    }

    public function test_unknown_clients_do_not_silently_use_another_theme(): void
    {
        config(['eureka.client_id' => 'missing-client']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falta el tema completo');

        app(EurekaTheme::class)->current();
    }

    public function test_the_client_identifier_cannot_be_used_as_a_file_path(): void
    {
        config(['eureka.client_id' => '../isi']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no identifica un tema válido');

        app(EurekaTheme::class)->current();
    }

    public function test_partial_themes_are_rejected(): void
    {
        $theme = app(EurekaTheme::class)->current();
        unset($theme['colors']['primary']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('todos los colores');

        app(EurekaTheme::class)->validate($theme);
    }

    public function test_css_values_other_than_hex_colors_are_rejected(): void
    {
        $theme = app(EurekaTheme::class)->current();
        $theme['colors']['primary'] = 'url(https://example.invalid/image)';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('formato #RRGGBB');

        app(EurekaTheme::class)->validate($theme);
    }
}
