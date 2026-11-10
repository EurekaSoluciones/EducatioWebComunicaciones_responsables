<?php

namespace Tests\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

trait TestsSuperviellePayments
{
    private function supervielleSettings(): void
    {
        config([
            'eureka.client_id' => 'isi', 'supervielle.enabled' => true,
            'supervielle.token_url' => 'https://bank.example/token',
            'supervielle.publication_url' => 'https://bank.example/publication',
            'supervielle.username' => 'test-user', 'supervielle.password' => 'test-secret',
            'supervielle.client_id' => 'test-client', 'supervielle.client_secret' => 'test-client-secret',
            'supervielle.ibm_client_id' => 'test-ibm', 'supervielle.agreement' => 123,
            'supervielle.forwarded_host' => null, 'supervielle.forwarded_for' => null,
            'supervielle.certificate' => null, 'supervielle.private_key' => null,
            'supervielle.publication_type' => 'Deuda', 'supervielle.modality' => 'Cerrada',
            'supervielle.scope' => 'boton-pago.publicar', 'supervielle.expires_after_days' => 1,
            'supervielle.checkout_hosts' => ['recaudaciones.supervielle.com.ar'],
        ]);
        $this->travelTo(Carbon::parse('2026-11-04 15:00:00', 'America/Argentina/Buenos_Aires'));
        $this->actingAs($this->user);
    }

    private function prepareSupervielle(string $amount = '1234,50'): string
    {
        $response = $this->post(route('family.supervielle.prepare'), [
            'alumno' => 11, 'importe' => $amount, 'metodo' => 'supervielle',
        ])->assertRedirect();

        return $response->headers->get('Location');
    }

