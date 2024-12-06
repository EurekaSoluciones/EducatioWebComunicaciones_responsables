<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Comunicacion extends Model
{
  use HasFactory;

  protected $table = "web_comunicaciones";

  protected $dates = [
    'created_at',
    'updated_at',
  ];

  // Accesor para formatear el ID con ceros a la izquierda
  public function getFormattedIdAttribute()
  {
    $longitud_deseada = 5;
    return str_pad($this->attributes['id'], $longitud_deseada, '0', STR_PAD_LEFT);
  }

  public function getetiquetasAAttribute()
  {
    return explode(";", $this->etiquetas);
  }

  public function leido(Responsable $responsable)
  {
    return \App\EureLib\EureFunctions::comunicacionPendienteDeLectura($this, $responsable);
  }

  public function remitente()
  {
    return $this->belongsTo(User::class, 'usuario_id');
  }

  public function tipo()
  {
    return $this->belongsTo(ComunicacionTipo::class, 'tipo_id');
  }

  public function grupo()
  {
    return $this->belongsTo(Grupo::class, 'Cod_Grupo');
  }

  public function tipo_respuesta()
  {
    return $this->belongsTo(RespuestaTipo::class, 'tipo_respuesta_id');
  }

  public function destinatarios()
  {
    $dest =
      $this->hasManyThrough(Alumno::class, ComunicacionDestinatario::class,
        'comunicacion_id', 'Cod_Alumno', 'id', 'Cod_Alumno');

    return $dest->distinct();
  }

  public function comunicacionDestinatarios()
  {
    return
      $this
        ->hasMany(ComunicacionDestinatario::class, 'comunicacion_id')
        ->orderBy('Cod_Responsable');
  }


  public function adjuntos()
  {
    return $this->hasMany(Adjunto::class, 'entityId')
      ->where('entity', '=', 'comunicacion')
      ->orderBy('id');
  }

  public function getcustomCardAttribute()
  {
    if ($this->grupo != null)
      return $this->grupo->card;

    return $this->remitente->card;
  }

  public function getgruposSeleccionAttribute()
  {
    $totalGrupos= $this->cursos . $this->divisiones . $this->turnos;

    return explode(';', $totalGrupos);
  }


  public function scopeAlumno($query, $alumno)
  {
    if ($alumno != "")
      $query
        ->whereIn('id', function ($subquery) use ($alumno) {
          $subquery->from('web_comunicaciones_destinatarios')
            ->select('comunicacion_id')
            ->where('Cod_Alumno', '=', $alumno->id);

        });
  }

  public function scopeDesde($query, $desde)
  {
    if ($desde != null)
      $query->where('created_at', '>=', Carbon::createFromFormat('d/m/Y', $desde)->startOfDay());
  }

  public function scopeHasta($query, $hasta)
  {
    if ($hasta != null)
      $query->where('created_at', '<', Carbon::createFromFormat('d/m/Y', $hasta)->addDay()->startOfDay());
  }

  public function scopeNoLeidos($query, $soloNoLeidos, $responsable)
  {
    if ($soloNoLeidos != null)
      $query
        ->whereIn('id', function ($subquery) use ($responsable) {
          $subquery->from('web_comunicaciones_destinatarios')
            ->select('comunicacion_id')
            ->where('Cod_Responsable', '=', $responsable->id)
            ->where('leido', '0');
        });
  }

  public function scopeNoLeidosPorAlumno($query, $soloNoLeidos, $responsable, $alumno)
  {
    if ($soloNoLeidos != null)
      $query
        ->whereIn('id', function ($subquery) use ($responsable, $alumno) {
          $subquery->from('web_comunicaciones_destinatarios')
            ->select('comunicacion_id')
            ->where('Cod_Responsable', '=', $responsable->id)
            ->where('Cod_Alumno', '=', $alumno->id)
            ->where('leido', '0');
        });
  }

  public function scopeRemitente($query, $remitenteId)
  {
    if ($remitenteId != null)
      $query->where('usuario_id', '=', $remitenteId);
  }


  public function scopeNroVueloDummy($query, $remitenteId)
  {
      if ($remitenteId != null)
        $query->where('usuario_id', '=', $remitenteId);
  }


}
