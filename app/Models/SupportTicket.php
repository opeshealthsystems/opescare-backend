<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'requester_type',
        'requester_id',
        'facility_id',
        'category',
        'priority',
        'status',
        'subject',
        'description_redacted',
        'pii_redaction_summary',
        'assigned_to',
        'escalation_level',
        'escalated_at',
        'sla_due_at',
        'resolved_at',
        'resolution_note',
        'incident_id',
    ];

    protected $casts = [
        'pii_redaction_summary' => 'array',
        'escalated_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class);
    }
}
