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
    public function test_pdf_requests_include_student_code_and_a_three_digit_suffix(): void
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
            foreach (['1234', '5678', '1234'] as $codigo) {
                $result = EureFunctions::obtenerPDF('Boletin.rpt', 'Boletin_', 'previo_', [
                    ['nombre' => '@CodAlumno', 'valor' => $codigo],
                ]);
                $this->assertTrue($result->RequestsStatusOK);
            }

            $names = array_column($payloads, 'fnMiddleInsert');
            $this->assertStringStartsWith('previo_1234_', $names[0]);
            $this->assertStringStartsWith('previo_5678_', $names[1]);
            $this->assertStringStartsWith('previo_1234_', $names[2]);
            foreach ($names as $name) {
                $this->assertMatchesRegularExpression('/^previo_(1234|5678)_[1-9][0-9]{2}_$/', $name);
            }
            $this->assertSame('Boletin_', $payloads[0]['fnPrefix']);
            $this->assertSame('1234', $payloads[0]['rptParams'][0]['valor']);
        } finally {
            Mockery::close();
        }
    }
}
