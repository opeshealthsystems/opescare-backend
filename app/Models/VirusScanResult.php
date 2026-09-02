<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * VirusScanResult — Module 18 (File Storage & Attachments)
 *
 * Records the antivirus scan result for an uploaded file.
 * Infected files must be quarantined before they are accessible.
 *
 * Scanner: In production uses ClamAV or equivalent.
 * For demo environments, scanner = 'placeholder'.
 */
class VirusScanResult extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id',
        'file_path',
        'scanner',          // clamav|sentinel|placeholder
        'status',           // pending|clean|infected|error
        'threat_name',
        'scan_output',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isClean(): bool
    {
        return $this->status === 'clean';
    }

    public function isInfected(): bool
    {
        return $this->status === 'infected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'clean'    => 'badge badge--success',
            'infected' => 'badge badge--danger',
            'error'    => 'badge badge--warning',
            'pending'  => 'badge badge--neutral',
            default    => 'badge badge--neutral',
        };
    }
}
