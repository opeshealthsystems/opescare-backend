<?php

namespace App\Modules\Subscription\Services;

use App\Models\FamilyLink;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * B2C (patient) subscription flow on the unified subscription engine.
 *
 * Patients have no organization, so feature entitlements are resolved directly
 * from the active subscription's plan rather than materialized ModuleEntitlement
 * rows (which are organization-scoped). One active subscription per patient.
 *
 * See docs/superpowers/specs/2026-06-17-subscription-billing-design.md (Phase 2).
 */
class PatientSubscriptionService
{
    /** The single active (or trialing) subscription for a patient, if any. */
    public function activeSubscription(Patient $patient): ?OrganizationSubscription
    {
        return OrganizationSubscription::with('plan.planFeatures')
            ->where('subscriber_type', 'patient')
            ->where('subscriber_id', $patient->id)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->orderByDesc('created_at')
            ->first();
    }

    /** The patient's current plan, or the Free plan when unsubscribed. */
    public function currentPlan(Patient $patient): ?SubscriptionPlan
    {
        return $this->activeSubscription($patient)?->plan ?? $this->freePlan();
    }

    public function freePlan(): ?SubscriptionPlan
    {
        return SubscriptionPlan::forAudience('patient')->where('slug', 'patient-free')->first();
    }

    /**
     * Ensure the patient has at least the Free plan (default state on signup).
     * Idempotent — returns the existing active subscription if one exists.
     */
    public function ensureFreePlan(Patient $patient, ?string $actorId = null): OrganizationSubscription
    {
        if ($existing = $this->activeSubscription($patient)) {
            return $existing;
        }

        $free = $this->freePlan();
        abort_if($free === null, 500, 'Patient Free plan is not seeded.');

        return $this->open($patient, $free, 'monthly', [], $actorId);
    }

