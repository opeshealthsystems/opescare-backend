<?php
namespace App\Modules\Appointments\Services;

use App\Jobs\BackfillWaitlistJob;
use App\Models\WaitlistEntry;

class WaitlistService
{
    public function addToWaitlist(
        string  $patientId,
        string  $providerId,
        string  $facilityId,
        array   $preferredDates,
        ?string $reason = null,
    ): WaitlistEntry {
        return WaitlistEntry::create([
            'patient_id'      => $patientId,
            'provider_id'     => $providerId,
            'facility_id'     => $facilityId,
            'preferred_dates' => $preferredDates,
            'reason'          => $reason,
            'status'          => 'waiting',
        ]);
    }

    public function triggerBackfill(string $providerId, string $facilityId, string $date): void
    {
        BackfillWaitlistJob::dispatch($providerId, $facilityId, $date);
    }
}
