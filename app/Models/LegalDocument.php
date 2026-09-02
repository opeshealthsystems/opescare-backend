<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug', 'title', 'document_type', 'language',
        'is_active', 'requires_acceptance', 'created_by',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'requires_acceptance'  => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class);
    }

    public function currentVersion(): ?LegalDocumentVersion
    {
        return $this->versions()->where('is_current', true)->first();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfLanguage($query, string $lang = 'en')
    {
        return $query->where('language', $lang);
    }
}
