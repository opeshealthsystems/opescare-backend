<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EmergencyEscalation — Module 16 (Triage & Emergency Workflow)
 *
 * Tracks internal or external escalation actions during an emergency case
 * (specialist call, referral, inter-facility transfer, etc.).
 */
class EmergencyEscalation extends Model
{
    use HasUuids;

    protected $fillable = [
        'emergency_case_id', 'visit_id',
        'escalated_by', 'escalated_to',
        'escalation_reason', 'escalation_type',
        'target', 'status',
        'acknowledged_at', 'escalated_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'escalated_at'    => 'datetime',
    ];

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function acknowledge(): void
    {
        $this->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
    }

    public function markResponded(): void
    {
        $this->update(['status' => 'responded']);
    }
}
