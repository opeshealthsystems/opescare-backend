<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleEntitlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id', 'organization_id', 'module_key',
        'is_enabled', 'granted_at', 'revoked_at', 'granted_by',
    ];

    protected $casts = [
        'is_enabled'  => 'boolean',
        'granted_at'  => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->is_enabled && $this->revoked_at === null;
    }
}
