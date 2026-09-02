<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiteSyncJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id', 'direction', 'status',
        'events_sent', 'events_applied', 'events_rejected',
        'conflicts_created', 'error_message',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class, 'lite_device_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
