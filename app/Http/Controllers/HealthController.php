<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function __invoke()
    {
        try {
            \DB::connection()->getPdo();

            return response()->json(['status' => 'ok', 'database' => 'connected']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }
}
