<?php

namespace App\Modules\Subscription\Services;

use App\Models\Patient;
use App\Models\ReferralInvite;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Refer & Earn" double-sided growth loop on the patient subscription engine.
 *
 * A patient shares their stable referral code; when a new patient signs up with
 * it, BOTH parties are granted Premium (patient-premium) days. Distinct from the
 * clinical referrals feature — see App\Models\ReferralInvite.
 */
class ReferralRewardService
{
    /** Premium days granted to the referrer when their invite converts. */
    public const REFERRER_REWARD_DAYS = 30;

    /** Premium days granted to the new patient (referee) on signup. */
    public const REFEREE_REWARD_DAYS = 30;

    public function __construct(private readonly PatientSubscriptionService $subscriptions)
    {
    }

    /** Get (or lazily create) the patient's stable referral code. */
    public function codeFor(Patient $patient): string
    {
        if (! empty($patient->referral_code)) {
            return $patient->referral_code;
        }

        $code = $this->generateUniqueCode();
        $patient->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    /**
     * Record a new signup against a referrer's code.
     *
     * Rejects self-referral and duplicate (referee already referred). Returns the
     * created 'joined' invite, or null when the code is unknown / invalid / rejected.
     */
    public function recordSignup(Patient $referee, string $code): ?ReferralInvite
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $referrer = Patient::where('referral_code', $code)->first();
        if (! $referrer) {
            return null;
        }

        // Reject self-referral.
        if ($referrer->id === $referee->id) {
            return null;
        }

        // Reject duplicate — this referee has already been referred.
        $already = ReferralInvite::where('referee_patient_id', $referee->id)->exists();
        if ($already) {
            return null;
        }

        return ReferralInvite::create([
            'referrer_patient_id'  => $referrer->id,
            'code'                 => $code,
            'referee_email'        => $referee->email,
            'referee_patient_id'   => $referee->id,
            'status'               => 'joined',
            'referrer_reward_days' => self::REFERRER_REWARD_DAYS,
            'referee_reward_days'  => self::REFEREE_REWARD_DAYS,
        ]);
    }

    /**
     * Grant BOTH parties their Premium days and mark the invite 'rewarded'.
     *
     * Never throws to the caller: any failure is logged and swallowed so it can
     * never break a signup flow. Idempotent — an already-rewarded invite is a no-op.
     */
    public function grantRewards(ReferralInvite $invite): void
    {
        try {
            if ($invite->status === 'rewarded') {
                return;
            }

            $premium = SubscriptionPlan::forAudience('patient')->where('slug', 'patient-premium')->first();
            if (! $premium) {
                Log::warning('referral_reward_skipped_no_premium_plan', ['invite_id' => $invite->id]);
                return;
            }

            $referrer = $invite->referrer;
            $referee  = $invite->referee;

            if ($referrer) {
                $this->grantPremiumDays($referrer, $premium, $invite->referrer_reward_days);
            }
            if ($referee) {
                $this->grantPremiumDays($referee, $premium, $invite->referee_reward_days);
            }

            $invite->forceFill([
                'status'      => 'rewarded',
                'rewarded_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::error('referral_grant_rewards_failed', [
                'invite_id' => $invite->id ?? null,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Put a patient on Premium for the given number of days. If they already have
     * an active subscription we extend its current_period_end; otherwise we start
     * a fresh Premium subscription whose period runs $days from now.
     */
    private function grantPremiumDays(Patient $patient, SubscriptionPlan $premium, int $days): void
    {
        if ($days <= 0) {
            return;
        }

        DB::transaction(function () use ($patient, $premium, $days) {
            $active = $this->subscriptions->activeSubscription($patient);

            // Already on Premium → extend the period.
            if ($active && $active->plan_id === $premium->id) {
                $base = $active->current_period_end && $active->current_period_end->isFuture()
                    ? $active->current_period_end->copy()
                    : now();
                $active->update([
                    'current_period_end' => $base->addDays($days)->toDateString(),
                    'status'             => 'active',
                ]);
                return;
            }

            // On Free (or another plan) → switch to Premium for $days, no auto-renew
            // since this is a comped reward period.
            $sub = $this->subscriptions->startSubscription($patient, $premium, 'monthly', [
                'auto_renew' => false,
            ]);
            $sub->update([
                'current_period_start' => now()->toDateString(),
                'current_period_end'   => now()->addDays($days)->toDateString(),
            ]);
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
            // Avoid ambiguous chars / ensure alnum-only.
            $code = preg_replace('/[^A-Z0-9]/', '', $code . strtoupper(bin2hex(random_bytes(4))));
            $code = substr($code, 0, 8);
        } while (Patient::where('referral_code', $code)->exists());

        return $code;
    }
}
