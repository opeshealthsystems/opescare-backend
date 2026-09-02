<?php

namespace App\Modules\Referral\Services;

use App\Models\ReferralAccessGrant;
use App\Models\ReferralCase;
use Illuminate\Support\Str;

class ReferralService
{
    public function create(array $data): ReferralCase
    {
        $referral = ReferralCase::create([
            'patient_id'             => $data['patient_id'],
            'referring_facility_id'  => $data['referring_facility_id'],
            'referring_provider_id'  => $data['referring_provider_id'] ?? null,
            'receiving_facility_id'  => $data['receiving_facility_id'] ?? null,
            'receiving_specialty'    => $data['receiving_specialty'] ?? null,
            'receiving_provider_name'=> $data['receiving_provider_name'] ?? null,
            'urgency'                => $data['urgency'] ?? 'routine',
            'status'                 => 'draft',
            'reason'                 => $data['reason'],
            'clinical_summary'       => $data['clinical_summary'] ?? null,
            'included_record_types'  => $data['included_record_types'] ?? null,
            'consent_grant_id'       => $data['consent_grant_id'] ?? null,
            'expires_at'             => $data['expires_at'] ?? now()->addDays(30),
            'created_by_id'          => $data['created_by_id'] ?? null,
        ]);

        return $referral;
    }

    public function send(ReferralCase $referral, ?string $actorId = null): ReferralCase
    {
        abort_if($referral->status !== 'draft', 422, 'Only draft referrals can be sent.');
        abort_if($referral->isExpired(), 422, 'Referral has expired.');

        $referral->update(['status' => 'sent']);

        // Issue a scoped, time-limited access token for the receiving facility
        if ($referral->receiving_facility_id) {
            $this->issueAccessGrant($referral, $actorId);
        }

        return $referral->fresh();
    }

    public function accept(ReferralCase $referral, string $acceptedById): ReferralCase
    {
        abort_if(!in_array($referral->status, ['sent']), 422, 'Only sent referrals can be accepted.');
        abort_if($referral->isExpired(), 422, 'Referral has expired and can no longer be accepted.');

        $referral->update([
            'status'        => 'accepted',
            'accepted_at'   => now(),
            'accepted_by_id'=> $acceptedById,
        ]);

        return $referral->fresh();
    }

    public function reject(ReferralCase $referral, string $reason): ReferralCase
    {
        abort_if(!in_array($referral->status, ['sent']), 422, 'Only sent referrals can be rejected.');

        $referral->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        // Revoke any outstanding access grant
        $referral->accessGrants()->where('status', 'active')->update([
            'status'            => 'revoked',
            'revoked_at'        => now(),
            'revocation_reason' => 'Referral rejected',
        ]);

        return $referral->fresh();
    }

    public function complete(ReferralCase $referral, ?string $feedback = null): ReferralCase
    {
        abort_if($referral->status !== 'accepted', 422, 'Only accepted referrals can be completed.');

        $referral->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'feedback'     => $feedback,
        ]);

        return $referral->fresh();
    }

    public function cancel(ReferralCase $referral, string $reason): ReferralCase
    {
        abort_if(in_array($referral->status, ['completed', 'cancelled']), 422, 'Referral cannot be cancelled in its current state.');

        $referral->update([
            'status'               => 'cancelled',
            'cancelled_at'         => now(),
            'cancellation_reason'  => $reason,
        ]);

        $referral->accessGrants()->where('status', 'active')->update([
            'status'            => 'revoked',
            'revoked_at'        => now(),
            'revocation_reason' => 'Referral cancelled: ' . $reason,
        ]);

        return $referral->fresh();
    }

    public function expireStale(): int
    {
        return ReferralCase::query()
            ->whereIn('status', ['sent', 'draft'])
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    private function issueAccessGrant(ReferralCase $referral, ?string $grantedById): ReferralAccessGrant
    {
        return ReferralAccessGrant::create([
            'referral_case_id'       => $referral->id,
            'patient_id'             => $referral->patient_id,
            'granted_to_facility_id' => $referral->receiving_facility_id,
            'granted_by_id'          => $grantedById,
            'token'                  => Str::random(64),
            'allowed_scopes'         => $referral->included_record_types ?? ['demographics', 'conditions', 'medications'],
            'status'                 => 'active',
            'expires_at'             => $referral->expires_at ?? now()->addDays(30),
        ]);
    }
}
