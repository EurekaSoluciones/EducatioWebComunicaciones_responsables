<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InMemoryUserProvider;
use Tests\TestCase;

class UpgradeBaselineTest extends TestCase
{
    public function test_login_renders_the_institution_and_authentication_form(): void
    {
        $this->withoutVite();
        $response = $this->get('/login')
            ->assertOk()
            ->assertViewIs('eureka');
        $page = $response->viewData('page');
        $this->assertSame('auth/Login', $page['component']);
        $this->assertSame('Institución de prueba', $page['props']['institution']);
        $this->assertSame(route('authenticate'), $page['props']['authenticateUrl']);
        $this->assertNotEmpty($page['props']['csrfToken']);
    }

    public function test_empty_web_credentials_return_validation_errors(): void
    {
        $this->from('/login')->post('/authenticate', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['login', 'password']);
    }

    public function test_web_authentication_rejects_posts_without_a_csrf_token(): void
    {
        // Laravel skips CSRF in testing; exercise the real middleware for this request.
        $this->app['env'] = 'local';

        $this->post('/authenticate', ['login' => '12345678', 'password' => 'test-password'])
            ->assertStatus(419);
    }

    public function test_protected_web_modules_redirect_guests_before_loading_domain_data(): void
    {
        foreach (['/home', '/alumno/42', '/comunicaciones/42', '/cc/42', '/pagos/42',
            '/informes/42/documentos', '/asistencias/42', '/alumno/42/rematriculacion', '/password'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_responsable_can_authenticate_with_the_existing_login_field(): void
    {
        $user = $this->configureUserProvider();

        $this->post('/authenticate', ['login' => '12345678', 'password' => 'test-password'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_incorrect_password_is_rejected(): void
    {
        $this->configureUserProvider();

        $this->from('/login')->post('/authenticate', ['login' => '12345678', 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('credentials');

        $this->assertGuest('web');
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_web_login_rejects_a_non_responsable(): void
    {
        $this->configureUserProvider('Profe');

        $this->from('/login')->post('/authenticate', ['login' => '12345678', 'password' => 'test-password'])
            ->assertSessionHasErrors('credentials');

        $this->assertGuest('web');
    }

    public function test_logout_ends_the_web_session(): void
    {
        $user = $this->configureUserProvider();
        $this->actingAs($user, 'web');

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest('web');
    }

    public function test_login_keeps_the_intended_destination(): void
    {
        $this->configureUserProvider();
        $this->withSession(['url.intended' => url('/alumno/42')])
            ->post('/authenticate', ['login' => '12345678', 'password' => 'test-password'])
            ->assertRedirect(url('/alumno/42'));
    }

    public function test_login_redisplays_errors_and_username_without_password(): void
    {
        $this->withoutVite();
        $this->configureUserProvider();
        $this->from('/login')->post('/authenticate', ['login' => '12345678', 'password' => 'wrong-password']);
        $response = $this->get('/login')->assertOk();
        $page = $response->viewData('page');
        $this->assertSame('12345678', $page['props']['oldLogin']);
        $this->assertNotEmpty($page['props']['errors']->credentials);
        $this->assertStringNotContainsString('wrong-password', json_encode($page));
    }

    public function test_api_guests_receive_json_unauthorized(): void
    {
        $this->getJson('/api/auth-check')->assertUnauthorized();
    }

    public function test_api_login_retains_its_validation_response(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['login', 'password']);
    }

    public function test_sanctum_guard_authenticates_without_exposing_the_password(): void
    {
        $user = $this->configureUserProvider();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user')->assertOk()->assertJsonPath('id', 42);

        $this->assertArrayNotHasKey('password', $response->json());
        $this->assertArrayNotHasKey('remember_token', $response->json());
    }

    public function test_password_change_rejects_other_roles_and_guests(): void
    {
        $this->post('/usuarios/password/update', [])->assertRedirect(route('login'));
        $user = $this->configureUserProvider('Secretaria');
        $this->actingAs($user, 'web');
        $this->get('/password')->assertForbidden();
        $this->post('/usuarios/password/update', [])->assertForbidden();
    }

    private function configureUserProvider(string $type = 'Responsable'): User
    {
        $user = (new User)->forceFill([
            'id' => 42,
            'login' => '12345678',
            'nombres' => 'Usuario de prueba',
            'apellidos' => 'Familias',
            'tipo' => $type,
            'password' => Hash::make('test-password'),
            'remember_token' => 'test-only',
        ]);

        Auth::guard('web')->setProvider(new InMemoryUserProvider($user));

        return $user;
    }
}
