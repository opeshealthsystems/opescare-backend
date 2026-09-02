<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * BreachReport — Security Operations / GDPR / HIPAA
 *
 * Documents a data-breach incident from discovery through containment
 * and regulatory notification.
 *
 * Security: breach reports must be created immediately upon discovery.
 * Regulatory timelines (e.g. 72-hour GDPR notification) are tracked here.
 */
class BreachReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'description',
        'breach_type',                   // unauthorized_access|data_leak|ransomware|lost_device|insider_threat
        'severity',                      // low|medium|high|critical
        'status',                        // open|investigating|contained|notified|closed
        'discovered_at',
        'contained_at',
        'reported_to_authority_at',
        'estimated_affected_records',
        'affected_data_types',
        'reported_by',
        'assigned_to',
    ];

    protected $casts = [
        'discovered_at'              => 'datetime',
        'contained_at'               => 'datetime',
        'reported_to_authority_at'   => 'datetime',
        'affected_data_types'        => 'array',
        'estimated_affected_records' => 'integer',
    ];

    public function contain(string $assignedTo): void
    {
        $this->update(['status' => 'contained', 'contained_at' => now(), 'assigned_to' => $assignedTo]);
    }

    public function markNotified(): void
    {
        $this->update(['status' => 'notified', 'reported_to_authority_at' => now()]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function requiresRegulatoryNotification(): bool
    {
        // 72-hour window from discovery (e.g. GDPR Art. 33)
        return $this->discovered_at !== null
            && $this->reported_to_authority_at === null
            && $this->discovered_at->diffInHours(now()) >= 72;
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating']);
    }
}
