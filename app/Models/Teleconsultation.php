<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Teleconsultation — Module 18 (Telemedicine)
 *
 * Represents a scheduled or ad-hoc virtual consultation between
 * a patient and a provider.
 *
 * OpesCare disclaimer: Telemedicine is a clinical service. The platform
 * facilitates the connection and records the encounter. Clinical decisions
 * remain the provider's responsibility.
 */
class Teleconsultation extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'facility_id',
        'provider_id',
        'status',               // scheduled|waiting|active|completed|cancelled|failed
        'platform',             // own|zoom|meet|teams
        'session_url',
        'session_token',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'cancellation_reason',
        'technical_notes',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'started_at'      => 'datetime',
        'ended_at'        => 'datetime',
        'duration_seconds' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

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
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    public function consent(): HasOne
    {
        return $this->hasOne(TelemedicineConsent::class);
    }

    public function waitingRoom(): HasOne
    {
        return $this->hasOne(VirtualWaitingRoom::class);
    }

    public function callSession(): HasOne
    {
        return $this->hasOne(CallSession::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TelemedicineNote::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function durationMinutes(): ?int
    {
        if ($this->duration_seconds === null) {
            return null;
        }
        return (int) ceil($this->duration_seconds / 60);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'scheduled' => 'badge badge--info',
            'waiting'   => 'badge badge--warning',
            'active'    => 'badge badge--success',
            'completed' => 'badge badge--neutral',
            'cancelled' => 'badge badge--danger',
            'failed'    => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
