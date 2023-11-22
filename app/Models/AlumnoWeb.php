<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumnoWeb extends Model
{
    use HasFactory;

    protected $table = 'web_alumnos';

  protected $primaryKey = 'Cod_Alumno';


}
