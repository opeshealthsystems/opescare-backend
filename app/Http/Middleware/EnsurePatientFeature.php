<?php

namespace App\Http\Middleware;

use App\Modules\Subscription\Services\PatientSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route behind a patient subscription feature.
 *
 *   Route::get(...)->middleware('patient.feature:teleconsult');
 *
 * Resolves the authenticated user's patient and checks hasFeature() — which also
 * honours family-sharing coverage for dependents. JSON callers get a 403 with an
 * upgrade message; web callers are redirected to the subscription page.
 */
class EnsurePatientFeature
{
    public function __construct(private readonly PatientSubscriptionService $subscriptions) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $patient = $request->user()?->patient;

        if ($patient && $this->subscriptions->hasFeature($patient, $feature)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => 'FEATURE_NOT_ENTITLED',
                'message'    => __('api.feature_requires_upgrade'),
            ], 403);
        }

        return redirect()
            ->route('portals.patient.subscription')
            ->with('info', __('flash.feature_requires_upgrade'));
    }
}
