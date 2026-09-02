<?php

namespace App\Http\Middleware;

use App\Models\ApiPlan;
use App\Models\ApiUsageLog;
use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceApiQuota — monthly request-quota enforcement for metered API plans.
 *
 * Runs AFTER the auth middleware that resolves the integration client (so
 * `integration_client_id` is present in request attributes). Complements the
 * per-minute ThrottleByClient limiter with a monthly quota tied to the client's
 * ApiPlan. Safe by design: a no-op unless the client is on a plan with a finite
 * quota AND has exceeded it. The testing bypass client is never metered.
 */
class EnforceApiQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->attributes->get('integration_client_id');

        if (! $clientId || $clientId === 'test_client_id') {
            return $next($request);
        }

        // Dunning: an overdue API invoice blocks access until it is settled.
        if (\App\Services\ApiBilling\ApiBillingService::clientIsPastDue($clientId)) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'PAYMENT_REQUIRED',
                'message'    => 'Your account has an overdue API invoice. Settle it to restore API access.',
            ], 402, ['Retry-After' => '86400']);
        }

        $client = IntegrationClient::where('client_id', $clientId)->first();
        $plan = ApiPlan::forKey($client?->api_plan_key ?: 'sandbox');

        // Unknown plan or unlimited quota -> not metered.
        if (! $plan || $plan->monthly_request_quota === null) {
            return $next($request);
        }

        $used = ApiUsageLog::where('integration_client_id', $clientId)
            ->where('logged_at', '>=', now()->startOfMonth())
            ->count();

        $resetAt = now()->startOfMonth()->addMonth();

        if ($used >= $plan->monthly_request_quota) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'QUOTA_EXCEEDED',
                'message'    => "Monthly API request quota exceeded for your plan ({$plan->name}). Upgrade your plan or wait for the monthly reset.",
                'plan'       => $plan->key,
                'quota'      => $plan->monthly_request_quota,
                'used'       => $used,
                'resets_at'  => $resetAt->toIso8601String(),
            ], 429, [
                'Retry-After'           => (string) max(1, (int) now()->diffInSeconds($resetAt)),
                'X-Quota-Limit'         => (string) $plan->monthly_request_quota,
                'X-Quota-Remaining'     => '0',
            ]);
        }

        $response = $next($request);
        $response->headers->set('X-Quota-Limit', (string) $plan->monthly_request_quota);
        $response->headers->set('X-Quota-Remaining', (string) max(0, $plan->monthly_request_quota - $used - 1));

        return $response;
    }
}
