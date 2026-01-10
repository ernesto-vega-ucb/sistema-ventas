<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        $latencyMs = null;

        try {
            $t0 = microtime(true);
            DB::select('select 1');
            $latencyMs = (int) ((microtime(true) - $t0) * 1000);
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'app' => [
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
            ],
            'db' => [
                'ok' => $dbOk,
                'latency_ms' => $latencyMs,
                'driver' => config('database.default'),
            ],
        ], $dbOk ? 200 : 503);
    }
}
