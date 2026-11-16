<?php

namespace Tests\Unit;

use App\Http\Controllers\AlumnoController;
use App\Models\Alumno;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RematriculacionNecesidadesTest extends TestCase
{
    /** @dataProvider casos */
    public function test_validacion_y_guardado(array $entrada, ?array $esperadas): void
    {
        $container = new Container;
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $eure = Mockery::mock('alias:App\EureLib\EureFunctions');
        $eure->shouldReceive('getLoggedResponsableAttribute')->andReturn(null);
        $eure->shouldReceive('esResponsableDeAlumno')->andReturn(true);
        Mockery::mock('alias:App\EureLib\EducatioCommFunctions')
            ->shouldReceive('MensajeBloqueoRematriculacion')->andReturn([
                'periodoActivo' => true, 'habilitaBoton' => true,
            ]);

        $db = Mockery::mock();
        DB::swap($db);
        $catalogo = Mockery::mock();
        $db->shouldReceive('table')->with('dbo.Tipo_NecesidadesEspeciales')->andReturn($catalogo);
        $catalogo->shouldReceive('whereNull')->with('fecha_baja')->andReturnSelf();
        $catalogo->shouldReceive('get')->andReturn(collect([
            (object) ['Cod_NecesidadEspecial' => 2, 'descripcion' => 'Apoyo'],
            (object) ['Cod_NecesidadEspecial' => 85, 'descripcion' => '  OTRA  '],
        ]));

        if ($esperadas === null) {
            $db->shouldNotReceive('transaction');
            $this->expectException(ValidationException::class);
        } else {
            $db->shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
            $db->shouldReceive('selectOne')->once()->withArgs(function ($sql, $parametros) {
                $this->assertStringContainsString('DECLARE @codigoRematriculacion INT = NULL;', $sql);
                $this->assertStringContainsString('@codigoRematriculacion = @codigoRematriculacion OUTPUT;', $sql);
                $this->assertStringContainsString('SELECT @codigoRematriculacion AS Cod_Rematriculacion;', $sql);
                $this->assertSame(substr_count($sql, '?'), count($parametros));
                return true;
            })->andReturn((object) ['Cod_Rematriculacion' => 123]);
            $rematriculacion = Mockery::mock();
            $db->shouldReceive('table')->with('dbo.AlumnosRematriculacion')->andReturn($rematriculacion);
            $rematriculacion->shouldReceive('where')->with('Codigo', 123)->andReturnSelf();
            $rematriculacion->shouldReceive('where')->with('codAlumno', 10)->andReturnSelf();
            $rematriculacion->shouldReceive('exists')->andReturn(true);
            $relacion = Mockery::mock();
            $db->shouldReceive('table')->with('dbo.AlumnosRematriculacion_NecesidadesEspeciales')->andReturn($relacion);
            $relacion->shouldReceive('where')->with('Cod_Rematriculacion', 123)->andReturnSelf();
            $relacion->shouldReceive('delete')->once();
            if ($esperadas) {
                $relacion->shouldReceive('insert')->once()->with($esperadas);
            } else {
                $relacion->shouldNotReceive('insert');
            }
            $redirect = Mockery::mock();
            $redirect->shouldReceive('route')->with('home')->andReturn('guardado');
            $container->instance('redirect', $redirect);
        }

        $request = new class extends Request {
            public function validate(array $rules, array $messages): array
            {
                $factory = new Factory(new Translator(new ArrayLoader, 'es'));
                return $factory->make($this->all(), $rules, $messages)->validate();
            }
        };
        $request->replace(array_merge([
            'CursoNuevo' => 1, 'aceptaReglamento' => '1', 'tieneNecesidadEspecial' => '1',
        ], $entrada));
        $alumno = new Alumno;
        $alumno->setRawAttributes(['Cod_Alumno' => 10]);

        try {
            $this->assertSame('guardado', (new AlumnoController)->guardarRematriculacion($request, $alumno));
        } finally {
            Mockery::close();
            Facade::clearResolvedInstances();
        }
    }

    public static function casos(): array
    {
        $fila = fn ($tipo, $texto = null) => [
            'Cod_Rematriculacion' => 123, 'Cod_NecesidadEspecial' => $tipo, 'descripcion_otra' => $texto,
        ];

        return [
            'sin selección con check marcado' => [[], null],
            'selección vacía con check marcado' => [['necesidadesEspeciales' => []], null],
            'sin selección con check desmarcado' => [['tieneNecesidadEspecial' => '0'], []],
            'selección múltiple y Otra por descripción' => [
                ['necesidadesEspeciales' => ['2', '85'], 'descripcion_otra' => 'Detalle'],
                [$fila(2), $fila(85, 'Detalle')],
            ],
            'Otra sin explicación' => [['necesidadesEspeciales' => ['85']], null],
            'Otra con explicación vacía' => [['necesidadesEspeciales' => ['85'], 'descripcion_otra' => '   '], null],
            'texto sin Otra se ignora' => [
                ['necesidadesEspeciales' => ['2'], 'descripcion_otra' => 'No corresponde'], [$fila(2)],
            ],
            'check desmarcado ignora detalles' => [
                ['tieneNecesidadEspecial' => '0', 'necesidadesEspeciales' => ['85'], 'descripcion_otra' => 'Detalle'], [],
            ],
            'código no vigente' => [['necesidadesEspeciales' => ['999']], null],
            'duplicados' => [['necesidadesEspeciales' => ['2', '2']], null],
            'texto demasiado largo' => [
                ['necesidadesEspeciales' => ['85'], 'descripcion_otra' => str_repeat('a', 501)], null,
            ],
        ];
    }
}
