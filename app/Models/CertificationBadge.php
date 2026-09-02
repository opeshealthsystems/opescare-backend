<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CertificationBadge — publicly verifiable badge issued on successful certification.
 *
 * @property string $id
 * @property string $integration_certification_id
 * @property string $badge_code  OC-CERT-XXXXXX
 * @property string $certification_level  bronze|silver|gold|platinum
 * @property string $integration_name
 * @property string $integration_type
 * @property string $issued_by
 * @property \Carbon\Carbon $issued_at
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_public
 * @property string|null $revoke_reason
 * @property \Carbon\Carbon|null $revoked_at
 */
class CertificationBadge extends Model
{
    use HasUuids;

    protected $fillable = [
        'integration_certification_id',
        'badge_code',
        'certification_level',
        'integration_name',
        'integration_type',
        'issued_by',
        'issued_at',
        'expires_at',
        'is_public',
        'revoke_reason',
        'revoked_at',
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_public'  => 'boolean',
    ];

    public function certification(): BelongsTo
    {
        return $this->belongsTo(IntegrationCertification::class, 'integration_certification_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isRevoked(): bool { return $this->revoked_at !== null; }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function revoke(string $reason): void
    {
        $this->update([
            'revoke_reason' => $reason,
            'revoked_at'    => now(),
        ]);
    }

    /** Lucide icon name for the badge level (no emoji — platform uses Lucide only). */
    public function levelIcon(): string
    {
        return match ($this->certification_level) {
            'platinum' => 'trophy',
            'gold'     => 'medal',
            'silver'   => 'award',
            default    => 'shield',
        };
    }

    /** Brand-appropriate colour for the badge level icon. */
    public function levelColor(): string
    {
        return match ($this->certification_level) {
            'platinum' => '#6366F1',
            'gold'     => '#D4A017',
            'silver'   => '#94A3B8',
            default    => '#B45309',
        };
    }

    /**
     * Generate a unique badge code in OC-CERT-XXXXXX format.
     */
    public static function generateBadgeCode(): string
    {
        do {
            $code = 'OC-CERT-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('badge_code', $code)->exists());

        return $code;
    }
}
