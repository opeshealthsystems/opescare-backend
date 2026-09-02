<?php

namespace App\Modules\Appointments\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditEvent;
use App\Models\FacilitySchedule;
use App\Models\ProviderAvailability;
use App\Modules\EncounterManagement\Services\VisitManagementService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(private VisitManagementService $visitManagementService)
    {
    }

    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $slot = null;
            $scheduledAt = $data['scheduled_at'] ?? null;

            if (! empty($data['appointment_slot_id'])) {
                $slot = AppointmentSlot::lockForUpdate()->findOrFail($data['appointment_slot_id']);
                $this->assertSlotCanBeBooked($slot);
                $scheduledAt = $slot->starts_at;
                $slot->increment('booked_count');
            }

            if (! $scheduledAt) {
                throw new Exception('APPOINTMENT_SCHEDULED_AT_REQUIRED');
            }

            $this->assertAvailability(
                $data['facility_id'],
                $data['provider_id'] ?? $slot?->provider_id,
                $scheduledAt
            );

            $appointment = Appointment::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'provider_id' => $data['provider_id'] ?? $slot?->provider_id,
                'appointment_slot_id' => $slot?->id,
                'appointment_type' => $data['appointment_type'],
                'status' => $data['status'] ?? 'scheduled',
                'scheduled_at' => $scheduledAt,
                'booked_by_type' => $data['booked_by_type'] ?? null,
                'booked_by_id' => $data['booked_by_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'billing_deferred' => true,
                'telemedicine_deferred' => true,
            ]);

            $this->audit($appointment, 'create', $data['booked_by_id'] ?? null, 'Appointment booked.');

            return $appointment->fresh();
        });
    }

    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            if (blank($data['reason'] ?? null)) {
                throw new Exception('APPOINTMENT_RESCHEDULE_REASON_REQUIRED');
            }

            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $before = $appointment->toArray();
            $appointment->update(['status' => 'rescheduled']);
            $this->releaseSlot($appointment);

            $newAppointment = $this->book([
                'patient_id' => $appointment->patient_id,
                'facility_id' => $appointment->facility_id,
                'provider_id' => $appointment->provider_id,
                'appointment_slot_id' => $data['appointment_slot_id'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'appointment_type' => $appointment->appointment_type,
                'booked_by_type' => 'reschedule',
                'booked_by_id' => $data['actor_id'] ?? null,
                'reason' => $data['reason'],
            ]);
            $newAppointment->update(['rescheduled_from_appointment_id' => $appointment->id]);

            $this->audit($appointment, 'reschedule', $data['actor_id'] ?? null, $data['reason'], $before, $appointment->fresh()->toArray());

            return $newAppointment->fresh();
        });
    }

    public function cancel(Appointment $appointment, string $reason, ?string $actorId = null): Appointment
    {
        if (blank($reason)) {
            throw new Exception('APPOINTMENT_CANCELLATION_REASON_REQUIRED');
        }

        return DB::transaction(function () use ($appointment, $reason, $actorId) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $before = $appointment->toArray();

            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by_id' => $actorId,
                'cancelled_at' => now(),
            ]);
            $this->releaseSlot($appointment);

            $this->audit($appointment, 'cancel', $actorId, $reason, $before, $appointment->fresh()->toArray());

            return $appointment->fresh();
        });
    }

    public function checkIn(Appointment $appointment, ?string $actorId = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actorId) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
                throw new Exception('APPOINTMENT_NOT_CHECK_IN_ELIGIBLE');
            }

            $before = $appointment->toArray();
            $visit = $this->visitManagementService->createVisit([
                'patient_id' => $appointment->patient_id,
                'facility_id' => $appointment->facility_id,
                'provider_id' => $appointment->provider_id,
                'visit_type' => $appointment->appointment_type,
            ], $actorId);

            $appointment->update([
                'status' => 'checked_in',
                'visit_id' => $visit->id,
                'checked_in_at' => now(),
            ]);

            $this->audit($appointment, 'check_in', $actorId, 'Appointment checked in.', $before, $appointment->fresh()->toArray());

            return $appointment->fresh();
        });
    }

    public function markNoShows(CarbonInterface $asOf, ?string $actorId = null): int
    {
        $appointments = Appointment::whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('visit_id')
            ->where('scheduled_at', '<', $asOf)
            ->get();

        foreach ($appointments as $appointment) {
            $before = $appointment->toArray();
            $appointment->update([
                'status' => 'no_show',
                'no_show_at' => now(),
            ]);
            $this->audit($appointment, 'no_show', $actorId, 'Appointment marked no-show.', $before, $appointment->fresh()->toArray());
        }

        return $appointments->count();
    }

    private function assertSlotCanBeBooked(AppointmentSlot $slot): void
    {
        if ($slot->status !== 'open' || $slot->booked_count >= $slot->capacity) {
            throw new Exception('APPOINTMENT_SLOT_FULL');
        }
    }

    private function assertAvailability(string $facilityId, ?string $providerId, mixed $scheduledAt): void
    {
        $time = Carbon::parse($scheduledAt);
        $dayOfWeek = (int) $time->dayOfWeekIso;
        $clock = $time->format('H:i:s');

        $facilityOpen = FacilitySchedule::where('facility_id', $facilityId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('opens_at', '<=', $clock)
            ->where('closes_at', '>=', $clock)
            ->exists();

        if (! $facilityOpen) {
            throw new Exception('FACILITY_NOT_AVAILABLE_FOR_APPOINTMENT');
        }

        if (! $providerId) {
            return;
        }

        $providerOpen = ProviderAvailability::where('facility_id', $facilityId)
            ->where('provider_id', $providerId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('starts_at', '<=', $clock)
            ->where('ends_at', '>=', $clock)
            ->exists();

        if (! $providerOpen) {
            throw new Exception('PROVIDER_NOT_AVAILABLE_FOR_APPOINTMENT');
        }
    }

    private function releaseSlot(Appointment $appointment): void
    {
        if (! $appointment->appointment_slot_id) {
            return;
        }

        AppointmentSlot::where('id', $appointment->appointment_slot_id)
            ->where('booked_count', '>', 0)
            ->decrement('booked_count');
    }

    private function audit(Appointment $appointment, string $action, ?string $actorId, ?string $reason, ?array $before = null, ?array $after = null): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $appointment->facility_id,
            'patient_id' => $appointment->patient_id,
            'encounter_id' => $appointment->visit_id,
            'action_type' => $action,
            'resource_type' => 'appointment',
            'resource_id' => $appointment->id,
            'reason' => $reason,
            'before_state' => $before,
            'after_state' => $after ?? $appointment->toArray(),
        ]);
    }
}
