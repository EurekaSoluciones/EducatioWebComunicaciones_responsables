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
    switch (EureFunctions::cliente_id()) {
      case 'rainbow':
        return 'Informes';

      case 'belgrano':
        return 'Informes / Boletín';

      case 'amancay':
        return 'Informe Pedagógico';

      case 'culturalnqn':
        return 'Boletines / Certificados ';

      case 'culturalcentenario':
        return 'Boletines / Certificados ';

      default:
        return 'Informes / DUCO';
    }
  }

  public static function MENU_leyenda_cuentaCorriente()
  {
    switch (EureFunctions::cliente_id()) {
      case 'culturalnqn':
        return 'Pagos';

      case 'culturalcentenario':
        return 'Pagos';

      default:
        return 'Cuenta Corriente';
    }
  }

  public static function MENU_leyenda_pagos()
  {
    switch (EureFunctions::cliente_id()) {
      case 'culturalnqn':
        return 'Recibos';

      case 'culturalcentenario':
        return 'Recibos';

      default:
        return 'Pagos';
    }
  }

  public static function CC_Obtener(Alumno $alumno, &$venceEsteMes, &$venceHoy, &$deudaVencida, &$proximoVencimiento)
  {
    $hoy = EureFunctions::hoy();
    $udm = EureFunctions::ultimoDiaMes();
    $hasta = EureFunctions::ultimoDiaMes()->addMonth();

    $ccItems = DB::select('exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = ?', [$alumno->id, null, $hasta]);

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

    foreach ($ccItems as $item) {
      // Básicamente ver si es deuda, si es este mes, etc etc
      if ($item->Saldo > 0) {
        if ($item->Fecha_Venc > $hoy) {
          if ($item->Fecha_Venc <= $udm) {
            $venceEsteMes += $item->Saldo;

            if ($item->Fecha_Venc < $proximoVencimiento) {
              $proximoVencimiento = $item->Fecha_Venc;
            }
          }
        }

        if ($item->Fecha_Venc < $hoy) {
          $deudaVencida += $item->Saldo;
        }

        if ($item->Fecha_Venc == $hoy) {
          $venceHoy += $item->Saldo;
        }
      }
    }

    return $ccItems;

  }

  public static function MensajeBloqueoBoletin(Alumno $alumno): array
  {
    $resultado = DB::select(
      'EXEC SP_WEB_MensajeBloqueoBoletin ?, ?',
      [$alumno->id, EureFunctions::al()]
    );

    return [
      'cumple' => isset($resultado[0]) ? (bool)$resultado[0]->Cumple : false,
      'mensaje' => $resultado[0]->Mensaje ?? 'No es posible descargar el primer informe',
    ];
  }

  public static function MensajeBloqueoCertificado(Alumno $alumno): array
  {
    $resultado = DB::select(
      'EXEC SP_WEB_MensajeBloqueoCertificado ?, ?',
      [$alumno->id, EureFunctions::al()]
    );

    return [
      'cumple' => isset($resultado[0]) ? (bool)$resultado[0]->Cumple : false,
      'mensaje' => $resultado[0]->Mensaje ?? 'No es posible descargar el certificado',
    ];
  }

  public static function MensajeBloqueo1Informe(Alumno $alumno): array
  {
    $resultado = DB::select(
      'EXEC SP_WEB_MensajeBloqueo1Informe ?, ?',
      [$alumno->id, EureFunctions::al()]
    );

    return [
      'cumple' => isset($resultado[0]) ? (bool)$resultado[0]->Cumple : false,
      'mensaje' => $resultado[0]->Mensaje ?? 'No es posible descargar el primer informe',
    ];
  }

  public static function MensajeBloqueo2Informe(Alumno $alumno): array
  {
    $resultado = DB::select(
      'EXEC SP_WEB_MensajeBloqueo2Informe ?, ?',
      [$alumno->id, EureFunctions::al()]
    );

    return [
      'cumple' => isset($resultado[0]) ? (bool)$resultado[0]->Cumple : false,
      'mensaje' => $resultado[0]->Mensaje ?? 'No es posible descargar el segundo informe',
    ];
  }

  public static function MensajeBloqueoInformeFinal(Alumno $alumno): array
  {
    $resultado = DB::select(
      'EXEC SP_WEB_MensajeBloqueoInformeFinal ?, ?',
      [$alumno->id, EureFunctions::al()]
    );

    return [
      'cumple' => isset($resultado[0]) ? (bool)$resultado[0]->Cumple : false,
      'mensaje' => $resultado[0]->Mensaje ?? 'No es posible descargar el informe final',
    ];
  }

  public static function pagosTic()
  {
    return strtolower(env('EURE_PAGOS_TIC', '')) === 's';
  }

  public static function mercadoPago()
  {
    return strtolower(env('EURE_MERCADO_PAGO', '')) === 's';
  }

  public static function pagosOnline()
  {
    return self::pagosTic() || self::mercadoPago();

  }

  public static function Pagos_Obtener(Alumno $alumno, $fDesde, $fHasta)
  {
    $pagos = DB::select('exec SP_WEB_PagosEfectuados @CodAlumno = ?, @FDesde = ?, @FHasta = ?', [$alumno->id, $fDesde, $fHasta]);

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

    foreach ($inasistencias as $i) {
      $cantidadTotal += $i->imputar;

      if ($i->fechaCarbon >= $semanaPasada) {
        $cantidadSemana += $i->imputar;
      }

      if ($i->fechaCarbon >= $mesPasado) {
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


  public static function Documentos_Obtener(Alumno $alumno)
  {
//    dd(EureFunctions::al());

    $documentos = DB::select(
      'exec SP_WEB_Documentos @CodAlumno = ?, @aniolectivo = ?',
      [$alumno->id, EureFunctions::al()]
    );

    return $documentos;
  }


  // Este está acá porque tiene cantidad de cosas específicas
  public static function MP_ObtenerLinkIntencionPago($importe, Alumno $alumno): string
  {
    EureFunctions::MP_Log(
      'info',
      "Redirigiendo a MercadoPago para pago de alumno ID {$alumno->id} por importe {$importe}"
    );

    $baseUrl = EureFunctions::MP_BaseUrl();
    $externalReference = implode(
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
        ],
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
      'external_reference' => $externalReference,
      'back_urls' => [
        'success' => $baseUrl . route('pagos.indexA', ['alumno' => $alumno->id], false),
      ],
      'auto_return' => 'approved',
      'notification_url' => $baseUrl . '/api/tercerizados-cobranza/mp/notificacion-pago',
    ];

    $http = Http::withToken(EureFunctions::MP_ObtenerAccessToken());
    if (app()->environment('local')) {
      $http = $http->withoutVerifying();
    }

    $response = $http->post('https://api.mercadopago.com/checkout/preferences', $payload);

    if (!$response->successful()) {
      EureFunctions::MP_Log(
        'error',
        "Error al solicitar preferencia de pago a MercadoPago para alumno ID {$alumno->id}",
        [
          'status' => $response->status(),
          'body' => $response->body(),
        ]
      );

      throw new \Exception('Error al crear preferencia de MercadoPago: ' . $response->status());
    }

    $data = $response->json();
    $initPoint = $data['init_point'] ?? null;
    if (!$initPoint) {
      throw new \Exception('Respuesta de MercadoPago sin init_point');
    }

    EureFunctions::MP_Log(
      'info',
      "Preferencia de pago creada exitosamente para alumno ID {$alumno->id}",
      [
        'alumno_id' => $alumno->id,
        'importe' => $importe,
        'preference_id' => $data['id'] ?? null,
        'init_point' => $initPoint,
        'status' => $response->status(),
        'external_reference' => $externalReference,
      ]
    );

    return $initPoint;
  }

  public static function MP_LogTest($raw): void
  {
    EureFunctions::MP_Log('info', 'Log de prueba recibido para MercadoPago', ['raw' => $raw]);
  }

  public static function MP_ProcesarNotificacionPago(array $payload): JsonResponse
  {
    EureFunctions::MP_Log('info', 'IPN recibido', $payload);

    $type = $payload['topic'] ?? null;
    if ($type !== 'payment') {
      return response()->json(['ignored' => true]);
    }

    $id = $payload['id'] ?? null;
    if (!$id) {
      return response()->json(['error' => 'ID de pago no recibido'], 400);
    }

    try {
      $payment = self::MP_ObtenerPagoPorId($id);
      EureFunctions::MP_Log('info', "Pago consultado ID {$id}", $payment);

      if (($payment['status'] ?? null) === 'approved') {
        return self::MP_ImputarPago($payment);
      }

      return response()->json(['ignored' => true, 'status' => $payment['status'] ?? null]);
    } catch (\Throwable $e) {
      if ((int)$e->getCode() === 404) {
        return response()->json(['status' => 'not_found'], 404);
      }

      EureFunctions::MP_Log('error', 'Error al procesar IPN', [
        'msg' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json(['error' => 'Error al procesar IPN'], 500);
    }
  }

  public static function MP_ObtenerPagoPorId($id): array
  {
    $http = Http::withHeaders([
      'Authorization' => 'Bearer ' . EureFunctions::MP_ObtenerAccessToken(),
      'Accept' => 'application/json',
    ]);

    if (app()->environment('local')) {
      $http = $http->withoutVerifying();
    }

    $response = $http->get("https://api.mercadopago.com/v1/payments/{$id}");

    if ($response->failed()) {
      if ($response->status() === 404) {
        EureFunctions::MP_Log('warning', "Pago no encontrado ID {$id}", $response->json());
        throw new \RuntimeException("Pago no encontrado ID {$id}", 404);
      }

      throw new \RuntimeException('Fallo al consultar pago: ' . $response->body(), $response->status());
    }

    return $response->json();
  }

  public static function MP_ImputarPago(array $payment): JsonResponse
  {
    try {
      $componentes = explode(';', $payment['external_reference'] ?? '');
      $codAlumno = isset($componentes[0]) ? (int)$componentes[0] : null;
      $fechaPago =
        Carbon::parse($payment['date_approved'] ?? now())->setTimezone('America/Argentina/Buenos_Aires')->format('Y-m-d H:i:s');

      DB::statement(
        'EXEC SP_WEB_CobroMercadoPago @codAlumno = ?, @fecha = ?, @Monto = ?, @Cadena = ?',
        [
          $codAlumno,
          $fechaPago,
          $payment['transaction_amount'],
          $payment['external_reference'],
          $payment['id'],
        ]
      );

      return response()->json([
        'message' => 'Pago imputado correctamente',
        'id' => $payment['id'] ?? null,
      ], 200);
    } catch (\Throwable $e) {
      EureFunctions::MP_Log('error', 'Error al imputar pago MercadoPago', [
        'msg' => $e->getMessage(),
        'payment' => $payment,
      ]);

      return response()->json([
        'message' => 'Error al imputar pago',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  public static function PTIC_ObtenerLinkIntencionPago(
    $accessToken,
    $importe,
    Alumno $alumno
  )
  {
    $urlPTIC_IP = 'https://api.paypertic.com/pagos';
    $external_transaction_id =
      implode(
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
      'concept_description' => '(' . $alumno->DNI . ') ' . $alumno->Apellido . ', ' . $alumno->Nombre,
      'amount' => $importe,
    ]];

    $ip_Payer =
      [
        'name' => $alumno->Nombre . ' ' . $alumno->Apellido,
        'email' => $email,
        'identification' => [
          'type' => 'DNI_ARG',
          'number' => $alumno->DNI,
          'country' => 'ARG',
        ],
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

    if (getenv('EURE_PAGOS_TIC_AMBIENTE') == 'DESARROLLO') {
      $HTTPPost = $HTTPPost->withOptions(['verify' => false]);
    }

    $response = $HTTPPost->post('https://api.paypertic.com/pagos', $pIntencionPago);

    if ($response->successful()) {
      return $response->json(); // ← Devuelve los datos como el link de pago
    }

    throw new \Exception('Error al crear intención de pago: ' . $response->status() . ' - ' . $response->body());
  }

  public static function PTIC_ImputarPago(
    int           $cod_alumno,
    Carbon|string $fecha,
    float         $importe,
    string        $external_transaction_id,
    string        $ptic_id
  ): JsonResponse
  {
    try {
      if (!$fecha instanceof Carbon) {
        $fecha = Carbon::parse($fecha);
      }

      DB::statement(
        'EXEC SP_WEB_CobroPagosTIC @codAlumno = ?, @fecha = ?, @Monto = ?, @Cadena = ?, @idPagosTic = ?',
        [
          $cod_alumno,
          $fecha->format('Y-m-d H:i:s'),
          $importe,
          $external_transaction_id,
          $ptic_id,
        ]
      );

      return response()->json([
        'message' => 'Pago imputado correctamente',
        'ptic_id' => $ptic_id,
      ], 200);

    } catch (\Exception $e) {
      EureFunctions::PTIC_PostLog('ERROR', json_encode([
        'nota' => 'en PTIC_ImputarPago',
        'cod_alumno' => $cod_alumno,
        'fecha' => $fecha,
        'importe' => $importe,
        'external_transaction_id' => $external_transaction_id,
        'ptic_id' => $ptic_id,
        'error' => $e->getMessage(),
      ]));

      return response()->json([
        'message' => 'Error al imputar pago',
        'error' => $e->getMessage(),
      ], 500); // Podés cambiar a 400 si preferís
    }
  }
}
