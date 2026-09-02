<?php

namespace App\Services\Lab;

use App\Models\CriticalValueAcknowledgement;
use App\Models\CriticalValueAlert;
use App\Models\LabResult;
use Illuminate\Support\Collection;

class CriticalValueService
{
    private const CRITICAL_FLAGS = ['HH', 'LL'];

    /**
     * Record that a critical lab value was communicated to a clinician.
     * Call this immediately after a critical result is resulted.
     */
    public function recordNotification(
        string $labResultId,
        string $notifiedBy,
        string $notificationMethod = 'phone',
        ?string $notifiedRecipient = null
    ): CriticalValueAcknowledgement {
        $result = LabResult::with('labOrder')->findOrFail($labResultId);

        if (!in_array($result->flag, self::CRITICAL_FLAGS, true)) {
            throw new \InvalidArgumentException(
                "Lab result {$labResultId} is not a critical value (flag: {$result->flag})"
            );
        }

        return CriticalValueAcknowledgement::create([
            'lab_result_id'       => $result->id,
            'patient_id'          => $result->patient_id,
            'facility_id'         => $result->labOrder?->facility_id
                                     ?? throw new \RuntimeException('Lab order not found for result'),
            'flag'                => $result->flag,
            'test_name'           => $result->parameter_name,
            'value'               => (string) $result->value,
            'unit'                => $result->unit,
            'notified_by'         => $notifiedBy,
            'notified_at'         => now(),
            'notification_method' => $notificationMethod,
            'notified_recipient'  => $notifiedRecipient,
        ]);
    }

    /**
     * Record that the clinician acknowledged the critical value notification.
     */
    public function acknowledge(
        string $acknowledgementId,
        string $acknowledgedBy,
        bool $isReadBack = false,
        ?string $notes = null
    ): CriticalValueAcknowledgement {
        $ack = CriticalValueAcknowledgement::findOrFail($acknowledgementId);

        if ($ack->acknowledged_at !== null) {
            return $ack; // Already acknowledged — idempotent
        }

        $ack->update([
            'acknowledged_by'       => $acknowledgedBy,
            'acknowledged_at'       => now(),
            'is_read_back'          => $isReadBack,
            'acknowledgement_notes' => $notes,
        ]);

        return $ack->fresh();
    }

    /**
     * Get unacknowledged critical values for a facility, ordered by oldest first.
     */
    public function getPendingForFacility(string $facilityId): Collection
    {
        return CriticalValueAcknowledgement::where('facility_id', $facilityId)
            ->whereNull('acknowledged_at')
            ->orderBy('notified_at')
            ->with(['labResult', 'patient', 'notifiedBy'])
            ->get();
    }

    /**
     * Get all critical value records for a patient.
     */
    public function getForPatient(string $patientId, int $limit = 20): Collection
    {
        return CriticalValueAcknowledgement::where('patient_id', $patientId)
            ->orderBy('notified_at', 'desc')
            ->limit($limit)
            ->with(['labResult', 'notifiedBy', 'acknowledgedBy'])
            ->get();
    }

    /**
     * Get turnaround stats for a facility.
     * Returns avg minutes from notification to acknowledgement.
     */
    public function getTurnaroundStats(string $facilityId): array
    {
        $records = CriticalValueAcknowledgement::where('facility_id', $facilityId)
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (acknowledged_at - notified_at))/60) as avg_minutes')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_read_back THEN 1 ELSE 0 END) as read_back_count')
            ->first();

        return [
            'avg_acknowledgement_minutes' => round($records->avg_minutes ?? 0, 1),
            'total_acknowledged'           => (int) ($records->total ?? 0),
            'read_back_rate'               => $records->total > 0
                ? round($records->read_back_count / $records->total * 100, 1)
                : 0,
        ];
    }

    // ── LOINC-based critical value evaluation ───────────────────────────────

    private array $loincThresholds = [
        '2823-3' => ['critical_low' => 3.0,  'critical_high' => 6.5,  'unit' => 'mmol/L'], // K+
        '2951-2' => ['critical_low' => 120.0, 'critical_high' => 160.0,'unit' => 'mmol/L'], // Na+
        '2345-7' => ['critical_low' => 2.2,   'critical_high' => 22.2, 'unit' => 'mmol/L'], // Glucose
        '718-7'  => ['critical_low' => 70.0,  'critical_high' => null, 'unit' => 'g/L'],    // Hb
    ];

    public function evaluateResult(LabResult $result): ?CriticalValueAlert
    {
        $threshold = $this->loincThresholds[$result->loinc_code ?? ''] ?? null;
        if (!$threshold) {
            return null;
        }

        $value     = (float) ($result->value ?? 0);
        $alertType = null;
        $thresholdValue = null;

        if ($threshold['critical_low'] !== null && $value < $threshold['critical_low']) {
            $alertType      = 'critical_low';
            $thresholdValue = $threshold['critical_low'];
        } elseif ($threshold['critical_high'] !== null && $value > $threshold['critical_high']) {
            $alertType      = 'critical_high';
            $thresholdValue = $threshold['critical_high'];
        }

        if (!$alertType) {
            return null;
        }

        return CriticalValueAlert::create([
            'lab_result_id'      => $result->id,
            'patient_id'         => $result->patient_id,
            'alert_type'         => $alertType,
            'test_name'          => $result->parameter_name,
            'result_value'       => $result->value . ' ' . $result->unit,
            'critical_threshold' => $thresholdValue . ' ' . $threshold['unit'],
            'acknowledged'       => false,
        ]);
    }

    public function acknowledgeAlert(string $alertId, string $providerId, string $note = ''): CriticalValueAlert
    {
        $alert = CriticalValueAlert::findOrFail($alertId);
        $alert->update([
            'acknowledged'         => true,
            'acknowledged_by'      => $providerId,
            'acknowledged_at'      => now(),
            'acknowledgement_note' => $note,
        ]);
        return $alert;
    }
}
