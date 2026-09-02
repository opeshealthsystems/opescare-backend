<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BridgeSyncBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id', 'facility_id', 'sync_type', 'status',
        'record_count', 'inserted_count', 'updated_count', 'skipped_count', 'error_count',
        'errors', 'checksum', 'completed_at',
    ];

    protected $casts = [
        'errors'       => 'array',
        'completed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class, 'bridge_agent_id');
    }
}
