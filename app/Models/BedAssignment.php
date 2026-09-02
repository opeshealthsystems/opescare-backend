<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BedAssignment — Ward Management (Module 22)
 *
 * Records the current and historical assignment of an inpatient to a bed.
 * One active record per admission at any time.
 * When a patient is transferred, the current record is released and a new one created.
 */
class BedAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id',
        'bed_id',
        'patient_id',
        'assigned_by',
        'assigned_at',
        'released_at',
        'status',   // active|released|transferred
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function release(?string $notes = null): void
    {
        $this->update([
            'status'      => 'released',
            'released_at' => now(),
            'notes'       => $notes ?? $this->notes,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
