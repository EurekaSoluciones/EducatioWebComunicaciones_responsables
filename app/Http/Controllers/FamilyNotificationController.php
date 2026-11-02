<?php

namespace App\Http\Controllers;

use App\Support\FamilyPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyNotificationController extends Controller
{
    public function __invoke(Request $request, FamilyPortal $portal): JsonResponse
    {
        return response()->json($portal->unread($portal->responsable($request->user())))
            ->header('Cache-Control', 'private, no-store');
    }
}
