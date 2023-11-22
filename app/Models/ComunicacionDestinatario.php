<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ComunicacionDestinatario extends Model
{
    use HasFactory;

    protected $table = "web_comunicaciones_destinatarios";


  protected $casts = [
    'fhLeido' => 'datetime',

  ];

    public function getcarbonFHLeidoAttribute()
    {
      return $this->fhLeido;
    }

  public function comunicacion()
  {
    return $this->belongsTo(Comunicacion::class, 'comunicacion_id');
  }

  public function responsable()
  {
    return $this->belongsTo(Responsable::class, 'Cod_Responsable');
  }

}
