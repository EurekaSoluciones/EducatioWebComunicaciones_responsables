<?php

namespace Tests\Feature;

use App\Support\SuperviellePaymentPoster;
use App\Support\SuperviellePublicationStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupervielleWebhookTest extends TestCase
{
    private const HASH = '11111111-1111-4111-8111-111111111111';

    private const ATTEMPT = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_11_04_000001_create_supervielle_ledger.php'))->up();
        Schema::create('fake_receipts', function (Blueprint $table) {
            $table->string('payment_id')->primary();
            $table->string('student_id');
            $table->string('amount');
        });
        config([
            'eureka.client_id' => 'isi', 'supervielle.webhook_enabled' => true,
            'supervielle.token_url' => 'https://bank.example/token',
            'supervielle.publication_url' => 'https://bank.example/publication',
            'supervielle.username' => 'fake-user', 'supervielle.password' => 'fake-password',
            'supervielle.client_id' => 'fake-client', 'supervielle.client_secret' => 'fake-secret',
            'supervielle.ibm_client_id' => 'fake-ibm', 'supervielle.agreement' => 123,
            'supervielle.publication_type' => 'Deuda', 'supervielle.modality' => 'Cerrada',
            'supervielle.forwarded_host' => null, 'supervielle.forwarded_for' => null,
            'supervielle.certificate' => null, 'supervielle.private_key' => null, 'supervielle.ca_bundle' => null,
            'supervielle.consult_scope' => 'boton-pago.consultar',
        ]);
        $store = app(SuperviellePublicationStore::class);
        $store->begin(['id' => self::ATTEMPT, 'student_id' => '11', 'amount' => '1234.50', 'due_date' => '2026-11-05']);
        $store->complete(self::ATTEMPT, self::HASH);
        $this->app->instance(SuperviellePaymentPoster::class, new class extends SuperviellePaymentPoster
        {
            public function post(object $publication, array $payment): void
            {
                DB::table('fake_receipts')->insert([
                    'payment_id' => $payment['payment_id'], 'student_id' => $publication->student_id, 'amount' => $payment['amount'],
                ]);
            }
        });
    }

    private function endpoint(string $hash = self::HASH): string
    {
        return route('supervielle.webhook', ['hash' => $hash]);
    }

    private function bankState(int $status = 1): array
    {
        // Schema from the supplied manual, page 12; fictitious values only.
        return [
            'id_publicacion_cliente' => 10, 'convenio' => 123,
            'identificadores' => ['cod_alumno|11', 'uuid|'.self::ATTEMPT], 'importe1' => 1234.50,
            'pagos' => [[
                'id_pago' => 456, 'id_estado_pago' => $status, 'estado_pago' => 'Estado de prueba',
                'id_medio_pago' => 2, 'medio_pago' => 'Tarjeta de Debito',
                'importe' => 1234.50, 'fecha' => '2026-11-04T12:00:00.000',
            ]],
        ];
    }

    private function fakeBank(array $state): void
    {
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'fake-token']),
            'bank.example/publication/'.self::HASH.'/estado' => Http::response($state),
        ]);
    }

    public function test_callback_repetition_and_approved_to_credited_post_only_once(): void
    {
        $state = $this->bankState();
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'fake-token']),
            'bank.example/publication/'.self::HASH.'/estado' => Http::sequence()
                ->push($state)->push($state)->push($this->bankState(2)),
        ]);
        for ($i = 0; $i < 3; $i++) {
            $this->getJson($this->endpoint())->assertOk()->assertJsonPath('status', 'success');
        }
        $this->assertSame(1, DB::table('fake_receipts')->count());
        $this->assertSame(1, DB::table('web_supervielle_payments')->count());
        $payment = DB::table('web_supervielle_payments')->first();
        $this->assertSame(2, $payment->bank_status);
        $this->assertNotNull($payment->posted_at);
        Http::assertSent(fn ($request) => $request->url() === 'https://bank.example/token'
            && $request['scope'] === 'boton-pago.consultar');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/estado')
            && $request->method() === 'GET' && $request->hasHeader('Authorization', 'Bearer fake-token'));
    }

    public function test_pending_then_approved_is_posted_when_approved(): void
    {
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'fake-token']),
            'bank.example/publication/'.self::HASH.'/estado' => Http::sequence()->push($this->bankState(3))->push($this->bankState()),
        ]);
        $this->getJson($this->endpoint())->assertOk();
        $this->assertSame(0, DB::table('fake_receipts')->count());
        $this->getJson($this->endpoint())->assertOk();
        $this->assertSame(1, DB::table('fake_receipts')->count());
    }

    public function test_invalid_unknown_other_client_and_head_do_not_query_bank(): void
    {
        $this->getJson($this->endpoint('invalid'))->assertStatus(400);
        $this->getJson($this->endpoint('33333333-3333-4333-8333-333333333333'))->assertStatus(400);
        $this->head($this->endpoint())->assertStatus(405);
        config(['eureka.client_id' => 'ifesnqn']);
        $this->getJson($this->endpoint())->assertNotFound();
        config(['eureka.client_id' => 'isi', 'supervielle.webhook_enabled' => false]);
        $this->getJson($this->endpoint())->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_bank_failure_returns_retryable_error_without_posting(): void
    {
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'fake-token']),
            'bank.example/publication/*' => Http::response('private bank error', 503),
        ]);
        $this->getJson($this->endpoint())->assertStatus(503)->assertDontSee('private bank error');
        $this->assertSame(0, DB::table('fake_receipts')->count());
        $this->assertSame(0, DB::table('web_supervielle_payments')->count());
    }

    public function test_mismatched_bank_identity_is_rejected(): void
    {
        $state = $this->bankState();
        $state['identificadores'] = ['cod_alumno|99', 'uuid|'.self::ATTEMPT];
        $this->fakeBank($state);
        $this->getJson($this->endpoint())->assertStatus(503);
        $this->assertSame(0, DB::table('fake_receipts')->count());
    }

    public function test_unknown_state_or_invalid_payment_rolls_back_all_payments(): void
    {
        $state = $this->bankState();
        $state['pagos'][] = array_merge($state['pagos'][0], ['id_pago' => 789, 'id_estado_pago' => 99]);
        $this->fakeBank($state);
        $this->getJson($this->endpoint())->assertStatus(503);
        $this->assertSame(0, DB::table('fake_receipts')->count());
        $this->assertSame(0, DB::table('web_supervielle_payments')->count());
    }

    public function test_reversal_is_saved_for_review_without_a_second_posting(): void
    {
        Http::fake([
            'bank.example/token' => Http::response(['access_token' => 'fake-token']),
            'bank.example/publication/'.self::HASH.'/estado' => Http::sequence()
                ->push($this->bankState())->push($this->bankState(4))->push($this->bankState(2)),
        ]);
        for ($i = 0; $i < 3; $i++) {
            $this->getJson($this->endpoint())->assertOk();
        }
        $this->assertSame(1, DB::table('fake_receipts')->count());
        $this->assertTrue((bool) DB::table('web_supervielle_payments')->value('needs_review'));
    }

    public function test_amount_difference_is_saved_for_review_without_posting(): void
    {
        $state = $this->bankState();
        $state['pagos'][0]['importe'] = 11.11;
        $this->fakeBank($state);
        $this->getJson($this->endpoint())->assertOk();
        $this->assertSame(0, DB::table('fake_receipts')->count());
        $this->assertTrue((bool) DB::table('web_supervielle_payments')->value('needs_review'));
    }

    public function test_missing_procedure_adapter_does_not_acknowledge_or_mark_as_posted(): void
    {
        $this->app->instance(SuperviellePaymentPoster::class, new SuperviellePaymentPoster);
        $this->fakeBank($this->bankState());
        $this->getJson($this->endpoint())->assertStatus(503);
        $this->assertSame(0, DB::table('web_supervielle_payments')->count());
    }

    public function test_posting_failure_rolls_back_receipt_and_allows_bank_retry(): void
    {
        $this->app->instance(SuperviellePaymentPoster::class, new class extends SuperviellePaymentPoster
        {
            public int $calls = 0;

            public function post(object $publication, array $payment): void
            {
                DB::table('fake_receipts')->insert(['payment_id' => $payment['payment_id'], 'student_id' => '11', 'amount' => $payment['amount']]);
                if (++$this->calls === 1) {
                    throw new \RuntimeException('Simulated stored procedure failure');
                }
            }
        });
        $this->fakeBank($this->bankState());
        $this->getJson($this->endpoint())->assertStatus(503);
        $this->assertSame(0, DB::table('fake_receipts')->count());
        $this->assertSame(0, DB::table('web_supervielle_payments')->count());
        $this->getJson($this->endpoint())->assertOk();
        $this->assertSame(1, DB::table('fake_receipts')->count());
    }
}
