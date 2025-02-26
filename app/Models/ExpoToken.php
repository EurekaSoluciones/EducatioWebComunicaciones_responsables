<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpoToken extends Model
{
  use HasFactory;

  protected $table = 'web_expo_tokens';

  protected $fillable = ['user_id', 'expo_push_token'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
