<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicalAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'patient_id', 'visit_id', 'rule_id',
        'alert_type', 'severity', 'alert_message', 'recommendation',
        'context_data', 'status', 'triggered_by', 'triggered_at',
        'acknowledged_at', 'acknowledged_by',
    ];

    protected $casts = [
        'context_data'     => 'array',
        'triggered_at'     => 'datetime',
        'acknowledged_at'  => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClinicalRule::class, 'rule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(AlertOverride::class, 'alert_id');
    }

    public function latestOverride(): HasOne
    {
        return $this->hasOne(AlertOverride::class, 'alert_id')->latestOfMany('overridden_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForVisit($query, string $visitId)
    {
        return $query->where('visit_id', $visitId);
    }

    public function severityColor(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'warning'  => 'warning',
            default    => 'info',
        };
    }
}
