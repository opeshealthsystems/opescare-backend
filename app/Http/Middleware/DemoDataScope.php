<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DemoDataScope
 *
 * For authenticated demo users (is_demo = true), activates the application-wide
 * demo mode flag so the IsDemoRecord global scope on all models restricts
 * queries to is_demo = true records only.
 *
 * This runs per-request in standard PHP-FPM (one request per process), so
 * toggling the runtime config is safe. If the application ever moves to
 * Octane/Swoole, replace this with a request-scoped context object.
 *
 * This middleware is registered globally in bootstrap/app.php so it runs
 * on every web + API request after authentication resolves.
 */
class DemoDataScope
{
    public function handle(Request $request, Closure $next)
    {
        // OCTANE/SWOOLE INCOMPATIBILITY WARNING:
        // This middleware sets a global config value that persists across requests in long-running
        // Octane/Swoole workers, which WILL cause demo data to leak into non-demo requests.
        // DO NOT use Octane with demo mode enabled.
        if (config('octane.server') !== null && config('demo.enabled', false)) {
            \Illuminate\Support\Facades\Log::critical('demo_data_scope_octane_conflict', [
                'message' => 'DemoDataScope middleware is incompatible with Octane. Aborting demo session.',
            ]);
            abort(503, 'Demo mode is not supported in this server configuration.');
        }

        if (Auth::check() && Auth::user()->is_demo) {
            // Force demo isolation: IsDemoRecord global scope will restrict
            // all model queries to is_demo = true rows.
            config(['demo.enabled' => true]);
        }

        return $next($request);
    }
}
