<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A revocable, expiring capability to view ONE prescription.
 *
 * Held by whoever the patient hands the script to — printed or on screen. The
 * secret half is never stored, only its hash; `lookup_id` is the indexed half
 * that finds the row. See PrescriptionAccessService.
 */
class PrescriptionAccessToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'prescription_id',
        'lookup_id',
        'token_hash',
        'status',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'use_count',
        'created_by',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'revoked_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'use_count'    => 'integer',
    ];

    /** Never let a token's secret hash reach a response. */
    protected $hidden = ['token_hash'];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function isUsable(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
