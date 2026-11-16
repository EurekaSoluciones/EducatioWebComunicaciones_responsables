<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;


class AlumnoController extends Controller
{
  public function show(Alumno $alumno)
  {
    // Obtengo el Responsable

    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');


    //  dd($responsable);

    return view('alumnos.show', compact('alumno', 'responsable'));
  }

  public function editPic(Alumno $alumno)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    //  dd($responsable);

    return view('alumnos.editPic', compact('alumno', 'responsable'));
  }


  public function updatePic(Request $request, Alumno $alumno)
  {
    //    dd($request->all());

    if ($request->chSinImagen == 'on') {
      $alumno->web->avatarImg = '';
      $alumno->web->save();
    } else {
      // La imagen viene cropeada ahora
      $data = $request->input('cropped_image');

      list($type, $data) = explode(';', $data);
      list(, $data)      = explode(',', $data);
      $data = base64_decode($data);

      $nombreArchivo = 'AVAlumno_' . date('ymdHis') . '_' . rand(100, 999) . '.png';
      Storage::disk('public')->put($nombreArchivo, $data);

      //  Storage::disk('public')->put($nombreArchivo, file_get_contents($archivo));

      $alumno->web->avatarImg = $nombreArchivo;
      $alumno->web->save();
    }

    return redirect()->route('alumnos.show', compact('alumno'))->with('success', 'Perfil Actualizado.');
  }

  public function api_update_foto(Request $request)
  {
    $request->validate([
      'Cod_Alumno' => 'required',
      'foto' => 'required|image|mimes:jpg,png,jpeg|max:2048',
    ]);

    $user = Auth::user();
    $alumno = Alumno::where('Cod_Alumno', $request->Cod_Alumno)->first();

    if ($alumno == null)
      return response()->json(['message' => 'Alumno no encontrado'], 401);

    $responsable = Responsable::where('Cod_Responsable', $user->Cod_Responsable)->first();

    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    $nombreArchivo = 'AVAlumno_' . date('ymdHis') . '_' . rand(100, 999) . '.png';

    $path = $request->file('foto')->storeAs('', $nombreArchivo, 'public');

    // Guardar la nueva ruta en la base de datos
    $alumno->web->avatarImg = $nombreArchivo;
    $alumno->web->save();


    return response()->json([
      'message' => 'Foto de perfil actualizada',
      'foto_url' => asset("storage/$path")
    ]);
  }

  public function api_update_foto_remover(Request $request)
  {
    $user = Auth::user();
    $alumno = Alumno::where('Cod_Alumno', $request->Cod_Alumno)->first();

    if ($alumno == null)
      return response()->json(['message' => 'Alumno no encontrado'], 401);

    $responsable = Responsable::where('Cod_Responsable', $user->Cod_Responsable)->first();

    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    $alumno->web->avatarImg = '';
    $alumno->web->save();


    return response()->json([
      'message' => 'Foto de perfil actualizada',
    ]);
  }

  public function createAdjunto(Alumno $alumno)
  {
    // Control de si realmente este docente tiene asuntos con este alumno
    $user = User::find(Auth::id());
    try {
      // $profe = Profe::findOrFail($user->Cod_Profesor);

      // if (! ($profe->MPGs->pluck('Cod_Grupo')->contains($alumno->Cod_Grupo))) {
      //   abort(403);
      // }

      // UUID para adjuntos
      $TempId = Str::uuid()->toString();

      // Hacky
      // $grupo = $alumno->grupo;
      // $etiquetas = Etiqueta::Activos()->get();
      // $modalidadDest = 'individual';
      // $tipos_respuestas = RespuestaTipo::All();

      return
        view(
          'alumnos.adjuntoCreate',
          compact('alumno', 'user', 'TempId')
        );
    } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      abort(403);
    }
  }

  public function storeAdjunto(Request $request, Alumno $alumno)
  {

    // dd($request->all());

    // Primero los datos obligatorios
    // $request->validate([
    //     'destinatario' => 'required|array|min:1',
    // ]);

    // Por ahora el tipo es fijo

    DB::beginTransaction();

    try {
      // Adjuntos. El entity tambien se podria actualziar aca y seria mejor
      DB::table('web_adjuntos')
        ->where('tempId', '=', $request->tempId4DZ)
        ->where('entity', '=', 'alumno')
        ->update(['entityId' => $alumno->id]);

      DB::commit();

      return redirect()->route('alumnos.show', $alumno->id);
    } catch (\Exception $e) {
      // Si ocurre algún error, se revierte la transacción
      DB::rollback();

      throw $e;
      // Aquí puedes manejar el error de alguna manera, por ejemplo, mostrar un mensaje de error
      // o registrar el error en algún lugar para su posterior revisión
    }
  }

  public function descargarRematriculacion(Alumno $alumno)
  {
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno)) {
      abort(403, 'Acceso no permitido');
    }

    $estado = EducatioCommFunctions::MensajeBloqueoRematriculacion($alumno);

    if (!($estado['habilitaPDF'] ?? false)) {
      abort(403, ($estado['mensajePDF'] ?? '') ?: 'El comprobante de reinscripción no está disponible.');
    }

    $resultado = EureFunctions::obtenerPDF('MatriculaRematriculacion.rpt', 'Rematriculacion_', '', [
      ['nombre' => '@CodAlumno', 'valor' => $alumno->Cod_Alumno],
    ]);

    if (!$resultado->RequestsStatusOK) {
      abort(400, $resultado->RequestsStatusObs);
    }

    return redirect()->away($resultado->pdf_URL);
  }

  public function rematriculacion(Alumno $alumno)
  {
    // Obtengo el Responsable

    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    $estadoRematriculacion = EducatioCommFunctions::MensajeBloqueoRematriculacion($alumno);

    if (!$estadoRematriculacion['periodoActivo'] || !$estadoRematriculacion['habilitaBoton']) {
      abort(403, $estadoRematriculacion['mensaje'] ?: 'La reinscripción no está disponible.');
    }

    $rematriculacion = collect(DB::select(
      'EXEC SP_WEB_DatosAlumno_Rematriculacion ?',
      [$alumno->id]
    ))->first();

    $cursos = DB::select(
      'EXEC dbo.SP_WEB_DatosAlumno_RematriculacionCursos @codAlumno = ?',
      [$alumno->id]
    );

    $convenios = DB::select('EXEC dbo.SP_WEB_DatosAlumno_RematriculacionConvenios');

    $ciudades = DB::table('Ciudad')->get();

    // dd($ciudades);

    $necesidadesEspeciales = DB::table('dbo.Tipo_NecesidadesEspeciales')
      ->whereNull('fecha_baja')
      ->orderBy('descripcion')
      ->get()
      ->sortBy(fn ($tipo) => mb_strtolower(trim($tipo->descripcion)) === 'otra' ? 1 : 0)
      ->values();

    return view('alumnos.rematriculaciones.form', compact('alumno', 'rematriculacion', 'cursos', 'convenios', 'ciudades', 'necesidadesEspeciales'));
  }

  public function guardarRematriculacion(Request $request, Alumno $alumno)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno)) {
      abort(403, 'Acceso no permitido');
    }

    $estadoRematriculacion = EducatioCommFunctions::MensajeBloqueoRematriculacion($alumno);

    if (!$estadoRematriculacion['periodoActivo'] || !$estadoRematriculacion['habilitaBoton']) {
      abort(403, $estadoRematriculacion['mensaje'] ?: 'La reinscripción no está disponible.');
    }

    $necesidadesEspeciales = DB::table('dbo.Tipo_NecesidadesEspeciales')
      ->whereNull('fecha_baja')
      ->get();
    $codigosOtra = $necesidadesEspeciales
      ->filter(fn ($tipo) => mb_strtolower(trim($tipo->descripcion)) === 'otra')
      ->pluck('Cod_NecesidadEspecial')->map(fn ($codigo) => (string) $codigo)->all();
    $seleccionaOtra = $request->boolean('tieneNecesidadEspecial')
      && is_array($request->input('necesidadesEspeciales'))
      && collect($request->input('necesidadesEspeciales'))->contains(
        fn ($codigo) => is_scalar($codigo) && in_array((string) $codigo, $codigosOtra, true)
      );

    $datos = $request->validate([
      'CursoNuevo' => ['required'],
      'aceptaReglamento' => ['required', 'accepted'],
      'tieneNecesidadEspecial' => ['required', 'boolean'],
      'necesidadesEspeciales' => [Rule::excludeIf(!$request->boolean('tieneNecesidadEspecial')), 'required', 'array', 'min:1'],
      'necesidadesEspeciales.*' => ['integer', 'distinct', Rule::in($necesidadesEspeciales->pluck('Cod_NecesidadEspecial')->all())],
      'descripcion_otra' => [Rule::excludeIf(!$seleccionaOtra), 'required', 'string', 'max:500'],
    ], [
      'CursoNuevo.required' => 'Debés seleccionar un curso para confirmar la reinscripción.',
      'aceptaReglamento.required' => 'Debés aceptar el reglamento para confirmar la reinscripción.',
      'aceptaReglamento.accepted' => 'Debés aceptar el reglamento para confirmar la reinscripción.',
      'necesidadesEspeciales.array' => 'La selección de necesidades especiales no es válida.',
      'necesidadesEspeciales.required' => 'Debés seleccionar al menos una opción al marcar que requiere apoyos particulares.',
      'necesidadesEspeciales.min' => 'Debés seleccionar al menos una opción.',
      'necesidadesEspeciales.*.integer' => 'La necesidad especial seleccionada no es válida.',
      'necesidadesEspeciales.*.distinct' => 'No se puede seleccionar dos veces la misma necesidad especial.',
      'necesidadesEspeciales.*.in' => 'Una de las necesidades especiales seleccionadas ya no está disponible.',
      'descripcion_otra.string' => 'La explicación de Otra debe ser un texto.',
      'descripcion_otra.required' => 'Debés especificar la necesidad al seleccionar Otra.',
      'descripcion_otra.max' => 'La explicación de Otra no puede superar los 500 caracteres.',
    ]);

    $sql = 'SET NOCOUNT ON;
      DECLARE @codigoRematriculacion INT = NULL;
      EXEC dbo.SP_WEB_DatosAlumno_RematriculacionInsert
      @codAlumno = ?,
      @Domicilio = ?,
      @Telefono = ?,
      @email = ?,
      @CodCiudad = ?,
      @nopermitefoto = ?,
      @R1Nombre = ?,
      @R1Apellido = ?,
      @R1Vinculo = ?,
      @R1DNI = ?,
      @R1Domicilio = ?,
      @R1Telefono = ?,
      @R1Celular = ?,
      @R1Email = ?, 
      @R1CodCiudad = ?,
      @R1Ocupacion = ?,
      @R2Nombre = ?,
      @R2Apellido = ?,
      @R2Vinculo = ?,
      @R2DNI = ?,
      @R2Domicilio = ?,
      @R2Telefono = ?, 
      @R2Celular = ?,
      @R2Email = ?,
      @R2CodCiudad = ?,
      @R2Ocupacion = ?,
      @RNombre = ?,
      @RApellido = ?, 
      @RDNI = ?,
      @RDomicilio = ?,
      @RTelefono = ?,
      @RCelular = ?,
      @REmail = ?,
      @RCodCiudad = ?,
      @Convenio = ?,
      @NombreTitularConvenio = ?,
      @Colegio = ?, 
      @GradoColegio = ?,
      @TurnoColegio = ?,
      @TieneFamiliaDirecto = ?, 
      @TieneFamiliaDirectoQuienes = ?,
      @CursoNuevo = ?,
      @Observaciones = ?,
      @tieneNecesidadEspecial = ?,
      @codigoRematriculacion = @codigoRematriculacion OUTPUT;
      SELECT @codigoRematriculacion AS Cod_Rematriculacion;';

    $parametros = [
      $alumno->id,
      $request->Domicilio,
      $request->Telefono,
      $request->email,
      $request->CodCiudad,
      $request->nopermitefoto,
      $request->R1Nombre,
      $request->R1Apellido,
      $request->R1Vinculo,
      $request->R1DNI,
      $request->R1Domicilio,
      $request->R1Telefono,
      $request->R1Celular,
      $request->R1Email,
      $request->R1CodCiudad,
      $request->R1Ocupacion,
      $request->R2Nombre,
      $request->R2Apellido,
      $request->R2Vinculo,
      $request->R2DNI,
      $request->R2Domicilio,
      $request->R2Telefono,
      $request->R2Celular,
      $request->R2Email,
      $request->R2CodCiudad,
      $request->R2Ocupacion,
      $request->RNombre,
      $request->RApellido,
      $request->RDNI,
      $request->RDomicilio,
      $request->RTelefono,
      $request->RCelular,
      $request->REmail,
      $request->RCodCiudad,
      $request->Convenio,
      $request->NombreTitularConvenio,
      $request->Colegio,
      $request->GradoColegio,
      $request->TurnoColegio,
      $request->TieneFamiliaDirecto,
      $request->TieneFamiliaDirectoQuienes,
      $request->CursoNuevo,
      $request->Observaciones,
      $request->tieneNecesidadEspecial
    ];

    try {
      DB::transaction(function () use ($sql, $parametros, $datos, $codigosOtra, $alumno) {
        $resultado = DB::selectOne($sql, $parametros);
        $codigo = filter_var($resultado->Cod_Rematriculacion ?? null, FILTER_VALIDATE_INT);

        if (!$codigo || !DB::table('dbo.AlumnosRematriculacion')
          ->where('Codigo', $codigo)->where('codAlumno', $alumno->id)->exists()) {
          throw new \RuntimeException('El procedimiento debe devolver un Cod_Rematriculacion válido para el alumno.');
        }

        DB::table('dbo.AlumnosRematriculacion_NecesidadesEspeciales')
          ->where('Cod_Rematriculacion', $codigo)->delete();

        $filas = collect($datos['necesidadesEspeciales'] ?? [])->map(fn ($tipo) => [
          'Cod_Rematriculacion' => $codigo,
          'Cod_NecesidadEspecial' => (int) $tipo,
          'descripcion_otra' => in_array((string) $tipo, $codigosOtra, true)
            ? ($datos['descripcion_otra'] ?? null) : null,
        ])->all();

        if ($filas) {
          DB::table('dbo.AlumnosRematriculacion_NecesidadesEspeciales')->insert($filas);
        }
      });
      return redirect()->route('home');
    } catch (\Throwable $e) {
      // El error técnico queda registrado para nosotros
      Log::error('Error al guardar rematriculación', [
        'alumno_id' => $alumno->id,
        'error' => $e->getMessage(),
        'exception' => get_class($e),
      ]);

      // Al usuario le mostramos un mensaje entendible
      return redirect()
        ->back()
        ->withInput()
        ->with('resultadoRematriculacion', [
          'alumnoId' => (string) $alumno->id,
          'mensaje' => 'No fue posible guardar la reinscripción. Por favor, intente nuevamente.',
          'tipo' => 'error',
        ]);
    }
  }
}
