<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MobileDevice — Mobile API Readiness (Module 21)
 *
 * Represents a registered mobile device for a user. Devices are trusted
 * after verification (e.g. MFA confirmation). Revoked devices cannot receive
 * push notifications or perform offline sync.
 */
class MobileDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'device_identifier',
        'platform',         // ios|android
        'app_version',
        'os_version',
        'push_token',
        'is_trusted',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_trusted'   => 'boolean',
        'last_seen_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function revoke(): void
    {
        $this->update(['is_trusted' => false, 'revoked_at' => now(), 'push_token' => null]);
    }

    public function trust(): void
    {
        $this->update(['is_trusted' => true]);
    }

    public function recordSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true)->whereNull('revoked_at');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
