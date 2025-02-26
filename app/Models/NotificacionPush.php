<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class NotificacionPush extends Model
{
    use HasFactory;

    protected $table = 'web_notificaciones_push';


  public function scopeDeResponsable($query, $responsable)
  {
    if ($responsable != null)
      $query
        ->where('Cod_Responsable', $responsable->Cod_Responsable);
  }

  public function scopeProcesado($query)
  {
    $query->whereNotNull('fhProcesado');
  }

  public function scopeNoDescartado($query)
  {
    $query->where('Estado', '!=', 'DESCARTADO');
  }

  public function scopeSinMostrar($query)
  {
    $query
      ->whereNull('fhMostrado');
  }

  public function scopeMostrados($query)
  {
    $query
      ->whereNotNull('fhMostrado');
  }

  public function scopeDesde($query, $fecha)
  {
    if ($fecha != null)
      $query
        ->where('created_at', '>=', $fecha);
  }

  public function scopeUltimos60Dias($query)
  {
      $query
        ->where('created_at', '>=',  Carbon::now()->subDays(60));
  }

  public function scopeUltimos30Dias($query)
  {
    $query
      ->where('created_at', '>=',  Carbon::now()->subDays(30));
  }
}
