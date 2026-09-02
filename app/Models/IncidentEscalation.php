<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IncidentEscalation — Module 10 (Support, Helpdesk & Incident Management)
 *
 * Records escalation of an incident to higher-level teams.
 * Linked to both IncidentReport and optionally a SupportTicket.
 */
class IncidentEscalation extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'incident_report_id',
        'support_ticket_id',
        'escalated_by',
        'escalated_to',
        'escalation_level',
        'reason',
        'status',
        'acknowledged_at',
        'resolved_at',
        'escalated_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at'     => 'datetime',
        'escalated_at'    => 'datetime',
    ];

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function acknowledge(string $by): void
    {
        $this->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved', 'resolved_at' => now()]);
    }
}
