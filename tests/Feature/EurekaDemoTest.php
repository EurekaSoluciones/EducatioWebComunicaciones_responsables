<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleEurekaRequests;
use App\Models\User;
use App\Support\EurekaTheme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Tests\TestCase;

class EurekaDemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Inertia::class)) {
            $this->markTestSkipped('Pendiente instalar inertiajs/inertia-laravel con Composer.');
        }
    }

    public function test_the_demo_is_public_while_the_application_still_requires_login(): void
    {
        $this->withoutVite();
        $this->get('/demo/eureka')->assertOk()->assertSee('data-page=', false);
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_the_demo_shares_only_theme_institution_and_navigation_data(): void
    {
        $version = app(HandleEurekaRequests::class)->version(Request::create('/demo/eureka'));
        $response = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version ?? '',
        ])->get('/demo/eureka');

        $response->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'demo/Ui')
            ->assertJsonPath('props.theme.clientId', 'isi')
            ->assertJsonPath('props.institution', 'Institución de prueba')
            ->assertJsonPath('props.demoLayout', 'sidebar')
            ->assertJsonPath('props.demoUrls.pranzo', route('demo.eureka.alternative'))
            ->assertJsonPath('props.homeUrl', route('home'));

        $this->assertArrayNotHasKey('user', $response->json('props'));
        $this->assertArrayNotHasKey('alumnos', $response->json('props'));
    }

    public function test_the_alternative_is_public_and_uses_the_original_theme(): void
    {
        $this->withoutVite();
        $this->get('/demo/eureka/alternativa')->assertOk()->assertSee('data-page=', false);

        $version = app(HandleEurekaRequests::class)->version(Request::create('/demo/eureka/alternativa'));
        $response = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version ?? '',
        ])->get('/demo/eureka/alternativa');

        $response->assertOk()
            ->assertJsonPath('component', 'demo/Ui')
            ->assertJsonPath('props.demoLayout', 'pranzo')
            ->assertJsonPath('props.demoUrls.sidebar', route('demo.eureka'))
            ->assertJsonPath('props.theme.clientId', 'isi');

        $theme = $response->json('props.theme');
        app(EurekaTheme::class)->validate($theme);
        $this->assertEqualsCanonicalizing(EurekaTheme::COLORS, array_keys($theme['colors']));
        $original = app(EurekaTheme::class)->current();
        $this->assertSame($original, $theme);
        $this->get('/demo/eureka')->assertOk()->assertJsonPath('props.theme', $original);
        $this->assertArrayNotHasKey('user', $response->json('props'));
        $this->assertArrayNotHasKey('alumnos', $response->json('props'));
    }

    public function test_the_root_page_renders_without_the_adminlte_layout(): void
    {
        $this->withoutVite();
        $this->actingAs((new User)->forceFill(['id' => 42]), 'web');

        $this->get('/demo/eureka')->assertOk()
            ->assertSee('data-page=', false)
            ->assertDontSee('adminlte.min.css', false);
    }

    public function test_an_unknown_client_returns_an_explicit_configuration_error(): void
    {
        config(['eureka.client_id' => 'missing-client']);
        $this->actingAs((new User)->forceFill(['id' => 42]), 'web');

        $this->get('/demo/eureka')->assertStatus(503);
        $this->get('/demo/eureka/alternativa')->assertStatus(503);
    }
}
