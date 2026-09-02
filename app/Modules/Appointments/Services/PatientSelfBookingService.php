<?php
namespace App\Modules\Appointments\Services;

use App\Models\Appointment;
use App\Models\ProviderAvailability;
use Carbon\Carbon;

class PatientSelfBookingService
{
    public function bookSlot(
        string $patientId,
        string $providerId,
        string $facilityId,
        Carbon $dateTime,
        string $reason,
    ): Appointment {
        $dayOfWeek = $dateTime->dayOfWeekIso;
        $timeStr   = $dateTime->format('H:i:s');

        $availability = ProviderAvailability::where('provider_id', $providerId)
            ->where('facility_id', $facilityId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('starts_at', '<=', $timeStr)
            ->where('ends_at', '>', $timeStr)
            ->exists();

        if (!$availability) {
            throw new \Exception('SLOT_OUTSIDE_AVAILABILITY');
        }

        $conflict = Appointment::where('provider_id', $providerId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('scheduled_at', $dateTime->toDateString())
            ->whereTime('scheduled_at', $dateTime->format('H:i:s'))
            ->exists();

        if ($conflict) {
            throw new \Exception('SLOT_ALREADY_BOOKED');
        }

        return Appointment::create([
            'patient_id'       => $patientId,
            'provider_id'      => $providerId,
            'facility_id'      => $facilityId,
            'scheduled_at'     => $dateTime,
            'appointment_type' => 'self_booked',
            'reason'           => $reason,
            'status'           => 'scheduled',
            'booked_by_type'   => 'patient',
            'booked_by_id'     => $patientId,
        ]);
    }
}
