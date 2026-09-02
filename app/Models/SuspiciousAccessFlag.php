<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SuspiciousAccessFlag — Security Operations
 *
 * Raised by anomaly-detection logic when a user's access pattern
 * deviates from expected norms (bulk download, off-hours access, etc.).
 *
 * Security: flags must be reviewed by a security officer within the
 * SLA defined for the flag's severity. Critical flags block the user
 * account pending review.
 */
class SuspiciousAccessFlag extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'flag_type',      // bulk_download|off_hours_access|unusual_patient_count|rapid_succession
        'severity',       // low|medium|high|critical
        'evidence',
        'status',         // open|reviewed|dismissed|escalated
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'evidence'    => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function review(string $reviewedBy, string $newStatus, ?string $notes = null): void
    {
        $this->update([
            'status'       => $newStatus,
            'reviewed_by'  => $reviewedBy,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    public function escalate(string $reviewedBy): void
    {
        $this->review($reviewedBy, 'escalated');
    }

    public function dismiss(string $reviewedBy, string $notes): void
    {
        $this->review($reviewedBy, 'dismissed', $notes);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
