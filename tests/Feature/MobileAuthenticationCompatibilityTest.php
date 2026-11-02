<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileAuthenticationCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['eureka.client_id' => 'ifesnqn']);
        Schema::create('Responsables', function (Blueprint $table) {
            $table->integer('Cod_Responsable')->primary();
        });
        Schema::create('web_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('Cod_Responsable');
            $table->string('tipo');
            $table->string('login');
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        DB::table('Responsables')->insert(['Cod_Responsable' => 7]);
        User::forceCreate(['id' => 42, 'Cod_Responsable' => 7, 'tipo' => 'Responsable', 'login' => 'mobile-test', 'password' => Hash::make('old-secret')]);
    }

    private function bearer(string $token): static
    {
        // Each mobile request must authenticate with the token, not a cached/session user.
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $this->app['session.store']->flush();

        return $this->withToken($token);
    }

    public function test_mobile_login_password_change_and_logout_keep_the_existing_token_contract(): void
    {
        $user = User::findOrFail(42);
        $oldToken = $user->createToken('Previous device')->plainTextToken;
        $this->bearer($oldToken)->getJson('/api/auth-check')->assertOk()->assertExactJson(['message' => 'Token OK']);
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $this->flushHeaders();
        $login = $this->postJson('/api/login', ['login' => 'mobile-test', 'password' => 'old-secret'])
            ->assertOk()->assertJsonStructure(['token', 'message'])->assertJsonPath('message', 'Login successful');
        $token = $login->json('token');
        $this->assertNotEmpty($token);
        $this->bearer($token)->getJson('/api/user')->assertOk()->assertJsonPath('id', 42)->assertJsonMissingPath('password')->assertJsonMissingPath('remember_token');
        $this->bearer($token)->postJson('/api/password-update', [
            'current_password' => 'wrong', 'new_password' => 'new-secret', 'new_password_confirmation' => 'new-secret',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame(2, DB::table('personal_access_tokens')->count());
        $this->bearer($token)->postJson('/api/password-update', [
            'current_password' => 'old-secret', 'new_password' => 'new-secret', 'new_password_confirmation' => 'new-secret',
        ])->assertOk()->assertExactJson(['message' => 'Contraseña actualizada correctamente']);
        $this->assertTrue(Hash::check('new-secret', User::findOrFail(42)->password));
        $this->bearer($oldToken)->getJson('/api/auth-check')->assertUnauthorized();
        $this->bearer($token)->getJson('/api/auth-check')->assertOk();
        $this->bearer($token)->postJson('/api/logout')->assertOk()->assertExactJson(['message' => 'Logout successful', 'expoTokensEliminados' => 0]);
        $this->bearer($token)->getJson('/api/auth-check')->assertUnauthorized();
    }

    public function test_web_password_change_keeps_the_mobile_bearer_token_valid(): void
    {
        $user = User::findOrFail(42);
        $token = $user->createToken('Mobile device')->plainTextToken;
        $this->actingAs($user, 'web')->post('/usuarios/password/update', [
            'currentPassword' => 'old-secret', 'newPassword' => 'web-secret', 'confirmPassword' => 'web-secret',
        ])->assertRedirect(route('responsables.show', 7));
        $this->assertSame(1, DB::table('personal_access_tokens')->count());
        $this->bearer($token)->getJson('/api/auth-check')->assertOk()->assertExactJson(['message' => 'Token OK']);
    }
}
