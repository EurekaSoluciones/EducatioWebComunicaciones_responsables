<?php

namespace App\Http\Controllers;

use App\EureLib\EducatioCommFunctions;
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
//dd(EureFunctions::cliente_id());

    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    // Y acá vamos a esto que es tan feo. Más adelante lo iremos arreglando
    switch (env('EURE_CLIENTE_ID'))
    {
      case 'ifesnqn':
      case 'ifesplottier':
        return $this->indexA_ifes($alumno);

      case 'sunrise':
        return $this->indexA_sunrise($alumno);

      case 'belgrano':
        // usamos el gen. Ahora el gen usa el nombre del cliente para la view
        return $this->indexA_gen_tagInst($alumno);

        case 'rainbow':
          return $this->indexA_gen_tagInst($alumno);

      case 'amancay':
        return $this->indexA_gen_tagInst($alumno);

      case 'demo':
        return $this->indexA_gen($alumno);




      default:
        return "Visualizador de informes estandar - DEFAULT SWITCH";

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
        return $this->indexA_sunrise_inicial($alumno);

      case NivelesEnum::Primario->value:
        return $this->indexA_sunrise_primario($alumno);

      case NivelesEnum::Secundario->value:
        return $this->indexA_sunrise_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_sunrise_inicial(Alumno $alumno)
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

    return view('informes.sunrise.inicial', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
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

  public function indexA_sunrise_secundario(Alumno $alumno)
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

    return view('informes.sunrise.secundario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }


  public function indexA_ifes(Alumno $alumno)
  {
//    dd($alumno->Ciclo);




    // Y ojo ahora cambia todo según el nivel
    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        return $this->indexA_ifes_inicial($alumno);

      case NivelesEnum::Primario->value:
        return $this->indexA_ifes_primario($alumno);

      case NivelesEnum::Secundario->value:
        return $this->indexA_ifes_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_ifes_inicial(Alumno $alumno)
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

    return view('informes.ifes.inicial', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

  public function indexA_ifes_primario(Alumno $alumno)
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

    return view('informes.ifes.primario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

  public function indexA_ifes_secundario(Alumno $alumno)
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

    return view('informes.ifes.secundario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }



  public function indexA_gen(Alumno $alumno)
  {
//    dd($alumno->Ciclo);

    // Y ojo ahora cambia todo según el nivel
    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        return $this->indexA_gen_inicial($alumno);

      case NivelesEnum::Primario->value:
        return $this->indexA_gen_primario($alumno);

      case NivelesEnum::Secundario->value:
        return $this->indexA_gen_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_gen_inicial(Alumno $alumno)
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

    return view('informes.gen.inicial', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

  public function indexA_gen_primario(Alumno $alumno)
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

    return view('informes.gen.primario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

  public function indexA_gen_secundario(Alumno $alumno)
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

    return view('informes.gen.secundario', compact('alumno', 'informeItems1er', 'informeItems2do', 'informeItems3er'));

    //    dd($notas);
  }

  public function indexA_gen_tagInst(Alumno $alumno)
  {
//    dd($alumno->Ciclo);

    // Y ojo ahora cambia todo según el nivel
    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        return $this->indexA_gen_tagInst_inicial($alumno);

      case NivelesEnum::Primario->value:
        return $this->indexA_gen_tagInst_primario($alumno);

      case NivelesEnum::Secundario->value:
        return $this->indexA_gen_tagInst_secundario($alumno);

      default:
        return abort(400, 'Ciclo inesperado ' . $alumno->Ciclo);
    }
  }

  public function indexA_gen_tagInst_inicial(Alumno $alumno)
  {
    $bloqueo1Informe = EducatioCommFunctions::MensajeBloqueo1Informe($alumno);
    return view('informes.' . EureFunctions::cliente_id() . '.inicial', compact('alumno', 'bloqueo1Informe'));

    //    dd($notas);
  }

  public function indexA_gen_tagInst_primario(Alumno $alumno)
  {
    $bloqueo1Informe = EducatioCommFunctions::MensajeBloqueo1Informe($alumno);
    return view('informes.' . EureFunctions::cliente_id() . '.primario', compact('alumno', 'bloqueo1Informe'));

    //    dd($notas);
  }

  public function indexA_gen_tagInst_secundario(Alumno $alumno)
  {
    $bloqueoCertificado = EducatioCommFunctions::MensajeBloqueoCertificado($alumno);
    $bloqueo1Informe = EducatioCommFunctions::MensajeBloqueo1Informe($alumno);
    $bloqueo2Informe = EducatioCommFunctions::MensajeBloqueo2Informe($alumno);
    $bloqueoInformeFinal = EducatioCommFunctions::MensajeBloqueoInformeFinal($alumno);
    return view('informes.' . EureFunctions::cliente_id() . '.secundario', compact('alumno', 'bloqueoCertificado', 'bloqueo1Informe','bloqueo2Informe', 'bloqueoInformeFinal'));

    //    dd($notas);
  }



  public function descargarDUCO(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');


    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo

    $rptParams =
    [
      [
        'nombre' => '@CodAlumno',
        'valor' => $alumno->Cod_Alumno,
      ],
      [
        'nombre' => '@anioLectivo',
        'valor' => EureFunctions::al(),
      ],
    ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Informe_INICIAL.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_PRIMARIA.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_MEDIA.rpt', 'Informe_', '', $rptParams);
        break;

    }

    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    return redirect()->away($resultado->pdf_URL);

  }


  public function descargarDUCO2(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');


    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo

    $rptParams =
    [
      [
        'nombre' => '@CodAlumno',
        'valor' => $alumno->Cod_Alumno,
      ],
      [
        'nombre' => '@anioLectivo',
        'valor' => EureFunctions::al(),
      ],
    ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Informe_INICIAL.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_PRIMARIA.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_MEDIA2.rpt', 'Informe_', '', $rptParams);
        break;

    }

    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    return redirect()->away($resultado->pdf_URL);

  }



  public function descargarExamenFinal(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');


    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo

    $rptParams =
    [
      [
        'nombre' => '@CodAlumno',
        'valor' => $alumno->Cod_Alumno,
      ],
      [
        'nombre' => '@anioLectivo',
        'valor' => EureFunctions::al(),
      ],
    ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Informe_Final.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_Final.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_Final.rpt', 'Informe_', '', $rptParams);
        break;

    }

    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    return redirect()->away($resultado->pdf_URL);

  }



  public function descargarCertificado(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo
    
    $rptParams =
    [
      [
        'nombre' => '@CodAlumno',
        'valor' => $alumno->Cod_Alumno,
      ],
      [
        'nombre' => '@anioLectivo',
        'valor' => EureFunctions::al(),
      ],
    ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Certificado.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Certificado.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Certificado.rpt', 'Informe_', '', $rptParams);
        break;

    }


    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    
      return redirect()->away($resultado->pdf_URL);

    

  }


  public function informeConceptual(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');


    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo

    $rptParams =
      [
        [
          'nombre' => '@CodAlumno',
          'valor' => $alumno->Cod_Alumno,
        ],
        [
          'nombre' => '@anioLectivo',
          'valor' => EureFunctions::al(),
        ],
      ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Informe_INICIAL.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_PRIMARIA.rpt', 'Informe_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Informe_MEDIA.rpt', 'Informe_', '', $rptParams);
        break;

    }

    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    return redirect()->away($resultado->pdf_URL);

  }

  public function descargarBoletin(Alumno $alumno)
  {
    // Validacion que no estén boludeando con las urls
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');


    // El duco va a ser genérico para todos. Separo nomás por nivel, pero para todos los
    // colegios lo mismo

    $rptParams =
      [
        [
          'nombre' => '@CodAlumno',
          'valor' => $alumno->Cod_Alumno,
        ],
        [
          'nombre' => '@anioLectivo',
          'valor' => EureFunctions::al(),
        ],
      ];


    switch ($alumno->Ciclo)
    {
      case NivelesEnum::Inicial->value:
        $resultado= EureFunctions::obtenerPDF('Boletin_INICIAL.rpt', 'Boletin_', '', $rptParams);
        break;

      case NivelesEnum::Primario->value:
        $resultado= EureFunctions::obtenerPDF('Boletin_PRIMARIA.rpt', 'Boletin_', '', $rptParams);
        break;

      case NivelesEnum::Secundario->value:
        $resultado= EureFunctions::obtenerPDF('Boletin_MEDIA.rpt', 'Boletin_', '', $rptParams);
        break;

    }

    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);


    return redirect()->away($resultado->pdf_URL);

  }


}
