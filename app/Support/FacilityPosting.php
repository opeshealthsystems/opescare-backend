<?php

namespace App\Support;

use App\Models\User;
use App\Services\Portal\FacilityMembershipService;
use Illuminate\Validation\ValidationException;

/**
 * "This person is posted at this facility" — the check every partner-API write
 * needs before it attributes a clinical act (ordering, reporting, prescribing,
 * signing) to a named user. Without it a client bound to facility A could
 * stamp any clinician in the country onto a record.
 */
final class FacilityPosting
{
    public static function isPostedAt(?string $userId, ?string $facilityId): bool
    {
        if (! $userId || ! $facilityId) {
            return false;
        }

        $user = User::find($userId);
        if (! $user) {
            return false;
        }

        $postings = app(FacilityMembershipService::class)->facilityIdsFor($user);

        return is_array($postings) && in_array((string) $facilityId, array_map('strval', $postings), true);
    }

    /** @throws ValidationException */
    public static function assert(?string $userId, ?string $facilityId, string $field): void
    {
        if (! self::isPostedAt($userId, $facilityId)) {
            throw ValidationException::withMessages([
                $field => __('api.radiology_ordered_by_not_facility_staff'),
            ]);
        }
    }
}
