<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ApiCredential — Connect Suite / Developer Portal
 *
 * Client credentials (client_id + hashed client_secret) for API authentication.
 * Secrets are NEVER stored in plaintext — only bcrypt hashes.
 *
 * Security: rotating a credential creates a new record; the old record
 * is marked 'rotated' and invalidated after a grace period.
 */
class ApiCredential extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_app_id',
        'client_id',
        'client_secret_hash',   // bcrypt; NEVER store plain secret
        'credential_type',      // server_to_server|auth_code|pkce
        'environment',          // sandbox|production
        'status',               // active|revoked|rotated
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function developerApp(): BelongsTo
    {
        return $this->belongsTo(DeveloperApp::class);
    }

    public function scopeGrants(): HasMany
    {
        return $this->hasMany(ApiScopeGrant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }

    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
