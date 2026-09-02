<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Features;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FrozenModulePlaceholderController — a class that exists ONLY so the route
 * table can be built. It implements nothing.
 *
 * ── Why this file exists ──────────────────────────────────────────────────
 *
 * Three modules were frozen out of V1 (see config/features.php and the URI
 * freeze map in bootstrap/app.php) and their controllers were DELETED. Their
 * route groups were not, and could not be: routes/api.php is SEALED
 * (apps/api-laravel/CLAUDE.md) and must not be edited to remove them.
 *
 * At request time that was harmless — EnforceFeatureFlag is global middleware,
 * so a frozen URI 404s before routing ever resolves a controller. But
 * `php artisan route:list` reflects on every action's class to print its file,
 * and a missing class made that command fatal for the WHOLE application:
 *
 *     ReflectionException: Class "App\Http\Controllers\Api\V1\BillingController"
 *     does not exist
 *
 * route:list is one of the four "verify before calling it done" checks in the
 * project brief, so a frozen module took the entire team's route-inspection
 * tooling down with it. The fix is to supply what the sealed file references,
 * not to edit the sealed file.
 *
 * ── What a subclass must NOT do ───────────────────────────────────────────
 *
 * It must not look like an implementation. A stub that answers 200 with an
 * empty list, or {"status":"synced"} while storing nothing, is worse than the
 * missing class was: the ReflectionException at least told the truth. Every
 * action routes to unavailable() and nothing else.
 *
 * ── The two answers ───────────────────────────────────────────────────────
 *
 *   404 — the module is frozen (today, always). Byte-identical to the
 *         ENDPOINT_NOT_FOUND envelope a route that never existed returns,
 *         because the NotFoundHttpException is thrown rather than hand-rolled
 *         and flows through the same exception handler. A frozen module must
 *         not advertise itself; 403 or 501 would tell an enumerating client
 *         exactly which modules to come back for.
 *
 *         This is a SECOND, independent gate. The global EnforceFeatureFlag
 *         already 404s these URIs, so in practice no request reaches here at
 *         all. Re-checking means the placeholder can never become an
 *         accidentally reachable surface if a pattern is dropped from the map
 *         in bootstrap/app.php.
 *
 *   501 — the flag was flipped ON without an implementation being shipped.
 *         An explicit, loud failure plus a warning log. Unreachable today; it
 *         exists so that "unfroze the flag, forgot the code" is a diagnosable
 *         event rather than a silent empty success.
 *
 * The 501 body is deliberately NOT run through __(). It is a developer
 * diagnostic for a state that cannot occur in a correctly configured
 * deployment, and seeding lang/en + lang/fr with keys for a branch that never
 * renders would put permanently dead strings into both catalogues.
 *
 * DELETE THIS HIERARCHY, don't extend it, when a module is genuinely built:
 * replace the subclass with the real controller.
 *
 * @see \App\Http\Middleware\EnforceFeatureFlag
 * @see \App\Support\Features
 * @see config/features.php
 * @see \Tests\Feature\Architecture\RouteTableIntegrityTest
 */
abstract class FrozenModulePlaceholderController extends Controller
{
    /**
     * The config/features.php flag key this module is frozen behind.
     */
    abstract protected function featureKey(): string;

    /**
     * Human-readable module name, for the 501 diagnostic only.
     */
    abstract protected function moduleLabel(): string;

    /**
     * The only behaviour any action in this hierarchy has.
     *
     * @param  string  $action  the calling method, for the diagnostic log
     */
    final protected function unavailable(string $action): JsonResponse
    {
        $feature = $this->featureKey();

        // Frozen: indistinguishable from a route that was never registered.
        if (Features::frozen($feature)) {
            throw new NotFoundHttpException();
        }

        // Reachable only if someone enabled the flag without shipping the code.
        Log::warning('Frozen module placeholder reached with its feature flag ON.', [
            'feature' => $feature,
            'module'  => $this->moduleLabel(),
            'action'  => static::class . '::' . $action,
        ]);

        return response()->json([
            'status'     => 'error',
            'error_code' => 'NOT_IMPLEMENTED',
            'message'    => sprintf(
                'The %s module is not implemented. Feature flag "%s" is enabled but no '
                . 'implementation is deployed behind it — this endpoint is a placeholder '
                . 'that exists only to satisfy the sealed route file. Turn the flag back off.',
                $this->moduleLabel(),
                $feature
            ),
            'feature'    => $feature,
        ], 501);
    }
}
