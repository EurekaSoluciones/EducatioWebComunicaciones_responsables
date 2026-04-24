<?php

namespace App\Models;

use App\EureLib\EureFunctions;
use App\EureLib\StaticArrays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsable extends Model
{
  use HasFactory;

  protected $table = 'Responsables';
  protected $primaryKey = 'Cod_Responsable';

  public function getIdAttribute()
  {
    return $this->attributes['Cod_Responsable'];
  }

  public function getnombreYApellidoAttribute()
  {
    return $this->Nombre . ' ' . $this->Apellido;
  }

  public function getapellidoComaNombreAttribute()
  {
    return $this->Apellido . ', ' . $this->Nombre;
  }


  public function getCardAttribute()
  {
    return StaticArrays::$tiposCards[$this->id % count(StaticArrays::$tiposCards)];
  }


  public function alumnosPorR1()
  {
  //  return $this->hasMany(Alumno::class, 'Responsable1');
    return $this->hasMany(Alumno::class, 'Responsable1')->where('fecha_baja', null);
  }

  public function alumnosPorR2()
  {
  //  return $this->hasMany(Alumno::class, 'Responsable2');
    return $this->hasMany(Alumno::class, 'Responsable2')->where('fecha_baja', null);
  }

  public function alumnos()
  {
    return $this->alumnosPorR1()->union($this->alumnosPorR2()->toBase());

    //return $this->hasManyWithKeys(Alumno::class, ['Responsable1', 'Responsable2']);

    //return $this->alumnosPorR1->merge($this->alumnosPorR2);
  }

  public function web_user()
  {
    // Pregunto y si no hay entrada en web_alumnos la creo

    $web_user = User::where('Cod_Responsable', $this->id)
      ->where('tipo', 'Responsable')
      ->first();

    // Doble acceso a la DB NO ME GUSTA
    if ($web_user == null) {
      // No se esto de crearlo pero podria ser
      EureFunctions::crearUsuarioResponsable
      (
        $this->usuarioAutoInscripcion,
        $this->Nombre,
        $this->Apellido,
        '',
        $this->id,
        $this->passwordAutoInscripcion
      );
    }

    return $web_user;
  }

  public function getwebuserAttribute()
  {
    return $this->web_user();
  }

  public function comunicacionesDest()
  {
    return $this->hasMany(ComunicacionDestinatario::class, 'Cod_Responsable')->orderByDesc('id');
  }


  public function comunicaciones()
  {
    return $this->obtenerComunicaciones(false);
  }

  public function comunicacionesSinLeer()
  {
    return $this->obtenerComunicaciones(true);
  }


  public function obtenerComunicaciones($soloSinLeer, Alumno $alumno = null)
  {
    // https://stackoverflow.com/questions/60411513/why-hasmanythrough-from-eloquent-documentation-not-work

    $ComRaw= $this->hasManyThrough(Comunicacion::class, ComunicacionDestinatario::class,
      'Cod_Responsable', 'id', 'id', 'comunicacion_id');

    //   'Cod_Responsable', 'id', 'Cod_Responsable', 'comunicacion_id');
    if ($soloSinLeer)
      $ComRaw= $ComRaw->whereNull('fhLeido');

    if ($alumno != null)
      $ComRaw= $ComRaw->where('Cod_Alumno', '=', $alumno->id);

//     aca queda todo planteado. Evidentemente hasta el return no se ejecuta el query

    return $ComRaw->orderBy('web_comunicaciones.id', 'desc');;
  }






}
