<?php

namespace App\Modules\OperationalFlow\Services;

use App\Models\ClinicalAlert;
use App\Models\ClinicalNote;
use App\Models\QueueTicket;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * VisitManagementService
 *
 * Manages visit lifecycle: create, status transitions, and closure.
 * Statuses: open → in_triage → in_consultation → awaiting_billing → awaiting_discharge → completed
 */
class VisitManagementService
{
    /** Valid status transitions */
    private const TRANSITIONS = [
        'open'                 => ['in_triage', 'in_consultation', 'awaiting_billing', 'awaiting_discharge', 'completed', 'cancelled'],
        'in_triage'            => ['in_consultation', 'awaiting_billing', 'awaiting_discharge', 'completed', 'cancelled'],
        'in_consultation'      => ['awaiting_lab', 'awaiting_billing', 'awaiting_pharmacy', 'awaiting_discharge', 'completed', 'cancelled'],
        'awaiting_lab'         => ['in_consultation', 'awaiting_billing', 'awaiting_discharge', 'completed', 'cancelled'],
        'awaiting_pharmacy'    => ['awaiting_billing', 'awaiting_discharge', 'completed', 'cancelled'],
        'awaiting_billing'     => ['awaiting_discharge', 'completed', 'cancelled'],
        'awaiting_discharge'   => ['completed', 'cancelled'],
        'completed'            => [],
        'cancelled'            => [],
        'abandoned'            => [],
    ];

    /** Queue-ticket statuses that count as "still open" (block visit closure). */
    private const OPEN_QUEUE_STATUSES = ['waiting', 'called', 'service_started'];

    /** Visit types that do NOT require a consultation note before closing. */
    private const NO_CONSULT_NOTE_TYPES = ['lab-only', 'pharmacy-only'];

    /**
     * Create a new visit.
     */
    public function createVisit(array $data): Visit
    {
        return Visit::create([
            'patient_id'  => $data['patient_id'],
            'facility_id' => $data['facility_id'],
            'provider_id' => $data['provider_id'] ?? null,
            'visit_type'  => $data['visit_type'] ?? 'general',
            'status'      => 'open',
            'started_at'  => now(),
        ]);
    }

    /**
     * Advance visit to a new status.
     */
    public function transition(string $visitId, string $newStatus, string $actorId): Visit
    {
        return DB::transaction(function () use ($visitId, $newStatus, $actorId) {
            $visit = Visit::findOrFail($visitId);

            $allowed = self::TRANSITIONS[$visit->status] ?? [];
            if (!in_array($newStatus, $allowed)) {
                throw new \Exception("VISIT_INVALID_TRANSITION: {$visit->status} → {$newStatus}");
            }

            $updates = ['status' => $newStatus];

            if ($newStatus === 'completed') {
                $this->assertCompletable($visit);
                $updates['ended_at'] = now();
            }

            $visit->update($updates);

            return $visit->fresh();
        });
    }

    /**
     * Close/complete a visit.
     */
    public function complete(string $visitId, string $actorId): Visit
    {
        return DB::transaction(function () use ($visitId) {
            $visit = Visit::findOrFail($visitId);

            if ($visit->status === 'completed') {
                return $visit;
            }

            $this->assertCompletable($visit);

            $visit->update([
                'status'   => 'completed',
                'ended_at' => now(),
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Patient-safety guards that must pass before a visit can be completed.
     * (GAP-006) Throws with a VISIT_BLOCKED_* code on the first failing guard.
     */
    private function assertCompletable(Visit $visit): void
    {
        // 1. No unacknowledged critical clinical alert may remain open.
        $hasActiveCriticalAlert = ClinicalAlert::where('visit_id', $visit->id)
            ->where('severity', 'critical')
            ->where('status', 'active')
            ->exists();
        if ($hasActiveCriticalAlert) {
            throw new \RuntimeException(
                'VISIT_BLOCKED_CRITICAL_ALERT: resolve or acknowledge the critical clinical alert before completing this visit.'
            );
        }

        // 2. Consultation-bearing visits require a consultation note.
        if (! in_array($visit->visit_type, self::NO_CONSULT_NOTE_TYPES, true)) {
            $hasConsultNote = ClinicalNote::where('visit_id', $visit->id)->exists();
            if (! $hasConsultNote) {
                throw new \RuntimeException(
                    'VISIT_BLOCKED_NO_CONSULT_NOTE: record a consultation note before completing this visit.'
                );
            }
        }

        // 3. No queue ticket may still be open for this visit.
        $hasOpenQueueTicket = QueueTicket::where('visit_id', $visit->id)
            ->whereIn('status', self::OPEN_QUEUE_STATUSES)
            ->exists();
        if ($hasOpenQueueTicket) {
            throw new \RuntimeException(
                'VISIT_BLOCKED_OPEN_QUEUE_TICKET: close the open queue ticket before completing this visit.'
            );
        }
    }

    /**
     * Cancel a visit.
     */
    public function cancel(string $visitId, string $actorId): Visit
    {
        $visit = Visit::findOrFail($visitId);

        if (in_array($visit->status, ['completed', 'cancelled'])) {
            throw new \Exception('VISIT_NOT_CANCELLABLE');
        }

        $visit->update([
            'status'   => 'cancelled',
            'ended_at' => now(),
        ]);

        return $visit->fresh();
    }

    /**
     * Get allowed next statuses for a visit.
     */
    public function allowedTransitions(Visit $visit): array
    {
        return self::TRANSITIONS[$visit->status] ?? [];
    }
}
