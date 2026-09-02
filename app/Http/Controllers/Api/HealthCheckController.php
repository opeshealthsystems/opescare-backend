<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];

        // Database check
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
        }

        // Cache check
        try {
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, true, 5);
            $checks['cache'] = Cache::get($key) ? 'ok' : 'error';
            Cache::forget($key);
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
        }

        $allOk  = !in_array('error', $checks);
        $status = $allOk ? 200 : 503;

        return response()->json([
            'status'    => $allOk ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'checks'    => $checks,
        ], $status);
    }
}
