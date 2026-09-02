<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FhirSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'facility_id',
        'status',
        'reason',
        'criteria',
        'channel_type',
        'endpoint',
        'headers',
        'payload_type',
        'end',
        'created_by',
        'signing_secret',
        'last_notified_at',
        'error_count',
    ];

    protected $casts = [
        'headers'          => 'array',
        'end'              => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    protected $hidden = ['signing_secret'];

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->end === null || $this->end->isFuture());
    }

    public function toFhirResource(): array
    {
        return [
            'resourceType' => 'Subscription',
            'id'           => $this->id,
            'status'       => $this->status,
            'reason'       => $this->reason,
            'criteria'     => $this->criteria,
            'channel'      => [
                'type'    => $this->channel_type,
                'endpoint'=> $this->endpoint,
                'payload' => $this->payload_type,
                'header'  => $this->headers ?? [],
            ],
            'end'          => $this->end?->toIso8601String(),
        ];
    }
}
