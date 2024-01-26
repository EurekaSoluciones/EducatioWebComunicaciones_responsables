<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\NivelesEnum;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformeController extends Controller
{
  public function indexA(Alumno $alumno)
  {
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    // Y acá vamos a esto que es tan feo. Más adelante lo iremos arreglando
    switch (env('EURE_CLIENTE_ID'))
    {
      case 'sunrise':
        return $this->indexA_sunrise($alumno);

      default:
        return "Visualizador de informes estandar";

    }
  }


  // Para cada colegio
  public function indexA_sunrise(Alumno $alumno)
  {
//    dd($alumno->Ciclo);

    //dd( NivelesEnum::Inicial);


    // Y ojo ahora cambia todo según el nivel
    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        return indexA_sunrise_inicial();

      case NivelesEnum::Primario->value:
        return $this->indexA_sunrise_primario($alumno);

      case NivelesEnum::Secundario->value:
        return indexA_sunrise_secundario();

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_sunrise_primario(Alumno $alumno)
  {
    $informesItems = DB::select('exec SP_WEB_ConsultaConceptos @CodAlumno = ?, @anioLect = ?', array($alumno->id, EureFunctions::al()));

     $informeItems1er = collect($informesItems)->filter(function ($item) {
       return str_starts_with($item->Pestania, '1_');
     })->all();

    $informeItems2do = collect($informesItems)->filter(function ($item) {
      return str_starts_with($item->Pestania, '2_');
    })->all();

    $informeItems3er = collect($informesItems)->filter(function ($item) {
      return str_starts_with($item->Pestania, '3_');
    })->all();



//    dump($informeItems1er);
//    dump($informeItems2do);
//    dd($informeItems3er);

    return view('informes.sunrise.primario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

}
