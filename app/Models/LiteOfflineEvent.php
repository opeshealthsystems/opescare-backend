<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LiteOfflineEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'lite_device_id', 'facility_id', 'event_type', 'client_id',
        'payload', 'status', 'reject_reason', 'captured_at',
        'received_at', 'applied_at', 'applied_by',
    ];

    protected $casts = [
        'payload'     => 'array',
        'captured_at' => 'datetime',
        'received_at' => 'datetime',
        'applied_at'  => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LiteDevice::class, 'lite_device_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function conflict(): HasOne
    {
        return $this->hasOne(LiteConflict::class, 'lite_offline_event_id');
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function hasConflict(): bool
    {
        return $this->status === 'conflict';
    }
}
