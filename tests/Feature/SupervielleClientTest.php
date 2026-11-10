<?php

namespace Tests\Feature;

use App\Support\SupervielleClient;
use App\Support\SupervielleException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SupervielleClientTest extends TestCase
{
    private mixed $server = null;

    private string $log;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertNotFalse($socket, 'No se pudo reservar un puerto local de prueba.');
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $this->baseUrl = 'http://'.$address;
        $this->log = tempnam(sys_get_temp_dir(), 'supervielle-test-');
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $this->server = proc_open([PHP_BINARY, '-S', $address, base_path('tests/Support/supervielle-router.php')], [
            0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w'],
        ], $pipes, base_path(), array_merge(getenv(), ['SUPERVIELLE_SIM_LOG' => $this->log]), ['bypass_shell' => true]);
        $this->assertIsResource($this->server);
        $ready = false;
        for ($i = 0; $i < 50; $i++) {
            $connection = @stream_socket_client('tcp://'.$address, $errno, $error, 0.1);
            if ($connection) {
                fclose($connection);
                $ready = true;
                break;
            }
            usleep(50000);
        }
        $this->assertTrue($ready, 'El servidor simulado local no inició.');
        // All other network calls remain forbidden by Tests\TestCase.
        Http::allowStrayRequests([$this->baseUrl.'/*']);
        config([
            'supervielle.token_url' => $this->baseUrl.'/token',
            'supervielle.publication_url' => $this->baseUrl.'/publication/success',
            'supervielle.username' => 'fake-user', 'supervielle.password' => 'fake-password',
            'supervielle.client_id' => 'fake-client', 'supervielle.client_secret' => 'fake-secret',
            'supervielle.ibm_client_id' => 'fake-ibm', 'supervielle.agreement' => 123,
            'supervielle.publication_type' => 'Deuda', 'supervielle.modality' => 'Cerrada',
            'supervielle.forwarded_host' => null, 'supervielle.forwarded_for' => null,
            'supervielle.certificate' => null, 'supervielle.private_key' => null,
            'supervielle.ca_bundle' => null,
            'supervielle.scope' => 'boton-pago.publicar', 'supervielle.timeout' => 1,
            'supervielle.checkout_hosts' => ['recaudaciones.supervielle.com.ar'],
        ]);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        if (isset($this->log) && is_file($this->log)) {
            unlink($this->log);
        }
        parent::tearDown();
    }

    private function attempt(): array
    {
        return [
            'id' => 'e7ebc978-9571-46bf-bcbb-541dfc489d3a',
            'student_id' => '11', 'responsible_id' => '7', 'student_name' => 'Alumno de prueba',
            'amount' => '1234.50', 'due_date' => '2026-11-05',
        ];
    }

    private function calls(): array
    {
        return array_map(fn ($row) => json_decode($row, true), file($this->log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    public function test_real_http_serializes_the_contract_and_extracts_the_documented_link(): void
    {
        $result = app(SupervielleClient::class)->publish($this->attempt());
        $this->assertSame('11111111-1111-4111-8111-111111111111', $result['hash']);
        $this->assertSame('https://recaudaciones.supervielle.com.ar/publicacion/'.$result['hash'], $result['url']);
        $calls = $this->calls();
        $this->assertSame(['/token', '/publication/success'], array_column($calls, 'path'));
        $this->assertStringStartsWith('application/x-www-form-urlencoded', $calls[0]['content_type']);
        $this->assertSame('boton-pago.publicar', $calls[0]['body']['scope']);
        $this->assertSame('application/json', $calls[1]['content_type']);
        $this->assertSame('Bearer local-test-token', $calls[1]['authorization']);
        $this->assertSame('fake-ibm', $calls[1]['ibm_client_id']);
        $this->assertSame(1234.5, $calls[1]['body']['importe1']);
        $this->assertSame('2026-11-05', $calls[1]['body']['fecha_vencimiento1']);
        $this->assertSame(['cod_alumno|11', 'cod_responsable|7', 'uuid|e7ebc978-9571-46bf-bcbb-541dfc489d3a'], $calls[1]['body']['identificadores']);
        $this->assertArrayNotHasKey('mail', $calls[1]['body']);
        $this->assertArrayNotHasKey('celular', $calls[1]['body']);
    }

    public function test_real_http_refreshes_once_on_explicit_unauthorized_publication(): void
    {
        config(['supervielle.publication_url' => $this->baseUrl.'/publication/refresh']);
        app(SupervielleClient::class)->publish($this->attempt());
        $this->assertSame(['/token', '/publication/refresh', '/token', '/publication/refresh'], array_column($this->calls(), 'path'));
    }

    public function test_real_http_status_query_uses_consult_scope_and_refreshes_once(): void
    {
        config(['supervielle.publication_url' => $this->baseUrl.'/publication/status-refresh',
            'supervielle.consult_scope' => 'boton-pago.consultar']);
        $hash = '11111111-1111-4111-8111-111111111111';
        $state = app(SupervielleClient::class)->publicationStatus($hash);
        $this->assertSame(456, $state['pagos'][0]['id_pago']);
        $calls = $this->calls();
        $this->assertSame(['/token', '/publication/status-refresh/'.$hash.'/estado', '/token', '/publication/status-refresh/'.$hash.'/estado'], array_column($calls, 'path'));
        $this->assertSame('boton-pago.consultar', $calls[0]['body']['scope']);
        $this->assertSame('GET', $calls[1]['method']);
        $this->assertSame('Bearer local-test-token', $calls[1]['authorization']);
        $this->assertSame('fake-ibm', $calls[1]['ibm_client_id']);
    }

    public function test_real_http_timeout_does_not_retry_a_possible_publication(): void
    {
        config(['supervielle.publication_url' => $this->baseUrl.'/publication/timeout']);
        try {
            app(SupervielleClient::class)->publish($this->attempt());
            $this->fail('Se esperaba un resultado indeterminado.');
        } catch (SupervielleException $exception) {
            $this->assertTrue($exception->uncertain);
            $this->assertSame(['/token', '/publication/timeout'], array_column($this->calls(), 'path'));
        }
    }

    public function test_real_http_does_not_follow_redirects_or_accept_unsafe_success_responses(): void
    {
        foreach (['redirect', 'unsafe-link', 'incomplete', 'server-error'] as $scenario) {
            config(['supervielle.publication_url' => $this->baseUrl.'/publication/'.$scenario]);
            try {
                app(SupervielleClient::class)->publish($this->attempt());
                $this->fail('Se esperaba rechazar la respuesta '.$scenario);
            } catch (SupervielleException $exception) {
                $this->assertTrue($exception->uncertain);
            }
        }
        $this->assertCount(8, $this->calls());
        $this->assertNotContains('/unexpected', array_column($this->calls(), 'path'));
    }

    public function test_real_http_rejection_is_safe_to_report_without_bank_details(): void
    {
        config(['supervielle.publication_url' => $this->baseUrl.'/publication/rejected']);
        try {
            app(SupervielleClient::class)->publish($this->attempt());
            $this->fail('Se esperaba un rechazo.');
        } catch (SupervielleException $exception) {
            $this->assertFalse($exception->uncertain);
            $this->assertStringNotContainsString('Private', $exception->getMessage());
        }
    }

    public function test_missing_credentials_or_certificate_prevents_all_http_calls(): void
    {
        config(['supervielle.password' => null]);
        try {
            app(SupervielleClient::class)->publish($this->attempt());
            $this->fail('Se esperaba una configuración incompleta.');
        } catch (SupervielleException $exception) {
            $this->assertFalse($exception->uncertain);
        }
        config(['supervielle.password' => 'fake-password', 'supervielle.certificate' => '/missing-test-cert.pem']);
        try {
            app(SupervielleClient::class)->publish($this->attempt());
            $this->fail('Se esperaba un certificado ausente.');
        } catch (SupervielleException $exception) {
            $this->assertFalse($exception->uncertain);
        }
        $this->assertSame([], $this->calls());
    }

    public function test_authentication_diagnostic_does_not_publish_or_expose_tokens(): void
    {
        app(SupervielleClient::class)->diagnoseAuthentication();
        $this->assertSame(['/token'], array_column($this->calls(), 'path'));
    }

    public function test_unreadable_ca_bundle_prevents_authentication(): void
    {
        config(['supervielle.ca_bundle' => '/missing-test-ca-bundle.pem']);
        try {
            app(SupervielleClient::class)->diagnoseAuthentication();
            $this->fail('Se esperaba rechazar el archivo CA ausente.');
        } catch (SupervielleException $exception) {
            $this->assertStringContainsString('autoridades certificadoras', $exception->getMessage());
        }
        $this->assertSame([], $this->calls());
    }

    public function test_custom_ca_bundle_is_used_for_token_and_publication(): void
    {
        // A readable fixture is enough: the fake verifies options without performing TLS.
        config(['supervielle.ca_bundle' => __FILE__]);
        Http::fake(function ($request, $options) {
            $this->assertSame(__FILE__, $options['verify']);

            return str_ends_with($request->url(), '/token')
                ? Http::response(['access_token' => 'fake-token'])
                : Http::response([
                    'hash' => '11111111-1111-4111-8111-111111111111',
                    'link_de_pago' => 'https://recaudaciones.supervielle.com.ar/publicacion/11111111-1111-4111-8111-111111111111',
                    'errores' => [],
                ], 201);
        });
        app(SupervielleClient::class)->publish($this->attempt());
        Http::assertSentCount(2);
    }

    public function test_token_failure_logs_only_allowlisted_technical_details(): void
    {
        Log::spy();
        Http::fake([$this->baseUrl.'/token' => Http::response([
            'error' => 'invalid_grant', 'error_description' => 'PRIVATE password=fake-password',
        ], 401)]);
        try {
            app(SupervielleClient::class)->diagnoseAuthentication();
            $this->fail('Se esperaba un rechazo de autenticacion.');
        } catch (SupervielleException $exception) {
            $this->assertSame(['stage' => 'token', 'http_status' => 401, 'oauth_error' => 'invalid_grant'], $exception->diagnostics);
            $this->assertStringNotContainsString('PRIVATE', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        Log::shouldHaveReceived('warning')->once()->with('Supervielle: fallo al obtener el token', [
            'stage' => 'token', 'http_status' => 401, 'oauth_error' => 'invalid_grant',
        ]);
        $this->assertSame([], $this->calls());
    }
}
