<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComunicacionTipo extends Model
{
  use HasFactory;

  public $timestamps = false;

  protected $table = 'web_comunicaciones_tipos';


}
