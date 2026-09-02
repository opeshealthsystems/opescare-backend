<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'provider_id',
        'visit_type',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function triageRecords()
    {
        return $this->hasMany(TriageRecord::class);
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function steps()
    {
        return $this->hasMany(VisitStep::class)->orderBy('display_order');
    }

    public function timeline()
    {
        return $this->hasMany(VisitTimeline::class)->orderBy('occurred_at');
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, ['in_progress', 'pending', 'triaged']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function currentStep(): ?VisitStep
    {
        return $this->steps()->where('status', 'in_progress')->first();
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'     => 'badge badge--neutral',
            'in_progress' => 'badge badge--info',
            'triaged'     => 'badge badge--warning',
            'completed'   => 'badge badge--success',
            'cancelled'   => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
