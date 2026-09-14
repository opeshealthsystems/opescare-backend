<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Services\FrontDesk\FrontDeskService;
use App\Services\Portal\PortalContextService;
use App\Services\Queue\PatientQueueService;
use App\Services\Queue\QueueRefusal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The patient queue desk — the screen a facility runs its waiting room from.
 *
 * Nine queue tables and nine models shipped with the operational MVP; nothing
 * wrote to them and nothing read them but a dashboard tile that was
 * therefore always zero. This controller is the first surface, and only a
 * surface: every write and the ranked read live in
 * App\Services\Queue\PatientQueueService, which owns the rules.
 *
 * ── SCOPE ──────────────────────────────────────────────────────────────────
 *
 * The facility comes from PortalContextService — the web tier's convention —
 * and never from the request. `queue_tickets.facility_id` is the tenancy
 * boundary: every read here starts from scoped(), so a ticket belonging to
 * another facility is not refused, it does not exist (404). The role gate is
 * `capability:queue` on the routes, which answers 403 `role_forbidden`.
 *
 * The patient arrives by EXACT Health ID or from THIS facility's appointment
 * book — no name search, no partial match, no listing. Same rule as every
 * other desk on this platform.
 *
 * ── WHAT THIS DESK IS NOT ──────────────────────────────────────────────────
 *
 * It is not a gate. Nothing that consults, prescribes, triages or dispenses
 * reads a ticket, and a patient with no ticket is seen exactly as one with a
 * ticket is (tests/Feature/Architecture/CareIsNeverGatedOnQueueTest).
 */
class QueueDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PatientQueueService $queue,
    ) {
    }

    /**
     * The live queue: everyone active at this facility, MOST URGENT FIRST,
     * then longest waiting — ordered in SQL by PatientQueueService::ranked().
     * Beneath it, the day's closed tickets and the day's measured figures.
     */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();
        $today      = now();

        $active = $this->queue->ranked($facilityId)
            ->with(['patient', 'assignedTo:id,name'])
            ->get();

        $closed = $this->queue->closedOn($facilityId, $today)
            ->with(['patient'])
            ->limit(50)
            ->get();

        $now = now();

        $rows = fn ($tickets) => $tickets->map(fn (QueueTicket $ticket) => [
            'ticket'    => $ticket,
            'durations' => $this->queue->durations($ticket, $now),
            'number'    => PatientQueueService::displayNumber($ticket),
        ]);

        return view('portals.queue.index', [
            'activeRows' => $rows($active),
            'closedRows' => $rows($closed),
            'summary'    => $this->queue->summaryFor($facilityId, $today),
            'facilityId' => $facilityId,
        ]);
    }

    /**
     * Check-in. Two doors, both exact: the patient's Health ID, or one of
     * today's bookings at this facility not yet in the queue.
     */
    public function create(Request $request): View
    {
        $facilityId = $this->facilityId();

        $patient  = null;
        $notFound = false;
        $existing = null;

        if ($healthId = trim((string) $request->input('health_id'))) {
            // resolveHealthId follows a merge, so a retired Health ID opens the
            // surviving record rather than an empty shell.
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;
            $existing = $patient ? $this->queue->activeTicketFor($patient->id, $facilityId) : null;
        }

        return view('portals.queue.create', [
            'patient'      => $patient,
            'notFound'     => $notFound,
            'existing'     => $existing,
            'appointments' => $this->todaysBookings($facilityId),
            'priorities'   => PatientQueueService::ARRIVAL_PRIORITIES,
        ]);
    }

    /** Create the ticket (and the appointment check-in when it is for a booking). */
    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'patient_id'      => 'required_without:appointment_id|nullable|uuid',
            'appointment_id'  => 'nullable|uuid',
            'priority_level'  => ['nullable', 'integer', 'in:' . implode(',', PatientQueueService::ARRIVAL_PRIORITIES)],
            // A priority above standard must say why. "The patient said so"
            // is a reason; silence is not.
            'priority_reason' => [
                \Illuminate\Validation\Rule::requiredIf(
                    $request->filled('priority_level')
                    && (int) $request->input('priority_level') !== PatientQueueService::DEFAULT_ARRIVAL_PRIORITY
                ),
                'nullable', 'string', 'max:500',
            ],
        ]);

        $appointment = null;

        if (! empty($validated['appointment_id'])) {
            // Inside the facility bound or not at all: another facility's
            // booking 404s exactly as its tickets do.
            $appointment = Appointment::query()
                ->where('facility_id', $facilityId)
                ->findOrFail($validated['appointment_id']);
            $patient = $appointment->patient;
        } else {
            $patient = Patient::resolveId($validated['patient_id']);
        }

        if ($patient === null) {
            return back()->withInput()->with('error', __('queue_desk.not_found'));
        }

        try {
            $ticket = $this->queue->checkIn([
                'patient'         => $patient,
                'facility_id'     => $facilityId,
                'actor_id'        => (string) auth()->id(),
                'appointment'     => $appointment,
                'priority_level'  => $validated['priority_level'] ?? PatientQueueService::DEFAULT_ARRIVAL_PRIORITY,
                'priority_reason' => $validated['priority_reason'] ?? null,
                'ip'              => $request->ip(),
            ]);
        } catch (QueueRefusal $e) {
            return redirect()->route('portals.queue.index')
                ->with('error', __('queue_desk.refusal.' . $e->key));
        }

        $this->context->auditPatientAccess(
            'queue_checked_in',
            'QueueTicket',
            $ticket->id,
            $patient->id,
            ['facility_id' => $facilityId]
        );

        return redirect()->route('portals.queue.index')
            ->with('success', __('queue_desk.checked_in', ['number' => PatientQueueService::displayNumber($ticket)]));
    }

    /** One ticket: its timestamps, its measured durations, its full history. */
    public function show(Request $request, string $id): View
    {
        $ticket = $this->scoped()
            ->with(['patient', 'assignedTo:id,name', 'histories', 'appointment'])
            ->findOrFail($id);

        $this->context->auditPatientAccess('queue_ticket_view', 'QueueTicket', $ticket->id, $ticket->patient_id);

        // `queue_status_histories.changed_by` is a string column holding the
        // actor's user id; resolved to names here, once, for the whole page.
        $actorIds = $ticket->histories->pluck('changed_by')->filter()->unique()->values();
        $actors   = $actorIds->isEmpty()
            ? collect()
            : \App\Models\User::query()->whereIn('id', $actorIds)->pluck('name', 'id');

        return view('portals.queue.show', [
            'ticket'    => $ticket,
            'number'    => PatientQueueService::displayNumber($ticket),
            'durations' => $this->queue->durations($ticket),
            'actors'    => $actors,
        ]);
    }

    // ── Transitions. POST only; there is no PUT/PATCH and no delete: a ticket
    //    moves forward by appending history, never by being rewritten. ──────

    public function call(Request $request, string $id): RedirectResponse
    {
        return $this->apply($id, fn (QueueTicket $t) => $this->queue->call($t, (string) auth()->id()), 'called');
    }

    public function start(Request $request, string $id): RedirectResponse
    {
        return $this->apply($id, fn (QueueTicket $t) => $this->queue->start($t, (string) auth()->id()), 'started');
    }

    public function complete(Request $request, string $id): RedirectResponse
    {
        return $this->apply($id, fn (QueueTicket $t) => $this->queue->complete($t, (string) auth()->id()), 'completed');
    }

    public function noShow(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:500']);

        return $this->apply($id, fn (QueueTicket $t) => $this->queue->noShow($t, (string) auth()->id(), $validated['reason'] ?? null), 'no_show');
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        // Mandatory: a ticket that vanished for no recorded reason is the one
        // the day's figures cannot explain.
        $validated = $request->validate(['reason' => 'required|string|max:500']);

        return $this->apply($id, fn (QueueTicket $t) => $this->queue->cancel($t, (string) auth()->id(), $validated['reason']), 'cancelled');
    }

    // ── internals ─────────────────────────────────────────────────────────

    private function apply(string $id, callable $transition, string $doneKey): RedirectResponse
    {
        $ticket = $this->scoped()->findOrFail($id);

        try {
            $ticket = $transition($ticket);
        } catch (QueueRefusal $e) {
            return redirect()->route('portals.queue.index')
                ->with('error', __('queue_desk.refusal.' . $e->key));
        }

        $this->context->auditPatientAccess('queue_' . $doneKey, 'QueueTicket', $ticket->id, $ticket->patient_id);

        return redirect()->route('portals.queue.index')
            ->with('success', __('queue_desk.done.' . $doneKey, ['number' => PatientQueueService::displayNumber($ticket)]));
    }

    /**
     * Tickets at THIS facility only. Every query in this controller that
     * touches an existing ticket starts here.
     */
    private function scoped(): Builder
    {
        return QueueTicket::query()->where('facility_id', $this->facilityId());
    }

    /**
     * Today's live bookings at this facility that have no active ticket yet —
     * the list the receptionist checks in from. Read-only over `appointments`;
     * the check-in itself goes through FrontDeskService.
     */
    private function todaysBookings(string $facilityId)
    {
        $activeTicketPatients = QueueTicket::query()
            ->where('facility_id', $facilityId)
            ->whereIn('status', PatientQueueService::ACTIVE_STATUSES)
            ->select('patient_id');

        return Appointment::query()
            ->where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereIn('status', FrontDeskService::ACTIVE_STATUSES)
            ->whereNotIn('patient_id', $activeTicketPatients)
            ->with(['patient', 'provider:id,name'])
            ->orderBy('scheduled_at')
            ->limit(100)
            ->get();
    }

    /**
     * The facility this desk is working for — PortalContextService only.
     * Never a header, never the body, never a "first facility" fallback.
     */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('queue_desk.no_facility_context'));
        }

        return (string) $facilityId;
    }
}
