<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\RespuestaTipoEnum;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComunicacionController extends Controller
{
    public function indexA(Alumno $alumno)
    {
        if (! EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno)) {
            abort(403, 'No permitido');
        }

        $responsable = EureFunctions::getLoggedResponsableAttribute();

        $inicioAnio = date('Y-01-01');
        $inicioAnio = date('d/m/Y', strtotime('-2 months', strtotime($inicioAnio)));
        // Después controlar la autorización
        $comunicaciones = Comunicacion::Alumno($alumno)
            ->Desde($inicioAnio)
            ->orderBy('id', 'desc')->get();

        $remitentes = User::RemitentesDe($comunicaciones)->orderBy('apellidos')->orderBy('nombres')->get();
        //    dd($remitentes);
        $filtros = [
            'filtrado' => 0,
            'desdeFiltro' => $inicioAnio,
        ];

        return view('comunicaciones.indexa', compact('alumno', 'responsable', 'comunicaciones', 'remitentes', 'filtros'));
    }

    public function indexAFiltered(Request $request, Alumno $alumno)
    {
        if (! EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno)) {
            abort(403, 'No permitido');
        }

        // Si no hay filtros nos re vimos
        if ($request->remitente == null && $request->desde == '' && $request->hasta == '' && $request->noLeidos == null) {
            return redirect()->route('comunicaciones.indexA', $alumno);
        }

        $responsable = EureFunctions::getLoggedResponsableAttribute();

        // Después controlar la autorización

        //    $fechaDesdeC= Carbon::createFromFormat('d/m/Y', $request->desde);
        //    $fechaHastaC= Carbon::createFromFormat('d/m/Y', $request->hasta);

        // Y acá los filtros
        $comunicaciones =
          Comunicacion::Alumno($alumno)
              ->Remitente($request->remitente)
              ->Desde($request->desde)
              ->Hasta($request->hasta)
              ->NoLeidos($request->noLeidos, $responsable)
              ->orderBy('id', 'desc')
              ->get();

        $remitentes = User::RemitentesDe($comunicaciones)->orderBy('Apellidos')->orderBy('Nombres')->get();

        $remitenteF = User::find($request->remitente);

        $filtros = [
            'filtrado' => 1,
            'remitenteIdFiltro' => $request->remitente,
            'remitenteNombre' => (($remitenteF != null) ? $remitenteF->apellidoComaNombres : ''),
            'desdeFiltro' => $request->desde,
            'hastaFiltro' => $request->hasta,
            'noLeidosFiltro' => $request->noLeidos != null ? 'checked' : '',
        ];

        // meter los scopes desde y hasta
        return view('comunicaciones.indexa', compact('alumno', 'responsable', 'comunicaciones', 'remitentes', 'filtros'));
    }

    public function show(Comunicacion $comunicacion, Alumno $alumno)
    {
        // Primero echarle flit a si el muchacho quiere ver una comm que no es de él
        $responsable = EureFunctions::getLoggedResponsableAttribute();

        if (! EureFunctions::esDestinatarioDeComunicacion($comunicacion, $responsable)) {
            abort(403);
        }

        if ($alumno->EResponsable1->id != $responsable->id && $alumno->EResponsable2->id != $responsable->id) {
            abort(403);
        }

        // Primero marco como leido
        ComunicacionDestinatario::where('Cod_Responsable', '=', $responsable->id)
            ->where('comunicacion_id', '=', $comunicacion->id)
            ->update(['fhLeido' => Carbon::now()]);

        // Lo paso porque es necesario para la respuesta
        $comunicacion_destinatario =
          ComunicacionDestinatario::where('Cod_Responsable', '=', $responsable->id)
              ->where('comunicacion_id', '=', $comunicacion->id)
              ->first();

        if ($comunicacion->tipo_respuesta_id == RespuestaTipoEnum::Fijas->value) {
            $respuestas_fijas = explode(';', $comunicacion->listas_respuestas_fijas);
            $respuestas_fijas = array_map('trim', $respuestas_fijas);
        } else {
            $respuestas_fijas = [];
        }

        // UUID para adjuntos
        $TempId = Str::uuid()->toString();

        return
          view(
              'comunicaciones.show',
              compact('comunicacion', 'comunicacion_destinatario', 'responsable', 'alumno', 'respuestas_fijas', 'TempId')
          );
    }

    public function storeRespuestaLibre(Request $request)
    {

        
        // Algun pequeño control
        $comunicacionDestinatario = ComunicacionDestinatario::find($request->conmunicacion_destinatario_id);

        if ($comunicacionDestinatario->Cod_Responsable != EureFunctions::getLoggedResponsableAttribute()->Cod_Responsable) {
            abort(403);
        }

        $comunicacionDestinatario->respuesta = $request->respuestaLibre;
        $comunicacionDestinatario->fhRespuesta = Carbon::now();
        $comunicacionDestinatario->save();

        DB::beginTransaction();

        try {
            // Adjuntos. El entity tambien se podria actualziar aca y seria mejor
            // dd(
            // $request->tempId,
            // DB::table('web_adjuntos')
            //     ->where('tempId', '=', $request->tempId)
            //     ->where('entity', '=', 'comunicaciond')
            //     ->whereNull('entityId')->get());

            DB::table('web_adjuntos')
                ->where('tempId', '=', $request->tempId)
                ->where('entity', '=', 'comunicaciond')
                ->whereNull('entityId')
                ->update(['entityId' => $comunicacionDestinatario->id]);

            DB::commit();

        } catch (\Exception $e) {
            // Si ocurre algún error, se revierte la transacción
            DB::rollback();

            throw $e;
            // Aquí puedes manejar el error de alguna manera, por ejemplo, mostrar un mensaje de error
            // o registrar el error en algún lugar para su posterior revisión
        }

        return
          redirect()->route(
              'comunicaciones.show',
              [
                  'comunicacion' => $comunicacionDestinatario->comunicacion_id,
                  'alumno' => $comunicacionDestinatario->Cod_Alumno,
              ]
          );
    }

    public function storeRespuestaFija(Request $request)
    {

        // Algun pequeño control
        $comunicacionDestinatario = ComunicacionDestinatario::find($request->conmunicacion_destinatario_id);

        if ($comunicacionDestinatario->Cod_Responsable != EureFunctions::getLoggedResponsableAttribute()->Cod_Responsable) {
            abort(403);
        }

        //    dd($request->all());

        $comunicacionDestinatario->respuesta = $request->respuestaFija;
        $comunicacionDestinatario->fhRespuesta = Carbon::now();
        $comunicacionDestinatario->save();

        return
          redirect()->route(
              'comunicaciones.show',
              [
                  'comunicacion' => $comunicacionDestinatario->comunicacion_id,
                  'alumno' => $comunicacionDestinatario->Cod_Alumno,
              ]
          );
    }

    public function appshow(Comunicacion $comunicacion, Responsable $responsable, $token)
    {
        // Más adelante recibir el idresponsable y marcarlo como leido

        if ($token != 'M4D' && $token != hash('sha256', 'M4D'.$comunicacion->id)) {
            abort(403);
        }

        // A ver si la comunicación es para el responsable
        $cd =
          ComunicacionDestinatario::where('comunicacion_id', $comunicacion->id)
              ->where('Cod_Responsable', $responsable->id)
              ->first();

        if ($cd == null) {
            abort(403);
        }

        // A marcar como leido. no me acuerdo como era
        if ($cd->leido == 0) {
            $cd->fhLeido = 1;
            $cd->fhLeido = Carbon::now();
            $cd->save();
        }

        return view('comunicaciones.appshow', compact('comunicacion'));
    }

    public function api_responderComunicacion(Request $request)
    {
        $request->validate([
            'comunicacion_destinatario_id' => 'required|integer',
            'respuesta' => 'nullable|string',
            'tempId' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        $comunicacionDestinatario = ComunicacionDestinatario::find($request->comunicacion_destinatario_id);

        if ($comunicacionDestinatario->Cod_Responsable != $user->Cod_Responsable) {
            abort(403);
        }

        DB::beginTransaction();

        try {
            $comunicacionDestinatario->respuesta = $request->respuesta;
            $comunicacionDestinatario->fhRespuesta = Carbon::now();
            $comunicacionDestinatario->save();

            if ($request->filled('tempId')) {
                DB::table('web_adjuntos')
                    ->where('tempId', '=', $request->tempId)
                    ->where('entity', '=', 'comunicaciond')
                    ->whereNull('entityId')
                    ->update(['entityId' => $comunicacionDestinatario->id]);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la respuesta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeAdjuntoEnRespuesta(Request $request, Comunicacion $com, Alumno $alumno)
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
                ->where('entity', '=', 'comunicaciond')
                ->update(['entityId' => $com->id]);

            DB::commit();

            return redirect()->route('comunicaciones.show', $com->comunicacion->id);

        } catch (\Exception $e) {
            // Si ocurre algún error, se revierte la transacción
            DB::rollback();

            throw $e;
            // Aquí puedes manejar el error de alguna manera, por ejemplo, mostrar un mensaje de error
            // o registrar el error en algún lugar para su posterior revisión
        }
    }
}
