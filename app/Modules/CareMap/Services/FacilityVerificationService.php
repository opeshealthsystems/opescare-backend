<?php

namespace App\Modules\CareMap\Services;

use App\Models\CareFacility;
use App\Models\FacilityUpdateAudit;
use Illuminate\Support\Facades\DB;

class FacilityVerificationService
{
    /**
     * Verify a facility listing
     */
    public function verifyFacility($id, $adminId, $status = 'license_verified')
    {
        $facility = CareFacility::findOrFail($id);
        
        DB::transaction(function () use ($facility, $adminId, $status) {
            $facility->update([
                'verification_status' => $status,
                'last_verified_at' => now(),
            ]);

            // Audit
            FacilityUpdateAudit::create([
                'facility_id' => $facility->id,
                'actor_id' => $adminId,
                'actor_type' => 'user',
                'field_changed' => 'verification_status',
                'old_value' => 'unverified',
                'new_value' => $status,
                'source' => 'admin_panel',
                'requires_review' => false,
            ]);
        });

        return $facility;
    }

    /**
     * Suspend a facility listing
     */
    public function suspendFacility($id, $adminId)
    {
        $facility = CareFacility::findOrFail($id);
        
        DB::transaction(function () use ($facility, $adminId) {
            $facility->update([
                'listing_status' => 'suspended',
            ]);

            // Audit
            FacilityUpdateAudit::create([
                'facility_id' => $facility->id,
                'actor_id' => $adminId,
                'actor_type' => 'user',
                'field_changed' => 'listing_status',
                'old_value' => 'active',
                'new_value' => 'suspended',
                'source' => 'admin_panel',
                'requires_review' => false,
            ]);
        });

        return $facility;
    }

    /**
     * Safely update a facility profile, intercepting high-risk fields for admin review
     */
    public function updateProfile($id, array $data, $actorId, $actorType = 'user')
    {
        $facility = CareFacility::findOrFail($id);
        $highRiskFields = ['facility_type', 'license_number', 'emergency_contact'];
        
        $updatesToApply = [];
        $pendingReviews = [];

        foreach ($data as $field => $newValue) {
            $oldValue = $facility->{$field};
            if ($oldValue != $newValue) {
                if (in_array($field, $highRiskFields)) {
                    // High-risk changes require review and do not apply immediately
                    $pendingReviews[] = [
                        'field' => $field,
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                } else {
                    // Low-risk changes apply immediately
                    $updatesToApply[$field] = $newValue;
                }
            }
        }

        DB::transaction(function () use ($facility, $updatesToApply, $pendingReviews, $actorId, $actorType) {
            if (!empty($updatesToApply)) {
                $updatesToApply['last_profile_update_at'] = now();
                $facility->update($updatesToApply);

                // Audit low-risk updates
                foreach ($updatesToApply as $field => $val) {
                    if ($field === 'last_profile_update_at') continue;
                    FacilityUpdateAudit::create([
                        'facility_id' => $facility->id,
                        'actor_id' => $actorId,
                        'actor_type' => $actorType,
                        'field_changed' => $field,
                        'old_value' => $facility->getOriginal($field),
                        'new_value' => $val,
                        'source' => 'profile_update',
                        'requires_review' => false,
                    ]);
                }
            }

            // Record high-risk updates as pending review
            foreach ($pendingReviews as $review) {
                FacilityUpdateAudit::create([
                    'facility_id' => $facility->id,
                    'actor_id' => $actorId,
                    'actor_type' => $actorType,
                    'field_changed' => $review['field'],
                    'old_value' => $review['old'],
                    'new_value' => $review['new'],
                    'source' => 'profile_update',
                    'requires_review' => true,
                ]);
            }
        });

        return [
            'facility' => $facility,
            'pending_review_count' => count($pendingReviews),
        ];
    }
}
