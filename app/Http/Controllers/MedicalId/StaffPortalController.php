<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\ReferralCase;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Search\Services\GlobalSearchService;
use App\Services\Appointments\AppointmentWaitlistService;
use App\Services\FrontDesk\FrontDeskService;
use App\Services\Portal\PortalContextService;
use App\Services\Queue\PatientQueueService;
use Illuminate\Http\Request;
use Throwable;

class StaffPortalController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    // ─── Dashboard ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();

        $apptQuery    = Appointment::whereBetween('scheduled_at', [$todayStart, $todayEnd]);
        // "In queue" = still in the room TODAY. The status list is the one
        // PatientQueueService owns, so the tile and the desk can never count
        // different things; the day bound stops a ticket nobody closed last
        // week from sitting on the dashboard forever.
        $queueQuery   = QueueTicket::whereIn('status', PatientQueueService::ACTIVE_STATUSES)
            ->whereBetween('checked_in_at', [$todayStart, $todayEnd]);
        $invoiceQuery = Invoice::where('status', 'issued');

        $this->ctx->scopeToFacility($apptQuery);
        $this->ctx->scopeToFacility($queueQuery);
        $this->ctx->scopeToFacility($invoiceQuery);

        $kpis = [
            'todays_appointments' => $apptQuery->count(),
            'in_queue'            => $queueQuery->count(),
            'pending_referrals'   => ReferralCase::query()
                ->where('status', 'draft')
                ->when($this->ctx->facilityId(), function ($q) {
                    $fid = $this->ctx->facilityId();
                    $q->where(fn ($w) => $w->where('referring_facility_id', $fid)->orWhere('receiving_facility_id', $fid));
                })
                ->count(),
            'open_invoices'       => $invoiceQuery->count(),
        ];

        // Patient verification — the dashboard "Verify Patient" form (GET) resolves
        // a Health ID, surfaces the patient's identity + verification status, and
        // logs the access for audit. Previously this form was inert (reloaded the
        // dashboard without resolving anything).
        $verification = null;
        $healthId = trim((string) $request->input('health_id', ''));
        if ($healthId !== '') {
            $patient = Patient::resolveHealthId($healthId);
            if ($patient) {
                $this->ctx->auditPatientAccess(
                    actionType:   'staff_patient_verification',
                    resourceType: 'Patient',
                    resourceId:   $patient->id,
                    patientId:    $patient->id,
                );
            }
            $verification = [
                'health_id' => $healthId,
                'purpose'   => $request->input('purpose'),
                'patient'   => $patient,
            ];
        }

        return view('portals.staff.index', compact('kpis', 'verification'));
    }

    // ─── Appointments ─────────────────────────────────────────────

    public function appointments(Request $request)
    {
        // Always this session's facility. A `facility_id` query parameter used
        // to be honoured here, which let any staff user read another
        // facility's appointment book by editing the URL — the cross-facility
        // read this codebase treats as a categorical prohibition.
        $query = $this->ctx->scopeToFacility(Appointment::query()->orderByDesc('scheduled_at'));

        if ($request->filled('patient_id')) {
            // Exact Health ID (or exact patient id) only. The previous
            // `LIKE %…%` was a patient enumeration search, which no OpesCare
            // surface may offer; a partial value now matches nothing.
            $patientId = $this->resolvePatientId((string) $request->patient_id);
            $query->where('patient_id', $patientId ?? '00000000-0000-0000-0000-000000000000');
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }
        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Eager-loaded: the table prints the patient's Health ID and phone and
        // the clinician's name for every row, which used to be one query per
        // row (and a raw provider uuid). Paginated: `limit(100)` silently
        // dropped the rest of a hospital's book with no page 2.
        $appointments = $query
            ->with(['patient:id,health_id,first_name,last_name,phone_number', 'provider:id,name'])
            ->paginate(50)
            ->withQueryString();

        return view('portals.staff.appointments', [
            'appointments' => $appointments,
            'facilityName' => $this->activeFacilityName(),
            // The clinicians posted HERE, for the filter — never a typed uuid.
            'clinicians'   => $this->clinicians(),
            // The column vocabulary the database actually holds, from the one
            // service that owns it (FrontDeskService), so the filter offers
            // `booked` (a patient's own booking) and not only `scheduled`.
            'statuses'     => array_values(array_unique(array_merge(FrontDeskService::ACTIVE_STATUSES, FrontDeskService::CLOSED_STATUSES))),
        ]);
    }

    /** The name of the facility this desk acts for, for display only. */
    private function activeFacilityName(): ?string
    {
        $facilityId = $this->ctx->facilityId();

        return $facilityId
            ? Facility::withoutGlobalScopes()->whereKey($facilityId)->value('name')
            : null;
    }

    /** Clinicians posted at this session's facility (FrontDeskService::clinicians). */
    private function clinicians()
    {
        $facilityId = $this->ctx->facilityId();

        return $facilityId ? app(FrontDeskService::class)->clinicians($facilityId) : collect();
    }

    public function appointmentsCreate(Request $request)
    {
        // No patient list: the form takes the patient's exact Health ID. The
        // dropdown of the twenty most recent patients in the country was a
        // patient enumeration, and it was not even scoped to this facility.
        $patients = collect();

        // Booking from the waitlist: the entry pre-fills the patient and type.
        // Another facility's entry does not exist here (404, never 403).
        $waitlistEntry = null;
        if ($request->filled('waitlist')) {
            $waitlistEntry = $this->facilityWaitlistQuery()
                ->with('patient:id,health_id')
                ->findOrFail((string) $request->query('waitlist'));
        }

        // The form shows the facility it books into; it used to show a generic
        // "the facility you are signed in to" because nothing passed the name.
        $activeFacilityName = $this->activeFacilityName();

        return view('portals.staff.appointments_create', compact('patients', 'waitlistEntry', 'activeFacilityName'));
    }

    /** The waitlist of this session's facility — never any other. */
    private function facilityWaitlistQuery()
    {
        $facilityId = $this->ctx->facilityId();

        return AppointmentWaitlist::query()
            ->where('facility_id', $facilityId ?: '00000000-0000-0000-0000-000000000000');
    }

    /**
     * Who is waiting for a slot at this facility: urgent first, then the
     * order they joined — decided in SQL so the desk and the notifier agree.
     */
    public function appointmentsWaitlist(Request $request)
    {
        $entries = $this->facilityWaitlistQuery()
            ->whereIn('status', ['waiting', 'notified'])
            ->with('patient:id,health_id,first_name,last_name')
            ->orderByRaw("CASE urgency WHEN 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        return view('portals.staff.appointments_waitlist', compact('entries'));
    }

    /**
     * The patient a staff member typed: an exact Health ID (case and spacing
     * forgiven) or an exact patient id. Never a partial match.
     */
    private function resolvePatientId(string $entered): ?string
    {
        $entered = trim($entered);

        if ($entered === '') {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $entered)) {
            return Patient::whereKey($entered)->value('id');
        }

        $healthId = strtoupper(preg_replace('/\s+/', '', $entered));

        return Patient::where('health_id', $healthId)->value('id');
    }

    public function appointmentsStore(Request $request, AppointmentService $svc, AppointmentWaitlistService $waitlist)
    {
        $request->validate([
            'patient_id'       => 'required|string',
            'appointment_type' => 'required|string',
            'scheduled_at'     => 'required|date',
            'reason'           => 'nullable|string|max:500',
            'waitlist_id'      => 'nullable|uuid',
        ]);

        /*
         * The facility comes from the signed-in session, never from the request.
         *
         * This used to validate and trust `facility_id` from the body, so a
         * clerk could book an appointment into any facility in the country by
         * changing one form field. Taking a facility id from user input is the
         * cross-facility IDOR this codebase already calls out as a categorical
         * prohibition; the same rule applies here as on the API.
         */
        $facilityId = $this->ctx->facilityId();

        // Exact Health ID or patient id — the same rule as the filter above.
        $patientId = $this->resolvePatientId((string) $request->patient_id);
        if ($patientId === null) {
            return redirect()->back()->withInput()->withErrors(['patient_id' => __('api.patient_not_found')]);
        }

        abort_unless(
            $facilityId !== null && $facilityId !== '',
            409,
            __('staff.desk.no_facility_for_booking')
        );

        try {
            $appointment = Appointment::create([
                'patient_id'            => $patientId,
                'facility_id'           => $facilityId,
                'appointment_type'      => $request->appointment_type,
                'status'                => 'scheduled',
                'scheduled_at'          => $request->scheduled_at,
                'booked_by_type'        => 'staff',
                'booked_by_id'          => $this->ctx->actorId(),
                'reason'                => $request->reason,
                'billing_deferred'      => true,
                'telemedicine_deferred' => true,
            ]);

            // Booked off the waitlist: close the entry. Only an entry of THIS
            // facility, for THIS patient — a foreign or mismatched id is ignored
            // rather than trusted, and the booking itself stands either way.
            if ($request->filled('waitlist_id')) {
                $entry = $this->facilityWaitlistQuery()
                    ->where('patient_id', $patientId)
                    ->whereIn('status', ['waiting', 'notified'])
                    ->find((string) $request->waitlist_id);
                if ($entry) {
                    rescue(fn () => $waitlist->bookFromWaitlist($entry->id, $appointment->id), null, false);
                }
            }

            return redirect()->route('portals.staff.appointments')
                ->with('success', __('public.staff_portal.appointment_booked', [], app()->getLocale()) ?: 'Appointment booked successfully.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function appointmentsConfirm(Request $request, string $id)
    {
        // Scoped to this session's facility. findOrFail($id) alone let any
        // staff user confirm any appointment in the country — the record is
        // another facility's patient data, and confirming it tells a patient
        // somewhere else that their slot is booked.
        $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);

        // Only a pending booking can be confirmed — `booked` (the patient's
        // own request from the app) as much as `scheduled`. This used to
        // rewrite ANY status to `confirmed`, a completed or cancelled visit
        // included, and the table offered Confirm on `scheduled` only, so a
        // patient's own request could never be confirmed from the web.
        if (! in_array($appointment->status, FrontDeskService::PENDING_STATUSES, true)) {
            return redirect()->route('portals.staff.appointments')
                ->with('error', __('desk_api.' . ($appointment->status === 'confirmed' ? 'already_confirmed' : 'not_confirm_eligible')));
        }

        $appointment->update(['status' => 'confirmed']);

        $this->ctx->auditPatientAccess(
            actionType:   'staff_appointment_confirmed',
            resourceType: 'Appointment',
            resourceId:   $appointment->id,
            patientId:    $appointment->patient_id,
        );

        return redirect()->route('portals.staff.appointments')
            ->with('success', __('public.staff_portal.appointment_confirmed', [], app()->getLocale()) ?: 'Appointment confirmed.');
    }

    public function appointmentsCancel(Request $request, string $id, AppointmentService $svc, AppointmentWaitlistService $waitlist)
    {
        $request->validate(['reason' => 'required|string|min:5|max:500']);

        // Same scoping as confirm: cancelling another facility's appointment
        // silently cancels a real patient's real slot. Resolved OUTSIDE the
        // try: the catch (Throwable) below turned a foreign id's 404 into a
        // redirect-back carrying the raw "No query results" string.
        $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);

        try {
            $cancelled   = $svc->cancel($appointment, $request->reason, $this->ctx->actorId());

            $this->ctx->auditPatientAccess(
                actionType:   'staff_appointment_cancelled',
                resourceType: 'Appointment',
                resourceId:   $appointment->id,
                patientId:    $appointment->patient_id,
            );

            // The slot is free: tell the next patient on the waitlist.
            // Best-effort — the cancel is already committed.
            rescue(fn () => $waitlist->announceFreedSlot($cancelled), null, false);

            return redirect()->route('portals.staff.appointments')
                ->with('success', __('public.staff_portal.appointment_cancelled', [], app()->getLocale()) ?: 'Appointment cancelled.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function appointmentsCheckIn(Request $request, string $id, PatientQueueService $queue)
    {
        // Scoped to this session's facility, like confirm/cancel — a bare
        // findOrFail let any staff user check a patient in at another facility.
        $appointment = $this->ctx->scopeToFacility(Appointment::query())->with('patient')->findOrFail($id);

        // ONE check-in path. This used to call AppointmentService::checkIn(),
        // which accepts scheduled|confirmed only (so a patient's own `booked`
        // request was refused with a raw exception string) and opened a visit
        // WITHOUT a queue ticket — while the queue desk's check-in of the same
        // booking did both. Two doors, two different results for one patient.
        // PatientQueueService::checkIn() is the door the queue desk uses: it
        // checks the appointment in through FrontDeskService (the wide status
        // vocabulary), opens the visit and issues the ticket.
        if ($appointment->patient === null) {
            return redirect()->back()->with('error', __('api.patient_not_found'));
        }

        try {
            $ticket = $queue->checkIn([
                'patient'        => $appointment->patient,
                'facility_id'    => (string) $appointment->facility_id,
                'actor_id'       => (string) $this->ctx->actorId(),
                'appointment'    => $appointment,
                'priority_level' => PatientQueueService::DEFAULT_ARRIVAL_PRIORITY,
                'ip'             => $request->ip(),
            ]);
        } catch (\App\Services\Queue\QueueRefusal $e) {
            return redirect()->route('portals.staff.appointments')
                ->with('error', __('queue_desk.refusal.' . $e->key));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'queue_checked_in',
            resourceType: 'QueueTicket',
            resourceId:   $ticket->id,
            patientId:    $appointment->patient_id,
        );

        return redirect()->route('portals.staff.appointments')
            ->with('success', __('queue_desk.checked_in', ['number' => PatientQueueService::displayNumber($ticket)]));
    }

    public function appointmentsNoShow(Request $request, string $id)
    {
        $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);

        // Same vocabulary as the phone desk: a no-show may follow a pending,
        // confirmed or checked-in booking and nothing that is already closed.
        if (! in_array($appointment->status, FrontDeskService::NO_SHOW_FROM, true)) {
            return redirect()->route('portals.staff.appointments')
                ->with('error', __('desk_api.' . ($appointment->status === 'no_show' ? 'already_no_show' : 'not_no_show_eligible')));
        }

        $appointment->update([
            'status'     => 'no_show',
            'no_show_at' => now(),
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'staff_appointment_no_show',
            resourceType: 'Appointment',
            resourceId:   $appointment->id,
            patientId:    $appointment->patient_id,
        );

        return redirect()->route('portals.staff.appointments')
            ->with('success', __('public.staff_portal.appointment_no_show', [], app()->getLocale()) ?: 'Appointment marked as no-show.');
    }

    // ─── Global Search ────────────────────────────────────────────

    public function search(Request $request, GlobalSearchService $svc)
    {
        $query = trim($request->input('q', ''));

        // No `include_sensitive` flag: GlobalSearchService no longer has a
        // patient branch to switch on. The flag used to be the only thing
        // between this screen and a name-prefix search of the patient
        // register, so the branch was deleted rather than left off.
        $context = [
            'actor_id'    => $this->ctx->actorId(),
            'facility_id' => $this->ctx->facilityId(),
        ];

        $data = $query !== '' ? $svc->search($query, $context) : ['query' => '', 'results' => [], 'counts' => []];

        $grouped = collect($data['results'])->groupBy('type');

        return view('portals.staff.search', [
            'query'   => $query,
            'grouped' => $grouped,
            'counts'  => $data['counts'],
            'total'   => count($data['results']),
        ]);
    }
}
