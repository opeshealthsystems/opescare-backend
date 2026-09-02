<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DischargePlan — Module 19 (Ward / Admission / Bed Management)
 *
 * Structured discharge planning document created during inpatient stay.
 * Must be approved before a patient is discharged.
 *
 * Covers target date, disposition, follow-up plan, medication reconciliation,
 * patient education, and social support assessment.
 */
class DischargePlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'planned_by',
        'target_discharge_date',
        'discharge_disposition',       // home|rehab|ltc|another_facility|expired
        'discharge_criteria',
        'follow_up_plan',
        'medication_reconciliation_notes',
        'patient_education_notes',
        'social_support_assessed',
        'transport_arranged',
        'status',                       // draft|ready|approved|completed|cancelled
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'target_discharge_date'   => 'date',
        'social_support_assessed' => 'boolean',
        'transport_arranged'      => 'boolean',
        'approved_at'             => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReadyForDischarge(): bool
    {
        return in_array($this->status, ['approved', 'completed']);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'     => 'badge badge--neutral',
            'ready'     => 'badge badge--info',
            'approved'  => 'badge badge--success',
            'completed' => 'badge badge--success',
            'cancelled' => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
