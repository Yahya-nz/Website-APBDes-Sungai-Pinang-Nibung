<?php

namespace App\Http\Controllers;

use App\Support\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(NotificationFeed::for($request->user()))->header('Cache-Control', 'no-store');
    }
}
