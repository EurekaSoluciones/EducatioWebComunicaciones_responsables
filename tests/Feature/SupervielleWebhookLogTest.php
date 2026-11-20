<?php

namespace Tests\Feature;

use App\Support\SupervielleWebhookProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupervielleWebhookLogTest extends TestCase
{
    private string $directory;

    private const HASH = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/sv-webhook-'.Str::uuid();
        mkdir($this->directory);
        config([
            'eureka.client_id' => 'isi', 'supervielle.enabled' => false,
            'supervielle.test_mode' => true, 'supervielle.test_logins' => [],
            'supervielle.webhook_enabled' => true, 'supervielle.webhook_log_only' => true,
            'logging.channels.supervielle_webhook.path' => $this->directory.'/supervielle-webhook.log',
        ]);
        Log::forgetChannel('supervielle_webhook');
        // No migration, login, bank credentials or database calls are needed for reception.
        DB::shouldReceive('connection')->never();
        DB::shouldReceive('transaction')->never();
        $this->mock(SupervielleWebhookProcessor::class)->shouldNotReceive('process');
    }

    protected function tearDown(): void
    {
        $logger = Log::getChannels()['supervielle_webhook'] ?? null;
        $logger?->getLogger()->close();
        Log::forgetChannel('supervielle_webhook');
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    private function endpoint(string $hash = self::HASH): string
    {
        return route('supervielle.webhook', ['hash' => $hash]);
    }

    public function test_records_each_delivery_and_returns_200_without_querying_or_posting(): void
    {
        $first = $this->withHeaders(['Authorization' => 'Bearer PRIVATE_TEST_TOKEN'])
            ->getJson($this->endpoint().'?api-key=PRIVATE_QUERY_SECRET')->assertOk()
            ->assertJsonPath('status', 'success');
        $second = $this->getJson($this->endpoint())->assertOk();
        $this->assertTrue(Str::isUuid($first->json('receipt_id')));
        $this->assertNotSame($first->json('receipt_id'), $second->json('receipt_id'));
        $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
        $files = glob($this->directory.'/supervielle-webhook-*.log');
        $this->assertCount(1, $files);
        $log = file_get_contents($files[0]);
        $this->assertSame(2, substr_count($log, self::HASH));
        $this->assertStringContainsString($first->json('receipt_id'), $log);
        $this->assertStringContainsString('"payment_verified":false', $log);
        $this->assertStringContainsString('"payment_posted":false', $log);
        $this->assertStringNotContainsString('PRIVATE_', $log);
        Http::assertNothingSent();
    }

    public function test_disabled_wrong_client_invalid_hash_and_head_do_not_log(): void
    {
        $this->getJson($this->endpoint('invalid'))->assertStatus(400);
        $this->head($this->endpoint())->assertStatus(405);
        config(['supervielle.webhook_enabled' => false]);
        $this->getJson($this->endpoint())->assertStatus(503);
        config(['supervielle.webhook_enabled' => true, 'eureka.client_id' => 'ifesnqn']);
        $this->getJson($this->endpoint())->assertNotFound();
        $this->assertSame([], glob($this->directory.'/*'));
        Http::assertNothingSent();
    }

    public function test_log_write_failure_does_not_acknowledge_receipt(): void
    {
        // A regular file used as the parent directory reliably prevents log creation.
        file_put_contents($this->directory.'/not-a-directory', 'fixture');
        config(['logging.channels.supervielle_webhook.path' => $this->directory.'/not-a-directory/webhook.log']);
        $this->getJson($this->endpoint())->assertStatus(503)->assertJsonPath('status', 'error');
        Http::assertNothingSent();
    }
}
