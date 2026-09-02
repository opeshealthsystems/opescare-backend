<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ComplianceCase — Module 15 (Security, Audit & Compliance Operations)
 *
 * Tracks compliance incidents including data breaches, policy violations,
 * audit findings, and regulatory issues.
 *
 * Security constraint: All compliance cases must be auditable.
 * Financial compliance cases must additionally reference FinancialAudit.
 */
class ComplianceCase extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'case_type',             // data_breach|policy_violation|audit_finding|regulatory|patient_complaint
        'severity',              // low|medium|high|critical
        'status',                // open|under_review|remediation|closed|escalated
        'description',
        'reported_by',
        'assigned_to',
        'closed_by',
        'remediation_plan',
        'resolution_notes',
        'due_date',
        'closed_at',
    ];

    protected $casts = [
        'due_date'  => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function close(string $closedBy, ?string $resolutionNotes = null): void
    {
        $this->update([
            'status'           => 'closed',
            'closed_by'        => $closedBy,
            'resolution_notes' => $resolutionNotes ?? $this->resolution_notes,
            'closed_at'        => now(),
        ]);
    }

    public function escalate(): void
    {
        $this->update(['status' => 'escalated']);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'under_review', 'remediation', 'escalated']);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'open'          => 'badge badge--danger',
            'under_review'  => 'badge badge--warning',
            'remediation'   => 'badge badge--info',
            'closed'        => 'badge badge--success',
            'escalated'     => 'badge badge--danger',
            default         => 'badge badge--neutral',
        };
    }

    public function severityBadgeClass(): string
    {
        return match($this->severity) {
            'critical' => 'badge badge--danger',
            'high'     => 'badge badge--warning',
            'medium'   => 'badge badge--info',
            'low'      => 'badge badge--neutral',
            default    => 'badge badge--neutral',
        };
    }
}
