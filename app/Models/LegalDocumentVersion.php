<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocumentVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'legal_document_id', 'version', 'content_html', 'content_markdown',
        'content_hash', 'is_current', 'requires_reacceptance', 'change_summary',
        'published_by', 'published_at', 'effective_at',
    ];

    protected $casts = [
        'is_current'             => 'boolean',
        'requires_reacceptance'  => 'boolean',
        'published_at'           => 'datetime',
        'effective_at'           => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(UserLegalAcceptance::class);
    }

    public function partnerAcceptances(): HasMany
    {
        return $this->hasMany(PartnerAgreementAcceptance::class);
    }

    public function isEffective(): bool
    {
        return $this->published_at !== null
            && ($this->effective_at === null || $this->effective_at->isPast());
    }
}
