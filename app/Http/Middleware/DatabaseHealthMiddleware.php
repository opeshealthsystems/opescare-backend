<?php

namespace App\Http\Middleware;

use App\Services\Infrastructure\RegionHealthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DatabaseHealthMiddleware
{
    public function __construct(
        private readonly RegionHealthService $regionHealth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->regionHealth->isDatabaseHealthy()) {
            Log::critical('Database unavailable — blocking request', [
                'region' => config('regions.current_region'),
                'path'   => $request->path(),
                'ip'     => $request->ip(),
            ]);

            $this->regionHealth->alertFailover('database');

            return response()->json([
                'error'       => 'Service temporarily unavailable',
                'retry_after' => config('regions.health_check_ttl', 30),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
