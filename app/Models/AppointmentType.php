<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AppointmentType — Module 5 (Appointments & Booking)
 *
 * Defines the types of appointments a facility supports.
 * Types drive scheduling rules, duration defaults, and referral requirements.
 */
class AppointmentType extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'name',
        'code',
        'description',
        'category',
        'default_duration_minutes',
        'requires_provider',
        'requires_referral',
        'is_telemedicine',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'requires_provider'       => 'boolean',
        'requires_referral'       => 'boolean',
        'is_telemedicine'         => 'boolean',
        'is_active'               => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'appointment_type_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where(function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId)->orWhereNull('facility_id');
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isTelemedicine(): bool
    {
        return (bool) $this->is_telemedicine;
    }
}
