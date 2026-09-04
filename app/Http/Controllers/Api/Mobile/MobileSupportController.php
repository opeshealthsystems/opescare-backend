<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Help & Support
 *
 * Patient-facing entry point onto the helpdesk tables created by
 * 2026_05_26_000004_create_support_helpdesk_tables (support_tickets,
 * ticket_messages). The staff-side Support module was removed during the
 * Finder extraction, so this controller talks to the Eloquent models
 * directly rather than through a service.
 *
 * Confidentiality rules enforced here:
 *  - every read is scoped to requester_type='patient' AND requester_id=<the
 *    authenticated patient>, so one patient can never load another's ticket;
 *  - ticket_messages flagged `internal` are staff-only notes and are NEVER
 *    serialised to a patient;
 *  - staff-owned columns (status, priority escalation, assigned_to,
 *    resolution_note, sla_due_at) are read-only from this surface.
 *
 * NOTE ON REDACTION: the `description_redacted` / `body_redacted` column names
 * come from the platform's PII-redaction pipeline, which did NOT survive the
 * extraction. Text written here is the patient's own words, stored verbatim,
 * with `pii_redaction_summary` left null to signal "never processed" rather
 * than "processed and found clean". Do not present this data as redacted.
 */
class MobileSupportController extends Controller
{
    /** Ticket categories offered to patients — mirrors SupportTicketCategory in the app. */
    private const CATEGORIES = [
        'technical_issue',
        'appointment_issue',
        'billing_question',
        'account_access',
        'medical_records',
        'prescription_pharmacy',
        'other',
    ];

    /**
     * Priorities a patient may choose. 'urgent' is deliberately excluded — it
     * is an operations-side escalation, not something a client can self-assign.
     */
    private const PATIENT_PRIORITIES = ['low', 'normal', 'high'];

    /** Requester discriminator written to support_tickets.requester_type. */
    private const REQUESTER_TYPE = 'patient';

    /** Sender discriminator written to ticket_messages.sender_type. */
    private const SENDER_TYPE = 'patient';

    /** Abuse guard: how many unresolved tickets one patient may hold at once. */
    private const MAX_OPEN_TICKETS = 10;

