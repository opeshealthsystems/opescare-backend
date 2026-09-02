<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleByClient
{
    public function __construct(private RateLimiter $limiter) {}

    /**
     * Rate-limit by integration client ID or SDK token client.
     *
     * Usage:   @middleware('throttle.client:60,1')  → 60 req/min
     * Default: 120 requests per minute per client.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 120, int $decayMinutes = 1): Response
    {
        // Resolve the client identifier from either auth mechanism
        $clientId = $request->attributes->get('integration_client_id')
            ?? ($request->attributes->get('integration_client')?->id)
            ?? $request->attributes->get('sdk_client_id')
            ?? $request->ip();

        $key = 'client_throttle:' . $clientId . ':' . floor(time() / ($decayMinutes * 60));

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'error'   => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please retry after ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After'           => $retryAfter,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);
        $remaining = max(0, $maxAttempts - $this->limiter->attempts($key));

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}
