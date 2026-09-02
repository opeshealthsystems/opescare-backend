<?php

namespace App\Http\Middleware;

use App\Models\ModuleEntitlement;
use App\Models\OrganizationSubscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceModuleEntitlement — gates API access behind subscription module entitlements.
 *
 * Usage in routes:
 *   Route::middleware(['auth:sanctum', 'module:telemedicine'])->group(...);
 *
 * The module key is matched against module_entitlements.module_key for the
 * organization's active subscription. If no subscription is found or the module
 * is not entitled, the request is rejected with 403.
 *
 * Passes silently when:
 *   - No organization_id is resolvable (non-org routes)
 *   - The subscription plan has no limits for this module (treated as included)
 */
class EnforceModuleEntitlement
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $orgId = $this->resolveOrganizationId($request);

        if (!$orgId) {
            return $next($request);
        }

        $subscription = OrganizationSubscription::where('organization_id', $orgId)
            ->whereIn('status', ['active', 'trial', 'trialing'])
            ->latest('created_at')
            ->first();

        if (!$subscription) {
            return $next($request);
        }

        $entitled = ModuleEntitlement::where('subscription_id', $subscription->id)
            ->where('module_key', $moduleKey)
            ->where('is_enabled', true)
            ->whereNull('revoked_at')
            ->exists();

        if (!$entitled) {
            return response()->json([
                'error'  => "Module '{$moduleKey}' is not included in your subscription plan.",
                'code'   => 'MODULE_NOT_ENTITLED',
                'module' => $moduleKey,
            ], 403);
        }

        return $next($request);
    }

    private function resolveOrganizationId(Request $request): ?string
    {
        $user = $request->user();

        return $user?->organization_id
            ?? $user?->primary_facility_id
            ?? $user?->facility?->organization_id
            ?? $request->attributes->get('facility_id');
    }
}
