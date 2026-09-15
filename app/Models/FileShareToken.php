<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * FileShareToken — File Storage & Medical Attachments
 *
 * A one-time or limited-use token for sharing a file with an external
 * party (e.g. an insurance adjuster, external lab, or patient portal).
 *
 * Security constraints:
 * - Tokens expire and cannot be extended.
 * - Each use is logged in AttachmentAccessLog.
 * - Tokens may be revoked by the issuer at any time.
 * - Tokens NEVER expose PHI in the URL itself.
 */
class FileShareToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id',
        'token',
        'shared_by',
        'share_purpose',     // external_lab|insurance|patient|etc
        'recipient_email',
        'max_uses',
        'use_count',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'max_uses'   => 'integer',
        'use_count'  => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function fileAsset(): BelongsTo
    {
        return $this->belongsTo(FileAsset::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture()
            && $this->use_count < $this->max_uses;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->use_count >= $this->max_uses;
    }

    public function consume(): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        $this->increment('use_count');
        return true;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public static function issue(
        string $fileAssetId,
        string $sharedBy,
        int $expiresInHours = 48,
        int $maxUses = 1,
        ?string $purpose = null,
        ?string $recipientEmail = null
    ): self {
        return static::create([
            'file_asset_id'   => $fileAssetId,
            'token'           => Str::random(64),
            'shared_by'       => $sharedBy,
            'share_purpose'   => $purpose,
            'recipient_email' => $recipientEmail,
            'max_uses'        => $maxUses,
            'use_count'       => 0,
            'expires_at'      => now()->addHours($expiresInHours),
        ]);
    }
}
