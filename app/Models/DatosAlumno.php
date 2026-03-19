<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosAlumno extends Model
{
    use HasFactory;

    protected $table = 'WEB_DatosAlumnos';

    protected $primaryKey = 'Cod_Alumno';
    
}
