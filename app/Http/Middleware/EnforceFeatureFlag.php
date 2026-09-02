<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * EnforceFeatureFlag — the V1 launch-scope kill switch. FAILS CLOSED.
 *
 * Two entry points, one behaviour:
 *
 *   1. Global, by URI pattern. Registered in bootstrap/app.php, which also
 *      declares the frozen URI surface via Features::freeze(). This is how
 *      frozen API modules are gated without editing routes/api.php — that file
 *      is SEALED (see apps/api-laravel/CLAUDE.md) and holds the insurance v1
 *      group. Gating by path keeps the seal intact.
 *
 *   2. Route middleware alias, `feature:<key>`, for anything that wants to opt
 *      in explicitly:  Route::middleware('feature:billing')->group(...)
 *
 * Returns 404, never 403. A module frozen out of the launch scope must not
 * advertise that it exists — 403 tells an enumerating client exactly which
 * modules to come back for. The NotFoundHttpException is thrown rather than
 * hand-rolled so it flows through the application's existing exception handler
 * and comes back byte-identical to a genuinely nonexistent route: the JSON
 * ENDPOINT_NOT_FOUND envelope on api/* and fhir/*, the standard 404 page on web.
 *
 * Contrast with EnforceModuleEntitlement ('module:<key>'), which fails OPEN and
 * cannot be used for this.
 *
 * @see \App\Support\Features
 * @see config/features.php
 */
class EnforceFeatureFlag
{
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        // Route-level use supplies the key. Global use resolves it from the
        // frozen URI map; null means "not a frozen module" — not our business.
        $feature ??= Features::featureForRequest($request);

        if ($feature === null || Features::enabled($feature)) {
            return $next($request);
        }

        throw new NotFoundHttpException();
    }
}
