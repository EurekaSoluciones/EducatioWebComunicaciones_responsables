<?php

namespace App\EureLib;

use App\Models\Alumno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EducatioCommFunctions
{
  public static function CC_Obtener(Alumno $alumno, &$venceEsteMes, &$venceHoy, &$deudaVencida, &$proximoVencimiento)
  {
    $hoy = EureFunctions::hoy();
    $udm= EureFunctions::ultimoDiaMes();
    $hasta= EureFunctions::ultimoDiaMes()->addMonth();

    $ccItems= DB::select('exec SP_WEB_CTACTE @CodAlumno = ?, @FechaDesde = ?, @FechaHasta = ?', array($alumno->id, null, $hasta));

    $ccItems = array_map(function ($fila) {
      $fila->Fecha_Venc = EureFunctions::toCarbonDateFromYmd($fila->Fecha_Venc);
      $fila->Monto = (float)$fila->Monto;
      $fila->Saldo= (float)$fila->Saldo;

      return $fila;
    }, $ccItems);

//    dd($ccitems);

    // Vamos a tener que hacer fuerza bruta acá. Después agregar los intereses

    $venceEsteMes= 0.0;
    $venceHoy= 0;
    $deudaVencida= 0;
    $proximoVencimiento= Carbon::create(2050, 1, 1, 12, 0, 0);

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
          $deudaVencida+= $item->Saldo;

        if ($item->Fecha_Venc == $hoy)
          $venceHoy+= $item->Saldo;
      }
    }

    return $ccItems;

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

}
