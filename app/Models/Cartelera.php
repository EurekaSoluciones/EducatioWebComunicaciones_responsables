<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cartelera extends Model
{
  use HasFactory;

  protected $table = 'web_carteleras';

  protected $guarded = [];

  public function updated_by_user()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function adjuntos()
  {
    return $this->hasMany(Adjunto::class, 'entityId')
      ->where('entity', '=', 'cartelera')
      ->orderBy('id');
  }

}
