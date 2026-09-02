<?php

namespace App\Modules\Support\Services;

use App\Models\AuditEvent;
use App\Models\IncidentReport;
use App\Models\KnowledgeBaseArticle;
use App\Models\SecurityIncident;
use App\Models\SupportTicket;
use App\Models\TicketAssignment;
use App\Models\TicketMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportService
{
    public function createTicket(array $data, ?string $actorId = null): SupportTicket
    {
        $redacted = $this->redact($data['description'] ?? '');

        $ticket = SupportTicket::create([
            'requester_type' => $data['requester_type'],
            'requester_id' => $data['requester_id'] ?? null,
            'facility_id' => $data['facility_id'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'subject' => $data['subject'],
            'description_redacted' => $redacted['text'],
            'pii_redaction_summary' => $redacted['summary'],
            'sla_due_at' => $this->slaDueAt($data['priority'] ?? 'normal'),
        ]);

        $this->audit($ticket, 'create', $actorId, null, $ticket->toArray());

        return $ticket;
    }

    public function addMessage(SupportTicket $ticket, array $data, ?string $actorId = null): TicketMessage
    {
        $redacted = $this->redact($data['body'] ?? '');

        $message = TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => $data['sender_type'],
            'sender_id' => $data['sender_id'] ?? null,
            'body_redacted' => $redacted['text'],
            'pii_redaction_summary' => $redacted['summary'],
            'internal' => $data['internal'] ?? false,
        ]);

        $this->audit($ticket, 'message', $actorId, null, $message->toArray());

        return $message;
    }

    public function assignTicket(SupportTicket $ticket, string $assignedTo, ?string $actorId = null): SupportTicket
    {
        $before = $ticket->toArray();

        TicketAssignment::create([
            'support_ticket_id' => $ticket->id,
            'assigned_to' => $assignedTo,
            'assigned_by' => $actorId,
            'assigned_at' => Carbon::now(),
        ]);

        $ticket->forceFill([
            'assigned_to' => $assignedTo,
            'status' => 'assigned',
        ])->save();

        $this->audit($ticket, 'assign', $actorId, $before, $ticket->fresh()->toArray());

        return $ticket->fresh();
    }

    public function escalateTicket(SupportTicket $ticket, string $level, ?string $actorId = null, ?string $reason = null): SupportTicket
    {
        $before = $ticket->toArray();

        $ticket->forceFill([
            'status' => 'escalated',
            'escalation_level' => $level,
            'escalated_at' => Carbon::now(),
        ])->save();

        $this->audit($ticket, 'escalate', $actorId, $before, array_merge($ticket->fresh()->toArray(), [
            'reason' => $reason,
        ]));

        return $ticket->fresh();
    }

    public function resolveTicket(SupportTicket $ticket, ?string $actorId = null, ?string $resolutionNote = null): SupportTicket
    {
        $before = $ticket->toArray();

        $ticket->forceFill([
            'status' => 'resolved',
            'resolved_at' => Carbon::now(),
            'resolution_note' => $resolutionNote,
        ])->save();

        $this->audit($ticket, 'resolve', $actorId, $before, $ticket->fresh()->toArray());

        return $ticket->fresh();
    }

    public function createIncidentFromTicket(SupportTicket $ticket, ?string $actorId, string $severity = 'medium'): SecurityIncident
    {
        return DB::transaction(function () use ($ticket, $actorId, $severity) {
            $incident = SecurityIncident::create([
                'incident_type' => 'support_ticket',
                'severity' => $severity,
                'status' => 'triaging',
                'summary' => $ticket->subject,
                'detected_at' => Carbon::now(),
                'created_by' => $actorId,
            ]);

            IncidentReport::create([
                'support_ticket_id' => $ticket->id,
                'security_incident_id' => $incident->id,
                'severity' => $severity,
                'summary' => $ticket->description_redacted,
                'created_by' => $actorId,
            ]);

            $before = $ticket->toArray();
            $ticket->forceFill(['incident_id' => $incident->id])->save();

            $this->audit($ticket, 'create_incident', $actorId, $before, $ticket->fresh()->toArray());

            return $incident;
        });
    }

    public function publishKnowledgeBaseArticle(array $data, ?string $actorId = null): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::create([
            'title' => $data['title'],
            'slug' => $data['slug'] ?? Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'audience' => $data['audience'] ?? 'all',
            'status' => $data['status'] ?? 'published',
            'body' => $data['body'],
            'view_count' => 0,
            'created_by' => $actorId,
            'published_at' => Carbon::now(),
        ]);

        AuditEvent::create([
            'actor_id' => $actorId,
            'action_type' => 'publish',
            'resource_type' => 'knowledge_base_article',
            'resource_id' => $article->id,
            'source_system' => 'opescare',
            'after_state' => $article->toArray(),
            'created_at' => Carbon::now(),
        ]);

        return $article;
    }

    public function recordArticleView(KnowledgeBaseArticle $article, ?string $actorId = null): KnowledgeBaseArticle
    {
        $article->increment('view_count');

        AuditEvent::create([
            'actor_id' => $actorId,
            'action_type' => 'view',
            'resource_type' => 'knowledge_base_article',
            'resource_id' => $article->id,
            'source_system' => 'opescare',
            'created_at' => Carbon::now(),
        ]);

        return $article->fresh();
    }

    public function redact(string $text): array
    {
        $summary = [];
        $patterns = [
            'health_id' => ['/OPC-\d+/i', '[REDACTED_HEALTH_ID]'],
            'email' => ['/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[REDACTED_EMAIL]'],
            'phone' => ['/\+\d[\d\s\-]{8,}\d/', '[REDACTED_PHONE]'],
            'national_id' => ['/\b\d{11}\b/', '[REDACTED_NATIONAL_ID]'],
        ];

        foreach ($patterns as $type => [$pattern, $replacement]) {
            $count = 0;
            $text = preg_replace($pattern, $replacement, $text, -1, $count);
            if ($count > 0) {
                $summary[$type] = $count;
            }
        }

        return ['text' => $text, 'summary' => $summary];
    }

    private function slaDueAt(string $priority): Carbon
    {
        return match ($priority) {
            'critical' => Carbon::now()->addHours(4),
            'urgent' => Carbon::now()->addHours(8),
            'high' => Carbon::now()->addHours(12),
            'low' => Carbon::now()->addHours(72),
            default => Carbon::now()->addHours(48),
        };
    }

    private function audit(SupportTicket $ticket, string $action, ?string $actorId, ?array $before, ?array $after): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $ticket->facility_id,
            'patient_id' => $ticket->requester_type === 'patient' ? $ticket->requester_id : null,
            'action_type' => $action,
            'resource_type' => 'support_ticket',
            'resource_id' => $ticket->id,
            'source_system' => 'opescare',
            'before_state' => $before,
            'after_state' => $after,
            'created_at' => Carbon::now(),
        ]);
    }
}
