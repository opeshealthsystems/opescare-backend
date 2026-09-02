<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertOverride extends Model
{
    use HasUuids;

    protected $fillable = [
        'alert_id', 'patient_id', 'visit_id',
        'overridden_by', 'override_reason', 'override_category', 'overridden_at',
    ];

    protected $casts = [
        'overridden_at' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(ClinicalAlert::class, 'alert_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