    /**
     * Activate a paid (or free) plan for a patient. For paid plans this is called
     * by the checkout flow AFTER payment is confirmed. Upgrading/switching from an
     * existing active subscription cancels the old one first (single active sub).
     */
    public function startSubscription(
        Patient $patient,
        SubscriptionPlan $plan,
        string $interval = 'monthly',
        array $billing = [],
        ?string $actorId = null
    ): OrganizationSubscription {
        return DB::transaction(function () use ($patient, $plan, $interval, $billing, $actorId) {
            // Close any current subscription so there is exactly one active row.
            $current = $this->activeSubscription($patient);
            if ($current) {
                $current->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now()->toDateString(),
                    'auto_renew'   => false,
                    'updated_by'   => $actorId,
                ]);
            }

            return $this->open($patient, $plan, $interval, $billing, $actorId);
        });
    }

    /**
     * Open a PENDING (unpaid) subscription as the holder for a Mobile Money
     * checkout. status='pending' is intentionally NOT in activeSubscription()'s
     * whitelist, so a patient gets no premium access until payment confirms.
     * Any older pending rows for the same patient are voided first.
     */
    public function beginCheckout(
        Patient $patient,
        SubscriptionPlan $plan,
        string $interval = 'monthly',
        ?string $actorId = null
    ): OrganizationSubscription {
        OrganizationSubscription::where('subscriber_type', 'patient')
            ->where('subscriber_id', $patient->id)
            ->where('status', 'pending')
            ->update(['status' => 'payment_failed', 'updated_by' => $actorId]);

        // current_period_* are NOT NULL; seed placeholders (overwritten on
        // confirmPaidCheckout). 'pending' status keeps this row out of
        // activeSubscription(), so the placeholder period grants no access.
        $now = now()->toDateString();

        return OrganizationSubscription::create([
            'subscriber_type'      => 'patient',
            'subscriber_id'        => $patient->id,
            'interval'             => $interval,
            'plan_id'              => $plan->id,
            'status'               => 'pending',
            'payment_method'       => 'mtn_momo',
            'auto_renew'           => false,
            'current_period_start' => $now,
            'current_period_end'   => $now,
            'created_by'           => $actorId,
        ]);
    }

    /** Store the payment-provider reference on a pending checkout row. */
    public function attachPaymentReference(OrganizationSubscription $pending, string $reference): void
    {
        $pending->update(['payment_reference' => $reference]);
    }

    /**
     * Confirm a pending checkout AFTER the provider reports success: set the
     * billing period, flip to active, cancel any other active subscription
     * (single active row), and write a paid invoice for the receipt.
     */
    public function confirmPaidCheckout(OrganizationSubscription $pending, ?string $actorId = null): OrganizationSubscription
    {
        return DB::transaction(function () use ($pending, $actorId) {
            $patient = Patient::withoutGlobalScopes()->find($pending->subscriber_id);
            $plan    = $pending->plan;

            // Close any other active/trialing/past_due row.
            OrganizationSubscription::where('subscriber_type', 'patient')
                ->where('subscriber_id', $pending->subscriber_id)
                ->where('id', '!=', $pending->id)
                ->whereIn('status', ['active', 'trialing', 'past_due'])
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now()->toDateString(),
                    'auto_renew'   => false,
                    'updated_by'   => $actorId,
                ]);

            $start = now();
            $end   = $pending->interval === 'annual'
                ? $start->copy()->addYear()
                : $start->copy()->addMonth();

            $pending->update([
                'status'               => 'active',
                'current_period_start' => $start->toDateString(),
                'current_period_end'   => $end->toDateString(),
                'auto_renew'           => true,
                'updated_by'           => $actorId,
            ]);

            $this->writePaidInvoice($pending->fresh('plan'), $patient, $actorId);

            return $pending->fresh('plan.planFeatures');
        });
    }

    /** Mark a pending checkout as failed (provider declined / timed out). */
    public function failCheckout(OrganizationSubscription $pending, ?string $actorId = null): void
    {
        if ($pending->status === 'pending') {
            $pending->update(['status' => 'payment_failed', 'updated_by' => $actorId]);
        }
    }

    /** Amount due for an interval, in major currency units (e.g. XAF). */
    public function amountFor(SubscriptionPlan $plan, string $interval): int
    {
        $kobo = $interval === 'annual'
            ? (int) ($plan->annual_price_kobo ?: $plan->price_kobo * 12)
            : (int) $plan->price_kobo;

        return intdiv($kobo, 100);
    }

    /** Write a paid SubscriptionInvoice as the patient's receipt. */
    private function writePaidInvoice(OrganizationSubscription $sub, ?Patient $patient, ?string $actorId): void
    {
        $kobo = $sub->interval === 'annual'
            ? (int) ($sub->plan->annual_price_kobo ?: $sub->plan->price_kobo * 12)
            : (int) $sub->plan->price_kobo;

        \App\Models\SubscriptionInvoice::create([
            'subscription_id'  => $sub->id,
            'organization_id'  => $patient?->facility_id,
            'invoice_number'   => 'INV-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'invoice_date'     => now()->toDateString(),
            'due_date'         => now()->toDateString(),
            'paid_at'          => now()->toDateString(),
            'status'           => 'paid',
            'subtotal_kobo'    => $kobo,
            'discount_kobo'    => 0,
            'tax_kobo'         => 0,
            'total_kobo'       => $kobo,
            'currency'         => $sub->plan->currency ?? 'XAF',
            'line_items'       => [[
                'description' => $sub->plan->name . ' (' . $sub->interval . ')',
                'amount_kobo' => $kobo,
            ]],
            'payment_reference' => $sub->payment_reference,
            'payment_method'    => 'mtn_momo',
            'created_by'         => $actorId,
        ]);
    }

    /** Cancel at period end (stays active until current_period_end, then lapses). */
    public function cancel(Patient $patient, string $reason = '', ?string $actorId = null): ?OrganizationSubscription
    {
        $sub = $this->activeSubscription($patient);
        if (!$sub) {
            return null;
        }

        $sub->update([
            'auto_renew' => false,
            'notes'      => trim(($sub->notes ?? '') . "\nCancellation requested: {$reason}"),
            'updated_by' => $actorId,
        ]);

        return $sub->fresh();
    }

    /**
     * Does the patient's current plan include a feature key? Free-plan features
     * count too. A dependent also inherits features from a guardian's Premium
     * family plan (within the family_sharing seat limit), so callers get a single
     * source of truth for gating.
     */
    public function hasFeature(Patient $patient, string $featureKey): bool
    {
        $plan = $this->currentPlan($patient);
        if ($plan !== null && $plan->hasFeature($featureKey)) {
            return true;
        }

        $covering = $this->coveringSubscription($patient);
        return $covering !== null && $covering->plan->hasFeature($featureKey);
    }

    // ── Family sharing ─────────────────────────────────────────────────────────

    /**
     * The guardian's Premium subscription that covers this dependent, if any.
     * Coverage goes to the earliest-linked dependents up to the family_sharing
     * seat limit on the guardian's plan.
     */
    public function coveringSubscription(Patient $dependent): ?OrganizationSubscription
    {
        $links = FamilyLink::where('dependent_patient_id', $dependent->id)
            ->where('status', 'active')
            ->get();

        foreach ($links as $link) {
            $guardianPatient = User::find($link->guardian_user_id)?->patient;
            if (!$guardianPatient) {
                continue;
            }

            $sub = $this->activeSubscription($guardianPatient);
            if (!$sub || !$sub->plan || !$sub->plan->hasFeature('family_sharing')) {
                continue;
            }

            $limit   = $this->featureLimit($guardianPatient, 'family_sharing');
            $covered = $this->coveredDependentIds($guardianPatient, $limit);
            if (in_array($dependent->id, $covered, true)) {
                return $sub;
            }
        }

        return null;
    }

    /**
     * IDs of the guardian's dependents currently covered by their family plan,
     * capped at the seat limit (null = uncapped). Earliest links win the seats.
     *
     * @return array<int, string>
     */
    public function coveredDependentIds(Patient $guardianPatient, ?int $limit): array
    {
        $guardianUserId = User::where('patient_id', $guardianPatient->id)->value('id');
        if (!$guardianUserId) {
            return [];
        }

        $ids = FamilyLink::where('guardian_user_id', $guardianUserId)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->pluck('dependent_patient_id');

        return ($limit !== null ? $ids->take($limit) : $ids)->values()->all();
    }

    /** Family seat usage for a guardian: [used, total] (total null = uncapped / no plan). */
    public function familySeats(Patient $guardianPatient): array
    {
        $sub = $this->activeSubscription($guardianPatient);
        if (!$sub || !$sub->plan || !$sub->plan->hasFeature('family_sharing')) {
            return ['used' => 0, 'total' => 0];
        }
        $limit = $this->featureLimit($guardianPatient, 'family_sharing');
        return ['used' => count($this->coveredDependentIds($guardianPatient, $limit)), 'total' => $limit];
    }

    /** Numeric limit for a metered feature (e.g. family_sharing => 5), or null. */
    public function featureLimit(Patient $patient, string $featureKey): ?int
    {
        $plan = $this->currentPlan($patient);
        $feature = $plan?->planFeatures()->where('feature_key', $featureKey)->first();
        return $feature?->limit_value !== null ? (int) $feature->limit_value : null;
    }

    // ── internals ────────────────────────────────────────────────────────────

    private function open(
        Patient $patient,
        SubscriptionPlan $plan,
        string $interval,
        array $billing,
        ?string $actorId
    ): OrganizationSubscription {
        $isTrialing  = $plan->trial_days > 0 && !$plan->isFree();
        $periodStart = now();
        $periodEnd   = $interval === 'annual'
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        return OrganizationSubscription::create([
            'subscriber_type'      => 'patient',
            'subscriber_id'        => $patient->id,
            'interval'             => $interval,
            'plan_id'              => $plan->id,
            'status'               => $isTrialing ? 'trialing' : 'active',
            'trial_starts_at'      => $isTrialing ? $periodStart->toDateString() : null,
            'trial_ends_at'        => $isTrialing ? $periodStart->copy()->addDays($plan->trial_days)->toDateString() : null,
            'current_period_start' => $periodStart->toDateString(),
            'current_period_end'   => $periodEnd->toDateString(),
            'billing_email'        => $billing['email'] ?? null,
            'billing_name'         => $billing['name'] ?? null,
            'payment_method'       => $billing['payment_method'] ?? null,
            'auto_renew'           => $billing['auto_renew'] ?? !$plan->isFree(),
            'created_by'           => $actorId,
        ])->load('plan.planFeatures');
    }
}
