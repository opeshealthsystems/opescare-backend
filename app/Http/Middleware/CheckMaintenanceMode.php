<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceWindow;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckMaintenanceMode
 *
 * Enforces active maintenance windows against all incoming HTTP requests.
 *
 * ## What it does
 * - Loads the current active MaintenanceWindow from cache (5-min TTL).
 * - If a window is active and time-in-range → returns 503 for APIs (JSON)
 *   or renders the maintenance view for web routes.
 * - Auto-expires windows whose `ends_at` has passed (no cron dependency).
 *
 * ## Bypass mechanisms (in priority order)
 *
 *  1. Always-allowed paths — health endpoints, and the Admin Control Center
 *     maintenance management pages (/portals/admin/cc/maintenance*) so admins
 *     can always reach the toggle to deactivate a window.
 *
 *  2. `MAINTENANCE_BYPASS_TOKEN` env var + `X-Maintenance-Bypass` header
 *     — for load-balancer health checks and deployment scripts.
 *
 *  3. Laravel's native `php artisan down --secret=<token>` cookie.
 *
 *  4. Bypass IPs — comma-separated list in `MAINTENANCE_BYPASS_IPS`.
 *
 *  5. Emergency-access paths — when `allow_emergency_access = true` on the
 *     active window, URL paths containing "emergency" are let through.
 *     Uses URL-path matching (NOT routeIs) because this middleware runs in
 *     the global stack BEFORE routes are matched.
 *     Satisfies Cameroon Law No. 2010/012.
 *
 * ## Cache
 * Active window data is cached as a plain array (not an Eloquent model) under
 * `maintenance:active_window` for 5 minutes. Storing a plain array prevents
 * deserialization failures after deployments that move or rename the model.
 * PlatformAdminService flushes this key immediately on any toggle.
 *
 * ## Production notes
 * - NEVER use $request->routeIs() in this middleware — routes are not matched
 *   yet when global middleware runs. Use $request->is() (URL path matching).
 * - All time comparisons use now() which respects APP_TIMEZONE.
 * - Retry-After header is always an integer (seconds).
 */
class CheckMaintenanceMode
{
    public const CACHE_KEY = 'maintenance:active_window';
    public const CACHE_TTL = 300; // 5 minutes

