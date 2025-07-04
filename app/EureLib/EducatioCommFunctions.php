<?php

namespace App\EureLib;

use App\Models\Alumno;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EducatioCommFunctions
{
  public static function MENU_leyenda_Boletin()
  {
    switch (EureFunctions::cliente_id())
    {
      case 'rainbow':
        return 'Informes';

      case 'belgrano':
        return 'Informes / Boletín';

      default:
        return 'Informes / DUCO';
    }
  }

  public static function CC_Obtener(Alumno $alumno, &$venceEsteMes, &$venceHoy, &$deudaVencida, &$proximoVencimiento)
  {
    $hoy = EureFunctions::hoy();
    $udm = EureFunctions::ultimoDiaMes();
    $hasta = EureFunctions::ultimoDiaMes()->addMonth();

    $ccItems = DB::select('exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = ?', array($alumno->id, null, $hasta));

    $ccItems = array_map(function ($fila) {
      $fila->Fecha_Venc = EureFunctions::toCarbonDateFromYmd($fila->Fecha_Venc);
      $fila->Monto = (float)$fila->Monto;
      $fila->Saldo = (float)$fila->Saldo;

      return $fila;
    }, $ccItems);

//    dd($ccitems);

    // Vamos a tener que hacer fuerza bruta acá. Después agregar los intereses

    $venceEsteMes = 0.0;
    $venceHoy = 0;
    $deudaVencida = 0;
    $proximoVencimiento = Carbon::create(2050, 1, 1, 12, 0, 0);

    foreach ($ccItems as $item)
    {
      // Básicamente ver si es deuda, si es este mes, etc etc
      if ($item->Saldo > 0)
      {
        if ($item->Fecha_Venc > $hoy)
        {
          if ($item->Fecha_Venc <= $udm)
          {
            $venceEsteMes += $item->Saldo;

            if ($item->Fecha_Venc < $proximoVencimiento)
              $proximoVencimiento = $item->Fecha_Venc;
          }
        }

        if ($item->Fecha_Venc < $hoy)
          $deudaVencida += $item->Saldo;

        if ($item->Fecha_Venc == $hoy)
          $venceHoy += $item->Saldo;
      }
    }

    return $ccItems;

  }

  public static function pagosTic()
  {
    return strtolower(env('EURE_PAGOS_TIC', '')) === 's';
  }

  public static function pagosOnline()
  {
    return self::pagosTic() || false;

  }

  public static function Pagos_Obtener(Alumno $alumno, $fDesde, $fHasta)
  {
    $pagos = DB::select('exec SP_WEB_PagosEfectuados @CodAlumno = ?, @FDesde = ?, @FHasta = ?', array($alumno->id, $fDesde, $fHasta));

    $pagos = array_map(function ($fila) {
      $fila->Fecha_Pago = EureFunctions::toCarbonDateFromYmd($fila->Fecha_Pago);
      $fila->Total = (float)$fila->Total;

      return $fila;
    }, $pagos);

    return $pagos;
  }

  public static function Inasistencias_Obtener(Alumno $alumno)
  {
    $inasistencias = DB::select(
      'exec SP_WEB_ConsultaInasistencias @CodAlumno = ?, @anioLect = ?',
      [$alumno->id, EureFunctions::al()]
    );

    // Agregamos la propiedad fechaCarbon a cada registro
    collect($inasistencias)->map(function ($i) {
      $i->fechaCarbon = \Carbon\Carbon::parse($i->fecha);
    });

    // Inicializamos los contadores
    $cantidadTotal = 0;
    $cantidadSemana = 0;
    $cantidadMes = 0;

    // Fechas de comparación
    $semanaPasada = \Carbon\Carbon::now()->subDays(7);
    $mesPasado = \Carbon\Carbon::now()->subMonth();

    foreach ($inasistencias as $i)
    {
      $cantidadTotal += $i->imputar;

      if ($i->fechaCarbon >= $semanaPasada)
      {
        $cantidadSemana += $i->imputar;
      }

      if ($i->fechaCarbon >= $mesPasado)
      {
        $cantidadMes += $i->imputar;
      }
    }

    // Crear un objeto estándar para devolver los resultados
    $resultado = new \stdClass();
    $resultado->lista = $inasistencias;
    $resultado->cantidadTotal = $cantidadTotal;
    $resultado->cantidadSemana = $cantidadSemana;
    $resultado->cantidadMes = $cantidadMes;

    return $resultado;
  }

  // Este está acá porque tiene cantidad de cosas específicas
  public static function PTIC_ObtenerLinkIntencionPago
  (
    $accessToken,
    $importe,
    Alumno $alumno
  )
  {
    $urlPTIC_IP = 'https://api.paypertic.com/pagos';
    $external_transaction_id =
      implode
      (
        ';',
        [$alumno->Cod_Alumno, $alumno->DNI, now()->format('YmdHisv'), str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT)]
      );


    $nurl = config('app.url') . '/api/tercerizados-cobranza/ptic/notificacion-pago';
    $backURL = config('app.url') . '/alumno/' . $alumno->Cod_Alumno;
    $RL = EureFunctions::getLoggedResponsableAttribute();
    $email = EureFunctions::primerEmailValido($RL->Email);


    $ip_Details = [[
      'external_reference' => $alumno->Cod_Alumno,
      'concept_id' => 1,
      'concept_description' => "(" . $alumno->DNI . ") " . $alumno->Apellido . ", " . $alumno->Nombre,
      'amount' => $importe,
    ]];

    $ip_Payer =
      [
        'name' => $alumno->Nombre . ' ' . $alumno->Apellido,
        'email' => $email,
        'identification' => [
          'type' => 'DNI_ARG',
          'number' => $alumno->DNI,
          'country' => 'ARG'
        ]
      ];

    $pIntencionPago = [
      'currency_id' => 'ARS',
      'external_transaction_id' => $external_transaction_id,
      'due_date' => now()->format('Y-m-d') . 'T00:00:00-0300',
      'last_due_date' => now()->addDay()->format('Y-m-d') . 'T00:00:00-0300',
      'notification_url' => $nurl, // URL de notificación
      'ip_details' => $ip_Details, // Detalles de la intencion de pago
      'nurl' => $nurl, // URL de notificación
      'back_url' => $backURL, // URL de retorno después del pago
      'importe' => $importe,
      'payer' => $ip_Payer, // Detalles del pagador
      'details' => $ip_Details,
    ];

    // Enviar solicitud
    $HTTPPost = Http::withToken($accessToken)->acceptJson();

    if (getenv('EURE_PAGOS_TIC_AMBIENTE') == "DESARROLLO")
      $HTTPPost = $HTTPPost->withOptions(['verify' => false]);

    $response = $HTTPPost->post('https://api.paypertic.com/pagos', $pIntencionPago);

    if ($response->successful())
    {
      return $response->json(); // ← Devuelve los datos como el link de pago
    }

    throw new \Exception("Error al crear intención de pago: " . $response->status() . ' - ' . $response->body());


  }

  static public function PTIC_ImputarPago(
    int           $cod_alumno,
    Carbon|string $fecha,
    float         $importe,
    string        $external_transaction_id,
    string        $ptic_id
  ): JsonResponse
  {
    try
    {
      if (!$fecha instanceof Carbon)
      {
        $fecha = Carbon::parse($fecha);
      }

      DB::statement(
        'EXEC SP_WEB_CobroPagosTIC @codAlumno = ?, @fecha = ?, @Monto = ?, @Cadena = ?, @idPagosTic = ?',
        [
          $cod_alumno,
          $fecha->format('Y-m-d H:i:s'),
          $importe,
          $external_transaction_id,
          $ptic_id
        ]
      );

      return response()->json([
        'message' => 'Pago imputado correctamente',
        'ptic_id' => $ptic_id
      ], 200);

    }
    catch (\Exception $e)
    {
      EureFunctions::PTIC_PostLog("ERROR", json_encode([
        'nota' => 'en PTIC_ImputarPago',
        'cod_alumno' => $cod_alumno,
        'fecha' => $fecha,
        'importe' => $importe,
        'external_transaction_id' => $external_transaction_id,
        'ptic_id' => $ptic_id,
        'error' => $e->getMessage()
      ]));


      return response()->json([
        'message' => 'Error al imputar pago',
        'error' => $e->getMessage()
      ], 500); // Podés cambiar a 400 si preferís
    }
  }
}
