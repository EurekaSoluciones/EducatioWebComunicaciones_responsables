<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\EureLib\EducatioCommFunctions;
use App\Models\Alumno;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    return view('cuentascorrientes.indexa', compact('ccItems', 'alumno', 'venceEsteMes', 'venceHoy', 'proximoVencimiento', 'deudaVencida'));
  }

  public function pagosA(Alumno $alumno)
  {
    // Tengo que controlar que no estén boludeando con la url
    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    $pagos = EducatioCommFunctions::Pagos_Obtener($alumno, null, null);


//    $pagos = DB::select('exec SP_WEB_PagosEfectuados @CodAlumno = ?, @FDesde = ?, @FHasta = ?', array($alumno->id, null, null));
//
//    $pagos = array_map(function ($fila) {
//      $fila->Fecha_Pago = EureFunctions::toCarbonDateFromYmd($fila->Fecha_Pago);
//      $fila->Total = (float)$fila->Total;
//
//      return $fila;
//    }, $pagos);

//dd($pagos);

    return view('cuentascorrientes.pagosa', compact('alumno', 'pagos'));
  }

  public function descargarPago($cod_pago)
  {
    $responsable = EureFunctions::getLoggedResponsableAttribute();

    $urlPDF = $this->descargarPagoInner($cod_pago, $responsable);

    return redirect()->away($urlPDF);
  }

  public function api_descargarPago($cod_pago)
  {
    $user = Auth::user();
    $responsable = $user->responsable;

    $urlPDF = $this->descargarPagoInner($cod_pago, $responsable);

    return response()->json(['url' => $urlPDF]);
  }

  public function descargarPagoInner($cod_pago, Responsable $responsable)
  {
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

    //dd( json_encode($rptParams));

    $resultado = EureFunctions::obtenerPDF('facturaFE.rpt', 'Factura', '', $rptParams);


    if (!$resultado->RequestsStatusOK)
      abort(400, $resultado->RequestsStatusObs);


    // Acá tendria que descargar el pdf que genera automáticamente el módulo que queda de los ws viejos
    return $resultado->pdf_URL;

  }

  public function appDescargarPago($cod_pago, $token)
  {
    if ($token != 'M4D' && $token != hash('sha256', 'M4D' . $cod_pago))
      abort(403);

    return $this->descargarPago($cod_pago);
  }

  public function pagar(Request $request)
  {
    $importeRAW = $request->input('importe');
    $metodo = $request->input('metodo');
    $cod_alumno = $request->input('alumno');
    $alumno = Alumno::find($cod_alumno);

    if (!$alumno) {
      abort(404, 'Alumno no encontrado');
    }

    $importe = EureFunctions::toNumericFromString_Argentina($importeRAW);

    if (!$importe)
      abort(400, 'Importe inválido');

    if (!EureFunctions::esUsuarioLogueadoEsResponsableDeAlumno($alumno))
      abort(403, 'No permitido');

    if (!$importe || !$metodo || !$alumno || !is_numeric($importe) || $importe <= 0) {
      abort(400, 'Faltan datos para procesar el pago');
    };

    switch ($metodo) {
      case 'pagostic':
        return $this->pagar_PTIC($importe, $alumno);

      case 'mercadopago':
        return $this->pagar_MercadoPago($importe, $alumno);
//
//        case 'transferencia':
//        return view('pago.transferencia')->with('importe', $importe);

      default:
        abort(400, 'Método de pago no reconocido');
    }
  }

  function pagar_MercadoPago($importe, Alumno $alumno)
  {
//    dd($alumno);

    Log::channel('mercadopago')->info("Redirigiendo a MercadoPago para pago de alumno ID {$alumno->id} por importe {$importe}");

    $accessToken = env('EURE_MERCADO_PAGO_ACCESS_TOKEN');

    $baseUrl = app()->environment('local')
      ? env('NGROK')
      : config('app.url');

    $external_reference=
      implode
      (
        ';',
        [$alumno->Cod_Alumno, $alumno->DNI, now()->format('YmdHisv'), str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT)]
      );

    $payload = [
      'items' => [
        [
          'title' => $alumno->nombreYApellido,
          'quantity' => 1,
          'unit_price' => $importe,
          'currency_id' => 'ARS',
        ]
      ],
      'payer' => [
        'name' => $alumno->nombre,
        'surname' => $alumno->apellido,
        'email' => $alumno->email ?? 'test_user@test.com',
        'identification' => [
          'type' => 'DNI',
          'number' => $alumno->dni,
        ],
      ],
      // esto es CLAVE para vincular pago con tu sistema
      'external_reference' => $external_reference,

      'back_urls' => [
        'success' => $baseUrl . route('cc.indexA', ['alumno' => $alumno->id], false),
//      'failure' => $baseUrl . route('mp.error', [], false),
//      'pending' => $baseUrl . route('mp.pendiente', [], false),
      ],
      'auto_return' => 'approved',
      'notification_url' => $baseUrl . '/api/tercerizados-cobranza/mp/notificacion-pago',
    ];

    try {

      $http = Http::withToken($accessToken);

      if (app()->environment('local')) {
        $http = $http->withoutVerifying();
      }

      $response = $http->post(
        'https://api.mercadopago.com/checkout/preferences',
        $payload
      );

      if (!$response->successful()) {

        Log::channel('mercadopago')->error("Error al solicitar preferencia de pago a MercadoPago para alumno ID {$alumno->id}", [
          'status' => $response->status(),
          'body' => $response->body(),
        ]);

        return response()->json([
          'ok' => false,
          'status' => $response->status(),
          'body' => $response->json(),
        ], 500);
      }

      Log::channel('mercadopago')->info(
        "Preferencia de pago creada exitosamente para alumno ID {$alumno->id}",
        [
          'alumno_id' => $alumno->id,
          'importe' => $importe,
          'preference_id' => $response->json('id'),
          'init_point' => $response->json('init_point'),
          'status' => $response->status(),
          'external_reference' => $external_reference,
        ]
      );

      $data = $response->json();

      return redirect($data['init_point']);
    } catch (\Exception $e) {
      Log::channel('mercadopago')->error("Error al crear preferencia de pago {$alumno->id}", [
        'exception' => $e->getMessage(),
      ]);

      throw $e;

    }
  }


  function api_tc_MP_logTest(Request $request)
  {
    $raw = $request->getContent();

    Log::channel('mercadopago')->info("Log de prueba recibido para MercadoPago", ['raw' => $raw]);
  }

  function api_tc_MP_notificacionPago(Request $request)
  {
    Log::channel('mercadopago')->info("IPN recibido", $request->all());

    $type = $request->input('topic');
    if ($type !== 'payment')
      return response()->json(['ignored' => true]);

    try {
      $accessToken = env('EURE_MERCADO_PAGO_ACCESS_TOKEN');

      $id = $request->input('id');
      if (!$id)
        throw new \Exception('ID de pago no recibido');

      $http = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/json',
      ]);

      //  Solo en local, desactivar SSL
      if (app()->environment('local'))
        $http = $http->withoutVerifying();

      $response = $http->get("https://api.mercadopago.com/v1/payments/{$id}");

      if ($response->failed()) {
        if ($response->status() === 404) {
          Log::channel('mercadopago')->warning("Pago no encontrado ID {$id}", $response->json());
          return response()->json(['status' => 'not_found'], 404);
        } else
          throw new \Exception('Fallo al consultar pago: ' . $response->body());
      }

      $paymentRAW = $response;
      $payment = $paymentRAW->json();

      Log::channel('mercadopago')->info("Pago consultado ID {$id}", $payment);

      // Si la tasa del ipn no me coincide con el external esta todo mal
      $componentes = explode(';', $payment['external_reference']);
      $codAlumno = isset($componentes[0]) ? (int)$componentes[0] : null;


      if ($payment['status'] === 'approved') {
        DB::statement(
          'EXEC SP_WEB_CobroMercadoPago @codAlumno = ?, @fecha = ?, @Monto = ?, @Cadena = ?',
          [
            $codAlumno,
            Carbon::parse($payment['date_approved']),
            $payment['transaction_amount'],
            $payment['external_reference'],
            $payment['id']
          ]);

        return response()->json([
          'message' => 'Pago imputado correctamente',
          'id' => $payment['id']
        ], 200);

      }
    } catch (\Throwable $e) {

      Log::channel('mercadopago')->error('Error al procesar IPN', [
        'msg' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
    }
  }


  function pagar_PTIC($importe, Alumno $alumno)
  {
    // Son 3 cosas PTIC. Autenticarse:
    $token = EureFunctions::PTIC_ObtenerToken(getenv('EURE_PAGOS_TIC_AMBIENTE'), getenv('PTIC_PASSWORD'));

    $iPago = EducatioCommFunctions::PTIC_ObtenerLinkIntencionPago($token['access_token'], $importe, $alumno);

    //dd($iPago);
    return redirect()->away($iPago['form_url']);

//    if (!$iPago || !$iPago->url)
//      abort(400, 'Error al obtener el link de pago');
//
//    // Redirigir a la URL de pago
//    return redirect()->away($iPago->url);


    //   return response()->json(['message' => 'Pago realizado con éxito', 'url' => $resultado->pdf_URL]);
  }


  function api_tc_PTIC_logTest(Request $request)
  {

    $raw = $request->getContent();

    // Log de prueba para PTIC
    EureFunctions::PTIC_PostLog("Test", $raw);
  }

  function api_tc_PTIC_notificacionPago(Request $request)
  {
    $raw = $request->getContent();

    // Log de prueba para PTIC
    EureFunctions::PTIC_PostLog("NotificacionPago", $raw);

    try {

      // Procesar la notificación de pago
      $pago = json_decode($raw, true);

      if ($pago['status'] != "approved")
        return;

      $componentes = explode(';', $pago['external_transaction_id']);
      $codAlumno = isset($componentes[0]) ? (int)$componentes[0] : null;

      return
        EducatioCommFunctions
          ::PTIC_ImputarPago(
            $codAlumno,
            Carbon::parse($pago['paid_date']),
            $pago['final_amount'],
            $pago['external_transaction_id'],
            $pago['id']
          );
    } catch (\Exception $e) {
      EureFunctions::PTIC_PostLog("ERROR", json_encode([
        'nota ' => 'Error en api_tc_PTIC_notificacionPago',
        'error' => $e->getMessage()
      ]));

    }
  }


}
