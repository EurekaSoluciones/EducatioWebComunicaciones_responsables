<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
  use HasFactory;

  protected $table = 'Turno';
  protected $primaryKey = 'Cod_Turno';
}
