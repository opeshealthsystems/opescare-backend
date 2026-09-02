<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * AuditExport — Security Operations / Compliance
 *
 * Records every audit-log export job including filters used, format,
 * and the generated file path. Export files expire and must be re-requested.
 */
class AuditExport extends Model
{
    use HasUuids;

    protected $fillable = [
        'requested_by',
        'export_type',   // full|date_range|user|facility|module
        'filters',
        'format',        // csv|json|pdf
        'status',        // pending|processing|ready|expired|failed
        'file_path',
        'expires_at',
    ];

    protected $casts = [
        'filters'    => 'array',
        'expires_at' => 'datetime',
    ];

    public function isReady(): bool
    {
        return $this->status === 'ready' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markReady(string $filePath, int $expiresInHours = 24): void
    {
        $this->update([
            'status'     => 'ready',
            'file_path'  => $filePath,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready')->where('expires_at', '>', now());
    }
}
