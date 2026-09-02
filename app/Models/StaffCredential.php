<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffCredential — Module 15 (Staff / HR / Shift Management)
 *
 * Academic degrees, certifications, professional registrations, and awards
 * held by a staff member. Separate from ProfessionalLicense (which tracks
 * practice licenses with expiry enforcement).
 */
class StaffCredential extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_profile_id', 'credential_type', 'title',
        'issuing_body', 'credential_number',
        'issue_date', 'expiry_date', 'document_path',
        'is_verified', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function verify(string $verifiedBy): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
    }
}
