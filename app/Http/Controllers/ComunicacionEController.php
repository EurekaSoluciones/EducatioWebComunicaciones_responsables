<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\ComunicacionTipoEnum;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Models\ComunicacionE;
use App\Models\User;
use App\Models\UsuarioGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Después controlar la autorización
    $comunicacionese=
      ComunicacionE
        ::DeAlumno($alumno)
        ->DeResponsable($responsable)
        ->orderBy('id', 'desc')->get();

//    dd($remitentes);
    $filtros= [
      'filtrado' => 0,
    ];

  //  dd($comunicacionese[0]->destinatario_web_user());

    return view('comunicacionese.indexa',compact( 'alumno', 'responsable', 'comunicacionese', 'filtros'));
  }

  public function show(ComunicacionE $comunicacione)
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if ($comunicacione->Cod_Responsable != $responsable->Cod_Responsable)
      abort(403, 'Acceso no permitido');

    // Si hay repuesta sin leer, la marco leída
    if ($comunicacione->fhRespuesta != null && $comunicacione->fhRespuestaLeida == null)
    {
      $comunicacione->fhRespuestaLeida= Carbon::now();
      $comunicacione->save();
    }

    return view('comunicacionese.show',compact('comunicacione'));
  }

  public function createA(Alumno $alumno)
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();
    $user= $responsable->web_user;

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');

    // Y acá tengo el tema de los destinatarios
    $destinarios= UsuarioGrupo::DeGrupo($alumno->grupo)->get();

//    dd($destinarios[0]->usuario);

    // UUID para adjuntos
    $TempId = Str::uuid()->toString();

    return view('comunicacionese.create',compact( 'alumno', 'responsable', 'user', 'destinarios', 'TempId'));
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
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Ahora vemos si este responsable deberia ver a este alumno o no
    if (!EureFunctions::esResponsableDeAlumno($responsable, $alumno))
      abort(403, 'Acceso no permitido');



    DB::beginTransaction();

    $comunicacionENew= new ComunicacionE();
    $comunicacionENew->tipo_id= ComunicacionTipoEnum::Entrante->value;
    $comunicacionENew->Cod_Responsable= $responsable->Cod_Responsable;
    $comunicacionENew->Cod_Alumno= $alumno->Cod_Alumno;
    $comunicacionENew->Cod_Usuario= $request->destinatario;
    $comunicacionENew->asunto= $request->asunto;
    $comunicacionENew->msg= $request->msg;

    $comunicacionENew->save();

    // Adjuntos
    DB::table('web_adjuntos')
      ->where('tempId', '=', $request->tempId4DZ)
      ->where('entity', '=', 'comunicacione')
      ->update(['entityId' => $comunicacionENew->id]);

    DB::commit();

    return redirect()->route('comunicaciones.e.indexA', ['alumno' => $alumno]);
  }
}
