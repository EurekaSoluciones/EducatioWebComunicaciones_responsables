<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Profe extends Model
{
  use HasFactory;

  use HasFactory;

  protected $table = 'Profesor';
  protected $primaryKey = 'Cod_Profesor';
  protected $keyType = 'int';


  public function getnombreYApellidoAttribute()
  {
    return $this->Nombre . ' ' . $this->Apellido;
  }

  public function getIdAttribute()
  {
    return $this->attributes['Cod_Profesor'];
  }

  public function grupos()
  {
    return $this->MPGs->pluck('Grupo', 'Cod_Grupo');
  }

  public function web_user()
  {
    // Pregunto y si no hay entrada en web_alumnos la creo

    $web_user = User::where('Cod_Profesor', $this->id)
      ->where('tipo', 'Profe')
      ->first();


    // Doble acceso a la DB NO ME GUSTA
    if ($web_user == null) {
      dd("preofe nulll");
      // No se esto de crearlo pero podria ser
//      $alumnoWeb = new AlumnoWeb();
//
//      $alumnoWeb->Cod_Alumno = $this->id;
//      $alumnoWeb->avatarImg = '';
//      $alumnoWeb->bgImg = '';
//
//      $alumnoWeb->save();
    }

    return $web_user;
  }


  public function MPGs()
  {
    $al = env('EURE_AL');

    return $this->hasMany(MateriaProfeGrupo::class, 'Cod_Profesor')
      ->whereHas('Grupo', function ($query) use ($al) {
        $query->where('año_lectivo', $al);
      });
  }

  public static function DRDCAA($alumno)
  {
    $alumnoId = 2254;

    $profesores = DB::table('Profesor AS P')
      ->join('web_users AS WU', 'P.Cod_Profesor', '=', 'WU.Cod_Profesor')
      ->whereIn('WU.id', function ($query) use ($alumnoId) {
        $query->select('C.usuario_id')
          ->from('web_comunicaciones AS C')
          ->whereIn('C.id', function ($query) use ($alumnoId) {
            $query->select('CD.comunicacion_id')
              ->from('web_comunicaciones_destinatarios AS CD')
              ->where('Cod_Alumno', $alumnoId);
          });
      })
      ->get();

    return $profesores;
  }

  public function scopeEsRemitenteDeComunicacionA($query, $alumno)
  {
    if ($alumno != "")
      $query
        ->join('web_users AS WU', 'Profesor.Cod_Profesor', '=', 'WU.Cod_Profesor')
        ->whereIn('WU.id', function ($query) use ($alumno) {
          $query->select('C.usuario_id')
            ->from('web_comunicaciones AS C')
            ->whereIn('C.id', function ($query) use ($alumno) {
              $query->select('CD.comunicacion_id')
                ->from('web_comunicaciones_destinatarios AS CD')
                ->where('Cod_Alumno', $alumno->id);
            });
        });
  }
}
