<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SdkToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id', 'token_hash', 'token_prefix', 'scopes', 'environment',
        'label', 'expires_at', 'last_used_at', 'revoked_by', 'revoked_at', 'is_active',
    ];

    protected $casts = [
        'scopes'      => 'array',
        'expires_at'  => 'datetime',
        'last_used_at'=> 'datetime',
        'revoked_at'  => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function displayToken(): string
    {
        return $this->token_prefix . '…' . str_repeat('*', 20);
    }
}
