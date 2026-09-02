<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAgreementAcceptance extends Model
{
    use HasUuids;

    protected $fillable = [
        'legal_document_version_id', 'partner_type', 'partner_id',
        'accepted_by_name', 'accepted_by_email', 'accepted_via',
        'ip_address', 'accepted_at', 'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
