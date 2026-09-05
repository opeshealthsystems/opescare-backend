<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The verifiable identity of one generated document (see the migration).
 *
 * `token_hash` is the hash of the QR token's secret half; the raw token is
 * never stored and never exposed by a verifier page.
 */
class DocumentVerification extends Model
{
    use HasUuids;

    public const STATUS_VALID   = 'valid';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'patient_id',
        'document_type',
        'reference',
        'lookup_id',
        'token_hash',
        'sha256',
        'issued_by_user_id',
        'issued_at',
        'status',
        'revoked_at',
        'revoke_reason',
        'scan_count',
        'last_verified_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'issued_at'        => 'datetime',
            'revoked_at'       => 'datetime',
            'last_verified_at' => 'datetime',
            'scan_count'       => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        // The document names its holder, so the holder must be resolvable even
        // when the demo scope would otherwise hide a non-demo patient.
        return $this->belongsTo(Patient::class)->withoutGlobalScopes();
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    /** The first twelve hex digits of the sealed SHA-256, grouped for reading. */
    public function shortFingerprint(): ?string
    {
        if (! $this->sha256) {
            return null;
        }

        return strtoupper(implode(' ', str_split(substr($this->sha256, 0, 12), 4)));
    }
}
