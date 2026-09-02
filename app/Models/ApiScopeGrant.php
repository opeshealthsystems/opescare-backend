<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiScopeGrant — Connect Suite / Developer Portal
 *
 * A scope granted to an ApiCredential. Scopes follow the pattern:
 * {resource}:{action} e.g. patient:read | lab:write | prescription:verify
 *
 * Revoked scopes are soft-deleted via revoked_at; the credential still
 * works for other scopes.
 */
class ApiScopeGrant extends Model
{
    use HasUuids;

    protected $fillable = [
        'api_credential_id',
        'scope',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function apiCredential(): BelongsTo
    {
        return $this->belongsTo(ApiCredential::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public static function hasScope(string $credentialId, string $scope): bool
    {
        return static::where('api_credential_id', $credentialId)
            ->where('scope', $scope)
            ->whereNull('revoked_at')
            ->exists();
    }
}