    /**
     * Paths that are ALWAYS allowed through, even during maintenance.
     * Patterns use $request->is() glob syntax (no leading slash needed).
     */
    private const ALWAYS_ALLOWED = [
        'up',                               // Laravel built-in health check
        'api/health',                       // OpesCare health endpoint
        'api/v1/health',
        'status',                           // Public status page — must be reachable DURING an outage
        'sla',                              // Public SLA page
        '.well-known/*',                    // OAuth/JWKS discovery (machine clients verifying tokens)
        'portals/admin/cc/maintenance',     // Admin toggle page (GET)
        'portals/admin/cc/maintenance/*',   // Admin toggle actions (POST)
        'portals/admin/cc',                 // Admin control centre dashboard
        'portals/admin/cc/health',          // System health page (to diagnose issues)
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // ── Always-allowed paths ────────────────────────────────────────────
        if ($request->is(self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        // ── Bypass token (header) ───────────────────────────────────────────
        $bypassToken = config('app.maintenance_bypass_token');
        if ($bypassToken && hash_equals($bypassToken, (string) $request->header('X-Maintenance-Bypass', ''))) {
            return $next($request);
        }

        // ── Laravel artisan down --secret cookie ────────────────────────────
        if ($this->hasLaravelBypassCookie($request)) {
            return $next($request);
        }

        // ── Bypass IP list ──────────────────────────────────────────────────
        if ($this->isBypassIp($request->ip())) {
            return $next($request);
        }

        // ── Load active window (cached as array) ────────────────────────────
        $windowData = $this->activeWindowData();

        if ($windowData === null) {
            return $next($request);
        }

        // ── Emergency-access bypass ─────────────────────────────────────────
        // NOTE: $request->routeIs() MUST NOT be used here — routes are not
        // matched yet in the global middleware stack. Use $request->is() instead.
        if ($windowData['allow_emergency_access'] && $request->is('*emergency*', '*urgent*')) {
            return $next($request);
        }

        // ── Return 503 ──────────────────────────────────────────────────────
        return $this->serviceUnavailable($request, $windowData);
    }

    // ── Cache helpers ─────────────────────────────────────────────────────

    /**
     * Load the currently active maintenance window as a plain array.
     *
     * Returns null when no window is active. Caches for CACHE_TTL seconds.
     *
     * Storing as an array (not Eloquent model) prevents deserialization
     * failures when the model class is moved or renamed between deployments.
     * A try/catch guards against stale/corrupt cache entries — on any error
     * the cache is flushed and the DB is queried fresh.
     */
    public static function activeWindowData(): ?array
    {
        try {
            $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $window = MaintenanceWindow::where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                    })
                    ->orderByDesc('starts_at')
                    ->first();

                if (! $window) {
                    return null;
                }

                // Serialise to a plain array so cache is deployment-safe
                return [
                    'id'                     => $window->id,
                    'title'                  => $window->title,
                    'message'                => $window->message,
                    'starts_at'              => $window->starts_at?->toIso8601String(),
                    'ends_at'                => $window->ends_at?->toIso8601String(),
                    'is_active'              => $window->is_active,
                    'allow_emergency_access' => $window->allow_emergency_access,
                ];
            });
        } catch (\Throwable $e) {
            // Corrupt / stale cache entry — flush and return null (safe-fail open)
            Log::warning('CheckMaintenanceMode: cache read failed, flushing', [
                'error' => $e->getMessage(),
            ]);
            Cache::forget(self::CACHE_KEY);
            return null;
        }

        // Guard: cache may contain a stale non-array value after a deployment
        // that changes the stored structure. Treat any non-array as corrupt.
        if (! is_array($data)) {
            Cache::forget(self::CACHE_KEY);
            return null;
        }

        if ($data === null) {
            return null;
        }

        // Auto-expire: if ends_at has now passed, bust cache and mark inactive
        if (! empty($data['ends_at'])) {
            $endsAt = \Carbon\Carbon::parse($data['ends_at']);
            if ($endsAt->isPast()) {
                Cache::forget(self::CACHE_KEY);
                MaintenanceWindow::where('id', $data['id'])->update(['is_active' => false]);
                Log::info('CheckMaintenanceMode: window auto-expired', [
                    'id'    => $data['id'],
                    'title' => $data['title'],
                ]);
                return null;
            }
        }

        return $data;
    }

    /**
     * Flush the cache immediately — must be called whenever a window is
     * toggled, created, or deleted via PlatformAdminService.
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function hasLaravelBypassCookie(Request $request): bool
    {
        $secret = config('app.maintenance_secret');
        if (! $secret) {
            return false;
        }

        $cookie = $request->cookie('laravel_maintenance');
        return $cookie && hash_equals(hash('sha256', $secret), (string) $cookie);
    }

    private function isBypassIp(string $ip): bool
    {
        $list = config('app.maintenance_bypass_ips', '');
        if (empty($list)) {
            return false;
        }

        $allowed = array_map('trim', explode(',', $list));
        return in_array($ip, $allowed, true);
    }

    private function serviceUnavailable(Request $request, array $window): Response
    {
        $isApi = $request->is('api/*')
            || $request->is('fhir/*')
            || $request->expectsJson();

        // Retry-After must be an integer (RFC 7231)
        $retryAfter = 3600;
        if (! empty($window['ends_at'])) {
            $retryAfter = (int) max(0, now()->diffInSeconds(\Carbon\Carbon::parse($window['ends_at'])));
        }

        $headers = [
            'Retry-After'   => (string) $retryAfter,
            'Cache-Control' => 'no-store, no-cache',
        ];

        if ($isApi) {
            return response()->json([
                'status'      => 'maintenance',
                'error_code'  => 'SERVICE_UNAVAILABLE',
                'message'     => $window['message']
                    ?? 'OpesCare is currently undergoing scheduled maintenance. Please try again shortly.',
                'title'       => $window['title'],
                'starts_at'   => $window['starts_at'],
                'ends_at'     => $window['ends_at'],
                'retry_after' => $retryAfter,
            ], 503, $headers);
        }

        // Web route — render branded maintenance page
        return response()
            ->view('errors.maintenance', [
                'window'     => (object) [
                    'title'                  => $window['title'],
                    'message'                => $window['message'],
                    'starts_at'              => $window['starts_at'] ? \Carbon\Carbon::parse($window['starts_at']) : null,
                    'ends_at'                => $window['ends_at']   ? \Carbon\Carbon::parse($window['ends_at'])   : null,
                    'allow_emergency_access' => $window['allow_emergency_access'],
                ],
                'retryAfter' => $retryAfter,
            ], 503)
            ->withHeaders($headers);
    }
}
