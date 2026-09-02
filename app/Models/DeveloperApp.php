<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DeveloperApp — Connect Suite / Developer Portal
 *
 * An application registered by a developer to consume the OpesCare API.
 * Each app has one or more ApiCredentials and operates in either sandbox
 * or production environments.
 */
class DeveloperApp extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_account_id',
        'developer_organization_id',
        'app_name',
        'environment',        // sandbox|production
        'status',             // active|suspended|revoked
        'description',
        'allowed_scopes',
        'redirect_uris',
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'redirect_uris'  => 'array',
    ];

    public function developerAccount(): BelongsTo
    {
        return $this->belongsTo(DeveloperAccount::class);
    }

    public function developerOrganization(): BelongsTo
    {
        return $this->belongsTo(DeveloperOrganization::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeProduction($query)
    {
        return $query->where('environment', 'production');
    }
}
