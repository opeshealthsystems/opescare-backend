<?php

namespace App\Services\Facility;

use App\Models\CareFacility;
use App\Models\FacilityRoleAssignment;
use App\Models\HealthIdVerificationEvent;
use App\Models\User;

/**
 * The facility admin's "Get started" checklist on /admin. Every step is read
 * from real rows; nothing is ticked on the admin's behalf.
 *
 *   team     — at least one OTHER user holds an active posting here
 *              (an accepted invite or approved join request creates one);
 *   profile  — the public listing is linked and has a phone and map position;
 *   verify   — a Health ID verification was recorded by this facility or by
 *              someone posted here.
 */
class FacilityAdminChecklist
{
    /** @return array<string,bool> */
    public static function for(string $facilityId, User $admin): array
    {
        $staffIds = FacilityRoleAssignment::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true)
            ->pluck('user_id');

        $listing = CareFacility::where('facility_id', $facilityId)->first();

        return [
            'team'    => $staffIds->contains(fn ($id) => (string) $id !== (string) $admin->id),
            'profile' => $listing !== null
                && ! in_array(strtoupper(trim((string) $listing->phone_primary)), ['', 'N/A'], true)
                && $listing->latitude !== null
                && $listing->longitude !== null,
            'verify'  => HealthIdVerificationEvent::query()
                ->where(fn ($q) => $q->where('verified_by_facility_id', $facilityId)
                    ->orWhereIn('verified_by_user_id', $staffIds->push($admin->id)->all()))
                ->exists(),
        ];
    }
}
