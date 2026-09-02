<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SignedDownloadToken — Module 18 (File Storage & Attachments)
 *
 * Time-limited, use-limited download token for secure file access.
 * Tokens are single-use by default and expire after a set time.
 *
 * Security constraint: "Do not expose patient data publicly."
 * All file downloads must go through a signed token or role-based access check.
 */
class SignedDownloadToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_asset_id',
        'token',
        'requested_by',
        'purpose',          // download|preview|share
        'max_uses',
        'use_count',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'max_uses'      => 'integer',
        'use_count'     => 'integer',
        'expires_at'    => 'datetime',
        'last_used_at'  => 'datetime',
    ];

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Create a new signed download token for a file.
     */
    public static function issue(
        string $fileAssetId,
        string $requestedBy,
        int $expiresInMinutes = 60,
        int $maxUses = 1,
        string $purpose = 'download'
    ): self {
        return static::create([
            'file_asset_id' => $fileAssetId,
            'token'         => Str::random(64),
            'requested_by'  => $requestedBy,
            'purpose'       => $purpose,
            'max_uses'      => $maxUses,
            'use_count'     => 0,
            'expires_at'    => now()->addMinutes($expiresInMinutes),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isExhausted();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->use_count >= $this->max_uses;
    }

    /**
     * Consume one use of the token. Returns false if invalid.
     */
    public function consume(): bool
    {
        if (! $this->isValid()) {
            return false;
        }
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);
        return true;
    }
}