    private function fakeSupervielleSuccess(): string
    {
        $hash = '11111111-1111-4111-8111-111111111111';
        $url = 'https://recaudaciones.supervielle.com.ar/publicacion/'.$hash;
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'test-access-token']),
            'bank.example/publication' => Http::response(['hash' => $hash, 'link_de_pago' => $url, 'errores' => []], 201),
        ]);

        return $url;
    }

    public function test_payment_buttons_follow_all_flag_combinations_for_each_institution(): void
    {
        $this->supervielleSettings();
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;', \Mockery::any())->times(16)->andReturn([]);
        foreach (['isi', 'ifesnqn'] as $client) {
            config(['eureka.client_id' => $client]);
            for ($flags = 0; $flags < 8; $flags++) {
                $supervielle = (bool) ($flags & 1);
                $tic = (bool) ($flags & 2);
                $mp = (bool) ($flags & 4);
                config(['supervielle.enabled' => $supervielle]);
                $this->withFinancePaymentSettings($tic, $mp, function () use ($supervielle, $tic, $mp) {
                    $expected = array_keys(array_filter(['supervielle' => $supervielle, 'pagostic' => $tic, 'mercadopago' => $mp]));
                    $response = $this->inertiaHome('/cc/11')->assertOk()
                        ->assertJsonPath('props.account.payment.url', route('cc.pagar'));
                    $this->assertSame($expected, array_column($response->json('props.account.payment.methods'), 'id'));
                    foreach (array_diff(['supervielle', 'pagostic', 'mercadopago'], $expected) as $method) {
                        $this->post('/cc/pagar', ['alumno' => 11, 'importe' => '100', 'metodo' => $method])->assertForbidden();
                    }
                });
            }
        }
        Http::assertNothingSent();
    }

    public function test_supervielle_confirmation_does_not_publish_and_preserves_amount_on_validation_error(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        $this->inertiaHome($location)->assertOk()->assertJsonPath('component', 'family/SuperviellePayment')
            ->assertJsonPath('props.payment.amount', 1234.5)->assertJsonPath('props.payment.dueDate', '2026-11-05')
            ->assertJsonPath('props.payment.canContinue', true);
        $this->assertSame($location, $this->prepareSupervielle('1234.50'));
        foreach (['0', '-1', '1.234,50', 'texto', '1e3', '0,001', '10000000000', ''] as $amount) {
            $this->from('/cc/11')->post(route('family.supervielle.prepare'), [
                'alumno' => 11, 'importe' => $amount, 'metodo' => 'supervielle',
            ])->assertRedirect('/cc/11')->assertSessionHasErrors('importe');
        }
        Http::assertNothingSent();
    }

    public function test_disabling_supervielle_blocks_new_and_existing_payment_routes(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        config(['supervielle.enabled' => false]);
        $this->post(route('family.supervielle.prepare'), [
            'alumno' => 11, 'importe' => '1234,50', 'metodo' => 'supervielle',
        ])->assertForbidden();
        $this->post(route('cc.pagar'), [
            'alumno' => 11, 'importe' => '1234,50', 'metodo' => 'supervielle',
        ])->assertForbidden();
        $this->get($location)->assertForbidden();
        $this->post($location)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_supervielle_missing_method_is_displayed_on_return_to_the_account(): void
    {
        $this->supervielleSettings();
        $this->from('/cc/11')->post(route('family.supervielle.prepare'), [
            'alumno' => 11, 'importe' => '1234,50', 'metodo' => '',
        ])->assertRedirect('/cc/11')->assertSessionHasErrors('metodo');
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;', \Mockery::any())->once()->andReturn([]);
        $this->inertiaHome('/cc/11')->assertOk()
            ->assertJsonPath('props.account.payment.error', 'No recibimos un medio de pago válido. Volvé a seleccionar Pagar con Supervielle.')
            ->assertJsonPath('props.account.payment.suggestedAmount', '1234,50');
        Http::assertNothingSent();
    }

    public function test_supervielle_publishes_only_confirmed_server_values_and_reuses_the_link(): void
    {
        $this->supervielleSettings();
        $url = $this->fakeSupervielleSuccess();
        $location = $this->prepareSupervielle();
        $this->post($location, ['importe' => '1', 'alumno' => 14, 'due_date' => '2099-01-01'])->assertRedirect($url);
        $this->post($location)->assertRedirect($url);
        $this->assertSame($location, $this->prepareSupervielle());
        $this->inertiaHome($location)->assertOk()->assertJsonPath('props.payment.published', true);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://bank.example/publication'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request->hasHeader('X-Ibm-Client-Id', 'test-ibm')
            && $request['importe1'] === 1234.5 && $request['convenio'] === 123
            && $request['fecha_vencimiento1'] === '2026-11-05'
            && $request['tipo_publicacion'] === 'Deuda' && $request['modalidad'] === 'Cerrada'
            && in_array('cod_alumno|11', $request['identificadores'], true)
            && ! isset($request['mail']) && ! isset($request['celular']));
        $this->assertStringNotContainsString('test-secret', $this->inertiaHome($location)->getContent());
    }

    public function test_supervielle_authorizes_institution_student_user_and_current_relationship(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        $this->post(route('family.supervielle.prepare'), ['alumno' => 14, 'importe' => '100', 'metodo' => 'supervielle'])->assertForbidden();
        config(['eureka.client_id' => 'ifesnqn']);
        // An enabled institution can prepare its own payment, but cannot reuse another institution's attempt.
        $this->get($location)->assertStatus(410);
        $this->post($location)->assertStatus(410);
        $other = $this->post(route('cc.pagar'), ['alumno' => 11, 'importe' => '100', 'metodo' => 'supervielle'])->assertRedirect();
        $this->inertiaHome($other->headers->get('Location'))->assertOk()->assertJsonPath('props.payment.canContinue', true);
        config(['eureka.client_id' => 'isi']);
        $id = basename($location);
        $data = Cache::get('supervielle:isi:attempt:'.$id);
        Cache::put('supervielle:isi:attempt:'.$id, array_merge($data, ['user_id' => '999']), 60);
        $this->get($location)->assertForbidden();
        $this->post($location)->assertForbidden();
        Cache::put('supervielle:isi:attempt:'.$id, $data, 60);
        DB::table('Alumnos')->where('Cod_Alumno', 11)->update(['Responsable1' => 8, 'Responsable2' => 8]);
        $this->post($location)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_supervielle_expired_or_missing_attempts_do_not_publish(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        $this->travelTo(Carbon::parse('2026-11-05 00:00:00', 'America/Argentina/Buenos_Aires'));
        $this->post($location)->assertRedirect($location);
        $this->inertiaHome($location)->assertOk()->assertJsonPath('props.payment.canContinue', false);
        Cache::forget('supervielle:isi:attempt:'.basename($location));
        $this->post($location)->assertStatus(410);
        Http::assertNothingSent();
    }

    public function test_supervielle_ambiguous_response_is_not_republished_and_keeps_amount(): void
    {
        $this->supervielleSettings();
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'test-token']),
            'bank.example/publication' => Http::failedConnection(),
        ]);
        $location = $this->prepareSupervielle();
        $this->post($location)->assertRedirect($location);
        $this->inertiaHome($location)->assertOk()->assertJsonPath('props.payment.amount', 1234.5)
            ->assertJsonPath('props.payment.canContinue', false);
        $this->post($location)->assertRedirect($location);
        $this->assertSame($location, $this->prepareSupervielle());
        $this->assertSame('uncertain', Cache::get('supervielle:isi:attempt:'.basename($location))['state']);
    }

    public function test_supervielle_rejection_keeps_confirmation_and_hides_bank_response(): void
    {
        $this->supervielleSettings();
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'test-token']),
            'bank.example/publication' => Http::response(['detalle' => 'PRIVATE bank details'], 400),
        ]);
        $location = $this->prepareSupervielle();
        $this->post($location)->assertRedirect($location);
        $response = $this->inertiaHome($location)->assertOk()->assertJsonPath('props.payment.canContinue', false)
            ->assertJsonPath('props.payment.amount', 1234.5);
        $this->assertNotEmpty($response->json('props.payment.error'));
        $this->assertStringNotContainsString('PRIVATE', $response->getContent());
        $this->post($location)->assertRedirect($location);
        Http::assertSentCount(2);
    }

    public function test_supervielle_token_error_disables_confirmation_and_prevents_resubmitting(): void
    {
        $this->supervielleSettings();
        Http::fake(['bank.example/token' => Http::response(['error' => 'invalid_grant'], 401)]);
        $location = $this->prepareSupervielle();
        $this->post($location)->assertRedirect($location);
        $this->inertiaHome($location)->assertOk()
            ->assertJsonPath('props.payment.canContinue', false)
            ->assertJsonPath('props.payment.error', 'No pudimos conectar con Supervielle. Intentá más tarde.');
        $this->post($location)->assertRedirect($location);
        Http::assertSentCount(1);
    }

    public function test_supervielle_webhook_mode_persists_publication_before_redirecting(): void
    {
        $this->supervielleSettings();
        (require database_path('migrations/2026_11_04_000001_create_supervielle_ledger.php'))->up();
        config(['supervielle.webhook_enabled' => true]);
        $url = $this->fakeSupervielleSuccess();
        $location = $this->prepareSupervielle();
        $this->post($location)->assertRedirect($url);
        $record = DB::table('web_supervielle_publications')->first();
        $this->assertSame(basename($location), $record->attempt_id);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $record->hash);
        $this->assertSame('11', $record->student_id);
        $this->assertSame('123', $record->agreement);
        $this->post($location)->assertRedirect($url);
        $this->assertSame(1, DB::table('web_supervielle_publications')->count());
        Http::assertSentCount(2);
    }

    public function test_supervielle_webhook_mode_requires_storage_before_calling_bank(): void
    {
        $this->supervielleSettings();
        config(['supervielle.webhook_enabled' => true]);
        $location = $this->prepareSupervielle();
        $this->post($location)->assertStatus(500);
        Http::assertNothingSent();
        $this->assertSame('publishing', Cache::get('supervielle:isi:attempt:'.basename($location))['state']);
    }

    public function test_supervielle_lock_blocks_double_submission_before_any_network_call(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        $lock = Cache::lock('supervielle:isi:attempt:'.basename($location).':lock', 60);
        $this->assertTrue($lock->get());
        try {
            $this->post($location)->assertRedirect($location);
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_supervielle_does_not_publish_if_it_cannot_persist_the_sending_state(): void
    {
        $this->supervielleSettings();
        $location = $this->prepareSupervielle();
        $cache = \Mockery::mock(Cache::getFacadeRoot());
        Cache::swap($cache);
        $cache->shouldReceive('put')->with('supervielle:isi:attempt:'.basename($location),
            \Mockery::on(fn ($attempt) => $attempt['state'] === 'publishing'), \Mockery::any())->once()->andReturn(false);
        $this->post($location)->assertStatus(503);
        Http::assertNothingSent();
    }
}
