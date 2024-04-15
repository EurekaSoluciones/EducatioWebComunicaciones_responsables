<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Adjunto;
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
    $file = $request->file('file');

    $safeClientOriginalName= EureFunctions::cleanFileName($file->getClientOriginalName());
    $fileName = 'CommEAdj_' . date('ymdHis') . '_' . $safeClientOriginalName;

    Storage::disk('public')->put($fileName, file_get_contents($file));

    $adjuntoNew= new Adjunto();
    $adjuntoNew->filename = $fileName;
    $adjuntoNew->originalFilename = $file->getClientOriginalName();
    $adjuntoNew->tempId= $request->tempId;
    $adjuntoNew->entity= "comunicacione";
    $adjuntoNew->save();

    return response()->json(['originalFN' => $file->getClientOriginalName(),  'newFN' => $fileName]);
  }

  public function destroyAdjunto()
  {
    $filename=  $request->get('filename');
    $tempId=  $request->get('tempId');

    // Si no hago esto pueden forgear el nombre de un archivo y borrar
    Adjunto::where('filename',$filename)->where('tempId', $tempId)->delete();

    if (Storage::disk('public')->exists($filename))
      Storage::disk('public')->delete($filename);

    return $filename;
  }

}
