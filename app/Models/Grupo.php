<?php

namespace App\Models;

use App\EureLib\StaticArrays;
use App\Http\Controllers\AdminGeneralController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
  use HasFactory;

  protected $table = 'Grupo';

  protected $primaryKey = 'Cod_Grupo';

  public function getIdAttribute()
  {
    return $this->attributes['Cod_Grupo'];
  }

  public function getdescripcionAttribute()
  {
    return
      'Curso: ' . $this->ECurso->Descripcion . ' ' .
      'División: ' . $this->EDivision->Descripcion . ' ' .
      'Turno: ' . $this->ETurno->Descripcion  . ' ' .
      'Plan: ' . $this->EPlan->Descripcion;
  }

  // ECurso = "entidad Curso". Por el campo se join se llama igual que la entidad
  public function ECurso()
  {
    return $this->belongsTo(Curso::class, 'Curso');
  }

  public function EDivision()
  {
    return $this->belongsTo(Division::class, 'Division');
  }

  public function ETurno()
  {
    return $this->belongsTo(Turno::class, 'Turno');
  }

  public function EPlan()
  {
    return $this->belongsTo(Plan::class, 'Cod_Plan');
  }

  public function alumnos()
  {
    return $this->hasMany(Alumno::class, 'Cod_Grupo');
  }

  public function profes()
  {
    // return Profe::where
  }

  public function MPG(){
    return $this->hasMany(MateriaProfeGrupo::class, 'Cod_Grupo');
  }
  
  public static function DevolverGruposDeProfesor(Profe $profe, $al)
  {
    return Grupo::where('año_lectivo', $al)->get();
  }

  // Estetico
  public function getCardAttribute()
  {
    return
      StaticArrays::$tiposCards[$this->id % count(StaticArrays::$tiposCards)];
  }


}
