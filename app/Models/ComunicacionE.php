<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComunicacionE extends Model
{
    use HasFactory;

    protected $table = 'web_comunicaciones_entrantes';

    protected $casts = [
        'created_at' => 'datetime',
        'fhLeido' => 'datetime',
        'fhRespuesta' => 'datetime',
    ];

    public function getFormattedIdAttribute()
    {
        $longitud_deseada = 5;

        return str_pad($this->attributes['id'], $longitud_deseada, '0', STR_PAD_LEFT);
    }

    public function getcustomCardAttribute()
    {
        return $this->responsable->card;
    }

    public function getrespondidaAttribute()
    {
        return $this->fhRespuesta != null;
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'Cod_Alumno', 'Cod_Alumno');
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class, 'Cod_Responsable', 'Cod_Responsable');
    }

    public function tipo()
    {
        return $this->belongsTo(ComunicacionTipo::class, 'tipo_id');
    }

    public function adjuntos()
    {
        return $this->hasMany(Adjunto::class, 'entityId')
            ->where('entity', '=', 'comunicacione')
            ->orderBy('id');
    }

    public function destinatario_web_user()
    {
        if ($this->tipo == 'Secretaria') {
            $web_user = $this->belongsTo(User::class, 'Cod_Usuario', 'cod_usuario');
        } else {
            $web_user = $this->belongsTo(User::class, 'Cod_Usuario', 'Cod_Profesor');
        }

        return $web_user;
    }

    public function scopeDeAlumno($query, $alumno)
    {
        if ($alumno != null) {
            $query->where('Cod_Alumno', $alumno->Cod_Alumno);
        }
    }

    public function scopeDeResponsable($query, $responsable)
    {
        if ($responsable != null) {
            $query->where('Cod_Responsable', $responsable->Cod_Responsable);
        }
    }

    public function scopeRespuestaSinLeer($query)
    {
        $query
            ->whereNotNull('fhRespuesta')
            ->whereNull('fhRespuestaLeida');
    }
}