    /**
     * Support contact details + the category list the composer should offer.
     *
     * GET /api/mobile/support/contact
     */
    public function contact(Request $request): JsonResponse
    {
        if (($error = $this->requirePatient($request)) !== null) {
            return $error;
        }

        // Blank config must surface as null — the client renders "no channel
        // configured" rather than a fabricated address.
        $email = trim((string) config('opescare.support_email'));
        $phone = trim((string) config('opescare.support_phone'));

        return response()->json([
            'data' => [
                'email'      => $email !== '' ? $email : null,
                'phone'      => $phone !== '' ? $phone : null,
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    /**
     * List the authenticated patient's own support tickets.
     *
     * GET /api/mobile/support/tickets
     */
    public function tickets(Request $request): JsonResponse
    {
        if (($error = $this->requirePatient($request)) !== null) {
            return $error;
        }

        $tickets = $this->ownedTickets($request)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $tickets->map(fn (SupportTicket $t) => $this->ticketPayload($t))->all(),
        ]);
    }

    /**
     * Open a new support ticket for the authenticated patient.
     *
     * POST /api/mobile/support/tickets
     * Body: { category, subject, description, priority? }
     */
    public function store(Request $request): JsonResponse
    {
        if (($error = $this->requirePatient($request)) !== null) {
            return $error;
        }

        $validated = $request->validate([
            'category'    => 'required|string|in:' . implode(',', self::CATEGORIES),
            'subject'     => 'required|string|min:3|max:200',
            'description' => 'required|string|min:3|max:5000',
            'priority'    => 'nullable|string|in:' . implode(',', self::PATIENT_PRIORITIES),
        ]);

        $openCount = $this->ownedTickets($request)
            ->whereNull('resolved_at')
            ->count();

        if ($openCount >= self::MAX_OPEN_TICKETS) {
            return response()->json([
                'message'    => 'You already have the maximum number of open support requests.',
                'error_code' => 'too_many_open_tickets',
            ], 429);
        }

        $ticket = SupportTicket::create([
            'requester_type'        => self::REQUESTER_TYPE,
            'requester_id'          => $this->patientId($request),
            'category'              => $validated['category'],
            'priority'              => $validated['priority'] ?? 'normal',
            'status'                => 'open',
            'subject'               => $validated['subject'],
            'description_redacted'  => $validated['description'],
            'pii_redaction_summary' => null,
        ]);

        return response()->json(['data' => $this->ticketPayload($ticket)], 201);
    }

    /**
     * A single ticket owned by the patient, with its non-internal thread.
     *
     * GET /api/mobile/support/tickets/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (($error = $this->requirePatient($request)) !== null) {
            return $error;
        }

        $ticket = $this->ownedTickets($request)->find($id);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        // `internal` notes are staff-only and must never reach the patient.
        $messages = TicketMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('internal', false)
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $patientId = $this->patientId($request);
        $payload   = $this->ticketPayload($ticket);

        $payload['messages'] = $messages
            ->map(fn (TicketMessage $m) => $this->messagePayload($m, $patientId))
            ->all();

        return response()->json(['data' => $payload]);
    }

    /**
     * Post a reply onto a ticket the patient owns.
     *
     * POST /api/mobile/support/tickets/{id}/messages
     * Body: { body }
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        if (($error = $this->requirePatient($request)) !== null) {
            return $error;
        }

        $validated = $request->validate([
            'body' => 'required|string|min:1|max:4000',
        ]);

        $ticket = $this->ownedTickets($request)->find($id);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $patientId = $this->patientId($request);

        $message = TicketMessage::create([
            'support_ticket_id'     => $ticket->id,
            'sender_type'           => self::SENDER_TYPE,
            'sender_id'             => $patientId,
            'body_redacted'         => $validated['body'],
            'pii_redaction_summary' => null,
            'internal'              => false,
        ]);

        // Deliberately does NOT reopen a resolved ticket — status is owned by
        // the operations side, not by the client.

        return response()->json([
            'data' => $this->messagePayload($message, $patientId),
        ], 201);
    }

    // -------------------------------------------------------------------------

    /**
     * Base query: tickets belonging to this patient and nobody else. Both
     * discriminator columns are matched so a patient id can never collide with
     * a staff/facility requester that happens to share a uuid.
     */
    private function ownedTickets(Request $request)
    {
        return SupportTicket::query()
            ->where('requester_type', self::REQUESTER_TYPE)
            ->where('requester_id', $this->patientId($request));
    }

    private function patientId(Request $request): string
    {
        return (string) $request->attributes->get('patient_id');
    }

    /**
     * The auth.mobile middleware guarantees this, but an empty patient id would
     * turn every scoped query into an unfiltered one — fail closed instead.
     */
    private function requirePatient(Request $request): ?JsonResponse
    {
        if ($this->patientId($request) === '') {
            return response()->json([
                'message'    => 'Unauthenticated.',
                'error_code' => 'patient_not_resolved',
            ], 401);
        }

        return null;
    }

    private function ticketNotFound(): JsonResponse
    {
        return response()->json([
            'message'    => 'Support request not found.',
            'error_code' => 'ticket_not_found',
        ], 404);
    }

    private function ticketPayload(SupportTicket $t): array
    {
        return [
            'id'              => (string) $t->id,
            'category'        => $t->category,
            'priority'        => $t->priority,
            'status'          => $t->status,
            'subject'         => $t->subject,
            'description'     => $t->description_redacted,
            'sla_due_at'      => $t->sla_due_at?->toIso8601String(),
            'resolved_at'     => $t->resolved_at?->toIso8601String(),
            'resolution_note' => $t->resolution_note,
            'created_at'      => $t->created_at?->toIso8601String(),
            'updated_at'      => $t->updated_at?->toIso8601String(),
        ];
    }

    private function messagePayload(TicketMessage $m, string $patientId): array
    {
        return [
            'id'          => (string) $m->id,
            'sender_type' => $m->sender_type,
            'is_mine'     => $m->sender_type === self::SENDER_TYPE
                && (string) $m->sender_id === $patientId,
            'body'        => $m->body_redacted,
            'created_at'  => $m->created_at?->toIso8601String(),
        ];
    }
}
