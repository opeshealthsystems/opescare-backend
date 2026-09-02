<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiteConfig extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id', 'allowed_modules', 'language',
        'offline_allowed', 'low_bandwidth_mode', 'sync_interval_seconds',
        'blocked_offline_actions', 'currency_code', 'extra_settings',
        'config_updated_at',
    ];

    protected $casts = [
        'allowed_modules'         => 'array',
        'blocked_offline_actions' => 'array',
        'extra_settings'          => 'array',
        'offline_allowed'         => 'boolean',
        'low_bandwidth_mode'      => 'boolean',
        'config_updated_at'       => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class, 'lite_device_id');
    }

    public function allowsModule(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->allowed_modules ?? [], true);
    }

    public function isActionBlockedOffline(string $action): bool
    {
        return in_array($action, $this->blocked_offline_actions ?? [], true);
    }
}
