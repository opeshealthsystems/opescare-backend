<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiteModuleEntitlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id', 'module_key', 'is_enabled', 'granted_at', 'revoked_at',
    ];

    protected $casts = [
        'is_enabled'  => 'boolean',
        'granted_at'  => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class, 'lite_device_id');
    }

    public function isActive(): bool
    {
        return $this->is_enabled && $this->revoked_at === null;
    }
}
