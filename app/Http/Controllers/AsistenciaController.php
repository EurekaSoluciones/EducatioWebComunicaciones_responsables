<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
// haceme un metodo indexA igual al del controller NotaController puede ser?
    public function indexA(Alumno $alumno)
    {
      $inasistencias = EducatioCommFunctions::Inasistencias_Obtener($alumno);

    //  dd($inasistencias, $cantidadTotal, $cantidadSemana, $cantidadMes);

      return view('asistencias.indexa', compact('alumno', 'inasistencias'));
    }
}
