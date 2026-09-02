<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderCredential extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'credential_type',
        'issuing_body',
        'credential_number',
        'issued_date',
        'expiry_date',
        'status',
        'document_path',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function verifier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $daysAhead = 30): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->isFuture()
            && $this->expiry_date->diffInDays(now()) <= $daysAhead;
    }
}
