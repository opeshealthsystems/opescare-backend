<?php

namespace App\Modules\Broadcasts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BroadcastAcknowledgement — Records that a user has acknowledged a broadcast.
 *
 * Unique constraint on (broadcast_id, user_id) ensures idempotency —
 * a user can only acknowledge a given broadcast once.
 */
class BroadcastAcknowledgement extends Model
{
    protected $fillable = [
        'broadcast_id',
        'user_id',
        'facility_id',
        'ip_address',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }
}
