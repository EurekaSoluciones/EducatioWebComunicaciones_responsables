<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MateriaProfeGrupo extends Model
{
  use HasFactory;

  protected $table = 'Materia-Profe-Grupo';

  protected $primaryKey = 'Cod_MPG';

  public function grupo()
  {
    return $this->belongsTo(Grupo::class, 'Cod_Grupo');
  }

  public function materia()
  {
    return $this->belongsTo(Materia::class, 'Cod_Materia');
  }



  public static function devolverMPGsDeProfesorYAL(Profe $profe, int $al)
  {
    return MateriaProfeGrupo::where('Cod_Profesor', $profe->id)
      ->whereHas('Grupo', function ($query) use ($al) {
        $query->where('año_lectivo', $al);
      })
      ->get();
  }

}
