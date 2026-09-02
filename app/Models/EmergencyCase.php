<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EmergencyCase — Module 16 (Triage & Emergency Workflow)
 *
 * Created when a patient is declared an emergency (cardiac arrest, trauma,
 * respiratory distress, etc.). Drives emergency routing, team alerts, and
 * clinical outcome tracking.
 */
class EmergencyCase extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id', 'patient_id', 'facility_id',
        'emergency_type', 'severity', 'status',
        'response_lead', 'emergency_notes',
        'declared_at', 'stabilized_at', 'resolved_at',
    ];

    protected $casts = [
        'declared_at'    => 'datetime',
        'stabilized_at'  => 'datetime',
        'resolved_at'    => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(EmergencyEscalation::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function stabilize(): void
    {
        $this->update(['status' => 'stabilized', 'stabilized_at' => now()]);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'active'      => 'badge badge--danger',
            'stabilized'  => 'badge badge--warning',
            'transferred' => 'badge badge--info',
            'resolved'    => 'badge badge--success',
            'deceased'    => 'badge badge--neutral',
            default       => 'badge badge--neutral',
        };
    }
}
