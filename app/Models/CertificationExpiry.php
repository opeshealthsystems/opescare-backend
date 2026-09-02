<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CertificationExpiry — training / certification
 *
 * Tracks the expiry lifecycle for a certification held by a staff member,
 * facility, or developer app. Triggers renewal notifications before expiry.
 */
class CertificationExpiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'certification_id',
        'holder_id',
        'holder_type',         // Staff|Facility|DeveloperApp
        'issued_at',
        'expires_at',
        'renewal_notified',
        'expired',
        'revoked_at',
    ];

    protected $casts = [
        'issued_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'revoked_at'       => 'datetime',
        'renewal_notified' => 'boolean',
        'expired'          => 'boolean',
    ];

    public function isValid(): bool
    {
        return !$this->expired
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function daysUntilExpiry(): int
    {
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function markExpired(): void
    {
        $this->update(['expired' => true]);
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now(), 'expired' => true]);
    }

    public function markRenewalNotified(): void
    {
        $this->update(['renewal_notified' => true]);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expired', false)
                     ->whereNull('revoked_at')
                     ->where('expires_at', '<=', now()->addDays($days))
                     ->where('expires_at', '>', now());
    }

    public function scopeForHolder($query, string $holderType, string $holderId)
    {
        return $query->where('holder_type', $holderType)->where('holder_id', $holderId);
    }
}
