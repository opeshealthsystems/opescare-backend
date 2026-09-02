<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Modules\Support\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Help & Support.
 *
 * Wraps the Support module (App\Modules\Support\Services\SupportService) for
 * the patient mobile app. The existing SupportController (Api\V1) is a
 * staff/facility surface gated on `facility_id` from the auth middleware —
 * unusable here, since a patient support request is not scoped to a single
 * facility. This controller is the real, additive patient entry point:
 * `requester_type = 'patient'` / `requester_id` always comes from
 * AuthenticateMobilePatient's `patient_id` attribute, never request input,
 * and every read is scoped to the caller's own tickets.
 */
class MobileSupportController extends Controller
{
    /** Categories offered in the mobile "Submit a Request" form. */
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
     * GET /api/mobile/support/contact — real, config-driven contact channels.
     * No phone/email is fabricated: a channel is only returned when the
     * platform has one configured (config/opescare.php, env-driven).
     */
    public function contact(): JsonResponse
    {
        $email = config('opescare.support_email') ?: null;
        $phone = config('opescare.support_phone') ?: null;

        return response()->json(['data' => [
            'email' => $email,
            'phone' => $phone,
            'categories' => self::CATEGORIES,
        ]]);
    }

    /** GET /api/mobile/support/tickets — the patient's own support tickets. */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $tickets = SupportTicket::where('requester_type', 'patient')
            ->where('requester_id', $patientId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $tickets->map(fn (SupportTicket $t) => $this->serializeTicket($t))->values()]);
    }

    /** POST /api/mobile/support/tickets — open a new support ticket. */
    public function store(Request $request, SupportService $service): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        $ticket = $service->createTicket([
            'requester_type' => 'patient',
            'requester_id' => $patientId,
            'facility_id' => null,
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 'normal',
            'subject' => $validated['subject'],
            'description' => $validated['description'],
        ], $patientId);

        return response()->json(['data' => $this->serializeTicket($ticket)], 201);
    }

    /** GET /api/mobile/support/tickets/{id} — ticket detail + message thread. */
    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = $this->findOwnTicket($request, $id);

        $messages = $ticket->messages()
            ->where('internal', false)
            ->orderBy('created_at')
            ->get()
            ->map(fn (TicketMessage $m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'is_mine' => $m->sender_type === 'patient',
                'body' => $m->body_redacted,
                'created_at' => $m->created_at?->toISOString(),
            ]);

        return response()->json(['data' => array_merge($this->serializeTicket($ticket), [
            'messages' => $messages,
        ])]);
    }

    /** POST /api/mobile/support/tickets/{id}/messages — reply on an existing ticket. */
    public function addMessage(Request $request, string $id, SupportService $service): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $ticket = $this->findOwnTicket($request, $id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $service->addMessage($ticket, [
            'sender_type' => 'patient',
            'sender_id' => $patientId,
            'body' => $validated['body'],
            'internal' => false,
        ], $patientId);

        return response()->json(['data' => [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'is_mine' => true,
            'body' => $message->body_redacted,
            'created_at' => $message->created_at?->toISOString(),
        ]], 201);
    }

    private function findOwnTicket(Request $request, string $id): SupportTicket
    {
        $patientId = $this->resolvePatientId($request);

        $ticket = SupportTicket::where('id', $id)
            ->where('requester_type', 'patient')
            ->where('requester_id', $patientId)
            ->first();

        if (! $ticket) {
            abort(404, 'Support ticket not found.');
        }

        return $ticket;
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'subject' => $ticket->subject,
            'description' => $ticket->description_redacted,
            'sla_due_at' => optional($ticket->sla_due_at)->toISOString(),
            'resolved_at' => optional($ticket->resolved_at)->toISOString(),
            'resolution_note' => $ticket->resolution_note,
            'created_at' => $ticket->created_at?->toISOString(),
            'updated_at' => $ticket->updated_at?->toISOString(),
        ];
    }

    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');
        if (! $patientId) {
            abort(401, 'Unauthenticated.');
        }

        return $patientId;
    }
}
