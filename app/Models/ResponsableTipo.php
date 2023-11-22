<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableTipo extends Model
{
  use HasFactory;

  protected $table = 'Tipo-Responsable';

  protected $primaryKey = 'Cod_Tipo_Responsable';

}
