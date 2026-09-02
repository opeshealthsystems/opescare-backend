<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VirtualWaitingRoom — Module 18 (Telemedicine)
 *
 * Tracks a patient's presence in the virtual waiting room before
 * a provider joins the teleconsultation.
 */
class VirtualWaitingRoom extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'facility_id',
        'teleconsultation_id',
        'patient_id',
        'status',               // waiting|called|joined|left|expired
        'joined_at',
        'called_at',
        'estimated_wait_minutes',
    ];

    protected $casts = [
        'joined_at'              => 'datetime',
        'called_at'              => 'datetime',
        'estimated_wait_minutes' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function call(): void
    {
        $this->update(['status' => 'called', 'called_at' => now()]);
    }

    public function waitMinutes(): ?int
    {
        if (! $this->joined_at) {
            return null;
        }
        $end = $this->called_at ?? now();
        return (int) $this->joined_at->diffInMinutes($end);
    }
}
