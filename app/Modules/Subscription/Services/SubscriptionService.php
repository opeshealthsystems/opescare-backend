<?php

namespace App\Modules\Subscription\Services;

use App\Models\ModuleEntitlement;
use App\Models\OrganizationSubscription;
use App\Models\PlanFeature;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionUsageMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    // ── Plan Management ───────────────────────────────────────────────────────

    public function createPlan(array $data, string $actorId): SubscriptionPlan
    {
        return DB::transaction(function () use ($data, $actorId) {
            $plan = SubscriptionPlan::create([
                'name'                    => $data['name'],
                'slug'                    => Str::slug($data['name']),
                'audience'                => $data['audience'] ?? 'facility',
                'billing_cycle'           => $data['billing_cycle'],
                'price_kobo'              => (int) ($data['price'] * 100),
                'annual_price_kobo'       => isset($data['annual_price']) && $data['annual_price'] !== null && $data['annual_price'] !== ''
                                                ? (int) ($data['annual_price'] * 100)
                                                : null,
                'currency'                => $data['currency'] ?? 'XAF',
                'description'             => $data['description'] ?? null,
                'features'                => $data['features'] ?? null,
                'max_facilities'          => $data['max_facilities'] ?? 1,
                'max_staff'               => $data['max_staff'] ?? null,
                'max_patients_per_month'  => $data['max_patients_per_month'] ?? null,
                'is_active'               => true,
                'is_public'               => $data['is_public'] ?? true,
                'trial_days'              => $data['trial_days'] ?? 0,
                'sort_order'              => $data['sort_order'] ?? 0,
                'created_by'              => $actorId,
            ]);

            // Seed plan features/entitlements
            foreach ($data['plan_features'] ?? [] as $feature) {
                PlanFeature::create([
                    'plan_id'       => $plan->id,
                    'feature_key'   => $feature['key'],
                    'feature_label' => $feature['label'],
                    'limit_type'    => $feature['limit_type'] ?? 'boolean',
                    'limit_value'   => $feature['limit_value'] ?? null,
                ]);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * List all active, public plans (additive — used by API plan listing).
     */
    public function listActivePlans()
    {
        return SubscriptionPlan::query()
            ->active()
            ->public()
            ->with('planFeatures')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Fetch a single plan with its features (additive).
     */
    public function getPlan(string $planId): SubscriptionPlan
    {
        return SubscriptionPlan::with('planFeatures')->findOrFail($planId);
    }

    /**
     * Get the most recent subscription for an organization (additive).
     */
    public function getForOrganization(string $organizationId): ?OrganizationSubscription
    {
        return OrganizationSubscription::with(['plan', 'moduleEntitlements'])
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function togglePlan(string $planId, bool $active, string $actorId): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_active' => $active]);
        return $plan;
    }

    // ── Subscription Lifecycle ────────────────────────────────────────────────

    public function subscribe(
        string $organizationId,
        string $organizationName,
        string $planId,
        array  $billing,
        string $actorId
    ): OrganizationSubscription {
        return DB::transaction(function () use ($organizationId, $organizationName, $planId, $billing, $actorId) {
            $plan = SubscriptionPlan::findOrFail($planId);

            $isTrialing  = $plan->trial_days > 0;
            $periodStart = now()->toDateString();
            $periodEnd   = $plan->billing_cycle === 'annual'
                ? now()->addYear()->toDateString()
                : now()->addMonth()->toDateString();

            $sub = OrganizationSubscription::create([
                'organization_id'      => $organizationId,
                'organization_name'    => $organizationName,
                'plan_id'              => $planId,
                'status'               => $isTrialing ? 'trialing' : 'active',
                'trial_starts_at'      => $isTrialing ? $periodStart : null,
                'trial_ends_at'        => $isTrialing ? now()->addDays($plan->trial_days)->toDateString() : null,
                'current_period_start' => $periodStart,
                'current_period_end'   => $periodEnd,
                'billing_email'        => $billing['email'] ?? null,
                'billing_name'         => $billing['name'] ?? null,
                'payment_method'       => $billing['payment_method'] ?? null,
                'auto_renew'           => $billing['auto_renew'] ?? true,
                'discount_percent'     => $billing['discount_percent'] ?? 0,
                'notes'                => $billing['notes'] ?? null,
                'created_by'           => $actorId,
            ]);

            // Grant module entitlements from plan features
            $this->grantEntitlements($sub, $plan, $actorId);

            // Generate first invoice (skip if trialing with no upfront charge)
            if (!$isTrialing && $plan->price_kobo > 0) {
                $this->generateInvoice($sub, $plan, $actorId);
            }

            return $sub->load(['plan', 'moduleEntitlements']);
        });
    }

    public function renewSubscription(string $subscriptionId, string $actorId): OrganizationSubscription
    {
        return DB::transaction(function () use ($subscriptionId, $actorId) {
            $sub  = OrganizationSubscription::with('plan')->findOrFail($subscriptionId);
            $plan = $sub->plan;

            $newStart = $sub->current_period_end->addDay();
            $newEnd   = $plan->billing_cycle === 'annual'
                ? $newStart->copy()->addYear()
                : $newStart->copy()->addMonth();

            $sub->update([
                'status'               => 'active',
                'current_period_start' => $newStart->toDateString(),
                'current_period_end'   => $newEnd->toDateString(),
                'updated_by'           => $actorId,
            ]);

            if ($plan->price_kobo > 0) {
                $this->generateInvoice($sub, $plan, $actorId);
            }

            return $sub->fresh();
        });
    }

    public function changePlan(string $subscriptionId, string $newPlanId, string $actorId): OrganizationSubscription
    {
        return DB::transaction(function () use ($subscriptionId, $newPlanId, $actorId) {
            $sub     = OrganizationSubscription::findOrFail($subscriptionId);
            $newPlan = SubscriptionPlan::findOrFail($newPlanId);

            $sub->update([
                'plan_id'    => $newPlanId,
                'updated_by' => $actorId,
            ]);

            // Revoke old entitlements, grant new ones
            ModuleEntitlement::where('subscription_id', $subscriptionId)
                ->whereNull('revoked_at')
                ->update(['is_enabled' => false, 'revoked_at' => now()]);

            $this->grantEntitlements($sub, $newPlan, $actorId);

            return $sub->fresh(['plan', 'moduleEntitlements']);
        });
    }

    public function cancelSubscription(string $subscriptionId, string $reason, string $actorId): OrganizationSubscription
    {
        $sub = OrganizationSubscription::findOrFail($subscriptionId);
        $sub->update([
            'status'       => 'cancelled',
            'cancelled_at' => now()->toDateString(),
            'auto_renew'   => false,
            'notes'        => trim(($sub->notes ?? '') . "\nCancelled by {$actorId}: {$reason}"),
            'updated_by'   => $actorId,
        ]);

        ModuleEntitlement::where('subscription_id', $subscriptionId)
            ->whereNull('revoked_at')
            ->update(['is_enabled' => false, 'revoked_at' => now()]);

        return $sub->fresh();
    }

    public function pauseSubscription(string $subscriptionId, string $actorId): OrganizationSubscription
    {
        $sub = OrganizationSubscription::findOrFail($subscriptionId);
        $sub->update(['status' => 'paused', 'updated_by' => $actorId]);
        return $sub->fresh();
    }

    public function reactivateSubscription(string $subscriptionId, string $actorId): OrganizationSubscription
    {
        $sub = OrganizationSubscription::findOrFail($subscriptionId);
        $sub->update(['status' => 'active', 'updated_by' => $actorId]);

        // Re-enable entitlements if they were paused (not cancelled)
        if (ModuleEntitlement::where('subscription_id', $subscriptionId)->where('is_enabled', false)->whereNull('revoked_at')->exists()) {
            $this->grantEntitlements($sub, $sub->plan, $actorId);
        }

        return $sub->fresh();
    }

    // ── Invoice Management ────────────────────────────────────────────────────

    public function generateInvoice(
        OrganizationSubscription $sub,
        SubscriptionPlan $plan,
        string $actorId
    ): SubscriptionInvoice {
        $discountKobo = (int) ($plan->price_kobo * $sub->discount_percent / 100);
        $subtotal     = $plan->price_kobo;
        $total        = max(0, $subtotal - $discountKobo);

        $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad(
            SubscriptionInvoice::whereYear('created_at', now()->year)->count() + 1,
            5, '0', STR_PAD_LEFT
        );

        return SubscriptionInvoice::create([
            'subscription_id'    => $sub->id,
            'organization_id'    => $sub->organization_id,
            'invoice_number'     => $invoiceNumber,
            'invoice_date'       => now()->toDateString(),
            'due_date'           => now()->addDays(7)->toDateString(),
            'status'             => 'sent',
            'subtotal_kobo'      => $subtotal,
            'discount_kobo'      => $discountKobo,
            'tax_kobo'           => 0,
            'total_kobo'         => $total,
            'currency'           => $plan->currency,
            'line_items'         => [[
                'description'  => "{$plan->name} ({$plan->billing_cycle}) — {$sub->current_period_start} to {$sub->current_period_end}",
                'amount_kobo'  => $subtotal,
            ]],
            'created_by'         => $actorId,
        ]);
    }

    public function markInvoicePaid(string $invoiceId, string $paymentRef, string $method, string $actorId): SubscriptionInvoice
    {
        $invoice = SubscriptionInvoice::findOrFail($invoiceId);
        $invoice->update([
            'status'             => 'paid',
            'paid_at'            => now()->toDateString(),
            'payment_reference'  => $paymentRef,
            'payment_method'     => $method,
        ]);

        // If the subscription was past_due, reactivate
        $sub = $invoice->subscription;
        if ($sub && $sub->status === 'past_due') {
            $sub->update(['status' => 'active', 'updated_by' => $actorId]);
        }

        return $invoice->fresh();
    }

    // ── Module Entitlement Enforcement ────────────────────────────────────────

    public function grantEntitlements(OrganizationSubscription $sub, SubscriptionPlan $plan, string $actorId): void
    {
        $features = $plan->planFeatures()->get();
        foreach ($features as $feature) {
            ModuleEntitlement::updateOrCreate(
                ['subscription_id' => $sub->id, 'module_key' => $feature->feature_key],
                [
                    'organization_id' => $sub->organization_id,
                    'is_enabled'      => true,
                    'granted_at'      => now(),
                    'revoked_at'      => null,
                    'granted_by'      => $actorId,
                ]
            );
        }
    }

    public function checkEntitlement(string $organizationId, string $moduleKey): bool
    {
        return ModuleEntitlement::where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('is_enabled', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    // ── Usage Tracking ────────────────────────────────────────────────────────

    public function recordUsage(
        string $subscriptionId,
        string $organizationId,
        string $metricKey,
        int    $value
    ): SubscriptionUsageMetric {
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd   = now()->endOfMonth()->toDateString();

        return SubscriptionUsageMetric::updateOrCreate(
            ['subscription_id' => $subscriptionId, 'metric_key' => $metricKey, 'period_start' => $periodStart],
            [
                'organization_id' => $organizationId,
                'metric_value'    => $value,
                'period_end'      => $periodEnd,
                'recorded_at'     => now(),
            ]
        );
    }

    public function getUsageSummary(string $subscriptionId): array
    {
        return SubscriptionUsageMetric::where('subscription_id', $subscriptionId)
            ->where('period_start', now()->startOfMonth()->toDateString())
            ->pluck('metric_value', 'metric_key')
            ->toArray();
    }

    // ── Dashboard Stats ───────────────────────────────────────────────────────

    public function getAdminStats(): array
    {
        return [
            'total'      => OrganizationSubscription::count(),
            'active'     => OrganizationSubscription::where('status', 'active')->count(),
            'trialing'   => OrganizationSubscription::where('status', 'trialing')->count(),
            'past_due'   => OrganizationSubscription::where('status', 'past_due')->count(),
            'cancelled'  => OrganizationSubscription::where('status', 'cancelled')->count(),
            'mrr_kobo'   => OrganizationSubscription::where('status', 'active')
                ->join('subscription_plans', 'subscription_plans.id', '=', 'organization_subscriptions.plan_id')
                ->where('subscription_plans.billing_cycle', 'monthly')
                ->sum('subscription_plans.price_kobo'),
            'overdue_invoices' => SubscriptionInvoice::where('status', 'sent')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }
}
