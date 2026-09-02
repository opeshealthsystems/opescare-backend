<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LiteDeviceRegistration — OpesCare Lite
 *
 * Records the onboarding/registration lifecycle for a Lite device at a facility.
 * A device must be approved before it can sync with OpesCare or operate offline.
 *
 * Security: unapproved devices MUST be blocked from all sync and data access.
 */
class LiteDeviceRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id',
        'facility_id',
        'registered_by',
        'registration_code',
        'status',              // pending|approved|rejected|revoked
        'approved_by',
        'approved_at',
        'revoked_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function liteDevice(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->revoked_at === null;
    }

    public function approve(string $approvedBy): void
    {
        $this->update(['status' => 'approved', 'approved_by' => $approvedBy, 'approved_at' => now()]);
    }

    public function reject(string $reason): void
    {
        $this->update(['status' => 'rejected', 'rejection_reason' => $reason]);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked', 'revoked_at' => now()]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->whereNull('revoked_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
