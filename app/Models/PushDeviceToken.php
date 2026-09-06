<?php

namespace App\Models;

use App\Services\ExpoPushService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A handset a patient has registered for push.
 *
 * `push_token` is an Expo push token (`ExponentPushToken[…]`) for a row the
 * platform can reach, or '' for a fingerprint-only registration — a device
 * that identified itself for the in-app path without a push credential. Only
 * the former is ever sent to; `isDeliverable()` is the test, and it is the
 * same predicate the sender and the registration endpoint use.
 *
 * Unique on (patient_id, device_fingerprint, platform): one row per handset
 * per account. A token belongs to exactly one account at a time — the
 * registration endpoint revokes it everywhere else when it moves.
 *
 * @property string      $id
 * @property string      $patient_id
 * @property string      $device_fingerprint
 * @property string      $platform
 * @property string      $push_token
 * @property bool        $is_active
 * @property \Illuminate\Support\Carbon|null $registered_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 */
class PushDeviceToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'device_fingerprint',
        'platform',
        'push_token',
        'is_active',
        'registered_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'registered_at' => 'datetime',
        'revoked_at'    => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushDelivery::class, 'push_device_token_id');
    }

    /** Rows that may still be sent to (subject to isDeliverable()). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    public function revoke(): void
    {
        $this->update(['is_active' => false, 'revoked_at' => now()]);
    }

    /** Holds a real Expo push token, as opposed to a fingerprint-only registration. */
    public function isDeliverable(): bool
    {
        return ExpoPushService::isExpoPushToken($this->push_token);
    }

    /**
     * A token for logs and consoles: the prefix and the last few characters,
     * never the whole credential. '' (fingerprint-only) reads as such.
     */
    public static function mask(?string $token): string
    {
        if ($token === null || $token === '') {
            return '(no push token)';
        }

        if (preg_match('/^(Expo(?:nent)?PushToken)\[(.+)\]$/', $token, $m) === 1) {
            return $m[1].'[...'.substr($m[2], -4).']';
        }

        return substr($token, 0, 4).'...';
    }
}
