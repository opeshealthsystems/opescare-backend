<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A clinician's mobile session. See the migration for why this is a separate
 * table from PatientAccessToken rather than a discriminated column.
 */
class ProviderAccessToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'token_lookup_hash',
        'token_hash',
        'token_prefix',
        'facility_id',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'device_name',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'revoked_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /** Never let either hash reach a response. */
    protected $hidden = ['token_hash', 'token_lookup_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
