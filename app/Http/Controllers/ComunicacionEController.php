<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\ComunicacionTipoEnum;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\ComunicacionE;
use App\Models\Responsable;
use App\Models\User;
use App\Models\UsuarioGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComunicacionEController extends Controller
{
  //
  public function indexA(Alumno $alumno)
  {
    //    dump($alumno);
    //
    //    return "enviadas";

    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Después controlar la autorización
    $comunicacionese =
      ComunicacionE::DeAlumno($alumno)
        ->DeResponsable($responsable)
        ->orderBy('id', 'desc')->get();

    $filtros = [
      'filtrado' => 0,
    ];

    //dd($comunicacionese[0]->tipo);

    return view('comunicacionese.indexa', compact('alumno', 'responsable', 'comunicacionese', 'filtros'));
  }

  public function show(ComunicacionE $comunicacione)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if ($comunicacione->Cod_Responsable != $responsable->Cod_Responsable) {
      abort(403, 'Acceso no permitido');
    }

    // Si hay repuesta sin leer, la marco leída
    if ($comunicacione->fhRespuesta != null && $comunicacione->fhRespuestaLeida == null) {
      $comunicacione->fhRespuestaLeida = Carbon::now();
      $comunicacione->save();
    }

    return view('comunicacionese.show', compact('comunicacione'));
  }

  public function createA(Alumno $alumno)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();
    $user = $responsable->web_user;

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno)) {
      abort(403, 'Acceso no permitido');
    }

    // Y acá tengo el tema de los destinatarios
    //  dd($alumno->grupo);

    $destinarios = UsuarioGrupo::DeGrupo($alumno->grupo)->get();

    // dd($destinarios);
    // dd($destinarios[7]);

    // UUID para adjuntos
    $TempId = Str::uuid()->toString();

    return view('comunicacionese.create', compact('alumno', 'responsable', 'user', 'destinarios', 'TempId'));
  }

  public function store(Request $request, Alumno $alumno)
  {
    $request->validate([
      'destinatario' => 'required',
      'asunto' => 'required',
      'msg' => 'required',
    ]);

    //    dump($alumno);
    //
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno)) {
      abort(403, 'Acceso no permitido');
    }

    // dd($request->destinatario);
    [$tipoDestinatario, $cod_Destinatario] = explode('|', $request->destinatario);

    DB::beginTransaction();

    $comunicacionENew = new ComunicacionE();
    $comunicacionENew->tipo_id = ComunicacionTipoEnum::Entrante->value;
    $comunicacionENew->Cod_Responsable = $responsable->Cod_Responsable;
    $comunicacionENew->Cod_Alumno = $alumno->Cod_Alumno;
    $comunicacionENew->Cod_Usuario = $cod_Destinatario;
    $comunicacionENew->asunto = $request->asunto;
    $comunicacionENew->msg = $request->msg;
    $comunicacionENew->tipo = $tipoDestinatario;

    $comunicacionENew->save();

    // Adjuntos
    DB::table('web_adjuntos')
      ->where('tempId', '=', $request->tempId4DZ)
      ->where('entity', '=', 'comunicacione')
      ->update(['entityId' => $comunicacionENew->id]);

    DB::commit();

    return redirect()->route('comunicaciones.e.indexA', ['alumno' => $alumno]);
  }

  public function appshow(ComunicacionE $comunicacionE, Responsable $responsable, $token)
  {
    // Más adelante recibir el idresponsable y marcarlo como leido

    if ($token != 'M4D' && $token != hash('sha256', 'M4D' . $comunicacionE->id)) {
      abort(403);
    }

    if ($comunicacionE->Cod_Responsable != $responsable->Cod_Responsable) {
      abort(403);
    }

    return view('comunicacionese.appshow', compact('comunicacionE'));
  }

  public function api_store(Request $request)
  {
    try {
      $request->validate([
        'Cod_Alumno' => 'required|integer',
        'destinatario' => 'required',
        'tipoDestinatario' => 'required|string',
        'asunto' => 'required|string',
        'msg' => 'required|string',
        'tempId' => 'nullable|string|max:100',
      ]);

      $user = auth()->user();

      if (!$user) {
        return response()->json(['success' => false, 'message' => 'Usuario no autenticado'], 401);
      }

      $alumno = Alumno::find($request->Cod_Alumno);

      if (!$alumno) {
        return response()->json(['success' => false, 'message' => 'Alumno no encontrado'], 404);
      }

      $responsable = $user->responsable;

      // Verificamos si el responsable tiene acceso al alumno
      if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno)) {
        return response()->json(['success' => false, 'message' => 'Acceso no permitido'], 403);
      }

      DB::beginTransaction();

      try {
        $comunicacionENew = new ComunicacionE();
        $comunicacionENew->tipo_id = ComunicacionTipoEnum::Entrante->value;
        $comunicacionENew->Cod_Responsable = $responsable->Cod_Responsable;
        $comunicacionENew->Cod_Alumno = $alumno->Cod_Alumno;
        $comunicacionENew->Cod_Usuario = $request->destinatario;
        $comunicacionENew->tipo = $request->tipoDestinatario;
        $comunicacionENew->asunto = $request->asunto;
        $comunicacionENew->msg = $request->msg;

        $comunicacionENew->save();

        if ($request->filled('tempId')) {
          DB::table('web_adjuntos')
            ->where('tempId', '=', $request->tempId)
            ->where('entity', '=', 'comunicacione')
            ->whereNull('entityId')
            ->update(['entityId' => $comunicacionENew->id]);
        }

        DB::commit();

        return response()->json(['success' => true, 'comunicacionE_id' => $comunicacionENew->id]);
      } catch (\Exception $e) {
        DB::rollBack();

        return response()->json(['success' => false, 'message' => 'Error al guardar la comunicación', 'error' => $e->getMessage()], 500);
      }
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => 'Error interno del servidor', 'error' => $e->getMessage()], 500);
    }

  }

  public function api_marcarRespuestaLeida(Request $request)
  {
    $user = auth()->user();

    $comunicacionE = ComunicacionE::find($request->comunicacionE_id);

    if ($comunicacionE->Cod_Responsable != $user->Cod_Responsable) {
      abort(403);
    }

    if ($comunicacionE->fhRespuesta != null && $comunicacionE->fhRespuestaLeida == null) {
      $comunicacionE->fhRespuestaLeida = Carbon::now();
      $comunicacionE->save();
    }

    return response()->json(['success' => true]);
  }
}
