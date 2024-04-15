<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioGrupo extends Model
{
  use HasFactory;

  protected $table = 'web_users_grupos';

  public function grupo()
  {
    return $this->belongsTo(Grupo::class, 'cod_grupo');
  }

  public function usuario()
  {
    return $this->belongsTo(User::class, 'cod_usuario', 'cod_usuario');
  }

  public function scopeDeGrupo($query, $grupo)
  {
    if ($grupo != null)
      $query->where('Cod_Grupo', $grupo->Cod_Grupo);
  }

}
