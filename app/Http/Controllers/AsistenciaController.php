<?php

namespace App\Http\Controllers;

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
      $inasistencias = DB::select('exec SP_WEB_ConsultaInasistencias @CodAlumno = ?, @anioLect = ?', array($alumno->id, EureFunctions::al()));

      collect($inasistencias)->map(function ($i) {
        $i->fechaCarbon = Carbon::parse($i->fecha);
      });

  //    dd($inasistencias);

      $cantidadTotal= 0;
      $cantidadSemana= 0;
      $cantidadMes= 0;

      ; // Obtén la fecha actual
      $semanaPasada=  Carbon::now()->subDays(7);
      $mesPasado= Carbon::now()->submonth();

      foreach ($inasistencias as $i)
      {
        $cantidadTotal+= $i->imputar;

        if ($i->fechaCarbon >= $semanaPasada)
          $cantidadSemana+= $i->imputar;

        if ($i->fechaCarbon >= $mesPasado)
          $cantidadMes+= $i->imputar;
      }

//      dump($cantidadTotal);
//      dump($cantidadSemana);
//      dd($cantidadMes);
//


      return view('asistencias.indexa', compact('alumno', 'inasistencias', 'cantidadTotal', 'cantidadSemana', 'cantidadMes'));
    }
}
