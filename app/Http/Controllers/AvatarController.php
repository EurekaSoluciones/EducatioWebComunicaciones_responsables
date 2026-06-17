<?php

namespace App\Http\Controllers;

use App\EureLib\InitialAvatar;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
  public function initials(Request $request)
  {
    $name = (string) $request->query('name', '');
    $size = (int) $request->query('size', 512);

    return response(InitialAvatar::svg($name, $size), 200)
      ->header('Content-Type', 'image/svg+xml')
      ->header('Cache-Control', 'public, max-age=31536000, immutable');
  }
}
