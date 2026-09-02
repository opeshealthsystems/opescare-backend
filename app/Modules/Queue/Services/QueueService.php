<?php

namespace App\Modules\Queue\Services;

use App\Models\AuditEvent;
use App\Models\FacilityQueue;
use App\Models\PatientCheckIn;
use App\Models\PatientFlowEvent;
use App\Models\QueueTicket;
use App\Modules\EncounterManagement\Services\VisitManagementService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueService
{
    public function __construct(private VisitManagementService $visitManagementService)
    {
    }

    public function checkInWalkIn(array $data): QueueTicket
    {
        return DB::transaction(function () use ($data) {
            $this->assertQueueExists($data['facility_id'], $data['destination_queue']);

            $visit = $data['visit_id'] ?? null;
            if (! $visit) {
                $visit = $this->visitManagementService->createVisit([
                    'patient_id' => $data['patient_id'],
                    'facility_id' => $data['facility_id'],
                    'provider_id' => $data['provider_id'] ?? null,
                    'visit_type' => $data['visit_type'] ?? 'outpatient',
                ], $data['actor_id'] ?? null)->id;
            }

            $checkIn = PatientCheckIn::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'visit_id' => $visit,
                'appointment_id' => $data['appointment_id'] ?? null,
                'checked_in_by_id' => $data['actor_id'] ?? null,
                'check_in_type' => $data['check_in_type'] ?? 'walk_in',
                'checked_in_at' => now(),
            ]);

            $ticket = QueueTicket::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'visit_id' => $visit,
                'appointment_id' => $data['appointment_id'] ?? null,
                'patient_check_in_id' => $checkIn->id,
                'queue_number' => $this->nextQueueNumber($data['facility_id'], $data['destination_queue']),
                'current_queue' => $data['destination_queue'],
                'status' => 'waiting',
                'priority_level' => $data['priority_level'] ?? 5,
                'checked_in_at' => now(),
            ]);

            $this->flow($ticket, 'checked_in', null, $ticket->current_queue, null, 'waiting', 'Patient checked in.', $data['actor_id'] ?? null);
            $this->audit($ticket, 'create', $data['actor_id'] ?? null, 'Queue ticket created.');

            return $ticket->fresh();
        });
    }

    public function prioritize(QueueTicket $ticket, string $status, string $reason, ?string $actorId = null): QueueTicket
    {
        if ($status !== 'emergency_priority') {
            throw new Exception('QUEUE_PRIORITY_STATUS_INVALID');
        }

        if (blank($reason)) {
            throw new Exception('QUEUE_PRIORITY_REASON_REQUIRED');
        }

        return DB::transaction(function () use ($ticket, $status, $reason, $actorId) {
            $ticket = QueueTicket::lockForUpdate()->findOrFail($ticket->id);
            $beforeStatus = $ticket->status;
            $ticket->update([
                'status' => $status,
                'priority_level' => 0,
                'priority_reason' => $reason,
            ]);

            $this->flow($ticket, 'priority_changed', $ticket->current_queue, $ticket->current_queue, $beforeStatus, $status, $reason, $actorId);
            $this->audit($ticket, 'prioritize', $actorId, $reason);

            return $ticket->fresh();
        });
    }

    public function callNext(string $facilityId, string $queueName, ?string $actorId = null): QueueTicket
    {
        return DB::transaction(function () use ($facilityId, $queueName, $actorId) {
            $ticket = QueueTicket::lockForUpdate()
                ->where('facility_id', $facilityId)
                ->where('current_queue', $queueName)
                ->whereIn('status', ['waiting', 'emergency_priority'])
                ->orderBy('priority_level')
                ->orderBy('checked_in_at')
                ->first();

            if (! $ticket) {
                throw new Exception('QUEUE_EMPTY');
            }

            $beforeStatus = $ticket->status;
            $ticket->update([
                'status' => 'called',
                'called_at' => now(),
            ]);

            $this->flow($ticket, 'called', $queueName, $queueName, $beforeStatus, 'called', 'Patient called.', $actorId);
            $this->audit($ticket, 'call', $actorId, 'Queue ticket called.');

            return $ticket->fresh();
        });
    }

    public function startService(QueueTicket $ticket, ?string $actorId = null): QueueTicket
    {
        return $this->transition($ticket, 'service_started', 'in_service', 'Service started.', $actorId, [
            'service_started_at' => now(),
            'assigned_to_id' => $actorId,
        ]);
    }

    public function transfer(QueueTicket $ticket, string $toQueue, string $reason, ?string $actorId = null): QueueTicket
    {
        if (blank($reason)) {
            throw new Exception('QUEUE_TRANSFER_REASON_REQUIRED');
        }

        return DB::transaction(function () use ($ticket, $toQueue, $reason, $actorId) {
            $ticket = QueueTicket::lockForUpdate()->findOrFail($ticket->id);
            $this->assertQueueExists($ticket->facility_id, $toQueue);

            $fromQueue = $ticket->current_queue;
            $fromStatus = $ticket->status;
            $ticket->update([
                'current_queue' => $toQueue,
                'status' => 'waiting',
                'status_reason' => $reason,
                'priority_level' => 5,
            ]);

            $this->flow($ticket, 'transferred', $fromQueue, $toQueue, $fromStatus, 'waiting', $reason, $actorId);
            $this->audit($ticket, 'transfer', $actorId, $reason);

            return $ticket->fresh();
        });
    }

    public function complete(QueueTicket $ticket, string $reason, ?string $actorId = null): QueueTicket
    {
        return $this->transition($ticket, 'completed', 'completed', $reason, $actorId, [
            'completed_at' => now(),
            'status_reason' => $reason,
        ]);
    }

    public function cancel(QueueTicket $ticket, string $reason, ?string $actorId = null): QueueTicket
    {
        if (blank($reason)) {
            throw new Exception('QUEUE_CANCELLATION_REASON_REQUIRED');
        }

        return $this->transition($ticket, 'cancelled', 'cancelled', $reason, $actorId, [
            'cancelled_at' => now(),
            'status_reason' => $reason,
        ]);
    }

    public function maskedDisplay(string $facilityId, ?string $queueName = null)
    {
        return QueueTicket::query()
            ->with('patient')
            ->where('facility_id', $facilityId)
            ->when($queueName, fn ($query) => $query->where('current_queue', $queueName))
            ->whereIn('status', ['waiting', 'called', 'emergency_priority'])
            ->orderBy('priority_level')
            ->orderBy('checked_in_at')
            ->get()
            ->map(fn (QueueTicket $ticket) => [
                'queue_number' => $ticket->queue_number,
                'current_queue' => $ticket->current_queue,
                'status' => $ticket->status,
                'masked_patient_name' => $this->maskName($ticket->patient?->first_name, $ticket->patient?->last_name),
            ]);
    }

    private function transition(QueueTicket $ticket, string $eventType, string $toStatus, string $reason, ?string $actorId, array $updates): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $eventType, $toStatus, $reason, $actorId, $updates) {
            $ticket = QueueTicket::lockForUpdate()->findOrFail($ticket->id);
            $fromStatus = $ticket->status;
            $ticket->update(array_merge(['status' => $toStatus], $updates));

            $this->flow($ticket, $eventType, $ticket->current_queue, $ticket->current_queue, $fromStatus, $toStatus, $reason, $actorId);
            $this->audit($ticket, $eventType === 'cancelled' ? 'cancel' : $eventType, $actorId, $reason);

            return $ticket->fresh();
        });
    }

    private function assertQueueExists(string $facilityId, string $queueName): void
    {
        $exists = FacilityQueue::where('facility_id', $facilityId)
            ->where('name', $queueName)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new Exception('FACILITY_QUEUE_NOT_AVAILABLE');
        }
    }

    private function nextQueueNumber(string $facilityId, string $queueName): string
    {
        $prefix = Str::upper(Str::substr($queueName, 0, 3));
        $count = QueueTicket::where('facility_id', $facilityId)
            ->whereDate('checked_in_at', now()->toDateString())
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    private function flow(QueueTicket $ticket, string $eventType, ?string $fromQueue, ?string $toQueue, ?string $fromStatus, ?string $toStatus, ?string $reason, ?string $actorId): void
    {
        PatientFlowEvent::create([
            'queue_ticket_id' => $ticket->id,
            'patient_id' => $ticket->patient_id,
            'facility_id' => $ticket->facility_id,
            'visit_id' => $ticket->visit_id,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'from_queue' => $fromQueue,
            'to_queue' => $toQueue,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }

    private function audit(QueueTicket $ticket, string $action, ?string $actorId, ?string $reason): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $ticket->facility_id,
            'patient_id' => $ticket->patient_id,
            'encounter_id' => $ticket->visit_id,
            'action_type' => $action,
            'resource_type' => 'queue_ticket',
            'resource_id' => $ticket->id,
            'reason' => $reason,
            'after_state' => $ticket->toArray(),
        ]);
    }

    private function maskName(?string $firstName, ?string $lastName): string
    {
        $mask = fn (?string $value) => $value ? Str::substr($value, 0, 1).'***' : '***';

        return trim($mask($firstName).' '.$mask($lastName));
    }
}
