<?php

namespace Tests\Unit;

use App\Http\Controllers\AlumnoController;
use App\Models\Alumno;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RematriculacionPdfTest extends TestCase
{
    /** @dataProvider estados */
    public function test_descarga_segun_permiso_actual_del_sp(bool $responsable, array $estado, bool $reporteOK, int $status, string $mensaje): void
    {
        $app = new Application;
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        $alumno = new Alumno;
        $alumno->setRawAttributes(['Cod_Alumno' => 10]);

        $eure = Mockery::mock('alias:App\EureLib\EureFunctions');
        $eure->shouldReceive('esUsuarioLogueadoEsResponsableDeAlumno')->once()->with($alumno)->andReturn($responsable);
        $db = Mockery::mock();
        DB::swap($db);

        if ($responsable) {
            $eure->shouldReceive('cliente_id')->andReturn('culturalnqn');
            $db->shouldReceive('select')->once()->with('EXEC SP_WEB_MensajeBloqueoRematriculacion ?', [10])
                ->andReturn([(object) array_merge(['PeriodoActivo' => 0, 'HabilitaBoton' => 0], $estado)]);
        } else {
            $db->shouldNotReceive('select');
        }

        if ($responsable && ($estado['habilitaPDF'] ?? false)) {
            $eure->shouldReceive('obtenerPDF')->once()->with('MatriculaRematriculacion.rpt', 'Rematriculacion_', '', [
                ['nombre' => '@CodAlumno', 'valor' => 10],
            ])->andReturn((object) [
                'RequestsStatusOK' => $reporteOK,
                'RequestsStatusObs' => 'Error del reporte',
                'pdf_URL' => 'https://example.test/comprobante.pdf',
            ]);
        } else {
            $eure->shouldNotReceive('obtenerPDF');
        }

        if ($status === 302) {
            $redirect = Mockery::mock();
            $redirect->shouldReceive('away')->once()->with('https://example.test/comprobante.pdf')->andReturn('pdf');
            $app->instance('redirect', $redirect);
        }

        try {
            try {
                $resultado = (new AlumnoController)->descargarRematriculacion($alumno);
                $this->assertSame(302, $status);
                $this->assertSame('pdf', $resultado);
            } catch (HttpException $e) {
                $this->assertSame($status, $e->getStatusCode());
                $this->assertSame($mensaje, $e->getMessage());
            }
        } finally {
            Mockery::close();
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
            Container::setInstance(null);
        }
    }

    public static function estados(): array
    {
        return [
            'vigente incluso fuera del periodo' => [true, ['habilitaPDF' => 1], true, 302, ''],
            'vencida' => [true, ['habilitaPDF' => 0, 'mensajePDF' => 'Debe rematricularse nuevamente.'], true, 403, 'Debe rematricularse nuevamente.'],
            'campos aun ausentes' => [true, [], true, 403, 'El comprobante de reinscripción no está disponible.'],
            'bloqueo sin mensaje' => [true, ['habilitaPDF' => 0, 'mensajePDF' => null], true, 403, 'El comprobante de reinscripción no está disponible.'],
            'otro alumno' => [false, ['habilitaPDF' => 1], true, 403, 'Acceso no permitido'],
            'fallo del reporte' => [true, ['habilitaPDF' => 1], false, 400, 'Error del reporte'],
        ];
    }
}
