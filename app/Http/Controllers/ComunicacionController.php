<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\Profe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComunicacionController extends Controller
{
  public function indexA(Alumno $alumno)
  {
    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Después controlar la autorización
    $comunicaciones= Comunicacion::Alumno($alumno)->orderBy('id', 'desc')->get();

    $remitentes= User::RemitentesDe($comunicaciones)->orderBy('apellidos')->orderBy('nombres')->get();

//    dd($remitentes);
    $filtros= [
      'filtrado' => 0,
    ];


    return view('comunicaciones.indexa',compact( 'alumno', 'responsable', 'comunicaciones', 'remitentes', 'filtros'));
  }

  public function indexAFiltered(Request $request, Alumno $alumno)
  {
    // Si no hay filtros nos re vimos
    if ($request->remitente == null && $request->desde == "" && $request->hasta == "" && $request->noLeidos == null)
      return redirect()->route('comunicaciones.indexA', $alumno);

    $responsable= EureFunctions::getLoggedResponsableAttribute();

    // Después controlar la autorización

//    $fechaDesdeC= Carbon::createFromFormat('d/m/Y', $request->desde);
//    $fechaHastaC= Carbon::createFromFormat('d/m/Y', $request->hasta);

    // Y acá los filtros
    $comunicaciones=
      Comunicacion
        ::Alumno($alumno)
        ->Remitente($request->remitente)
        ->Desde($request->desde)
        ->Hasta($request->hasta)
        ->NoLeidos($request->noLeidos, $responsable)
        ->get();

    $remitentes= User::RemitentesDe($comunicaciones)->orderBy('Apellidos')->orderBy('Nombres')->get();

    $remitenteF= User::find($request->remitente);

    $filtros = [
      'filtrado' => 1,
      'remitenteIdFiltro' => $request->remitente,
      'remitenteNombre' => (($remitenteF != null) ? $remitenteF->apellidoComaNombres : ''),
      'desdeFiltro' => $request->desde,
      'hastaFiltro' => $request->hasta,
      'noLeidosFiltro' => $request->noLeidos != null ? "checked" : "",
    ];


    // meter los scopes desde y hasta
    return view('comunicaciones.indexA', compact( 'alumno', 'responsable', 'comunicaciones', 'remitentes', 'filtros'));
  }

    public function show(Comunicacion $comunicacion,  Alumno $alumno)
    {
      // Primero echarle flit a si el muchacho quiere ver una comm que no es de él
      $responsable= EureFunctions::getLoggedResponsableAttribute();

       if (!EureFunctions::esDestinatarioDeComunicacion($comunicacion, $responsable))
         abort(403);

       if ($alumno->EResponsable1->id != $responsable->id && $alumno->EResponsable2->id != $responsable->id )
         abort(403);

       // Primero marco como leido
      ComunicacionDestinatario
        ::where('Cod_Responsable', '=', $responsable->id)
        ->where('comunicacion_id', '=', $comunicacion->id)
        ->update(['fhLeido' => Carbon::now()]);

      return view('comunicaciones.show', compact('comunicacion', 'responsable', 'alumno'));
    }


}
