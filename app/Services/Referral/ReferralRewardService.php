<?php

namespace App\Services\Referral;

use App\Models\Patient;
use App\Models\ReferralInvite;
use Illuminate\Support\Facades\DB;

/**
 * "Refer & Earn" — the growth-referral engine behind portals.patient.refer.
 *
 * GROWTH referral, not the clinical one (ReferralCase). Each patient owns a
 * stable shareable code (patients.referral_code); a new patient who signs up
 * with that code produces a ReferralInvite row and both sides are credited the
 * reward days recorded ON THAT ROW.
 *
 * Scope note: the reward is written to referral_invites only. Finder has no
 * subscription/Premium ledger of its own (billing lives in the platform), so
 * this service never touches one — it records what was earned and leaves
 * redemption to whoever owns the tier.
 *
 * The two REWARD_DAYS constants are the product's dial. They were not
 * recoverable when this service was restored, so they are stated here in one
 * place rather than scattered; change them here and the page, the lang string
 * and the credited rows all follow.
 */
class ReferralRewardService
{
    public const REFERRER_REWARD_DAYS = 30;

    public const REFEREE_REWARD_DAYS = 30;

    /** Unambiguous alphabet — no O/0, I/1, so a code read aloud survives. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    /**
     * The patient's stable invite code, minted on first use and then reused
     * for the life of the account.
     */
    public function codeFor(Patient $patient): string
    {
        $existing = trim((string) ($patient->referral_code ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->mint();
            if (Patient::where('referral_code', $code)->exists()) {
                continue;
            }

            $patient->referral_code = $code;
            $patient->save();

            return $code;
        }

        // Vanishingly unlikely; fall back to something guaranteed unique.
        $code = strtoupper(substr(str_replace('-', '', (string) $patient->getKey()), 0, self::CODE_LENGTH));
        $patient->referral_code = $code;
        $patient->save();

        return $code;
    }

    /** The patient who owns a code, or null when it is unknown. */
    public function referrerFor(string $code): ?Patient
    {
        $code = $this->normalise($code);

        return $code === '' ? null : Patient::where('referral_code', $code)->first();
    }

    /**
     * Record that $referee signed up with $code. Returns the invite row, or
     * null when the code is unknown, self-referring, or already used by this
     * patient — never throws, because sign-up must not depend on it.
     */
    public function recordSignup(Patient $referee, string $code): ?ReferralInvite
    {
        $referrer = $this->referrerFor($code);
        if ($referrer === null || (string) $referrer->getKey() === (string) $referee->getKey()) {
            return null;
        }

        // One conversion per referee, whatever code they arrive with.
        $already = ReferralInvite::where('referee_patient_id', $referee->getKey())->first();
        if ($already !== null) {
            return null;
        }

        return ReferralInvite::create([
            'referrer_patient_id' => $referrer->getKey(),
            'code'                => $this->normalise($code),
            'referee_email'       => $referee->email,
            'referee_patient_id'  => $referee->getKey(),
            'status'              => 'joined',
        ]);
    }

    /**
     * Credit both sides. Idempotent: an invite that is already rewarded is
     * left exactly as it is.
     */
    public function grantRewards(ReferralInvite $invite): void
    {
        if ($invite->status === 'rewarded') {
            return;
        }

        DB::transaction(function () use ($invite) {
            $invite->forceFill([
                'status'               => 'rewarded',
                'referrer_reward_days' => self::REFERRER_REWARD_DAYS,
                'referee_reward_days'  => self::REFEREE_REWARD_DAYS,
                'rewarded_at'          => now(),
            ])->save();
        });
    }

    private function mint(): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    private function normalise(string $code): string
    {
        return strtoupper(trim($code));
    }
}
