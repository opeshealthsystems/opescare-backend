<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LiteDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'device_name', 'device_fingerprint', 'environment',
        'status', 'platform', 'os_info', 'app_version', 'authorized_by',
        'last_seen_at', 'activated_at', 'revoked_at', 'revoke_reason',
        'allowed_modes', 'device_secret',
    ];

    protected $casts = [
        'allowed_modes'  => 'array',
        'last_seen_at'   => 'datetime',
        'activated_at'   => 'datetime',
        'revoked_at'     => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function config(): HasOne
    {
        return $this->hasOne(LiteConfig::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(LiteModuleEntitlement::class);
    }

    public function offlineEvents(): HasMany
    {
        return $this->hasMany(LiteOfflineEvent::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(LiteSyncJob::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(LiteConflict::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->entitlements()
            ->where('module_key', $moduleKey)
            ->where('is_enabled', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function supportsOffline(): bool
    {
        return $this->config?->offline_allowed === true;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'pending'   => 'warning',
            'suspended' => 'warning',
            'revoked'   => 'danger',
            'lost'      => 'danger',
            default     => 'default',
        };
    }

    public function touchSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
