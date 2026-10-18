<?php

namespace App\Services\Documents;

use App\Models\ProfessionalLicense;
use App\Models\StaffProfile;
use App\Models\User;

/**
 * Who signed a document, as the record knows them: name, profession / job
 * title, and professional registration (licence) number.
 *
 * Shared by every printed document that names a responsible clinician. Nothing
 * is invented: a user with no staff profile comes back as a name alone, and an
 * unknown user as nulls, which the sheet prints as "not recorded".
 */
class ClinicianCredential
{
    /** @return array{name: ?string, credential: ?string, licence: ?string} */
    public static function for(int|string|null $userId): array
    {
        if ($userId === null || $userId === '') {
            return ['name' => null, 'credential' => null, 'licence' => null];
        }

        $name  = User::find($userId)?->name;
        $staff = StaffProfile::where('user_id', $userId)->first();

        if (! $staff) {
            return ['name' => $name ?: null, 'credential' => null, 'licence' => null];
        }

        $license = ProfessionalLicense::where('staff_profile_id', $staff->id)
            ->orderByDesc('created_at')
            ->first();

        return [
            'name'       => $name ?: null,
            'credential' => $license?->profession ?: ($staff->job_title ?: null),
            'licence'    => $license?->license_number ?: null,
        ];
    }
}
