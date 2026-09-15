<?php

namespace App\Modules\Support\Services;

use App\Models\SupportTicket;
use App\Models\TicketAssignment;
use App\Models\AuditEvent;

/**
 * TicketAssignmentService — Manages support ticket assignment and escalation.
 *
 * Tickets can be assigned to individual agents or to a team.
 * Reassignment creates a new TicketAssignment record (history preserved).
 * SLA breach triggers escalation workflow.
 */
class TicketAssignmentService
{
    public function assign(
        string $ticketId,
        string $assignedTo,
        string $assignedBy,
        string $notes = null
    ): TicketAssignment {
        // Unassign previous assignments
        TicketAssignment::where('support_ticket_id', $ticketId)
            ->whereNull('unassigned_at')
            ->update(['unassigned_at' => now()]);

        $assignment = TicketAssignment::create([
            'support_ticket_id' => $ticketId,
            'assigned_to'       => $assignedTo,
            'assigned_by'       => $assignedBy,
            'notes'             => $notes,
            'assigned_at'       => now(),
        ]);

        SupportTicket::where('id', $ticketId)->update(['assignee_id' => $assignedTo]);

        AuditEvent::create([
            'actor_id' => $assignedBy,
            'action'   => 'support_ticket.assigned',
            'module'   => 'support',
            'metadata' => ['ticket_id' => $ticketId, 'assigned_to' => $assignedTo],
        ]);

        return $assignment;
    }

    public function escalate(
        string $ticketId,
        string $escalatedBy,
        string $reason
    ): SupportTicket {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update(['priority' => 'urgent', 'escalated_at' => now()]);

        AuditEvent::create([
            'actor_id' => $escalatedBy,
            'action'   => 'support_ticket.escalated',
            'module'   => 'support',
            'metadata' => ['ticket_id' => $ticketId, 'reason' => $reason],
        ]);

        return $ticket->fresh();
    }

    public function getUnassignedTickets(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::whereNull('assignee_id')
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->get();
    }
}
