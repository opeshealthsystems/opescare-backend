<?php
namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Facility;
use App\Models\User;
use App\Support\Notify;
use Carbon\Carbon;

/**
 * Waitlist backed by `appointment_waitlists` (App\Models\AppointmentWaitlist).
 *
 * Not to be confused with App\Modules\Appointments\Services\WaitlistService,
 * which writes `waitlist_entries` and serves the /api/v1 appointment routes.
 * The two tables are different and are deliberately not merged.
 */
class AppointmentWaitlistService
{
    public function addToWaitlist(array $data): AppointmentWaitlist
    {
        $data['status'] = $data['status'] ?? 'waiting';
        return AppointmentWaitlist::create($data);
    }

    /**
     * The next patient to tell about a freed slot. `$providerId` may be null
     * (the freed appointment had no provider): then only entries with no
     * provider preference qualify — a patient waiting for Dr X is not offered
     * a slot with nobody in particular. A blank string is treated as null.
     */
    public function notifyNextInLine(string $facilityId, ?string $providerId, Carbon $slotTime): ?AppointmentWaitlist
    {
        $providerId = ($providerId === '' || $providerId === null) ? null : $providerId;

        $entry = AppointmentWaitlist::where('facility_id', $facilityId)
            ->where(function ($q) use ($providerId) {
                if ($providerId !== null) {
                    $q->where('provider_id', $providerId)
                        ->orWhereNull('provider_id');
                } else {
                    $q->whereNull('provider_id');
                }
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

    /**
     * A freed slot: find the next patient in line at this facility and tell
     * them in-app. Called from both cancel paths (staff desk, patient portal)
     * inside a rescue(), so a notification failure never undoes the cancel.
     * Returns the entry that was notified, if any.
     */
    public function announceFreedSlot(Appointment $appointment): ?AppointmentWaitlist
    {
        if (! $appointment->facility_id || ! $appointment->scheduled_at) {
            return null;
        }

        $slotTime = Carbon::parse($appointment->scheduled_at);
        $entry    = $this->notifyNextInLine((string) $appointment->facility_id, $appointment->provider_id, $slotTime);

        if ($entry === null) {
            return null;
        }

        $facilityName = Facility::whereKey($appointment->facility_id)->value('name') ?? '';
        $userId       = User::where('patient_id', $entry->patient_id)->value('id');

        if ($userId) {
            $when = $slotTime->isoFormat('ddd D MMM YYYY, HH:mm');
            Notify::toUser(
                userId:       (string) $userId,
                eventType:    'waitlist_slot_opened',
                category:     'appointment',
                payload:      [
                    'title'   => __('waitlist.notify_title'),
                    'message' => __('waitlist.notify_body', ['facility' => $facilityName, 'date' => $when]),
                    'url'     => route('portals.patient.appointments.book'),
                ],
                priority:     $entry->urgency === 'urgent' ? 'high' : 'normal',
                resourceType: 'AppointmentWaitlist',
                resourceId:   (string) $entry->id,
            );
        }

        return $entry;
    }

    public function expireOldEntries(): int
    {
        return AppointmentWaitlist::where('status', 'waiting')
            ->whereNotNull('preferred_latest_date')
            ->where('preferred_latest_date', '<', Carbon::today()->toDateString())
            ->update(['status' => 'expired']);
    }
}
