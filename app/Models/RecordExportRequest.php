<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RecordExportRequest — Patient Rights & Data Governance (GDPR Article 20)
 *
 * A patient's request to receive a copy of their personal data in a
 * portable, machine-readable format (data portability right).
 *
 * Regulatory timeline: must be fulfilled within 30 days (GDPR) or applicable
 * national equivalent. response_due_date is set at creation.
 */
class RecordExportRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'request_type',         // full|partial|specific_date_range
        'requested_sections',
        'format',               // pdf|json|csv
        'status',               // pending|processing|ready|expired|failed
        'file_path',
        'response_due_date',
        'fulfilled_at',
        'expires_at',
        'handled_by',
    ];

    protected $casts = [
        'requested_sections' => 'array',
        'response_due_date'  => 'datetime',
        'fulfilled_at'       => 'datetime',
        'expires_at'         => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->response_due_date !== null
            && $this->response_due_date->isPast();
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markReady(string $filePath): void
    {
        $this->update([
            'status'       => 'ready',
            'file_path'    => $filePath,
            'fulfilled_at' => now(),
            'expires_at'   => now()->addDays(7),
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                     ->whereNotNull('response_due_date')
                     ->where('response_due_date', '<', now());
    }
}
