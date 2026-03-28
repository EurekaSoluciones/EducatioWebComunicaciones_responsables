<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Adjunto;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdjuntoController extends Controller
{
    //
  public function storeImagenComunicacione(Request $request)
  {
    $image = $request->file('file');
    $imageName = 'CommImg_' . date('ymdHis') . '_' . $image->getClientOriginalName();

    Storage::disk('public')->put($imageName, file_get_contents($image));

    return response()->json(['success' => $imageName]);
  }

  public function storeAdjuntoComunicacione(Request $request)
  {
    return response()->json(
      $this->guardarAdjunto($request->file('file'), $request->tempId, 'comunicacione', 'CommEAdj_')
    );
  }

  public function destroyAdjunto(Request $request)
  {
    $filename=  $request->get('filename');
    $tempId=  $request->get('tempId');

    // Si no hago esto pueden forgear el nombre de un archivo y borrar
    Adjunto::where('filename',$filename)->where('tempId', $tempId)->delete();

    if (Storage::disk('public')->exists($filename))
      Storage::disk('public')->delete($filename);

    return $filename;
  }

  public function storeAdjuntoRespuesta(Request $request)
  {
    return response()->json(
      $this->guardarAdjunto($request->file('file'), $request->tempId, 'comunicaciond', 'CommRespAdj_')
    );
  }

  public function api_storeAdjuntoComunicacione(Request $request)
  {
    $request->validate([
      'file' => 'required|file',
      'tempId' => 'required|string|max:100',
      'Cod_Alumno' => 'required|integer',
    ]);

    $authResponse = $this->autorizarAlumnoApi($request->Cod_Alumno);

    if ($authResponse !== null) {
      return $authResponse;
    }

    return response()->json([
      'success' => true,
      'adjunto' => $this->guardarAdjunto($request->file('file'), $request->tempId, 'comunicacione', 'CommEAdj_'),
    ], 201);
  }

  public function api_destroyAdjuntoComunicacione(Request $request)
  {
    $request->validate([
      'filename' => 'required|string|max:255',
      'tempId' => 'required|string|max:100',
      'Cod_Alumno' => 'required|integer',
    ]);

    $authResponse = $this->autorizarAlumnoApi($request->Cod_Alumno);

    if ($authResponse !== null) {
      return $authResponse;
    }

    $adjunto = Adjunto::where('filename', $request->filename)
      ->where('tempId', $request->tempId)
      ->where('entity', 'comunicacione')
      ->whereNull('entityId')
      ->first();

    if ($adjunto == null) {
      return response()->json(['success' => false, 'message' => 'Adjunto no encontrado'], 404);
    }

    $adjunto->delete();

    if (Storage::disk('public')->exists($request->filename)) {
      Storage::disk('public')->delete($request->filename);
    }

    return response()->json(['success' => true, 'filename' => $request->filename]);
  }

  private function guardarAdjunto($file, $tempId, $entity, $prefix)
  {
    $safeClientOriginalName= EureFunctions::cleanFileName($file->getClientOriginalName());
    $fileName = $prefix . date('ymdHis') . '_' . $safeClientOriginalName;

    Storage::disk('public')->put($fileName, file_get_contents($file));

    $adjuntoNew= new Adjunto();
    $adjuntoNew->filename = $fileName;
    $adjuntoNew->originalFilename = $file->getClientOriginalName();
    $adjuntoNew->tempId= $tempId;
    $adjuntoNew->entity= $entity;
    $adjuntoNew->save();

    return [
      'originalFN' => $file->getClientOriginalName(),
      'newFN' => $fileName,
      'url' => url("/storage/$fileName"),
    ];
  }

  private function autorizarAlumnoApi($codAlumno)
  {
    $user = auth()->user();

    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Usuario no autenticado'], 401);
    }

    $alumno = Alumno::find($codAlumno);

    if (!$alumno) {
      return response()->json(['success' => false, 'message' => 'Alumno no encontrado'], 404);
    }

    $responsable = $user->responsable;

    if ($responsable == null || !EureFunctions::esResponsableDeAlumno($responsable, $alumno)) {
      return response()->json(['success' => false, 'message' => 'Acceso no permitido'], 403);
    }

    return null;
  }


}
