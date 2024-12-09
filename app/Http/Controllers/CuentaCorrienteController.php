<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\EureLib\EducatioCommFunctions;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\TestRunner\ExecutionAborted;

class CuentaCorrienteController extends Controller
{
  //
  public function indexA(Alumno $alumno)
  {
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    $venceEsteMes = 0.0;
    $venceHoy = 0;
    $deudaVencida = 0;
    $proximoVencimiento = Carbon::create(2050, 1, 1, 12, 0, 0);


    $ccItems = EducatioCommFunctions::CC_Obtener($alumno, $venceEsteMes, $venceHoy, $deudaVencida, $proximoVencimiento);

//    dd($venceEsteMes);

    return view('cuentascorrientes.indexa', compact('ccItems', 'alumno', 'venceEsteMes', 'venceHoy', 'proximoVencimiento', 'deudaVencida' ));
  }

  public function pagosA(Alumno $alumno)
  {
    // Tengo que controlar que no estén boludeando con la url
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    $pagos = DB::select('exec SP_WEB_PagosEfectuados @CodAlumno = ?, @FDesde = ?, @FHasta = ?', array($alumno->id, null, null));

    $pagos = array_map(function ($fila) {
      $fila->Fecha_Pago = EureFunctions::toCarbonDateFromYmd($fila->Fecha_Pago);
      $fila->Total = (float)$fila->Total;

      return $fila;
    }, $pagos);

//dd($pagos);

    return view('cuentascorrientes.pagosa', compact('alumno', 'pagos'));
  }

  public function descargarPago($cod_pago)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    // Controlamos que sea legit la descarga.
    $subconsulta = DB::table('alumnos as A')
      ->select('A.Cod_ResponsableCobro')
      ->where(function ($query) use ($responsable) {
        $query->where('A.Responsable1', '=', $responsable->id)
          ->orWhere('A.Responsable2', '=', $responsable->id);
      });

    $resultado = DB::table('recibos as REC')
      ->where('REC.Cod_Recibo', $cod_pago)
      ->whereIn('REC.cod_responsable', $subconsulta)
      ->count();

    // Si el resultado es 0 es unauthorized
    if ($resultado == 0)
      abort(401, 'No autorizado');

    $rptParams = [
      [
        'nombre' => '@iRecibo',
        'valor' => $cod_pago,
      ],];

    $resultado= EureFunctions::obtenerPDF('facturaFE.rpt', 'Factura', '', $rptParams);



    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);

    return redirect()->away($resultado->pdf_URL);

    // Acá tendria que descargar el pdf que genera automáticamente el módulo que queda de los ws viejos


  }
}
