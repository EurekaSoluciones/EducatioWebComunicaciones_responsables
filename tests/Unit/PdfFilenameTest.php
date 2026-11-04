<?php

namespace Tests\Unit;

use App\EureLib\EureFunctions;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;

class PdfFilenameTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_pdf_requests_send_only_student_code_as_middle_insert(): void
    {
        $payloads = [];
        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldReceive('request')->once()->andReturnUsing(
            function ($method, $url, $options) use (&$payloads) {
                $payloads[] = json_decode($options['body'], true);
                return new Response(200, [], '{"RequestsStatusOK":true,"pdf_URL":"https://example.test/boletin.pdf"}');
            }
        );

        try {
            foreach (['1', '5678', '123456'] as $codigo) {
                $result = EureFunctions::obtenerPDF('Boletin.rpt', 'Boletin_', '', [
                    ['nombre' => '@CodAlumno', 'valor' => $codigo],
                ]);
                $this->assertTrue($result->RequestsStatusOK);
                $this->assertSame('https://example.test/boletin.pdf', $result->pdf_URL);
            }

            $names = array_column($payloads, 'fnMiddleInsert');
            $this->assertSame(['000001', '005678', '123456'], $names);
            $this->assertSame('Boletin_', $payloads[0]['fnPrefix']);
            $this->assertSame('1', $payloads[0]['rptParams'][0]['valor']);
        } finally {
            Mockery::close();
        }
    }
}
