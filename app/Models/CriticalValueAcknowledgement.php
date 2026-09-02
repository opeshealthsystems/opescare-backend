<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriticalValueAcknowledgement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lab_result_id',
        'patient_id',
        'facility_id',
        'flag',
        'test_name',
        'value',
        'unit',
        'notified_by',
        'notified_at',
        'notification_method',
        'notified_recipient',
        'acknowledged_by',
        'acknowledged_at',
        'is_read_back',
        'acknowledgement_notes',
    ];

    protected $casts = [
        'notified_at'     => 'datetime',
        'acknowledged_at' => 'datetime',
        'is_read_back'    => 'boolean',
    ];

    public function labResult(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LabResult::class);
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function notifiedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'notified_by');
    }

    public function acknowledgedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isPending(): bool
    {
        return $this->acknowledged_at === null;
    }

    public function minutesToAcknowledge(): ?int
    {
        if ($this->acknowledged_at === null) {
            return null;
        }
        return (int) $this->notified_at->diffInMinutes($this->acknowledged_at);
    }
}
