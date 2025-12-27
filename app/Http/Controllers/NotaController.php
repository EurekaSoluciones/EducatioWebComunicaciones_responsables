<?php

namespace App\Http\Controllers;

use App\EureLib\Enums\NivelesEnum;
use App\EureLib\EureFunctions;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaController extends Controller
{
  //
  public function indexA(Alumno $alumno)
  {
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    // Y acá vamos a esto que es tan feo. Más adelante lo iremos arreglando
    switch (env('EURE_CLIENTE_ID'))
    {
      case 'ifesnqn':
      case 'ifesplottier':
      return $this->indexA_ifes($alumno);
        break;


      case 'sunrise':
        return $this->indexA_sunrise($alumno);

      default:
        return "Visualizador de notas estandar";

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
        return $this->indexA_sunrise_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_sunrise_primario(Alumno $alumno)
  {

    $nacademicas = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ningles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Ingles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ndesempenio = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Desempenio @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexcastellano = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExCastellano @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexingles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExIngles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));

    return view('notas.sunrise.primario', compact('alumno', 'nacademicas', 'ningles', 'ndesempenio', 'nexcastellano', 'nexingles'));
  }

  public function indexA_sunrise_secundario(Alumno $alumno)
  {

    $nacademicas = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ningles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Ingles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ndesempenio = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Desempenio @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexcastellano = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExCastellano @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexingles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExIngles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));

    return view('notas.sunrise.secundario', compact('alumno', 'nacademicas', 'ningles', 'ndesempenio', 'nexcastellano', 'nexingles'));
  }

  public function indexA_ifes(Alumno $alumno)
  {
//    dd($alumno->Ciclo);

    //dd( NivelesEnum::Inicial);


    // Y ojo ahora cambia todo según el nivel
    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        return indexA_ifes_inicial();

      case NivelesEnum::Primario->value:
        return $this->indexA_ifes_primario($alumno);

      case NivelesEnum::Secundario->value:
        return $this->indexA_ifes_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_ifes_primario(Alumno $alumno)
  {

    $nacademicas = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ningles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Ingles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ndesempenio = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Desempenio @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexcastellano = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExCastellano @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexingles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExIngles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));

    return view('notas.ifes.primario', compact('alumno', 'nacademicas', 'ningles', 'ndesempenio', 'nexcastellano', 'nexingles'));
  }

  public function indexA_ifes_secundario(Alumno $alumno)
  {

    $nacademicas = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ningles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Ingles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $ndesempenio = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_Desempenio @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexcastellano = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExCastellano @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));
    $nexingles = DB::select('exec SP_WEB_NOTASAGRUPADASPORALUMNOBOLETIN_ExIngles @CodAlumno = ?, @añolectivo = ?', array($alumno->id, EureFunctions::al()));

    return view('notas.ifes.secundario', compact('alumno', 'nacademicas', 'ningles', 'ndesempenio', 'nexcastellano', 'nexingles'));
  }


}
