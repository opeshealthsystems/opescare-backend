<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReportExport — Analytics & Reporting (Module 19)
 *
 * Tracks report export jobs. Files expire after a configured TTL and
 * must be re-requested. All exports are access-logged.
 */
class ReportExport extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_definition_id',
        'requested_by',
        'parameters',
        'format',       // csv|pdf|xlsx|json
        'status',       // pending|processing|ready|failed|expired
        'file_path',
        'row_count',
        'expires_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'row_count'  => 'integer',
        'expires_at' => 'datetime',
    ];

    public function reportDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markReady(string $filePath, int $rowCount = 0, int $ttlHours = 24): void
    {
        $this->update([
            'status'     => 'ready',
            'file_path'  => $filePath,
            'row_count'  => $rowCount,
            'expires_at' => now()->addHours($ttlHours),
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update(['status' => 'failed']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
