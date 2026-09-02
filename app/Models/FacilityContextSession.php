<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FacilityContextSession — Provider Mobile App / Multi-Facility Staff
 *
 * Tracks a provider's active facility context — which facility they are
 * currently operating within, across web and mobile platforms.
 *
 * Multi-facility staff must activate a context before performing
 * facility-scoped actions. Only one active context per device at a time.
 */
class FacilityContextSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'facility_id',
        'organization_id',
        'device_id',
        'platform',          // web|ios|android
        'activated_at',
        'expires_at',
        'terminated_at',
        'status',            // active|expired|terminated
    ];

    protected $casts = [
        'activated_at'  => 'datetime',
        'expires_at'    => 'datetime',
        'terminated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && $this->terminated_at === null;
    }

    public function terminate(): void
    {
        $this->update(['status' => 'terminated', 'terminated_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get or create the active context for a user on a given device.
     */
    public static function activate(string $userId, string $facilityId, ?string $deviceId = null, ?string $platform = null): self
    {
        // Terminate any existing active sessions for this user/device
        static::forUser($userId)
            ->active()
            ->when($deviceId, fn($q) => $q->where('device_id', $deviceId))
            ->each(fn($s) => $s->terminate());

        return static::create([
            'user_id'      => $userId,
            'facility_id'  => $facilityId,
            'device_id'    => $deviceId,
            'platform'     => $platform,
            'activated_at' => now(),
            'status'       => 'active',
        ]);
    }
}
