<?php
namespace App\Services\Appointments;

use App\Models\AppointmentWaitlist;
use Carbon\Carbon;

class WaitlistService
{
    public function addToWaitlist(array $data): AppointmentWaitlist
    {
        $data['status'] = $data['status'] ?? 'waiting';
        return AppointmentWaitlist::create($data);
    }

    public function notifyNextInLine(string $facilityId, string $providerId, Carbon $slotTime): ?AppointmentWaitlist
    {
        $entry = AppointmentWaitlist::where('facility_id', $facilityId)
            ->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhereNull('provider_id');
            })
            ->where('status', 'waiting')
            ->where(function ($q) use ($slotTime) {
                $q->whereNull('preferred_earliest_date')
                    ->orWhere('preferred_earliest_date', '<=', $slotTime->toDateString());
            })
            ->where(function ($q) use ($slotTime) {
                $q->whereNull('preferred_latest_date')
                    ->orWhere('preferred_latest_date', '>=', $slotTime->toDateString());
            })
            ->orderByRaw("CASE urgency WHEN 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->first();

        if ($entry === null) {
            return null;
        }

        $entry->update(['status' => 'notified', 'notified_at' => now()]);

        return $entry->fresh();
    }

    public function bookFromWaitlist(string $waitlistId, string $appointmentId): AppointmentWaitlist
    {
        $entry = AppointmentWaitlist::findOrFail($waitlistId);
        $entry->update(['status' => 'booked', 'booked_appointment_id' => $appointmentId]);
        return $entry->fresh();
    }

    public function expireOldEntries(): int
    {
        return AppointmentWaitlist::where('status', 'waiting')
            ->whereNotNull('preferred_latest_date')
            ->where('preferred_latest_date', '<', Carbon::today()->toDateString())
            ->update(['status' => 'expired']);
    }
}
