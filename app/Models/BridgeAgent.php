<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BridgeAgent extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'agent_key', 'agent_key_prefix',
        'agent_key_argon', 'secret_upgraded_at',
        'status', 'version', 'hostname', 'ip_address',
        'last_sync_at', 'last_seen_at', 'capabilities', 'notes', 'registered_by',
    ];

    protected $casts = [
        'capabilities'      => 'array',
        'last_sync_at'      => 'datetime',
        'last_seen_at'      => 'datetime',
        'secret_upgraded_at'=> 'datetime',
    ];

    protected $hidden = ['agent_key', 'agent_key_argon'];

    public function syncBatches(): HasMany
    {
        return $this->hasMany(BridgeSyncBatch::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayKey(): string
    {
        return $this->agent_key_prefix . '…';
    }
}
