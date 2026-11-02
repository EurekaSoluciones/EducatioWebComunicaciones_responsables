<?php

namespace Tests\Feature;

use App\EureLib\EureFunctions;
use App\Http\Controllers\CuentaCorrienteController;
use App\Http\Middleware\HandleEurekaRequests;
use App\Models\Alumno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InMemoryUserProvider;
use Tests\TestCase;

class FamilyPortalTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Isolated legacy schema: no SQL Server, migrations or shared attachments.
        Schema::create('Responsables', function (Blueprint $table) {
            $table->integer('Cod_Responsable')->primary();
            $table->string('Nombre');
            $table->string('Apellido');
            $table->string('Email')->nullable();
            $table->string('Telefono')->nullable();
            $table->string('Celular')->nullable();
            $table->string('dni')->nullable();
        });
        Schema::create('Alumnos', function (Blueprint $table) {
            $table->integer('Cod_Alumno')->primary();
            $table->string('Nombre');
            $table->string('Apellido');
            $table->integer('Responsable1')->nullable();
            $table->integer('Responsable2')->nullable();
            $table->integer('Tipo_Responsable1')->nullable();
            $table->integer('Tipo_Responsable2')->nullable();
            $table->integer('Cod_Grupo')->nullable();
            $table->date('fecha_baja')->nullable();
        });
        Schema::create('Grupo', function (Blueprint $table) {
            $table->integer('Cod_Grupo')->primary();
            $table->integer('Curso')->nullable();
            $table->integer('Division')->nullable();
            $table->integer('Turno')->nullable();
        });
        foreach (['Curso' => 'Cod_Curso', 'Division' => 'Cod_Division', 'Turno' => 'Cod_Turno', 'Tipo-Responsable' => 'Cod_Tipo_Responsable'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key) {
                $table->integer($key)->primary();
                $table->string('Descripcion');
            });
        }
        Schema::create('WEB_DatosAlumnos', function (Blueprint $table) {
            $table->integer('Cod_Alumno')->primary();
            $table->string('responsableAcademico')->nullable();
            $table->string('plan')->nullable();
        });
        Schema::create('web_alumnos', function (Blueprint $table) {
            $table->integer('Cod_Alumno')->primary();
            $table->string('avatarImg')->nullable();
            $table->string('bgImg')->nullable();
            $table->timestamps();
        });
        Schema::create('web_users', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('Cod_Responsable');
            $table->integer('Cod_Profesor')->nullable();
            $table->integer('cod_usuario')->nullable();
            $table->string('tipo');
            $table->string('avatarImg')->nullable();
            $table->string('nombres')->nullable();
            $table->string('apellidos')->nullable();
            $table->string('desc')->nullable();
            $table->dateTime('fh_visualizacion_cartelera_general')->nullable();
            $table->timestamps();
        });
        Schema::create('web_carteleras', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('tipo');
            $table->string('nombre');
            $table->text('cartelera')->nullable();
            $table->boolean('activa');
            $table->timestamps();
        });
        Schema::create('web_comunicaciones', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('asunto')->nullable();
            $table->text('msg')->nullable();
            $table->integer('usuario_id')->nullable();
            $table->integer('tipo_id')->nullable();
            $table->integer('tipo_respuesta_id')->nullable();
            $table->text('listas_respuestas_fijas')->nullable();
            $table->string('templateDestinatarios')->nullable();
            $table->string('etiquetas')->nullable();
            $table->string('cursos')->nullable();
            $table->string('divisiones')->nullable();
            $table->string('turnos')->nullable();
            $table->timestamps();
        });
        Schema::create('web_comunicaciones_tipos', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('descripcion');
        });
        Schema::create('web_adjuntos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entity');
            $table->integer('entityId')->nullable();
            $table->string('tempId')->nullable();
            $table->string('filename')->nullable();
            $table->string('originalFilename')->nullable();
            $table->timestamps();
        });
        Schema::create('web_comunicaciones_destinatarios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('comunicacion_id');
            $table->integer('Cod_Responsable');
            $table->integer('Cod_Alumno');
            $table->boolean('leido');
            $table->dateTime('fhLeido')->nullable();
            $table->dateTime('fhRespuesta')->nullable();
            $table->text('respuesta')->nullable();
            $table->timestamps();
        });
        Schema::create('web_comunicaciones_entrantes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tipo_id')->nullable();
            $table->integer('Cod_Responsable');
            $table->integer('Cod_Alumno');
            $table->integer('Cod_Usuario')->nullable();
            $table->string('tipo')->nullable();
            $table->string('asunto')->nullable();
            $table->text('msg')->nullable();
            $table->text('respuesta')->nullable();
            $table->dateTime('fhLeido')->nullable();
            $table->dateTime('fhRespuesta')->nullable();
            $table->dateTime('fhRespuestaLeida')->nullable();
            $table->timestamps();
        });

        DB::table('Responsables')->insert([
            ['Cod_Responsable' => 7, 'Nombre' => 'Ana', 'Apellido' => 'Pérez'],
            ['Cod_Responsable' => 8, 'Nombre' => 'Otro', 'Apellido' => 'Responsable'],
        ]);
        DB::table('Alumnos')->insert([
            ['Cod_Alumno' => 11, 'Nombre' => 'Lucía', 'Apellido' => 'Pérez', 'Responsable1' => 7, 'Responsable2' => 8, 'fecha_baja' => null],
            ['Cod_Alumno' => 12, 'Nombre' => 'Tomás', 'Apellido' => 'Pérez', 'Responsable1' => 8, 'Responsable2' => 7, 'fecha_baja' => null],
            ['Cod_Alumno' => 13, 'Nombre' => 'Baja', 'Apellido' => 'Pérez', 'Responsable1' => 7, 'Responsable2' => null, 'fecha_baja' => '2026-01-01'],
            ['Cod_Alumno' => 14, 'Nombre' => 'Ajeno', 'Apellido' => 'Pérez', 'Responsable1' => 8, 'Responsable2' => null, 'fecha_baja' => null],
        ]);
        DB::table('web_alumnos')->insert(['Cod_Alumno' => 11, 'avatarImg' => 'alumno.jpg']);
        DB::table('web_users')->insert(['id' => 42, 'Cod_Responsable' => 7, 'tipo' => 'Responsable', 'avatarImg' => 'responsable.jpg']);
        DB::table('web_comunicaciones')->insert([['id' => 100], ['id' => 101]]);
        DB::table('web_comunicaciones_destinatarios')->insert([
            ['comunicacion_id' => 100, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => false],
            ['comunicacion_id' => 100, 'Cod_Responsable' => 7, 'Cod_Alumno' => 12, 'leido' => false],
            ['comunicacion_id' => 101, 'Cod_Responsable' => 8, 'Cod_Alumno' => 14, 'leido' => false],
        ]);
        DB::table('web_comunicaciones_entrantes')->insert([
            ['Cod_Responsable' => 7, 'Cod_Alumno' => 12, 'fhRespuesta' => '2026-10-01', 'fhRespuestaLeida' => null],
            ['Cod_Responsable' => 8, 'Cod_Alumno' => 11, 'fhRespuesta' => '2026-10-01', 'fhRespuestaLeida' => null],
            ['Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'fhRespuesta' => '2026-10-01', 'fhRespuestaLeida' => '2026-10-02'],
        ]);

        $this->user = (new User)->forceFill([
            'id' => 42, 'Cod_Responsable' => 7, 'tipo' => 'Responsable',
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'avatarImg' => 'responsable.jpg',
            'password' => Hash::make('only-test'), 'remember_token' => 'do-not-expose',
        ]);
        Auth::guard('web')->setProvider(new InMemoryUserProvider($this->user));
    }

    public function test_password_page_uses_react_and_exposes_only_the_current_account(): void
    {
        $this->actingAs($this->user);
        $response = $this->inertiaHome('/password')->assertOk()
            ->assertJsonPath('component', 'family/Password')
            ->assertJsonPath('props.profile.responsibleId', '7')
            ->assertJsonPath('props.passwordUpdateUrl', route('auth.password.update'));
        $this->assertStringNotContainsString($this->user->password, $response->getContent());
        $this->assertStringNotContainsString('do-not-expose', $response->getContent());
    }

    public function test_password_validation_never_flashes_secrets_or_changes_the_hash(): void
    {
        $this->actingAs($this->user);
        $original = $this->user->password;
        foreach ([
            [[], ['currentPassword', 'newPassword', 'confirmPassword']],
            [['currentPassword' => 'wrong', 'newPassword' => 'a-new-secret', 'confirmPassword' => 'a-new-secret'], ['currentPassword']],
            [['currentPassword' => 'only-test', 'newPassword' => 'a-new-secret', 'confirmPassword' => 'mismatch'], ['confirmPassword']],
            [['currentPassword' => ['only-test'], 'newPassword' => ['secret'], 'confirmPassword' => ['secret']], ['currentPassword', 'newPassword', 'confirmPassword']],
        ] as [$data, $errors]) {
            $this->post('/usuarios/password/update', $data)->assertRedirect(route('auth.password'))->assertSessionHasErrors($errors);
            foreach (['currentPassword', 'newPassword', 'confirmPassword'] as $field) {
                $this->assertNull(session()->getOldInput($field));
            }
            $this->assertSame($original, $this->user->password);
            $this->assertAuthenticatedAs($this->user);
        }
    }

    public function test_password_change_updates_only_own_account_keeps_current_session_and_rejects_stale_session(): void
    {
        Schema::table('web_users', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
        });
        $oldHash = Hash::make(' current secret ');
        $otherHash = Hash::make('another-account');
        DB::table('web_users')->where('id', 42)->update(['password' => $oldHash]);
        DB::table('web_users')->insert(['id' => 43, 'Cod_Responsable' => 8, 'tipo' => 'Responsable', 'password' => $otherHash]);
        $user = User::findOrFail(42);
        Auth::guard('web')->setProvider(new InMemoryUserProvider($user));
        $this->actingAs($user)->withSession(['password_hash_web' => $oldHash]);
        $this->post('/usuarios/password/update', [
            'id' => 43, 'Cod_Responsable' => 8,
            'currentPassword' => ' current secret ', 'newPassword' => ' new secret ', 'confirmPassword' => ' new secret ',
        ])->assertRedirect(route('responsables.show', 7))->assertSessionHas('success');
        $newHash = DB::table('web_users')->where('id', 42)->value('password');
        $this->assertTrue(Hash::check(' new secret ', $newHash));
        $this->assertFalse(Hash::check('new secret', $newHash));
        $this->assertFalse(Hash::check(' current secret ', $newHash));
        $this->assertSame($otherHash, DB::table('web_users')->where('id', 43)->value('password'));
        foreach (['currentPassword', 'newPassword', 'confirmPassword'] as $field) {
            $this->assertNull(session()->getOldInput($field));
        }
        $this->get('/password')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->withSession(['password_hash_web' => $oldHash])->get('/password')->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->actingAs($user)->withSession(['password_hash_web' => $oldHash])->getJson('/password')->assertUnauthorized();
        $this->assertGuest('web');
    }

    public function test_home_exposes_only_active_related_students_without_creating_photo_or_user_records(): void
    {
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->inertiaHome()->assertOk()->assertJsonPath('component', 'family/Home');
        $students = collect($response->json('props.portal.students'))->keyBy('id');
        $this->assertSame([11, 12], $students->keys()->all());
        $this->assertStringContainsString('/storage/alumno.jpg', $students[11]['avatar']);
        $this->assertNull($students[12]['avatar']);
        $this->assertSame(route('comunicaciones.indexA', ['alumno' => 12, 'desde' => now()->startOfYear()->subMonths(2)->toDateString()]), $students[12]['links']['received']);
        $this->assertSame('', $students[12]['course']);
        $this->assertFalse($students[12]['enrollment']['periodoActivo']);
        $this->assertArrayNotHasKey('password', $response->json('props.portal.user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('props.portal.user'));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        $this->assertDatabaseCount('web_users', 1);
        $this->assertDatabaseCount('web_alumnos', 1);
    }

    public function test_notification_counts_are_scoped_and_do_not_mark_messages_read(): void
    {
        $this->actingAs($this->user);
        $this->getJson('/familia/notificaciones')->assertOk()
            ->assertJsonPath('received', 1)->assertJsonPath('replies', 1)
            ->assertJsonPath('byStudent.0.studentId', '11')->assertJsonPath('byStudent.0.received', 1)
            ->assertJsonPath('byStudent.0.replies', 0)
            ->assertJsonPath('byStudent.1.studentId', '12')->assertJsonPath('byStudent.1.replies', 1)
            ->assertJsonCount(2, 'byStudent');
        $this->assertSame(0, DB::table('web_comunicaciones_destinatarios')->where('leido', true)->count());
        $this->assertSame(2, DB::table('web_comunicaciones_entrantes')->whereNull('fhRespuestaLeida')->count());
    }

    public function test_new_general_board_redirects_before_rendering_or_marking_it_seen(): void
    {
        $this->addBoard();
        $this->actingAs($this->user);
        $this->get('/home')->assertRedirect(route('carteleras.show', 5));
        $this->assertNull($this->user->fh_visualizacion_cartelera_general);
    }

    public function test_seen_board_and_enrollment_result_reach_the_new_home(): void
    {
        $this->addBoard();
        $this->user->fh_visualizacion_cartelera_general = '2026-10-28 11:00:00';
        $this->actingAs($this->user);
        $this->withSession(['resultadoRematriculacion' => ['alumnoId' => 12, 'tipo' => 'success', 'mensaje' => 'Solicitud registrada']]);
        $this->inertiaHome()->assertOk()->assertJsonPath('props.portal.board.title', 'Novedades')
            ->assertJsonPath('props.enrollmentResult.studentId', '12')
            ->assertJsonPath('props.enrollmentResult.message', 'Solicitud registrada');
    }

    public function test_home_previews_all_active_boards_without_marking_them_seen(): void
    {
        $this->addBoard();
        DB::table('web_carteleras')->insert([
            ['id' => 6, 'tipo' => 'OTRA', 'nombre' => 'Calendario', 'cartelera' => '<p>Fechas</p>', 'activa' => true],
            ['id' => 7, 'tipo' => 'OTRA', 'nombre' => 'Actividades', 'cartelera' => null, 'activa' => true],
            ['id' => 8, 'tipo' => 'OTRA', 'nombre' => 'Borrador', 'cartelera' => 'No publicado', 'activa' => false],
        ]);
        $this->user->fh_visualizacion_cartelera_general = '2026-10-28 11:00:00';
        $seen = $this->user->fh_visualizacion_cartelera_general;
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $this->inertiaHome()->assertOk()->assertJsonCount(3, 'props.boardPreviews')
            ->assertJsonPath('props.boardPreviews.0.title', 'Novedades')
            ->assertJsonPath('props.boardPreviews.1.html', '<p>Fechas</p>')
            ->assertJsonPath('props.boardPreviews.1.url', route('carteleras.show', 6))
            ->assertJsonPath('props.boardPreviews.2.html', '');
        $this->assertEquals($seen, $this->user->fh_visualizacion_cartelera_general);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_home_supports_a_responsable_without_students_or_boards(): void
    {
        $this->user->Cod_Responsable = 9;
        DB::table('Responsables')->insert(['Cod_Responsable' => 9, 'Nombre' => 'Sin', 'Apellido' => 'Alumnos']);
        $this->actingAs($this->user);
        $this->inertiaHome()->assertOk()->assertJsonPath('props.portal.students', [])
            ->assertJsonPath('props.boardPreviews', [])
            ->assertJsonPath('props.portal.board', null)->assertJsonPath('props.portal.notifications.received', 0);
    }

    public function test_missing_responsable_and_wrong_user_type_are_rejected(): void
    {
        $this->user->Cod_Responsable = 999;
        $this->actingAs($this->user);
        $this->get('/home')->assertForbidden();
        $this->getJson('/familia/notificaciones')->assertForbidden();
        $this->user->tipo = 'Profe';
        $this->get('/home')->assertForbidden();
    }

    public function test_guest_notifications_require_login_without_domain_access(): void
    {
        $this->getJson('/familia/notificaciones')->assertUnauthorized();
    }

    public function test_authenticated_login_redirects_to_home(): void
    {
        $this->actingAs($this->user);
        $this->get('/login')->assertRedirect(route('home'));
    }

    public function test_student_profile_scopes_messages_and_files_without_marking_reads_or_creating_records(): void
    {
        config(['eureka.client_id' => 'ifesnqn', 'eureka.institution' => 'Colegio IFES Neuquén']);
        DB::table('web_users')->where('id', 42)->update(['nombres' => 'Ana', 'apellidos' => 'Pérez']);
        for ($id = 200; $id <= 206; $id++) {
            DB::table('web_comunicaciones')->insert(['id' => $id, 'asunto' => 'Mensaje '.$id, 'usuario_id' => 42, 'created_at' => '2026-10-29 10:00:00']);
            DB::table('web_comunicaciones_destinatarios')->insert(['comunicacion_id' => $id, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => $id === 205]);
        }
        DB::table('web_comunicaciones')->insert(['id' => 999, 'asunto' => 'Privado de otro responsable']);
        // Separate rows must not grant access: same pupil/other guardian and same guardian/other pupil.
        DB::table('web_comunicaciones_destinatarios')->insert([
            ['comunicacion_id' => 999, 'Cod_Responsable' => 8, 'Cod_Alumno' => 11, 'leido' => false],
            ['comunicacion_id' => 999, 'Cod_Responsable' => 7, 'Cod_Alumno' => 12, 'leido' => false],
            ['comunicacion_id' => 206, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => false],
        ]);
        DB::table('web_adjuntos')->insert([
            ['id' => 1, 'entity' => 'alumno', 'entityId' => 11, 'filename' => 'legajo.pdf', 'originalFilename' => 'Ficha médica.pdf'],
            ['id' => 2, 'entity' => 'alumno', 'entityId' => 12, 'filename' => 'otro.pdf', 'originalFilename' => 'Otro'],
            ['id' => 3, 'entity' => 'comunicacion', 'entityId' => 11, 'filename' => 'mensaje.pdf', 'originalFilename' => 'Mensaje'],
        ]);
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->inertiaHome('/alumno/11')->assertOk()
            ->assertJsonPath('component', 'family/StudentProfile')
            ->assertJsonPath('props.theme.clientId', 'ifesnqn')
            ->assertJsonPath('props.profile.studentId', '11')
            ->assertJsonPath('props.profile.newAttachmentUrl', route('alumnos.adjunto.create', 11))
            ->assertJsonCount(5, 'props.profile.communications')
            ->assertJsonPath('props.profile.communications.0.id', '206')
            ->assertJsonPath('props.profile.communications.0.unread', true)
            ->assertJsonPath('props.profile.communications.0.sender.name', 'Ana Pérez')
            ->assertJsonPath('props.profile.communications.1.unread', false)
            ->assertJsonCount(1, 'props.profile.attachments')
            ->assertJsonPath('props.profile.attachments.0.name', 'Ficha médica.pdf');
        $this->assertSame(['206', '205', '204', '203', '202'], array_column($response->json('props.profile.communications'), 'id'));
        $this->assertStringEndsWith('/storage/legajo.pdf', $response->json('props.profile.attachments.0.url'));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        $this->assertDatabaseCount('web_users', 1);
        $this->assertDatabaseCount('web_alumnos', 1);
    }

    public function test_student_profile_handles_missing_relations_empty_files_and_legacy_photo_feedback(): void
    {
        $this->actingAs($this->user);
        $this->withSession(['success' => 'Perfil Actualizado.']);
        $response = $this->inertiaHome('/alumno/12')->assertOk()
            ->assertJsonPath('props.profile.success', 'Perfil Actualizado.')
            ->assertJsonPath('props.profile.attachments', [])
            ->assertJsonPath('props.profile.communications.0.sender.name', 'Institución')
            ->assertJsonPath('props.profile.communications.0.date', null);
        $student = collect($response->json('props.portal.students'))->firstWhere('id', '12');
        $this->assertNull($student['avatar']);
        $this->assertSame('', $student['course']);
        $this->assertSame(route('alumnos.editPic', 12), $student['links']['photo']);
        $this->assertDatabaseCount('web_alumnos', 1);
        // Preserve the existing direct-access rule for related pupils who are no longer active.
        $response = $this->inertiaHome('/alumno/13')->assertOk()->assertJsonPath('props.profile.communications', []);
        $this->assertContains('13', array_column($response->json('props.portal.students'), 'id'));
    }

    public function test_student_profile_enforces_authentication_relationship_and_user_type(): void
    {
        $this->get('/alumno/11')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->get('/alumno/14')->assertForbidden();
        $this->get('/alumno/99999')->assertNotFound();
        $this->user->tipo = 'Profe';
        $this->get('/alumno/11')->assertForbidden();
        $this->user->tipo = 'Responsable';
        $this->user->Cod_Responsable = 999;
        $this->user->unsetRelation('responsable');
        $this->get('/alumno/11')->assertForbidden();
    }

    public function test_responsible_profile_and_photo_editor_expose_own_data_without_writes(): void
    {
        DB::table('Responsables')->where('Cod_Responsable', 7)->update([
            'Email' => 'ana@example.test', 'Telefono' => '12345', 'Celular' => '67890', 'dni' => '00123456',
        ]);
        DB::table('WEB_DatosAlumnos')->insert(['Cod_Alumno' => 11, 'plan' => 'Primario']);
        $this->user->login = 'ana';
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $this->get('/resp/logged')->assertRedirect(route('responsables.show', 7));
        $response = $this->inertiaHome('/resp/7')->assertOk()->assertJsonPath('component', 'family/ResponsibleProfile')
            ->assertJsonPath('props.profile.responsibleId', '7')->assertJsonPath('props.profile.login', 'ana')
            ->assertJsonPath('props.profile.email', 'ana@example.test')->assertJsonPath('props.profile.phone', '12345')
            ->assertJsonPath('props.profile.mobile', '67890')->assertJsonPath('props.profile.dni', '00123456')
            ->assertJsonPath('props.profile.studentPlans.11', 'Primario')->assertJsonCount(2, 'props.portal.students')
            ->assertJsonPath('props.profile.updatePhotoUrl', route('responsables.update', 42));
        $this->assertArrayNotHasKey('password', $response->json('props.portal.user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('props.portal.user'));
        $this->inertiaHome('/resp/7/edit')->assertOk()->assertJsonPath('component', 'family/ResponsiblePhoto');
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_responsible_profile_rejects_other_people_and_supports_empty_data(): void
    {
        $this->get('/resp/7')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->inertiaHome('/resp/8')->assertForbidden();
        $this->inertiaHome('/resp/8/edit')->assertForbidden();
        $this->get('/resp/999')->assertNotFound();
        DB::table('Alumnos')->update(['fecha_baja' => '2026-01-01']);
        $this->user->avatarImg = null;
        $this->inertiaHome('/resp/7')->assertOk()->assertJsonPath('props.profile.email', '')
            ->assertJsonPath('props.profile.dni', '')->assertJsonPath('props.portal.students', [])
            ->assertJsonPath('props.portal.user.avatar', null);
        $this->user->tipo = 'Profe';
        $this->get('/resp/logged')->assertForbidden();
        $this->get('/resp/7')->assertForbidden();
        $this->get('/resp/7/edit')->assertForbidden();
    }

    public function test_responsible_can_replace_and_remove_own_photo_preserving_shared_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('responsable.jpg', 'previous photo');
        $this->actingAs($this->user);
        $this->postJson('/resp/42/edit', ['remove_photo' => '0', 'photo' => UploadedFile::fake()->image('recorte.png', 700, 700)])
            ->assertOk()->assertJsonPath('redirectUrl', route('responsables.show', 7));
        $filename = DB::table('web_users')->where('id', 42)->value('avatarImg');
        $this->assertStringStartsWith('AVResp_', $filename);
        $image = getimagesizefromstring(Storage::disk('public')->get($filename));
        $this->assertSame([512, 512, 'image/png'], [$image[0], $image[1], $image['mime']]);
        Storage::disk('public')->assertExists('responsable.jpg');
        $this->postJson('/resp/42/edit', ['remove_photo' => true, 'nombre' => 'Nombre inyectado'])
            ->assertOk()->assertSessionHas('success', 'Se quitó tu foto de perfil.');
        $this->assertSame('', DB::table('web_users')->where('id', 42)->value('avatarImg'));
        $this->assertSame('Ana', DB::table('Responsables')->where('Cod_Responsable', 7)->value('Nombre'));
        Storage::disk('public')->assertExists($filename);
        $this->assertDatabaseCount('web_users', 1);
    }

    public function test_responsible_photo_requires_own_user_valid_crop_and_csrf(): void
    {
        Storage::fake('public');
        DB::table('web_users')->insert(['id' => 43, 'Cod_Responsable' => 8, 'tipo' => 'Responsable', 'avatarImg' => 'other.png']);
        $this->postJson('/resp/42/edit', ['remove_photo' => true])->assertUnauthorized();
        $this->actingAs($this->user);
        $this->postJson('/resp/43/edit', ['remove_photo' => true])->assertForbidden();
        $this->postJson('/resp/42/edit', ['remove_photo' => false])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson('/resp/42/edit', ['remove_photo' => false, 'photo' => UploadedFile::fake()->image('wide.png', 200, 100)])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->postJson('/resp/42/edit', ['remove_photo' => true, 'photo' => UploadedFile::fake()->image('crop.png', 100, 100)])
            ->assertUnprocessable();
        $this->assertSame('responsable.jpg', DB::table('web_users')->where('id', 42)->value('avatarImg'));
        $this->assertSame('other.png', DB::table('web_users')->where('id', 43)->value('avatarImg'));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->app['env'] = 'local';
        $this->postJson('/resp/42/edit', ['remove_photo' => true])->assertStatus(419);
    }

    public function test_student_photo_editor_is_read_only_and_keeps_ownership_checks(): void
    {
        $this->get('/alumno/11/editPic')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $this->inertiaHome('/alumno/12/editPic')->assertOk()->assertJsonPath('component', 'family/StudentPhoto')
            ->assertJsonPath('props.photo.studentId', '12')
            ->assertJsonPath('props.photo.updateUrl', route('alumnos.updatePic', 12));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        $this->assertDatabaseCount('web_alumnos', 1);
        $this->get('/alumno/14/editPic')->assertForbidden();
    }

    public function test_student_photo_save_normalizes_the_crop_and_keeps_previous_shared_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('alumno.jpg', 'previous-shared-file');
        $this->actingAs($this->user);
        $this->postJson('/alumno/11/editPic', [
            '_method' => 'PATCH', 'remove_photo' => '0', 'photo' => UploadedFile::fake()->image('recorte.png', 700, 700),
        ])->assertOk()->assertJsonPath('redirectUrl', route('alumnos.show', 11))
            ->assertSessionHas('success', 'Foto del alumno actualizada.');
        $filename = DB::table('web_alumnos')->where('Cod_Alumno', 11)->value('avatarImg');
        $this->assertMatchesRegularExpression('/^AVAlumno_[a-f0-9-]+\.png$/', $filename);
        Storage::disk('public')->assertExists($filename);
        Storage::disk('public')->assertExists('alumno.jpg');
        $size = getimagesizefromstring(Storage::disk('public')->get($filename));
        $this->assertSame([512, 512, IMAGETYPE_PNG], array_slice($size, 0, 3));
    }

    public function test_student_photo_can_create_the_missing_web_record_and_remove_without_deleting_files(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);
        $this->patchJson('/alumno/12/editPic', ['remove_photo' => true])->assertOk();
        $this->assertDatabaseCount('web_alumnos', 1);
        $this->postJson('/alumno/12/editPic', ['_method' => 'PATCH', 'remove_photo' => false, 'photo' => UploadedFile::fake()->image('foto.png', 512, 512)])->assertOk();
        $filename = DB::table('web_alumnos')->where('Cod_Alumno', 12)->value('avatarImg');
        $this->assertDatabaseCount('web_alumnos', 2);
        $this->patchJson('/alumno/12/editPic', ['remove_photo' => true])->assertOk()
            ->assertSessionHas('success', 'Se quitó la foto del alumno.');
        $this->assertSame('', DB::table('web_alumnos')->where('Cod_Alumno', 12)->value('avatarImg'));
        Storage::disk('public')->assertExists($filename);
    }

    public function test_student_photo_rejects_invalid_missing_oversized_and_conflicting_uploads(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);
        $this->patchJson('/alumno/11/editPic', ['remove_photo' => false])->assertUnprocessable()->assertJsonValidationErrors('photo');
        $invalid = [
            UploadedFile::fake()->create('bad.png', 2, 'text/plain'),
            UploadedFile::fake()->image('rectangle.png', 512, 300),
            UploadedFile::fake()->image('huge.png', 1025, 1025),
            UploadedFile::fake()->image('tiny.png', 32, 32),
            UploadedFile::fake()->image('large.png', 512, 512)->size(2049),
        ];
        foreach ($invalid as $file) {
            $this->postJson('/alumno/11/editPic', ['_method' => 'PATCH', 'remove_photo' => false, 'photo' => $file])->assertUnprocessable()->assertJsonValidationErrors('photo');
        }
        $this->postJson('/alumno/11/editPic', ['_method' => 'PATCH', 'remove_photo' => true, 'photo' => UploadedFile::fake()->image('photo.png', 512, 512)])->assertUnprocessable();
        $this->assertSame('alumno.jpg', DB::table('web_alumnos')->where('Cod_Alumno', 11)->value('avatarImg'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_student_photo_writes_require_a_related_responsable_and_csrf(): void
    {
        Storage::fake('public');
        $this->patchJson('/alumno/11/editPic', ['remove_photo' => true])->assertUnauthorized();
        $this->actingAs($this->user);
        $this->patchJson('/alumno/14/editPic', ['remove_photo' => true])->assertForbidden();
        $this->postJson('/alumno/14/editPic', ['_method' => 'PATCH', 'remove_photo' => false, 'photo' => UploadedFile::fake()->image('photo.png', 512, 512)])->assertForbidden();
        $this->user->tipo = 'Profe';
        $this->patchJson('/alumno/11/editPic', ['remove_photo' => true])->assertForbidden();
        $this->user->tipo = 'Responsable';
        $this->app['env'] = 'local';
        $this->patchJson('/alumno/11/editPic', ['remove_photo' => true])->assertStatus(419);
        $this->assertSame('alumno.jpg', DB::table('web_alumnos')->where('Cod_Alumno', 11)->value('avatarImg'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_photo_storage_does_not_replace_the_existing_avatar(): void
    {
        $this->actingAs($this->user);
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->postJson('/alumno/11/editPic', ['_method' => 'PATCH', 'remove_photo' => false, 'photo' => UploadedFile::fake()->image('photo.png', 512, 512)])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertSame('alumno.jpg', DB::table('web_alumnos')->where('Cod_Alumno', 11)->value('avatarImg'));
    }

    public function test_full_board_uses_react_and_only_its_attachments_without_writes_for_other_boards(): void
    {
        $this->addBoard();
        DB::table('web_carteleras')->where('id', 5)->update(['tipo' => 'OTRA', 'created_at' => '2026-10-27 09:00:00']);
        DB::table('web_adjuntos')->insert([
            ['id' => 1, 'entity' => 'cartelera', 'entityId' => 5, 'filename' => 'calendario.pdf', 'originalFilename' => 'Calendario escolar.pdf'],
            ['id' => 2, 'entity' => 'cartelera', 'entityId' => 6, 'filename' => 'otra.pdf', 'originalFilename' => 'Otra'],
            ['id' => 3, 'entity' => 'alumno', 'entityId' => 5, 'filename' => 'legajo.pdf', 'originalFilename' => 'Legajo'],
            ['id' => 4, 'entity' => 'cartelera', 'entityId' => 5, 'filename' => null, 'originalFilename' => 'Sin archivo'],
        ]);
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $this->inertiaHome('/carteleras/5')->assertOk()->assertJsonPath('component', 'family/Board')
            ->assertJsonPath('props.board.html', '<p>Bienvenidos</p>')
            ->assertJsonPath('props.board.createdAt', '2026-10-27T09:00:00-03:00')
            ->assertJsonPath('props.board.url', route('carteleras.show', 5))
            ->assertJsonCount(1, 'props.board.attachments')
            ->assertJsonPath('props.board.attachments.0.name', 'Calendario escolar.pdf')
            ->assertJsonPath('props.board.attachments.0.url', Storage::disk('public')->url('calendario.pdf'));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_full_general_board_records_read_and_then_home_is_accessible(): void
    {
        $this->addBoard();
        $this->user->exists = true;
        $this->user->syncOriginal();
        $this->actingAs($this->user);
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
        $this->get('/carteleras/GENERAL')->assertRedirect(route('carteleras.show', 5));
        $this->assertNull(DB::table('web_users')->where('id', 42)->value('fh_visualizacion_cartelera_general'));
        $this->inertiaHome('/carteleras/5')->assertOk()->assertJsonPath('component', 'family/Board');
        $this->assertSame('2026-10-30 12:00:00', DB::table('web_users')->where('id', 42)->value('fh_visualizacion_cartelera_general'));
        $this->assertSame(0, DB::table('web_comunicaciones_destinatarios')->where('leido', true)->count());
        $this->inertiaHome()->assertOk()->assertJsonPath('component', 'family/Home');
    }

    public function test_board_requires_responsable_and_rejects_inactive_and_missing_boards(): void
    {
        $this->addBoard();
        $this->get('/carteleras/5')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        DB::table('web_carteleras')->where('id', 5)->update(['activa' => false]);
        $this->get('/carteleras/5')->assertStatus(400);
        $this->get('/carteleras/999')->assertNotFound();
        $this->get('/carteleras/GENERAL')->assertRedirect(route('home'));
        $this->user->tipo = 'Profe';
        $this->get('/carteleras/5')->assertForbidden();
        $this->get('/carteleras/GENERAL')->assertForbidden();
        $this->assertNull(DB::table('web_users')->where('id', 42)->value('fh_visualizacion_cartelera_general'));
    }

    public function test_board_supports_empty_content_no_attachments_and_no_students(): void
    {
        $this->addBoard();
        DB::table('web_carteleras')->where('id', 5)->update(['tipo' => 'OTRA', 'cartelera' => null]);
        DB::table('Alumnos')->update(['fecha_baja' => '2026-01-01']);
        $this->actingAs($this->user);
        $this->inertiaHome('/carteleras/5')->assertOk()->assertJsonPath('props.board.html', '')
            ->assertJsonPath('props.board.attachments', [])->assertJsonPath('props.portal.students', []);
    }

    private function seedReceivedDetail(int $mode = 2): int
    {
        $this->seedReceived();
        DB::table('web_comunicaciones')->where('id', 100)->update([
            'tipo_respuesta_id' => $mode, 'listas_respuestas_fijas' => 'Sí; No; ; Sí',
            'msg' => '<p>Mensaje <strong>con formato</strong></p>', 'templateDestinatarios' => 'CURSO_COMPLETO',
            'etiquetas' => 'Salida;Importante;;Salida',
        ]);
        DB::table('web_comunicaciones_destinatarios')->insert([
            'comunicacion_id' => 100, 'Cod_Responsable' => 8, 'Cod_Alumno' => 11, 'leido' => false,
        ]);
        // Simulate the legacy derived read flag without ever writing it in application code.
        DB::statement('CREATE TRIGGER recipient_read_flag AFTER UPDATE OF fhLeido ON web_comunicaciones_destinatarios BEGIN UPDATE web_comunicaciones_destinatarios SET leido = (NEW.fhLeido IS NOT NULL) WHERE id = NEW.id; END');

        return DB::table('web_comunicaciones_destinatarios')->where('comunicacion_id', 100)->where('Cod_Alumno', 11)->where('Cod_Responsable', 7)->value('id');
    }

    public function test_received_detail_marks_only_current_pair_and_preserves_first_read(): void
    {
        $id = $this->seedReceivedDetail();
        $this->actingAs($this->user);
        $response = $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()->assertJsonPath('component', 'family/ReceivedDetail')
            ->assertJsonPath('props.received.message.html', '<p>Mensaje <strong>con formato</strong></p>')
            ->assertJsonPath('props.received.message.audience', 'Todo el curso')
            ->assertJsonPath('props.received.message.tags', ['Salida', 'Importante'])
            ->assertJsonPath('props.received.message.sender.name', 'Laura Docente')
            ->assertJsonCount(2, 'props.received.message.readers')->assertJsonPath('props.received.reply.answered', false)
            ->assertJsonPath('props.received.reply.mode', 2);
        $this->assertNotEmpty($response->json('props.received.reply.tempId'));
        $firstRead = DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('fhLeido');
        $this->assertSame('2026-10-30 12:00:00', $firstRead);
        $this->assertSame(1, DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('leido'));
        $this->assertSame(0, DB::table('web_comunicaciones_destinatarios')->where('comunicacion_id', 100)->where('id', '<>', $id)->whereNotNull('fhLeido')->count());
        $this->travel(1)->hours();
        $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()
            ->assertJsonPath('props.received.reply.tempId', $response->json('props.received.reply.tempId'));
        $this->assertSame($firstRead, DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('fhLeido'));
    }

    public function test_received_detail_rejects_crossed_recipients_and_other_responsible_messages(): void
    {
        $this->seedReceivedDetail();
        $this->get('/comunicaciones/100/alumno/11')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        foreach (['/comunicaciones/102/alumno/11', '/comunicaciones/103/alumno/11', '/comunicaciones/100/alumno/14'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->assertSame(0, DB::table('web_comunicaciones_destinatarios')->whereNotNull('fhLeido')->count());
    }

    public function test_free_reply_uploads_are_bound_to_session_and_recipient_and_submit_once(): void
    {
        Storage::fake('public');
        $id = $this->seedReceivedDetail();
        $this->actingAs($this->user);
        $page = $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk();
        $reply = $page->json('props.received.reply');
        $upload = $this->postJson($reply['uploadUrl'], ['tempId' => $reply['tempId'], 'file' => UploadedFile::fake()->image('foto.png')])->assertCreated();
        $fileId = $upload->json('id');
        $filename = DB::table('web_adjuntos')->where('id', $fileId)->value('filename');
        Storage::disk('public')->assertExists($filename);
        $this->assertNull(DB::table('web_adjuntos')->where('id', $fileId)->value('entityId'));
        // A same-student other responsible, a sibling and an unrelated temporary token must not be linked.
        DB::table('web_adjuntos')->insert(['entity' => 'comunicaciond', 'tempId' => 'other-draft', 'filename' => 'other.pdf']);
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Confirmo la recepción.', 'tempId' => $reply['tempId'], 'conmunicacion_destinatario_id' => 9999])
            ->assertOk()->assertJsonPath('redirectUrl', route('comunicaciones.show', [100, 11]));
        $this->assertSame('Confirmo la recepción.', DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('respuesta'));
        $this->assertSame(1, DB::table('web_comunicaciones_destinatarios')->whereNotNull('fhRespuesta')->count());
        $this->assertSame($id, DB::table('web_adjuntos')->where('id', $fileId)->value('entityId'));
        $this->assertNull(DB::table('web_adjuntos')->where('tempId', 'other-draft')->value('entityId'));
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Sobrescribir', 'tempId' => $reply['tempId']])->assertUnprocessable();
        $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()->assertJsonPath('props.received.reply.answered', true)
            ->assertJsonPath('props.received.reply.text', 'Confirmo la recepción.')->assertJsonCount(1, 'props.received.reply.attachments');
    }

    public function test_reply_draft_files_can_be_removed_but_other_drafts_and_submitted_files_cannot(): void
    {
        Storage::fake('public');
        $this->seedReceivedDetail();
        $this->actingAs($this->user);
        $reply = $this->inertiaHome('/comunicaciones/100/alumno/11')->json('props.received.reply');
        $file = $this->postJson($reply['uploadUrl'], ['tempId' => $reply['tempId'], 'file' => UploadedFile::fake()->image('foto.png')])->assertCreated()->json();
        $filename = DB::table('web_adjuntos')->where('id', $file['id'])->value('filename');
        $this->deleteJson($reply['uploadUrl'].'/'.$file['id'], ['tempId' => (string) Str::uuid()])->assertForbidden();
        $this->deleteJson($reply['uploadUrl'].'/'.$file['id'], ['tempId' => $reply['tempId']])->assertOk();
        Storage::disk('public')->assertMissing($filename);
        $other = DB::table('web_adjuntos')->insertGetId(['entity' => 'comunicaciond', 'entityId' => 999, 'tempId' => $reply['tempId'], 'filename' => 'keep.png']);
        Storage::disk('public')->put('keep.png', 'already submitted');
        $this->deleteJson($reply['uploadUrl'].'/'.$other, ['tempId' => $reply['tempId']])->assertNotFound();
        Storage::disk('public')->assertExists('keep.png');
    }

    public function test_fixed_reply_validates_options_type_ownership_and_prevents_changes(): void
    {
        $id = $this->seedReceivedDetail(3);
        $this->actingAs($this->user);
        $reply = $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()->assertJsonPath('props.received.reply.options', ['Sí', 'No'])->json('props.received.reply');
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Tal vez'])->assertUnprocessable();
        $this->postJson(route('comunicaciones.respuestas.libres.store', $id), ['respuesta' => 'Sí'])->assertForbidden();
        $foreign = DB::table('web_comunicaciones_destinatarios')->where('comunicacion_id', 100)->where('Cod_Responsable', 8)->value('id');
        $this->postJson(route('comunicaciones.respuestas.fijas.store', $foreign), ['respuesta' => 'Sí'])->assertForbidden();
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Sí'])->assertOk();
        $this->postJson($reply['submitUrl'], ['respuesta' => 'No'])->assertUnprocessable();
        $this->assertSame('Sí', DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('respuesta'));
    }

    public function test_received_reply_validation_disabled_responses_and_csrf(): void
    {
        Storage::fake('public');
        $id = $this->seedReceivedDetail();
        $this->postJson(route('comunicaciones.respuestas.libres.store', $id), ['respuesta' => 'Hola'])->assertUnauthorized();
        $this->actingAs($this->user);
        $reply = $this->inertiaHome('/comunicaciones/100/alumno/11')->json('props.received.reply');
        $this->postJson($reply['submitUrl'], ['respuesta' => '  ', 'tempId' => $reply['tempId']])->assertUnprocessable();
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Hola', 'tempId' => (string) Str::uuid()])->assertForbidden();
        $this->postJson($reply['uploadUrl'], ['tempId' => $reply['tempId'], 'file' => UploadedFile::fake()->create('evil.php', 1, 'text/plain')])->assertUnprocessable();
        $this->postJson($reply['uploadUrl'], ['tempId' => $reply['tempId'], 'file' => UploadedFile::fake()->image('big.png')->size(12289)])->assertUnprocessable();
        $this->assertSame([], Storage::disk('public')->allFiles());
        DB::table('web_comunicaciones')->where('id', 100)->update(['tipo_respuesta_id' => 1]);
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Hola', 'tempId' => $reply['tempId']])->assertForbidden();
        $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()->assertJsonPath('props.received.reply.submitUrl', null);
        $this->app['env'] = 'local';
        $this->postJson($reply['submitUrl'], ['respuesta' => 'Hola'])->assertStatus(419);
    }

    public function test_failed_reply_upload_cleans_only_the_new_file_and_keeps_response_unsent(): void
    {
        $id = $this->seedReceivedDetail();
        $this->actingAs($this->user);
        $reply = $this->inertiaHome('/comunicaciones/100/alumno/11')->json('props.received.reply');
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with(\Mockery::on(fn ($name) => str_starts_with($name, 'CommRespAdj_')))->andReturn(true);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $before = DB::table('web_adjuntos')->count();
        $this->postJson($reply['uploadUrl'], ['tempId' => $reply['tempId'], 'file' => UploadedFile::fake()->image('foto.png')])->assertUnprocessable();
        $this->assertSame($before, DB::table('web_adjuntos')->count());
        $this->assertNull(DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('respuesta'));
    }

    public function test_reply_attachment_link_failure_rolls_back_the_answer(): void
    {
        $id = $this->seedReceivedDetail();
        $this->actingAs($this->user);
        $reply = $this->inertiaHome('/comunicaciones/100/alumno/11')->json('props.received.reply');
        DB::table('web_adjuntos')->insert(['entity' => 'comunicaciond', 'tempId' => $reply['tempId'], 'filename' => 'pending.pdf']);
        DB::statement("CREATE TRIGGER fail_reply_attachment BEFORE UPDATE OF entityId ON web_adjuntos BEGIN SELECT RAISE(ABORT, 'simulated attachment failure'); END");
        $this->postJson($reply['submitUrl'], ['respuesta' => 'No debe quedar enviada', 'tempId' => $reply['tempId']])->assertStatus(500);
        $this->assertNull(DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('respuesta'));
        $this->assertNull(DB::table('web_comunicaciones_destinatarios')->where('id', $id)->value('fhRespuesta'));
        $this->assertNull(DB::table('web_adjuntos')->where('tempId', $reply['tempId'])->value('entityId'));
    }

    public function test_reading_status_requires_login_relationship_and_responsible_role(): void
    {
        $this->getJson('/familia/comunicaciones/11/estados/received')->assertUnauthorized();
        $this->actingAs($this->user);
        foreach (['received', 'sent'] as $kind) {
            $this->getJson("/familia/comunicaciones/14/estados/$kind")->assertForbidden();
            $this->getJson("/familia/comunicaciones/999/estados/$kind")->assertNotFound();
        }
        $this->getJson('/familia/comunicaciones/11/estados/invalid')->assertNotFound();
        $this->user->tipo = 'Profe';
        $this->getJson('/familia/comunicaciones/11/estados/received')->assertForbidden();
        $this->getJson('/familia/comunicaciones/11/estados/sent')->assertForbidden();
    }

    public function test_received_status_is_read_only_scoped_and_reflects_a_confirmed_detail_read(): void
    {
        $this->seedReceivedDetail();
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->getJson('/familia/comunicaciones/11/estados/received')->assertOk()->assertJsonPath('userId', '42');
        $states = collect($response->json('states'))->keyBy('id');
        $this->assertTrue($states[100]['unread']);
        $this->assertFalse($states[104]['unread']); // An unread sibling must not override this student's read.
        $this->assertFalse($states->has(102)); // Only the other responsible.
        $this->assertFalse($states->has(103)); // Responsible and student match different rows.
        foreach ($states as $state) {
            $this->assertSame(['id', 'unread'], array_keys($state));
        }
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        DB::disableQueryLog();
        $this->inertiaHome('/comunicaciones/100/alumno/11')->assertOk()->assertJsonPath('props.received.readConfirmed', true);
        $updated = collect($this->getJson('/familia/comunicaciones/11/estados/received')->assertOk()->json('states'))->keyBy('id');
        $this->assertFalse($updated[100]['unread']);
        $sibling = collect($this->getJson('/familia/comunicaciones/12/estados/received')->assertOk()->json('states'))->keyBy('id');
        $this->assertTrue($sibling[100]['unread']);
        $this->inertiaHome('/comunicaciones/11')->assertOk()
            ->assertJsonPath('props.received.statusUrl', route('family.communications.status', [11, 'received']));
    }

    public function test_sent_status_only_exposes_own_student_states_and_updates_after_reading_reply(): void
    {
        $this->seedSent();
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->getJson('/familia/comunicaciones/11/estados/sent')->assertOk()->assertJsonPath('userId', '42');
        $states = collect($response->json('states'))->keyBy('id');
        $this->assertTrue($states[202]['replyUnread']);
        $this->assertFalse($states[203]['replyUnread']);
        $this->assertFalse($states->has(204));
        $this->assertFalse($states->has(205));
        $this->assertSame('2026-10-27T10:00:00-03:00', $states[202]['readAt']);
        $this->assertSame('2026-10-28T11:00:00-03:00', $states[202]['answeredAt']);
        foreach ($states as $state) {
            $this->assertSame(['id', 'readAt', 'answeredAt', 'replyUnread'], array_keys($state));
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        DB::disableQueryLog();
        $this->inertiaHome('/comunicaciones/e/show/202')->assertOk();
        $updated = collect($this->getJson('/familia/comunicaciones/11/estados/sent')->assertOk()->json('states'))->keyBy('id');
        $this->assertFalse($updated[202]['replyUnread']);
        $this->assertSame($states[202]['readAt'], $updated[202]['readAt']);
        $this->inertiaHome('/comunicaciones/e/11')->assertOk()
            ->assertJsonPath('props.sent.statusUrl', route('family.communications.status', [11, 'sent']));
    }

    private function seedCompose(): void
    {
        $this->seedSent();
        Schema::create('web_users_grupos', function (Blueprint $table) {
            $table->integer('Cod_Grupo');
            $table->string('tipo');
            $table->integer('cod_usuario');
        });
        DB::table('Grupo')->insert([['Cod_Grupo' => 90], ['Cod_Grupo' => 91]]);
        DB::table('Alumnos')->where('Cod_Alumno', 11)->update(['Cod_Grupo' => 90]);
        DB::table('web_users')->insert(['id' => 82, 'Cod_Responsable' => 0, 'Cod_Profesor' => 78, 'tipo' => 'Profe', 'nombres' => 'Docente', 'apellidos' => 'Ajeno']);
        DB::table('web_users_grupos')->insert([
            ['Cod_Grupo' => 90, 'tipo' => 'Profe', 'cod_usuario' => 77],
            ['Cod_Grupo' => 90, 'tipo' => 'Profe', 'cod_usuario' => 77],
            ['Cod_Grupo' => 90, 'tipo' => 'Secretaria', 'cod_usuario' => 77],
            ['Cod_Grupo' => 90, 'tipo' => 'Profe', 'cod_usuario' => 999],
            ['Cod_Grupo' => 91, 'tipo' => 'Profe', 'cod_usuario' => 78],
        ]);
    }

    public function test_finance_account_keeps_legacy_totals_states_and_query_window_without_writing(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $this->actingAs($this->user);
        $entries = [
            ['2026-09-10', '0'], ['2026-09-10', '100.25'], ['2026-10-15', '20.50'],
            ['2026-10-20', '30.75'], ['2026-10-10', '10'], ['2026-11-15', '50'], ['2026-09-20', '-5'],
        ];
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;', ['2026-12-01 23:59:59.999', 11, null])->once()
            ->andReturn(array_map(fn ($row) => (object) ['Fecha_Venc' => $row[0], 'Saldo' => $row[1], 'Monto' => '200.75', 'Descripcion' => 'Cuota escolar', 'Nro_Cuota' => '10', 'cod_factura' => 555, 'internal' => 'private'], $entries));
        DB::enableQueryLog();
        $response = $this->inertiaHome('/cc/11')->assertOk()->assertJsonPath('component', 'family/Account')
            ->assertJsonPath('props.account.totals.month', 61.25)->assertJsonPath('props.account.totals.today', 20.5)
            ->assertJsonPath('props.account.totals.overdue', 95.25)->assertJsonPath('props.account.payment.suggestedAmount', '156,50')
            ->assertJsonPath('props.account.payment.url', route('cc.pagar'))->assertJsonCount(7, 'props.account.rows')
            ->assertJsonPath('props.account.rows.0.state', 'paid')->assertJsonPath('props.account.rows.1.state', 'overdue')
            ->assertJsonPath('props.account.rows.2.state', 'today')->assertJsonPath('props.account.rows.3.state', 'upcoming')
            ->assertJsonPath('props.account.rows.4.state', 'upcoming')->assertJsonPath('props.account.rows.5.state', 'upcoming')
            ->assertJsonPath('props.account.rows.6.balance', -5)->assertJsonPath('props.account.rows.6.state', 'overdue')
            ->assertJsonPath('props.account.rows.2.date', '2026-10-15T00:00:00-03:00')
            ->assertJsonPath('props.account.rows.2.dateLabel', '15/10/2026')->assertJsonPath('props.account.rows.2.amount', 200.75);
        $this->assertArrayNotHasKey('internal', $response->json('props.account.rows.0'));
        $this->assertCount(7, array_unique(array_column($response->json('props.account.rows'), 'id')));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_finance_month_end_and_first_day_keep_the_existing_boundary_rules(): void
    {
        $this->actingAs($this->user);
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;', \Mockery::any())->twice()
            ->andReturnUsing(fn () => array_map(fn ($row) => (object) ['Fecha_Venc' => $row[0], 'Saldo' => $row[1], 'Monto' => $row[1], 'Descripcion' => 'Cuota', 'Nro_Cuota' => '1'], [['2026-10-15', 10], ['2026-10-31', 20], ['2026-11-15', 30]]));
        $this->travelTo(Carbon::parse('2026-10-31 12:00:00'));
        $this->inertiaHome('/cc/11')->assertOk()->assertJsonPath('props.account.totals.today', 30)
            ->assertJsonPath('props.account.totals.month', 30)->assertJsonPath('props.account.totals.overdue', 0)
            ->assertJsonPath('props.account.rows.0.state', 'today')->assertJsonPath('props.account.rows.1.state', 'today');
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
        $this->inertiaHome('/cc/11')->assertOk()->assertJsonPath('props.account.totals.month', 0)
            ->assertJsonPath('props.account.totals.today', 0)->assertJsonPath('props.account.totals.overdue', 30)
            ->assertJsonPath('props.account.rows.0.state', 'overdue')->assertJsonPath('props.account.rows.2.state', 'upcoming');
    }

    private function withFinancePaymentSettings(bool $tic, bool $mp, callable $callback): void
    {
        $before = [];
        foreach (['EURE_PAGOS_TIC' => $tic, 'EURE_MERCADO_PAGO' => $mp] as $key => $enabled) {
            $before[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            $_ENV[$key] = $_SERVER[$key] = $enabled ? 's' : 'n';
            putenv($key.'='.($enabled ? 's' : 'n'));
        }
        try {
            $callback();
        } finally {
            foreach ($before as $key => [$env, $server, $process]) {
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
                putenv($process === false ? $key : $key.'='.$process);
            }
        }
    }

    public function test_finance_empty_account_only_offers_configured_payment_methods(): void
    {
        $this->actingAs($this->user);
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('DECLARE @EureFechaHasta smalldatetime; SET @EureFechaHasta = CONVERT(smalldatetime, ?, 121); exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = @EureFechaHasta;', \Mockery::any())->times(4)->andReturn([]);
        foreach ([[false, false, []], [true, false, ['pagostic']], [false, true, ['mercadopago']], [true, true, ['pagostic', 'mercadopago']]] as [$tic, $mp, $expected]) {
            $this->withFinancePaymentSettings($tic, $mp, function () use ($expected) {
                $response = $this->inertiaHome('/cc/11')->assertOk()->assertJsonCount(0, 'props.account.rows')
                    ->assertJsonPath('props.account.payment.suggestedAmount', '0,00')->assertJsonPath('props.account.totals.month', 0)
                    ->assertJsonPath('props.account.monthNotice', 'No hay vencimientos en este mes');
                $this->assertSame($expected, array_column($response->json('props.account.payment.methods'), 'id'));
            });
        }
    }

    public function test_finance_pages_authorize_before_calling_procedures(): void
    {
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->never();
        foreach (['cc', 'pagos'] as $path) {
            $this->get('/'.$path.'/11')->assertRedirect(route('login'));
        }
        $this->actingAs($this->user);
        foreach (['cc', 'pagos'] as $path) {
            $this->get('/'.$path.'/14')->assertForbidden();
            $this->get('/'.$path.'/999')->assertNotFound();
        }
        $this->user->tipo = 'Profe';
        foreach (['cc', 'pagos'] as $path) {
            $this->get('/'.$path.'/11')->assertForbidden();
        }
    }

    public function test_finance_payments_keep_full_history_pending_receipt_links_and_iso_dates(): void
    {
        $this->actingAs($this->user);
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('exec SP_WEB_PagosEfectuados @CodAlumno = ?, @FDesde = ?, @FHasta = ?', [11, null, null])->once()
            ->andReturn([
                (object) ['cod_recibo' => 500, 'Nro_Comprobante' => 'A-000500', 'Forma_Pago' => 'Transferencia', 'Total' => '1234.50', 'Fecha_Pago' => '2025-03-10', 'Pendiente' => 0],
                (object) ['cod_recibo' => 501, 'Nro_Comprobante' => 'A-000501', 'Forma_Pago' => 'Tarjeta', 'Total' => '2500.75', 'Fecha_Pago' => '2026-10-30', 'Pendiente' => 1],
            ]);
        $this->inertiaHome('/pagos/11')->assertOk()->assertJsonPath('component', 'family/Payments')
            ->assertJsonCount(2, 'props.payments.rows')->assertJsonPath('props.payments.rows.0.pending', false)
            ->assertJsonPath('props.payments.rows.1.pending', true)->assertJsonPath('props.payments.rows.0.amount', 1234.5)
            ->assertJsonPath('props.payments.rows.0.date', '2025-03-10T00:00:00-03:00')
            ->assertJsonPath('props.payments.rows.1.url', route('pagos.descargar', 501));
    }

    public function test_finance_payment_post_keeps_decimal_amount_and_existing_provider_dispatch_without_network(): void
    {
        $this->actingAs($this->user);
        $controller = $this->partialMock(CuentaCorrienteController::class);
        $student = \Mockery::on(fn ($alumno) => $alumno instanceof Alumno && $alumno->id === 11);
        $controller->shouldReceive('pagar_PTIC')->once()->with(1234.5, $student)->andReturn(redirect()->away('https://payments.example/tic'));
        $controller->shouldReceive('pagar_MercadoPago')->once()->with(2500.75, $student)->andReturn(redirect()->away('https://payments.example/mp'));
        $this->post('/cc/pagar', ['alumno' => 11, 'importe' => '1234,50', 'metodo' => 'pagostic'])->assertRedirect('https://payments.example/tic');
        $this->post('/cc/pagar', ['alumno' => 11, 'importe' => '2500,75', 'metodo' => 'mercadopago'])->assertRedirect('https://payments.example/mp');
        $this->post('/cc/pagar', ['alumno' => 14, 'importe' => '1234,50', 'metodo' => 'pagostic'])->assertForbidden();
        foreach (['0', '-1', '1.234,50', 'texto'] as $amount) {
            $this->post('/cc/pagar', ['alumno' => 11, 'importe' => $amount, 'metodo' => 'pagostic'])->assertStatus(400);
        }
        $this->post('/cc/pagar', ['alumno' => 11, 'importe' => '100', 'metodo' => 'unknown'])->assertStatus(400);
    }

    public function test_finance_receipt_download_rejects_an_unrelated_payer_before_pdf_generation(): void
    {
        $this->actingAs($this->user);
        Schema::table('Alumnos', fn (Blueprint $table) => $table->integer('Cod_ResponsableCobro')->nullable());
        Schema::create('recibos', function (Blueprint $table) {
            $table->integer('Cod_Recibo')->primary();
            $table->integer('cod_responsable');
        });
        DB::table('Alumnos')->where('Cod_Alumno', 11)->update(['Cod_ResponsableCobro' => 70]);
        DB::table('Alumnos')->where('Cod_Alumno', 14)->update(['Cod_ResponsableCobro' => 80]);
        DB::table('recibos')->insert(['Cod_Recibo' => 500, 'cod_responsable' => 80]);
        $this->get('/pagos/500/descargar')->assertUnauthorized();
        $this->get('/pagos/999/descargar')->assertUnauthorized();
    }

    public function test_attendance_keeps_weighted_rolling_totals_all_rows_and_does_not_write(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
        $this->actingAs($this->user);
        $entries = [
            ['2026-10-01 11:59:59', 'Falta doble', '2', 'Fuera del último mes'],
            ['2026-10-01 12:00:00', 'Media falta', '0.5', 'Límite del mes'],
            ['2026-10-25 11:59:59', 'Falta', '1', 'Fuera de los últimos 7 días'],
            ['2026-10-25 12:00:00', 'Tardanza', '0.25', 'Límite de los 7 días'],
            ['2026-10-31 00:00:00', 'Falta', '1', 'Certificado pendiente'],
            ['2026-11-01 00:00:00', 'Justificada', '0', 'No suma peso'],
            ['2026-11-01 00:00:00', 'Justificada', '0', 'No suma peso'],
        ];
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('exec SP_WEB_ConsultaInasistencias @CodAlumno = ?, @anioLect = ?', [11, EureFunctions::al()])->once()
            ->andReturn(array_map(fn ($entry) => (object) ['fecha' => $entry[0], 'descripcion' => $entry[1], 'imputar' => $entry[2], 'observaciones' => $entry[3], 'internal' => 'not public'], $entries));
        DB::enableQueryLog();
        $response = $this->inertiaHome('/asistencias/11')->assertOk()->assertJsonPath('component', 'family/Attendance')
            ->assertJsonPath('props.attendance.studentId', '11')
            ->assertJsonPath('props.attendance.schoolYear', (string) EureFunctions::al())
            ->assertJsonPath('props.attendance.totals.year', 4.75)
            ->assertJsonPath('props.attendance.totals.month', 2.75)
            ->assertJsonPath('props.attendance.totals.week', 1.25)
            ->assertJsonCount(7, 'props.attendance.rows')
            ->assertJsonPath('props.attendance.rows.3.weight', 0.25)
            ->assertJsonPath('props.attendance.rows.3.date', '2026-10-25T12:00:00-03:00')
            ->assertJsonPath('props.attendance.rows.3.dateLabel', '25/10/2026')
            ->assertJsonPath('props.attendance.rows.5.type', 'Justificada')
            ->assertJsonPath('props.attendance.rows.5.weight', 0);
        $this->assertArrayNotHasKey('internal', $response->json('props.attendance.rows.0'));
        $this->assertCount(7, array_unique(array_column($response->json('props.attendance.rows'), 'id')));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_attendance_requires_login_responsible_role_and_relationship_before_calling_the_procedure(): void
    {
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('exec SP_WEB_ConsultaInasistencias @CodAlumno = ?, @anioLect = ?', \Mockery::any())->never();
        $this->get('/asistencias/11')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->get('/asistencias/14')->assertForbidden();
        $this->get('/asistencias/999')->assertNotFound();
        $this->user->tipo = 'Profe';
        $this->get('/asistencias/11')->assertForbidden();
    }

    public function test_attendance_empty_state_has_zero_totals_and_no_invented_rows(): void
    {
        $this->actingAs($this->user);
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')->with('exec SP_WEB_ConsultaInasistencias @CodAlumno = ?, @anioLect = ?', [12, EureFunctions::al()])->once()->andReturn([]);
        $this->inertiaHome('/asistencias/12')->assertOk()->assertJsonCount(0, 'props.attendance.rows')
            ->assertJsonPath('props.attendance.totals.year', 0)->assertJsonPath('props.attendance.totals.month', 0)->assertJsonPath('props.attendance.totals.week', 0);
    }

    public function test_documents_preserve_report_availability_and_only_show_the_students_legajo(): void
    {
        $this->actingAs($this->user);
        DB::table('web_adjuntos')->insert([
            ['entity' => 'alumno', 'entityId' => 11, 'filename' => 'own.pdf', 'originalFilename' => 'Certificado médico.pdf', 'created_at' => '2026-10-30 10:00:00'],
            ['entity' => 'alumno', 'entityId' => 12, 'filename' => 'sibling.pdf', 'originalFilename' => 'Hermano', 'created_at' => null],
            ['entity' => 'alumno', 'entityId' => null, 'filename' => 'pending.pdf', 'originalFilename' => 'Pendiente', 'created_at' => null],
            ['entity' => 'comunicacione', 'entityId' => 11, 'filename' => 'message.pdf', 'originalFilename' => 'Otra entidad', 'created_at' => null],
        ]);
        $this->get('/informes/14/documentos')->assertForbidden();
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')
            ->with('exec SP_WEB_Documentos @CodAlumno = ?, @aniolectivo = ?', [11, EureFunctions::al()])->once()
            ->andReturn([
                (object) ['id_doc' => 1, 'descripcion' => 'Boletín', 'habilitado' => 1, 'mensaje' => '', 'reporte' => 'private.rpt', 'prefijo' => 'secret'],
                (object) ['id_doc' => 2, 'descripcion' => 'Certificado', 'habilitado' => 0, 'mensaje' => 'Todavía no está habilitado.'],
            ]);
        $response = $this->withSession(['mensajeDocumento' => 'Consultá en secretaría.'])->inertiaHome('/informes/11/documentos')
            ->assertOk()->assertJsonPath('component', 'family/Documents')
            ->assertJsonPath('props.documents.reports.0.enabled', true)
            ->assertJsonPath('props.documents.reports.0.url', route('informes.descargarDocumento', [11, 1]))
            ->assertJsonPath('props.documents.reports.1.enabled', false)
            ->assertJsonPath('props.documents.reports.1.url', null)
            ->assertJsonPath('props.documents.reports.1.message', 'Todavía no está habilitado.')
            ->assertJsonPath('props.documents.notice', 'Consultá en secretaría.')
            ->assertJsonCount(1, 'props.documents.attachments')
            ->assertJsonPath('props.documents.attachments.0.url', '/storage/own.pdf');
        $this->assertMatchesRegularExpression('/(Z|[+-]\d{2}:\d{2})$/', $response->json('props.documents.attachments.0.date'));
        $this->assertArrayNotHasKey('reporte', $response->json('props.documents.reports.0'));
        $this->assertDatabaseCount('web_adjuntos', 4);
        $this->assertDatabaseCount('web_alumnos', 1);
    }

    public function test_document_downloads_keep_authorization_blocks_external_links_and_notice_redirects(): void
    {
        $this->actingAs($this->user);
        $this->get('/informes/14/documentos/1')->assertForbidden();
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')
            ->with('exec SP_WEB_Documentos @CodAlumno = ?, @aniolectivo = ?', [11, EureFunctions::al()])->times(4)
            ->andReturn([
                (object) ['id_doc' => 1, 'habilitado' => 0, 'mensaje' => 'No disponible'],
                (object) ['id_doc' => 2, 'habilitado' => 1, 'mensaje' => 'https://institution.example/document.pdf'],
                (object) ['id_doc' => 3, 'habilitado' => 1, 'mensaje' => 'Solicitalo en secretaría.'],
            ]);
        $this->get('/informes/11/documentos/1')->assertForbidden();
        $this->get('/informes/11/documentos/99')->assertNotFound();
        $this->get('/informes/11/documentos/2')->assertRedirect('https://institution.example/document.pdf');
        $this->get('/informes/11/documentos/3')->assertRedirect(route('informes.indexDocumentos', 11))->assertSessionHas('mensajeDocumento', 'Solicitalo en secretaría.');
    }

    public function test_documents_empty_state_does_not_create_records(): void
    {
        $this->actingAs($this->user);
        $database = \Mockery::mock(DB::getFacadeRoot());
        DB::swap($database);
        $database->shouldReceive('select')
            ->with('exec SP_WEB_Documentos @CodAlumno = ?, @aniolectivo = ?', [11, EureFunctions::al()])->once()->andReturn([]);
        $this->inertiaHome('/informes/11/documentos')->assertOk()
            ->assertJsonCount(0, 'props.documents.reports')->assertJsonCount(0, 'props.documents.attachments');
        $this->assertDatabaseCount('web_adjuntos', 0);
    }

    private function legajoDraft(int $student = 11): array
    {
        $this->actingAs($this->user);
        $url = $this->get('/alumnos/'.$student.'/adjunto/create')->assertRedirect()->headers->get('Location');

        return $this->inertiaHome($url)->assertOk()->assertJsonPath('component', 'family/StudentAttachments')->json('props.upload');
    }

    public function test_legajo_saves_a_batch_once_with_relative_filenames_and_survives_retry(): void
    {
        Storage::fake('public');
        $draft = $this->legajoDraft();
        $files = [UploadedFile::fake()->image('foto.png'), UploadedFile::fake()->createWithContent('permiso.pdf', '%PDF-1.4 prueba')];
        $data = ['tempId' => $draft['tempId'], 'fileCount' => 2, 'files' => $files];
        $response = $this->postJson($draft['submitUrl'], $data)->assertOk()->assertJsonPath('redirectUrl', route('alumnos.show', 11));
        $this->postJson($draft['submitUrl'], ['tempId' => $draft['tempId']])->assertOk()->assertJsonPath('redirectUrl', $response->json('redirectUrl'));
        $this->assertDatabaseCount('web_adjuntos', 2);
        $saved = DB::table('web_adjuntos')->get();
        foreach ($saved as $file) {
            $this->assertSame('alumno', $file->entity);
            $this->assertSame(11, $file->entityId);
            $this->assertSame($draft['tempId'], $file->tempId);
            $this->assertMatchesRegularExpression('/^CommAdj_[a-f0-9-]+\.(png|pdf)$/', $file->filename);
            Storage::disk('public')->assertExists($file->filename);
        }
        $this->inertiaHome('/alumno/11')->assertOk()->assertJsonCount(2, 'props.profile.attachments');
        $this->get('/alumnos/11/adjunto/create?tempId='.$draft['tempId'])->assertRedirect(route('alumnos.show', 11));
        $this->postJson('/uploads/adjuntos/e/delete/', ['filename' => $saved[0]->filename, 'tempId' => $draft['tempId']])->assertForbidden();
        Storage::disk('public')->assertExists($saved[0]->filename);
    }

    public function test_legajo_rejects_foreign_students_sessions_invalid_files_and_partial_batches(): void
    {
        Storage::fake('public');
        $draft = $this->legajoDraft();
        $this->get('/alumnos/14/adjunto/create')->assertForbidden();
        $base = ['tempId' => $draft['tempId'], 'fileCount' => 1];
        $this->postJson('/alumnos/14/adjunto/store', $base)->assertForbidden();
        $this->postJson('/alumnos/12/adjunto/store', $base)->assertForbidden();
        $this->postJson($draft['submitUrl'], ['tempId' => (string) Str::uuid()])->assertForbidden();
        $this->get('/alumnos/12/adjunto/create?tempId='.$draft['tempId'])->assertForbidden();
        $this->postJson($draft['submitUrl'], $base)->assertUnprocessable();
        foreach ([UploadedFile::fake()->createWithContent('bad.svg', '<svg/>'), UploadedFile::fake()->createWithContent('fake.png', '<script>bad</script>')->mimeType('text/html'), UploadedFile::fake()->create('large.pdf', 12289, 'application/pdf')] as $file) {
            $this->postJson($draft['submitUrl'], $base + ['files' => [$file]])->assertUnprocessable();
        }
        $this->postJson($draft['submitUrl'], ['tempId' => $draft['tempId'], 'fileCount' => 2, 'files' => [UploadedFile::fake()->image('only-one.png')]])->assertUnprocessable();
        $this->assertDatabaseCount('web_adjuntos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->postJson('/uploads/alumno/adjuntos', $base)->assertNotFound();
        $this->postJson('/uploads/adjuntos/alumno', $base)->assertNotFound();
        $this->app['session']->flush();
        $this->postJson($draft['submitUrl'], $base)->assertForbidden();
    }

    public function test_legajo_cleans_only_new_files_when_the_database_rejects_a_batch(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('existing.pdf', 'keep');
        $draft = $this->legajoDraft();
        DB::statement("CREATE TRIGGER fail_legajo BEFORE INSERT ON web_adjuntos WHEN NEW.originalFilename = 'second.png' BEGIN SELECT RAISE(ABORT, 'failure'); END");
        $data = ['tempId' => $draft['tempId'], 'fileCount' => 2, 'files' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.png')]];
        $this->postJson($draft['submitUrl'], $data)->assertStatus(500);
        $this->assertDatabaseCount('web_adjuntos', 0);
        $this->assertSame(['existing.pdf'], Storage::disk('public')->allFiles());
        DB::statement('DROP TRIGGER fail_legajo');
        $this->postJson($draft['submitUrl'], $data)->assertOk();
        $this->assertDatabaseCount('web_adjuntos', 2);
    }

    public function test_legajo_storage_failure_creates_no_records_and_only_cleans_the_attempted_file(): void
    {
        $draft = $this->legajoDraft();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->with(\Mockery::on(fn ($name) => str_starts_with($name, 'CommAdj_')))->andReturn(true);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->postJson($draft['submitUrl'], ['tempId' => $draft['tempId'], 'fileCount' => 1, 'files' => [UploadedFile::fake()->image('foto.png')]])
            ->assertUnprocessable()->assertJsonValidationErrors('files');
        $this->assertDatabaseCount('web_adjuntos', 0);
    }

    private function composeDraft(int $student = 11): array
    {
        $this->actingAs($this->user);
        $url = $this->get("/comunicaciones/e/$student/create")->assertRedirect()->headers->get('Location');

        return $this->inertiaHome($url)->assertOk()->assertJsonPath('component', 'family/Compose')->json('props.compose');
    }

    public function test_compose_lists_only_group_recipients_with_photos_and_separate_drafts_without_db_writes(): void
    {
        $this->seedCompose();
        DB::enableQueryLog();
        $draft = $this->composeDraft();
        $this->assertSame(['Profe|77', 'Secretaria|77'], array_column($draft['recipients'], 'id'));
        $this->assertSame('Laura Docente', $draft['recipients'][0]['name']);
        $this->assertSame(Storage::disk('public')->url('docente.png'), $draft['recipients'][0]['avatar']);
        $this->assertSame('Ana Secretaría', $draft['recipients'][1]['name']);
        $this->assertNotSame($draft['tempId'], $this->composeDraft()['tempId']);
        $this->assertSame([], $this->composeDraft(12)['recipients']);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
        $this->inertiaHome('/comunicaciones/e/11/create?tempId='.$draft['tempId'])->assertOk()->assertJsonPath('props.compose.tempId', $draft['tempId']);
    }

    public function test_compose_enforces_group_student_session_and_csrf_before_writing(): void
    {
        $this->seedCompose();
        $this->get('/comunicaciones/e/11/create')->assertRedirect(route('login'));
        $this->postJson('/comunicaciones/e/11/store')->assertUnauthorized();
        $draft = $this->composeDraft();
        $data = ['tempId' => $draft['tempId'], 'destinatario' => 'Profe|77', 'asunto' => 'Consulta', 'msg' => '<p>Hola 😊</p>'];
        $count = DB::table('web_comunicaciones_entrantes')->count();
        $this->get('/comunicaciones/e/14/create')->assertForbidden();
        $this->postJson('/comunicaciones/e/14/store', $data)->assertForbidden();
        $this->postJson('/comunicaciones/e/12/store', $data)->assertForbidden();
        $this->postJson('/comunicaciones/e/11/store', array_replace($data, ['tempId' => (string) Str::uuid()]))->assertForbidden();
        foreach (['Profe|78', 'Profe|80', 'Secretaria|81', 'Admin|77'] as $invalid) {
            $this->postJson('/comunicaciones/e/11/store', array_replace($data, ['destinatario' => $invalid]))->assertUnprocessable()->assertJsonValidationErrors('destinatario');
        }
        foreach (['', '<p><br></p>', '<script>alert(1)</script>'] as $empty) {
            $this->postJson('/comunicaciones/e/11/store', array_replace($data, ['msg' => $empty]))->assertUnprocessable()->assertJsonValidationErrors('msg');
        }
        $this->assertDatabaseCount('web_comunicaciones_entrantes', $count);
        $this->app['env'] = 'local';
        $this->postJson('/comunicaciones/e/11/store', $data)->assertStatus(419);
    }

    public function test_compose_sends_once_links_only_own_files_and_retries_return_original_message(): void
    {
        Storage::fake('public');
        $this->seedCompose();
        $draft = $this->composeDraft();
        $other = $this->composeDraft();
        $upload = fn ($token, $kind, $file) => $this->postJson($draft['uploadUrl'], ['tempId' => $token, 'kind' => $kind, 'file' => $file])->assertCreated()->json();
        $file = $upload($draft['tempId'], 'file', UploadedFile::fake()->createWithContent('nota.pdf', '%PDF-1.4 contenido'));
        $image = $upload($draft['tempId'], 'image', UploadedFile::fake()->image('foto.png', 40, 40));
        $filename = DB::table('web_adjuntos')->where('id', $image['id'])->value('filename');
        $this->assertSame('/storage/'.$filename, $image['url']);
        $foreign = $upload($other['tempId'], 'file', UploadedFile::fake()->image('otro.png', 40, 40));
        $count = DB::table('web_comunicaciones_entrantes')->count();
        $data = ['tempId' => $draft['tempId'], 'destinatario' => 'Secretaria|77', 'asunto' => 'Consulta', 'msg' => '<p><strong>Hola 😊</strong></p><img src="'.$image['url'].'" onerror="alert(1)"><script>alert(1)</script>'];
        $sent = $this->postJson($draft['submitUrl'], $data)->assertOk()->json('redirectUrl');
        $this->postJson($draft['submitUrl'], array_replace($data, ['asunto' => 'No duplicar']))->assertOk()->assertJsonPath('redirectUrl', $sent);
        $this->assertDatabaseCount('web_comunicaciones_entrantes', $count + 1);
        $message = DB::table('web_comunicaciones_entrantes')->orderByDesc('id')->first();
        $this->assertSame(7, $message->Cod_Responsable);
        $this->assertSame(11, $message->Cod_Alumno);
        $this->assertSame(77, $message->Cod_Usuario);
        $this->assertSame('Secretaria', $message->tipo);
        $this->assertSame(3, $message->tipo_id);
        $this->assertStringContainsString('Hola 😊', $message->msg);
        $this->assertStringContainsString('src="/storage/'.$filename.'"', $message->msg);
        $this->assertStringNotContainsString('script', $message->msg);
        $this->assertStringNotContainsString('onerror', $message->msg);
        foreach ([$file, $image] as $attachment) {
            $this->assertSame($message->id, DB::table('web_adjuntos')->where('id', $attachment['id'])->value('entityId'));
        }
        $this->assertNull(DB::table('web_adjuntos')->where('id', $foreign['id'])->value('entityId'));
        $this->inertiaHome($sent)->assertOk()->assertJsonCount(1, 'props.sent.message.attachments');
        $this->deleteJson($draft['uploadUrl'].'/'.$file['id'], ['tempId' => $draft['tempId']])->assertConflict();
        $this->postJson($draft['uploadUrl'], ['tempId' => $draft['tempId'], 'kind' => 'image', 'file' => UploadedFile::fake()->image('late.png')])->assertConflict();
        $this->get('/comunicaciones/e/11/create?tempId='.$draft['tempId'])->assertRedirect($sent);
    }

    public function test_compose_normalizes_existing_draft_image_urls_without_accepting_foreign_hosts_or_files(): void
    {
        Storage::fake('public', ['url' => 'http://localhost:8003/storage']);
        $this->seedCompose();
        $draft = $this->composeDraft();
        $other = $this->composeDraft();
        $upload = fn ($token) => $this->postJson($draft['uploadUrl'], ['tempId' => $token, 'kind' => 'image', 'file' => UploadedFile::fake()->image('foto.png')])->assertCreated()->json();
        $image = $upload($draft['tempId']);
        $foreign = $upload($other['tempId']);
        $this->assertStringStartsWith('/storage/CommEImg_', $image['url']);
        $this->assertSame('http://localhost:8003'.$image['url'], $image['previousUrl']);
        $this->inertiaHome('/comunicaciones/e/11/create?tempId='.$draft['tempId'])
            ->assertOk()->assertJsonPath('props.compose.files.0.url', $image['url'])
            ->assertJsonPath('props.compose.files.0.previousUrl', $image['previousUrl']);
        $data = ['tempId' => $draft['tempId'], 'destinatario' => 'Secretaria|77', 'asunto' => 'Foto'];
        foreach (['https://foreign.example'.$image['url'], $foreign['previousUrl'], $foreign['url']] as $url) {
            $this->postJson($draft['submitUrl'], $data + ['msg' => '<img src="'.$url.'">'])->assertUnprocessable()->assertJsonValidationErrors('msg');
        }
        // A tab opened before the fix can still submit its absolute URL.
        $sent = $this->postJson($draft['submitUrl'], $data + ['msg' => '<img src="'.$image['previousUrl'].'" style="width:50%">'])->assertOk()->json('redirectUrl');
        $message = DB::table('web_comunicaciones_entrantes')->orderByDesc('id')->first();
        $this->assertStringContainsString('src="'.$image['url'].'"', $message->msg);
        $this->assertStringContainsString('width:50%', $message->msg);
        $this->assertStringNotContainsString('localhost', $message->msg);
        $this->inertiaHome($sent)->assertOk()->assertJsonPath('props.sent.message.html', $message->msg);
    }

    public function test_compose_upload_and_removal_reject_foreign_drafts_and_bad_images(): void
    {
        Storage::fake('public');
        $this->seedCompose();
        $draft = $this->composeDraft();
        $other = $this->composeDraft();
        $data = ['tempId' => $draft['tempId'], 'kind' => 'image'];
        $this->postJson($draft['uploadUrl'], $data + ['file' => UploadedFile::fake()->createWithContent('evil.svg', '<svg onload="alert(1)"/>')])->assertUnprocessable();
        $this->postJson($draft['uploadUrl'], $data + ['file' => UploadedFile::fake()->create('huge.png', 12289, 'image/png')])->assertUnprocessable();
        $this->postJson($draft['uploadUrl'], $data + ['file' => UploadedFile::fake()->createWithContent('not.png', '<script>hello</script>')])->assertUnprocessable();
        $this->postJson('/comunicaciones/e/12/adjuntos', $data + ['file' => UploadedFile::fake()->image('wrong.png')])->assertForbidden();
        $file = $this->postJson($draft['uploadUrl'], $data + ['file' => UploadedFile::fake()->image('ok.png')])->assertCreated()->json();
        $filename = DB::table('web_adjuntos')->where('id', $file['id'])->value('filename');
        $this->postJson('/uploads/adjuntos/e/delete/', ['tempId' => $draft['tempId'], 'filename' => $filename])->assertForbidden();
        $this->deleteJson($draft['uploadUrl'].'/'.$file['id'], ['tempId' => $other['tempId']])->assertNotFound();
        Storage::disk('public')->assertExists($filename);
        $this->deleteJson($draft['uploadUrl'].'/'.$file['id'], ['tempId' => $draft['tempId']])->assertOk();
        Storage::disk('public')->assertMissing($filename);
        $this->assertDatabaseMissing('web_adjuntos', ['id' => $file['id']]);
    }

    public function test_compose_rejects_foreign_inline_images_and_rolls_back_a_failed_send(): void
    {
        $this->seedCompose();
        $draft = $this->composeDraft();
        $data = ['tempId' => $draft['tempId'], 'destinatario' => 'Profe|77', 'asunto' => 'Consulta', 'msg' => '<p>Foto</p><img src="https://example.test/otra.png">'];
        $this->postJson($draft['submitUrl'], $data)->assertUnprocessable()->assertJsonValidationErrors('msg');
        $count = DB::table('web_comunicaciones_entrantes')->count();
        DB::statement("CREATE TRIGGER fail_send_receipt BEFORE INSERT ON web_adjuntos WHEN NEW.entity = 'comunicacione_envio' BEGIN SELECT RAISE(ABORT, 'receipt failure'); END");
        $this->postJson($draft['submitUrl'], array_replace($data, ['msg' => '<p>Hola</p>']))->assertStatus(500);
        $this->assertDatabaseCount('web_comunicaciones_entrantes', $count);
        $this->assertDatabaseCount('web_adjuntos', 0);
        DB::statement('DROP TRIGGER fail_send_receipt');
        $this->postJson($draft['submitUrl'], array_replace($data, ['msg' => '<p>Hola</p>']))->assertOk();
        $this->assertDatabaseCount('web_comunicaciones_entrantes', $count + 1);
    }

    public function test_compose_keeps_catalog_emojis_without_uploads_and_rejects_fake_catalog_urls(): void
    {
        Storage::fake('public');
        $this->seedCompose();
        $draft = $this->composeDraft();
        $this->assertSame('/assets/eure/emoji/v1', $draft['emojiBaseUrl']);
        $data = ['tempId' => $draft['tempId'], 'destinatario' => 'Profe|77', 'asunto' => 'Emojis'];
        foreach (['https://evil.example/assets/eure/emoji/v1/1f604.png', $draft['emojiBaseUrl'].'/unknown.png', $draft['emojiBaseUrl'].'/../1f604.png'] as $url) {
            $this->postJson($draft['submitUrl'], $data + ['msg' => '<img data-eure-emoji="1f604" src="'.$url.'">'])->assertUnprocessable()->assertJsonValidationErrors('msg');
        }
        $url = $draft['emojiBaseUrl'].'/1f604.png';
        $sent = $this->postJson($draft['submitUrl'], $data + ['msg' => '<p>Hola <img src="'.$url.'">!</p>'])->assertOk()->json('redirectUrl');
        $html = $this->inertiaHome($sent)->assertOk()->assertJsonCount(0, 'props.sent.message.attachments')->json('props.sent.message.html');
        $this->assertStringContainsString('data-eure-emoji="1f604"', $html);
        $this->assertStringContainsString('width:24px;height:24px', $html);
        $this->assertSame(0, DB::table('web_adjuntos')->whereIn('entity', ['comunicacione', 'comunicacione_imagen'])->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function seedSent(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
        // Intentionally overlap legacy teacher/office codes; neither is the web user's ID.
        DB::table('web_users')->insert([
            ['id' => 80, 'Cod_Responsable' => 0, 'Cod_Profesor' => 77, 'cod_usuario' => null, 'tipo' => 'Profe', 'nombres' => 'Laura', 'apellidos' => 'Docente', 'avatarImg' => 'docente.png', 'desc' => 'Primaria'],
            ['id' => 81, 'Cod_Responsable' => 0, 'Cod_Profesor' => null, 'cod_usuario' => 77, 'tipo' => 'Secretaria', 'nombres' => 'Ana', 'apellidos' => 'Secretaría', 'avatarImg' => 'secretaria.png', 'desc' => 'Atención a familias'],
        ]);
        foreach ([
            [200, 7, 11, 'Profe', null, null, null],
            [201, 7, 11, 'Secretaria', '2026-10-27 10:00:00', null, null],
            [202, 7, 11, 'Profe', '2026-10-27 10:00:00', '2026-10-28 11:00:00', null],
            [203, 7, 11, null, '2026-10-27 10:00:00', '2026-10-28 11:00:00', '2026-10-29 12:00:00'],
            [204, 7, 12, 'Profe', null, '2026-10-28 11:00:00', null],
            [205, 8, 11, 'Profe', null, '2026-10-28 11:00:00', null],
            [206, 7, 11, 'Secretaria', null, null, null],
        ] as [$id, $responsible, $student, $type, $read, $reply, $replyRead]) {
            DB::table('web_comunicaciones_entrantes')->insert([
                'id' => $id, 'Cod_Responsable' => $responsible, 'Cod_Alumno' => $student,
                'Cod_Usuario' => $id === 206 ? 999 : 77, 'tipo' => $type,
                'asunto' => "Consulta $id", 'msg' => '<p>Mensaje con <strong>formato</strong></p>',
                'respuesta' => "Respuesta de prueba\n<script>Texto literal</script>",
                'fhLeido' => $read, 'fhRespuesta' => $reply, 'fhRespuestaLeida' => $replyRead,
                'created_at' => $id === 200 ? '2024-01-02 08:00:00' : '2026-10-27 08:00:00',
            ]);
        }
    }

    public function test_sent_list_preserves_states_mixed_recipients_and_does_not_write(): void
    {
        $this->seedSent();
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->inertiaHome('/comunicaciones/e/11')->assertOk()->assertJsonPath('component', 'family/Sent')
            ->assertJsonPath('props.sent.createUrl', route('comunicaciones.e.create', 11))->assertJsonCount(6, 'props.sent.messages');
        $messages = collect($response->json('props.sent.messages'))->keyBy('id');
        $this->assertSame([206, 203, 202, 201, 200, 3], $messages->keys()->all());
        $this->assertSame('Laura Docente', $messages[200]['recipient']['name']);
        $this->assertSame(Storage::disk('public')->url('docente.png'), $messages[200]['recipient']['avatar']);
        $this->assertSame('Ana Secretaría', $messages[201]['recipient']['name']);
        $this->assertSame('Ana Secretaría', $messages[203]['recipient']['name']);
        $this->assertSame('Destinatario no disponible', $messages[206]['recipient']['name']);
        $this->assertNull($messages[200]['readAt']);
        $this->assertSame('2024-01-02T08:00:00-03:00', $messages[200]['dateTime']);
        $this->assertSame('2026-10-27T10:00:00-03:00', $messages[201]['readAt']);
        $this->assertTrue($messages[202]['replyUnread']);
        $this->assertFalse($messages[203]['replyUnread']);
        $this->assertNull($messages[201]['answeredAt']);
        $this->assertArrayNotHasKey('html', $messages[202]);
        $this->assertArrayNotHasKey('reply', $messages[202]);
        $this->assertArrayNotHasKey('password', $messages[202]['recipient']);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_sent_loads_all_history_for_local_search_and_pagination(): void
    {
        $this->seedSent();
        for ($id = 300; $id < 421; $id++) {
            DB::table('web_comunicaciones_entrantes')->insert([
                'id' => $id, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'asunto' => "Histórico $id",
                'created_at' => '2020-01-01 10:00:00',
            ]);
        }
        $this->actingAs($this->user);
        $this->inertiaHome('/comunicaciones/e/11?q=NOEXISTE&page=8&desde=2026-10-30')->assertOk()
            ->assertJsonCount(127, 'props.sent.messages')->assertJsonPath('props.sent.messages.0.id', '420');
    }

    public function test_sent_detail_marks_only_own_response_once_and_keeps_scoped_attachments(): void
    {
        $this->seedSent();
        DB::table('web_adjuntos')->insert([
            ['id' => 50, 'entity' => 'comunicacione', 'entityId' => 202, 'filename' => 'permiso.pdf', 'originalFilename' => 'Permiso.pdf'],
            ['id' => 51, 'entity' => 'comunicacion', 'entityId' => 202, 'filename' => 'otro.pdf', 'originalFilename' => 'Ajeno'],
            ['id' => 52, 'entity' => 'comunicacione', 'entityId' => 205, 'filename' => 'privado.pdf', 'originalFilename' => 'Otro responsable'],
        ]);
        $this->actingAs($this->user);
        $this->inertiaHome('/comunicaciones/e/show/202')->assertOk()->assertJsonPath('component', 'family/SentDetail')
            ->assertJsonPath('props.sent.message.html', '<p>Mensaje con <strong>formato</strong></p>')
            ->assertJsonPath('props.sent.message.reply', "Respuesta de prueba\n<script>Texto literal</script>")
            ->assertJsonPath('props.sent.message.replyUnread', false)->assertJsonCount(1, 'props.sent.message.attachments')
            ->assertJsonPath('props.sent.message.attachments.0.name', 'Permiso.pdf')
            ->assertJsonPath('props.sent.message.attachments.0.url', Storage::disk('public')->url('permiso.pdf'));
        $firstRead = DB::table('web_comunicaciones_entrantes')->where('id', 202)->value('fhRespuestaLeida');
        $this->assertSame('2026-10-30 12:00:00', $firstRead);
        $this->travel(1)->hours();
        $this->get('/comunicaciones/e/show/202')->assertOk();
        $this->assertSame($firstRead, DB::table('web_comunicaciones_entrantes')->where('id', 202)->value('fhRespuestaLeida'));
        $this->assertSame('2026-10-27 10:00:00', DB::table('web_comunicaciones_entrantes')->where('id', 202)->value('fhLeido'));
        foreach ([204, 205] as $id) {
            $this->assertNull(DB::table('web_comunicaciones_entrantes')->where('id', $id)->value('fhRespuestaLeida'));
        }
        $this->inertiaHome('/comunicaciones/e/show/200')->assertOk()->assertJsonPath('props.sent.message.reply', null);
        $this->assertNull(DB::table('web_comunicaciones_entrantes')->where('id', 200)->value('fhRespuestaLeida'));
    }

    public function test_sent_requires_ownership_and_handles_empty_and_inactive_students(): void
    {
        $this->seedSent();
        $this->get('/comunicaciones/e/11')->assertRedirect(route('login'));
        $this->get('/comunicaciones/e/show/202')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->get('/comunicaciones/e/14')->assertForbidden();
        $this->get('/comunicaciones/e/show/205')->assertForbidden();
        DB::table('web_comunicaciones_entrantes')->where('id', 202)->update(['Cod_Alumno' => 14]);
        $this->get('/comunicaciones/e/show/202')->assertForbidden();
        $this->assertNull(DB::table('web_comunicaciones_entrantes')->where('id', 202)->value('fhRespuestaLeida'));
        $this->inertiaHome('/comunicaciones/e/13')->assertOk()->assertJsonPath('props.sent.messages', []);
        $this->get('/comunicaciones/e/show/9999')->assertNotFound();
        $this->user->tipo = 'Profe';
        $this->get('/comunicaciones/e/11')->assertForbidden();
        $this->get('/comunicaciones/e/show/200')->assertForbidden();
    }

    public function test_received_list_scopes_student_and_recipient_on_same_row_and_does_not_write(): void
    {
        $this->seedReceived();
        $this->actingAs($this->user);
        DB::enableQueryLog();
        $response = $this->inertiaHome('/comunicaciones/11?desde=2025-11-01')->assertOk()->assertJsonPath('component', 'family/Received')
            ->assertJsonPath('props.received.filters.desde', '2025-11-01')
            ->assertJsonPath('props.received.filters.filtrado', true)
            ->assertJsonCount(2, 'props.received.messages');
        $messages = $response->json('props.received.messages');
        $this->assertSame(['104', '100'], array_column($messages, 'id'));
        $this->assertFalse($messages[0]['unread']); // The same message is unread for the sibling only.
        $this->assertTrue($messages[1]['unread']);
        $this->assertSame(1, $messages[1]['attachmentCount']);
        $this->assertSame('Circular', $messages[1]['type']);
        $this->assertSame('2026-10-27T08:00:00-03:00', $messages[1]['dateTime']);
        $this->assertSame(route('comunicaciones.show', [100, 11]), $messages[1]['url']);
        $this->assertSame('Laura Docente', $messages[1]['sender']['name']);
        $this->assertSame('Docente de primaria', $messages[1]['sender']['description']);
        $this->assertNull($messages[0]['sender']['description']);
        $this->assertSame('Docente', $messages[1]['sender']['role']);
        $this->assertSame([], $messages[1]['sender']['subjects']);
        $this->assertSame(['name', 'avatar', 'description', 'role', 'subjects'], array_keys($messages[1]['sender']));
        $this->assertSame(Storage::disk('public')->url('docente.jpg'), $messages[1]['sender']['avatar']);
        $this->assertSame(['42', '50'], array_column($response->json('props.received.senders'), 'id'));
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)/i', $query['query']);
        }
    }

    public function test_sender_card_subjects_are_unique_and_scoped_to_the_student_group(): void
    {
        $this->seedReceived();
        Schema::create('Materias', function (Blueprint $table) {
            $table->integer('Cod_Materia')->primary();
            $table->string('Descripcion');
        });
        Schema::create('Materia-Profe-Grupo', function (Blueprint $table) {
            $table->increments('Cod_MPG');
            $table->integer('Cod_Profesor');
            $table->integer('Cod_Grupo');
            $table->integer('Cod_Materia');
        });
        DB::table('web_users')->where('id', 50)->update(['Cod_Profesor' => 70]);
        DB::table('Alumnos')->where('Cod_Alumno', 11)->update(['Cod_Grupo' => 90]);
        DB::table('Materias')->insert([
            ['Cod_Materia' => 1, 'Descripcion' => 'Matemática'],
            ['Cod_Materia' => 2, 'Descripcion' => 'Lengua'],
            ['Cod_Materia' => 3, 'Descripcion' => 'De otro curso'],
        ]);
        DB::table('Materia-Profe-Grupo')->insert([
            ['Cod_Profesor' => 70, 'Cod_Grupo' => 90, 'Cod_Materia' => 1],
            ['Cod_Profesor' => 70, 'Cod_Grupo' => 90, 'Cod_Materia' => 1],
            ['Cod_Profesor' => 70, 'Cod_Grupo' => 90, 'Cod_Materia' => 2],
            ['Cod_Profesor' => 70, 'Cod_Grupo' => 91, 'Cod_Materia' => 3],
            ['Cod_Profesor' => 71, 'Cod_Grupo' => 90, 'Cod_Materia' => 3],
        ]);
        $this->actingAs($this->user);
        $this->inertiaHome('/comunicaciones/11?remitente=50')->assertOk()
            ->assertJsonPath('props.received.messages.0.sender.subjects', ['Lengua', 'Matemática']);
        DB::table('web_users')->where('id', 50)->update(['tipo' => 'Secretaria']);
        $this->inertiaHome('/comunicaciones/11?remitente=50')->assertOk()
            ->assertJsonPath('props.received.messages.0.sender.role', 'Secretaría')
            ->assertJsonPath('props.received.messages.0.sender.subjects', []);
    }

    public function test_received_filters_cover_dates_unread_sender_and_full_history(): void
    {
        $this->seedReceived();
        $this->actingAs($this->user);
        $this->inertiaHome('/comunicaciones/11?filtrado=1')->assertJsonPath('props.received.filters.filtrado', false);
        $this->inertiaHome('/comunicaciones/11?desde=&hasta=')->assertJsonPath('props.received.filters.filtrado', false);
        $this->inertiaHome('/comunicaciones/11?q=Laura')->assertJsonPath('props.received.filters.filtrado', false);
        $this->inertiaHome('/comunicaciones/11?noLeidos=1')->assertJsonCount(2, 'props.received.messages')->assertJsonPath('props.received.messages.0.id', '105');
        $this->inertiaHome('/comunicaciones/11?desde=2026-10-28&hasta=2026-10-28')->assertJsonCount(1, 'props.received.messages')
            ->assertJsonPath('props.received.messages.0.id', '104');
        $this->inertiaHome('/comunicaciones/11?remitente=50&q=Excursi')->assertJsonCount(2, 'props.received.messages')->assertJsonPath('props.received.messages.0.id', '105');
        $this->inertiaHome('/comunicaciones/11?q=Laura')->assertJsonCount(3, 'props.received.messages');
        $this->inertiaHome('/comunicaciones/11?q=00100')->assertJsonCount(3, 'props.received.messages');
        $this->inertiaHome('/comunicaciones/11?q=Circular')->assertJsonCount(3, 'props.received.messages');
        $this->inertiaHome('/comunicaciones/11?remitente=51')->assertJsonCount(0, 'props.received.messages')->assertJsonCount(2, 'props.received.senders');
        $this->inertiaHome('/comunicaciones/11?desde=&hasta=&orden=antiguas')->assertJsonCount(3, 'props.received.messages')->assertJsonPath('props.received.messages.0.id', '105');
        $this->inertiaHome('/comunicaciones/11?orden=asunto')->assertJsonPath('props.received.messages.0.id', '105');
    }

    public function test_received_clear_link_removes_all_filters_including_the_initial_period(): void
    {
        $this->seedReceived();
        $this->actingAs($this->user);
        $filtered = $this->inertiaHome('/comunicaciones/11?remitente=50&desde=2026-10-27&hasta=2026-10-27&noLeidos=1&filtrado=1')
            ->assertOk()->assertJsonCount(1, 'props.received.messages');
        $this->inertiaHome($filtered->json('props.received.clearUrl'))->assertOk()
            ->assertJsonPath('props.received.filters', [
                'remitente' => '', 'desde' => '', 'hasta' => '', 'noLeidos' => false, 'filtrado' => false,
            ])->assertJsonCount(3, 'props.received.messages')
            ->assertJsonPath('props.received.messages.0.id', '105');
        $this->assertSame(route('comunicaciones.indexA', 11), $filtered->json('props.received.clearUrl'));
        // Only entry links supply the period. A bare URL, including after clearing, has no filters.
        $entry = $this->inertiaHome('/home')->assertOk()->json('props.portal.students.0.links.received');
        $this->assertSame(route('comunicaciones.indexA', ['alumno' => 11, 'desde' => '2025-11-01']), $entry);
        $this->inertiaHome($entry)->assertJsonPath('props.received.filters.desde', '2025-11-01')
            ->assertJsonPath('props.received.filters.filtrado', true)->assertJsonCount(2, 'props.received.messages');
    }

    public function test_received_accepts_boolean_strings_serialized_by_inertia_in_get_requests(): void
    {
        $this->seedReceived();
        $this->actingAs($this->user);
        $this->inertiaHome('/comunicaciones/11?filtrado=true&noLeidos=false')->assertOk()
            ->assertJsonPath('props.received.filters.filtrado', false)
            ->assertJsonPath('props.received.filters.noLeidos', false)
            ->assertJsonCount(3, 'props.received.messages');
        $this->inertiaHome('/comunicaciones/11?filtrado=true&noLeidos=true')->assertOk()
            ->assertJsonPath('props.received.filters.noLeidos', true)
            ->assertJsonCount(2, 'props.received.messages');
        $this->inertiaHome('/comunicaciones/11?filtrado=false&noLeidos=false&desde=&hasta=&q=Laura')->assertOk()
            ->assertJsonPath('props.received.filters.filtrado', false);
    }

    public function test_received_loads_the_complete_filtered_set_without_server_pagination_or_duplicates(): void
    {
        $this->seedReceived();
        for ($id = 200; $id < 325; $id++) {
            DB::table('web_comunicaciones')->insert(['id' => $id, 'asunto' => 'Circular '.$id, 'usuario_id' => 50, 'tipo_id' => 1, 'created_at' => '2026-10-28 10:00:00']);
            DB::table('web_comunicaciones_destinatarios')->insert(['comunicacion_id' => $id, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => false]);
        }
        DB::table('web_comunicaciones_destinatarios')->insert(['comunicacion_id' => 100, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => false]);
        $this->actingAs($this->user);
        $response = $this->inertiaHome('/comunicaciones/11?noLeidos=1&remitente=50')->assertJsonCount(127, 'props.received.messages');
        $this->assertArrayNotHasKey('pagination', $response->json('props.received'));
        $this->assertSame(127, count(array_unique(array_column($response->json('props.received.messages'), 'id'))));
        $this->inertiaHome('/comunicaciones/11?noLeidos=1&remitente=50&page=3&porPagina=20&q=NOEXISTE')
            ->assertJsonCount(127, 'props.received.messages');
    }

    public function test_received_preview_is_plain_text_and_never_leaks_markup(): void
    {
        $this->seedReceived();
        $cases = [
            400 => ['<p style="color:red">Hola&nbsp;a&nbsp;todos.</p>  <p>Los  esperamos</p>', 'Hola a todos. Los esperamos'],
            401 => ['<img src="data:image/png;base64,AAAA"><b>Reunión</b>', 'Reunión'],
            402 => ['<script>alert(1)</script>', ''],
            403 => [null, ''],
            404 => [str_repeat('palabra ', 60), null],
        ];
        foreach ($cases as $id => [$body]) {
            DB::table('web_comunicaciones')->insert(['id' => $id, 'asunto' => 'Con cuerpo', 'msg' => $body, 'usuario_id' => 50, 'created_at' => '2026-10-28 10:00:00']);
            DB::table('web_comunicaciones_destinatarios')->insert(['comunicacion_id' => $id, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => true]);
        }
        $this->actingAs($this->user);
        $messages = collect($this->inertiaHome('/comunicaciones/11')->assertOk()->json('props.received.messages'))->keyBy('id');
        foreach ($cases as $id => [, $expected]) {
            $preview = $messages[(string) $id]['preview'];
            $this->assertStringNotContainsString('<', $preview);
            if ($expected !== null) {
                $this->assertSame($expected, $preview);
            }
        }
        // A long body is trimmed with an ellipsis instead of travelling whole.
        $this->assertStringEndsWith('…', $messages['404']['preview']);
        $this->assertLessThanOrEqual(180, mb_strlen($messages['404']['preview']));
    }

    public function test_received_rejects_invalid_filters_and_unauthorized_students(): void
    {
        $this->get('/comunicaciones/11')->assertRedirect(route('login'));
        $this->actingAs($this->user);
        $this->get('/comunicaciones/14')->assertForbidden();
        foreach (['desde=invalid', 'desde=2026-02-30', 'desde=2026-10-30&hasta=2026-10-01', 'noLeidos=other', 'remitente[]=invalid', 'filtrado=invalid'] as $query) {
            $this->getJson('/comunicaciones/11?'.$query)->assertUnprocessable();
        }
        $this->user->tipo = 'Profe';
        $this->get('/comunicaciones/11')->assertForbidden();
    }

    public function test_received_legacy_filter_redirect_and_related_inactive_student(): void
    {
        $this->actingAs($this->user);
        $response = $this->post('/comunicaciones/11/filtrado', ['desde' => '01/10/2026', 'hasta' => '30/10/2026', 'noLeidos' => 'on']);
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('2026-10-01', $query['desde']);
        $this->assertSame('2026-10-30', $query['hasta']);
        $this->assertSame('1', $query['noLeidos']);
        $this->post('/comunicaciones/11/filtrado', [])->assertRedirect(route('comunicaciones.indexA', 11));
        $this->post('/comunicaciones/14/filtrado', ['noLeidos' => 'on'])->assertForbidden();
        $response = $this->inertiaHome('/comunicaciones/13')->assertOk()->assertJsonPath('props.received.studentId', '13')->assertJsonCount(0, 'props.received.messages');
        $this->assertContains('13', array_column($response->json('props.portal.students'), 'id'));
    }

    private function seedReceived(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
        DB::table('web_comunicaciones_tipos')->insert(['id' => 1, 'descripcion' => 'Circular']);
        DB::table('web_users')->insert([
            ['id' => 50, 'Cod_Responsable' => 0, 'tipo' => 'Profe', 'nombres' => 'Laura', 'apellidos' => 'Docente', 'avatarImg' => 'docente.jpg', 'desc' => 'Docente de primaria'],
            ['id' => 51, 'Cod_Responsable' => 0, 'tipo' => 'Profe', 'nombres' => 'Ajeno', 'apellidos' => 'Oculto', 'avatarImg' => null, 'desc' => 'Emisor ajeno'],
        ]);
        DB::table('web_comunicaciones')->where('id', 100)->update(['asunto' => 'Excursión', 'usuario_id' => 50, 'tipo_id' => 1, 'created_at' => '2026-10-27 08:00:00']);
        DB::table('web_comunicaciones')->insert([
            ['id' => 102, 'asunto' => 'Solo otro responsable', 'usuario_id' => 51, 'tipo_id' => 1, 'created_at' => '2026-10-28 10:00:00'],
            ['id' => 103, 'asunto' => 'Destinatarios cruzados', 'usuario_id' => 51, 'tipo_id' => 1, 'created_at' => '2026-10-28 10:00:00'],
            ['id' => 104, 'asunto' => 'Actividad leída', 'usuario_id' => 42, 'tipo_id' => 1, 'created_at' => '2026-10-28 23:59:59'],
            ['id' => 105, 'asunto' => 'Histórica', 'usuario_id' => 50, 'tipo_id' => 1, 'created_at' => '2024-10-28 10:00:00'],
        ]);
        DB::table('web_comunicaciones_destinatarios')->insert([
            ['comunicacion_id' => 102, 'Cod_Responsable' => 8, 'Cod_Alumno' => 11, 'leido' => false],
            ['comunicacion_id' => 103, 'Cod_Responsable' => 7, 'Cod_Alumno' => 12, 'leido' => false],
            ['comunicacion_id' => 103, 'Cod_Responsable' => 8, 'Cod_Alumno' => 11, 'leido' => false],
            ['comunicacion_id' => 104, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => true],
            ['comunicacion_id' => 104, 'Cod_Responsable' => 7, 'Cod_Alumno' => 12, 'leido' => false],
            ['comunicacion_id' => 105, 'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'leido' => false],
        ]);
        DB::table('web_adjuntos')->insert([
            ['id' => 1, 'entity' => 'comunicacion', 'entityId' => 100, 'filename' => 'nota.pdf', 'originalFilename' => 'Nota'],
            ['id' => 2, 'entity' => 'alumno', 'entityId' => 100, 'filename' => 'legajo.pdf', 'originalFilename' => 'Legajo'],
        ]);
    }

    private function inertiaHome(string $path = '/home')
    {
        $version = app(HandleEurekaRequests::class)->version(Request::create($path));

        return $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version ?? ''])->get($path);
    }

    public function test_mobile_received_webview_keeps_blade_token_access_and_read_tracking(): void
    {
        config(['eureka.client_id' => 'ifesnqn']);
        $html = '<p>Mensaje móvil</p><img src="/storage/test-mobile.png" style="width: 500px"><img src="/assets/eure/emoji/v1/1f600.png" width="24">';
        DB::table('web_comunicaciones')->where('id', 100)->update(['msg' => $html]);
        $path = '/comunicaciones/app/100/7/';
        $this->get($path.'invalid')->assertForbidden();
        $this->assertNull(DB::table('web_comunicaciones_destinatarios')->where('comunicacion_id', 100)->where('Cod_Alumno', 11)->value('fhLeido'));
        $this->get($path.hash('sha256', 'M4D100'))->assertOk()->assertViewIs('comunicaciones.appshow')
            ->assertSee('Mensaje móvil')->assertSee('width: 100%', false)
            ->assertSee('/storage/test-mobile.png', false)->assertSee('/assets/eure/emoji/v1/1f600.png', false)
            ->assertDontSee('data-page=', false);
        $this->assertNotNull(DB::table('web_comunicaciones_destinatarios')->where('comunicacion_id', 100)->where('Cod_Alumno', 11)->value('fhLeido'));
        $this->get('/comunicaciones/app/100/8/'.hash('sha256', 'M4D100'))->assertForbidden();
        $this->get($path.'M4D')->assertOk();
        $this->assertSame($html, DB::table('web_comunicaciones')->where('id', 100)->value('msg'));
    }

    public function test_mobile_sent_and_board_webviews_remain_standalone_blade(): void
    {
        config(['eureka.client_id' => 'ifesnqn']);
        $id = DB::table('web_comunicaciones_entrantes')->insertGetId([
            'Cod_Responsable' => 7, 'Cod_Alumno' => 11, 'msg' => '<p>Enviada móvil</p><img src="/storage/test.png" style="width: 50%">',
        ]);
        $token = hash('sha256', 'M4D'.$id);
        $this->get("/comunicaciones/e/app/$id/7/$token")->assertOk()->assertViewIs('comunicacionese.appshow')
            ->assertSee('Enviada móvil')->assertSee('width: 100%', false)->assertDontSee('data-page=', false);
        $this->get("/comunicaciones/e/app/$id/7/invalid")->assertForbidden();
        $this->get("/comunicaciones/e/app/$id/8/$token")->assertForbidden();
        $this->assertNull(DB::table('web_comunicaciones_entrantes')->where('id', $id)->value('fhRespuestaLeida'));
        $this->addBoard();
        $this->get('/carteleras/app/5')->assertOk()->assertViewIs('carteleras.appshow')->assertSee('Bienvenidos')->assertDontSee('data-page=', false);
        DB::table('web_carteleras')->where('id', 5)->update(['activa' => false]);
        $this->get('/carteleras/app/5')->assertStatus(400);
    }

    public function test_mobile_attachment_upload_and_delete_preserve_their_own_routes_and_response_shapes(): void
    {
        config(['eureka.client_id' => 'ifesnqn']);
        Storage::fake('public');
        Sanctum::actingAs($this->user);
        foreach (['e' => 'comunicacione', 'respuesta' => 'comunicaciond'] as $kind => $entity) {
            $base = "/api/adjunto/comunicaciones/$kind/";
            $this->postJson($base.'upload', ['Cod_Alumno' => 14, 'tempId' => 'mobile-draft', 'file' => UploadedFile::fake()->create('test.pdf', 1, 'application/pdf')])->assertForbidden();
            $response = $this->postJson($base.'upload', ['Cod_Alumno' => 11, 'tempId' => 'mobile-draft', 'file' => UploadedFile::fake()->create('test.pdf', 1, 'application/pdf')])
                ->assertCreated()->assertJsonPath('success', true)->assertJsonPath('adjunto.originalFN', 'test.pdf')->assertJsonStructure(['adjunto' => ['originalFN', 'newFN', 'url']]);
            $filename = $response->json('adjunto.newFN');
            $this->assertSame(url('/storage/'.$filename), $response->json('adjunto.url'));
            Storage::disk('public')->assertExists($filename);
            $this->assertDatabaseHas('web_adjuntos', ['filename' => $filename, 'entity' => $entity, 'entityId' => null]);
            $this->postJson($base.'delete', ['Cod_Alumno' => 11, 'tempId' => 'mobile-draft', 'filename' => $filename])
                ->assertOk()->assertExactJson(['success' => true, 'filename' => $filename]);
            Storage::disk('public')->assertMissing($filename);
            $this->assertDatabaseMissing('web_adjuntos', ['filename' => $filename]);
        }
    }

    private function addBoard(): void
    {
        DB::table('web_carteleras')->insert([
            'id' => 5, 'tipo' => 'GENERAL', 'nombre' => 'Novedades', 'cartelera' => '<p>Bienvenidos</p>',
            'activa' => true, 'updated_at' => '2026-10-28 10:00:00',
        ]);
    }
}
