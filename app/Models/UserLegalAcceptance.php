<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLegalAcceptance extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'legal_document_version_id',
        'accepted_via', 'ip_address', 'user_agent',
        'accepted_at', 'revoked_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
