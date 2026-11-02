<?php

namespace Tests\Feature;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DateAndMoneyCompatibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sql_server_dates_preserve_the_day_and_local_timezone(): void
    {
        $date = EureFunctions::toCarbonDateFromYmd('2026-10-26 18:45:59.000');

        $this->assertSame('2026-10-26 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('America/Argentina/Buenos_Aires', $date->timezoneName);
        $this->assertNull(EureFunctions::toCarbonDateFromYmd(null));
    }

    public function test_end_of_month_preserves_leap_day_and_today(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 2, 15, 14, 30, 0));

        $this->assertSame('2024-02-29', EureFunctions::ultimoDiaMes()->format('Y-m-d'));
        $this->assertSame('2024-02-15 00:00:00', EureFunctions::hoy()->format('Y-m-d H:i:s'));
    }

    public function test_argentinian_money_format_and_decimal_input_remain_stable(): void
    {
        $this->assertSame('$ 1.234,50', EureFunctions::toMoneyFromFloat(1234.5));
        $this->assertSame(1234.5, EureFunctions::toNumericFromString_Argentina('1234,50'));
        $this->assertSame(1234.5, EureFunctions::toNumericFromString_Argentina('1234.50'));
        $this->assertNull(EureFunctions::toNumericFromString_Argentina('no es un importe'));
    }

    public function test_account_dates_use_explicit_sql_conversion_without_changing_legacy_cutoffs(): void
    {
        // SQL Server's conversion was checked separately in SSMS by the user.
        // Here we verify the actual helper's batch and bindings, never a real database.
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $student = (new Alumno)->forceFill(['Cod_Alumno' => 1456]);
        foreach ([
            '2026-11-02' => '2026-12-30 23:59:59.999',
            '2026-01-15' => '2026-03-03 23:59:59.999',
            '2024-01-15' => '2024-03-02 23:59:59.999',
            '2026-12-15' => '2027-01-31 23:59:59.999',
        ] as $now => $expected) {
            Carbon::setTestNow(Carbon::parse($now));
            $database->shouldReceive('select')->with(
                'DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;',
                [$expected, 1456, null]
            )->once()->andReturn([]);
            $month = $today = $overdue = 0;
            $next = null;
            $this->assertSame([], EducatioCommFunctions::CC_Obtener($student, $month, $today, $overdue, $next));
            $this->assertEquals(0, $month + $today + $overdue);
        }
    }
}
