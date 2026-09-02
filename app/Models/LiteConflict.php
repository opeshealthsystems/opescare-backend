<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiteConflict extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id', 'lite_offline_event_id', 'conflict_type',
        'server_version', 'device_version', 'status',
        'resolved_by', 'resolution_note', 'resolved_at',
    ];

    protected $casts = [
        'server_version' => 'array',
        'device_version' => 'array',
        'resolved_at'    => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class, 'lite_device_id');
    }

    public function offlineEvent(): BelongsTo
    {
        return $this->belongsTo(LiteOfflineEvent::class, 'lite_offline_event_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'open'      => 'danger',
            'resolved'  => 'success',
            'dismissed' => 'default',
            default     => 'default',
        };
    }
}
