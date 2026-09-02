<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Subscription\Services\SubscriptionService;
use App\Modules\Subscription\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SubscriptionController — SaaS Subscription & Billing API.
 *
 * IMPORTANT: This is SaaS subscription billing (organization plans).
 * This is NOT patient billing. Patient billing uses BillingController.
 * Do NOT mix these two billing domains.
 *
 * Covers: plan management, organization subscriptions, trials, usage limits,
 * module entitlements, upgrade/downgrade, cancellation.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private PlanLimitService    $limits
    ) {}

    // ── Plans ──────────────────────────────────────────────────────────────

    public function listPlans(): JsonResponse
    {
        return response()->json($this->subscriptions->listActivePlans());
    }

    public function showPlan(string $planId): JsonResponse
    {
        return response()->json($this->subscriptions->getPlan($planId));
    }

    // ── Organization Subscriptions ─────────────────────────────────────────

    public function getMySubscription(Request $request): JsonResponse
    {
        return response()->json(
            $this->subscriptions->getForOrganization($request->user()->organization_id)
        );
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'           => ['required', 'uuid'],
            'organization_id'   => ['required', 'uuid'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'billing_cycle'     => ['required', 'in:monthly,annual'],
            'billing_email'     => ['nullable', 'email'],
            'billing_name'      => ['nullable', 'string', 'max:255'],
            'payment_method'    => ['nullable', 'string', 'max:100'],
            'auto_renew'        => ['nullable', 'boolean'],
        ]);

        $subscription = $this->subscriptions->subscribe(
            $validated['organization_id'],
            $validated['organization_name'] ?? ($request->user()->organization_name ?? ''),
            $validated['plan_id'],
            [
                'email'          => $validated['billing_email'] ?? null,
                'name'           => $validated['billing_name'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'auto_renew'     => $validated['auto_renew'] ?? true,
            ],
            $request->user()->id
        );

        return response()->json($subscription, 201);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_plan_id'     => ['required', 'uuid'],
            'organization_id' => ['required', 'uuid'],
        ]);

        $subscription = $this->subscriptions->getForOrganization($validated['organization_id']);
        if (!$subscription) {
            return response()->json(['message' => __('api.subscription_not_found_org')], 404);
        }

        return response()->json(
            $this->subscriptions->changePlan($subscription->id, $validated['new_plan_id'], $request->user()->id)
        );
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'reason'          => ['nullable', 'string'],
        ]);

        $subscription = $this->subscriptions->getForOrganization($validated['organization_id']);
        if (!$subscription) {
            return response()->json(['message' => __('api.subscription_not_found_org')], 404);
        }

        return response()->json(
            $this->subscriptions->cancelSubscription(
                $subscription->id,
                $validated['reason'] ?? 'No reason provided',
                $request->user()->id
            )
        );
    }

    // ── Usage & Limits ─────────────────────────────────────────────────────

    public function getUsage(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->getForOrganization($request->user()->organization_id);
        if (!$subscription) {
            return response()->json(['message' => __('api.subscription_not_found_org')], 404);
        }

        return response()->json([
            'subscription_id' => $subscription->id,
            'usage'           => $this->subscriptions->getUsageSummary($subscription->id),
            'limits'          => $this->limits->getLimitsForPlan($subscription->plan_id),
        ]);
    }

    public function checkLimit(Request $request, string $featureKey): JsonResponse
    {
        $subscription = $this->subscriptions->getForOrganization($request->user()->organization_id);
        if (!$subscription) {
            return response()->json(['message' => __('api.subscription_not_found_org')], 404);
        }

        $usage        = $this->subscriptions->getUsageSummary($subscription->id);
        $currentUsage = (int) ($usage[$featureKey] ?? 0);
        $allowed      = $this->limits->isWithinLimit($subscription, $featureKey, $currentUsage);

        return response()->json([
            'feature'       => $featureKey,
            'allowed'       => $allowed,
            'current_usage' => $currentUsage,
            'limit'         => $this->limits->getLimit($subscription->plan_id, $featureKey),
        ]);
    }
}
