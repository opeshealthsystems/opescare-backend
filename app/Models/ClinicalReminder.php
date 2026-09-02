<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalReminder extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'patient_id', 'reminder_type', 'title', 'body',
        'due_date', 'status', 'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status === 'pending';
    }
}
