<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * FileClassification — Module 18 (File Storage & Attachments)
 *
 * Classification metadata for stored files.
 * Sensitivity levels drive access control and retention policies.
 *
 * Security constraint: "Do not expose patient data publicly."
 * restricted + confidential files require role-based access checks.
 */
class FileClassification extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id',
        'classification_type',  // medical_record|billing|lab|imaging|legal|consent|other
        'sensitivity_level',    // public|internal|confidential|restricted
        'tags',
        'classified_by',
        'classified_at',
    ];

    protected $casts = [
        'tags'          => 'array',
        'classified_at' => 'datetime',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isRestricted(): bool
    {
        return $this->sensitivity_level === 'restricted';
    }

    public function isConfidential(): bool
    {
        return in_array($this->sensitivity_level, ['confidential', 'restricted']);
    }

    public function isPubliclyAccessible(): bool
    {
        return $this->sensitivity_level === 'public';
    }

    public function sensitivityBadgeClass(): string
    {
        return match($this->sensitivity_level) {
            'restricted'    => 'badge badge--danger',
            'confidential'  => 'badge badge--warning',
            'internal'      => 'badge badge--info',
            'public'        => 'badge badge--neutral',
            default         => 'badge badge--neutral',
        };
    }
}
