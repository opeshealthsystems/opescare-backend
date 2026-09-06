<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\ReferralCase;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Search\Services\GlobalSearchService;
use App\Services\Portal\PortalContextService;
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
        $queueQuery   = QueueTicket::whereIn('status', ['waiting', 'called', 'service_started']);
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

        $appointments = $query->limit(100)->get();

        return view('portals.staff.appointments', compact('appointments'));
    }

    public function appointmentsCreate(Request $request)
    {
        // No patient list: the form takes the patient's exact Health ID. The
        // dropdown of the twenty most recent patients in the country was a
        // patient enumeration, and it was not even scoped to this facility.
        $patients = collect();

        return view('portals.staff.appointments_create', compact('patients'));
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

    public function appointmentsStore(Request $request, AppointmentService $svc)
    {
        $request->validate([
            'patient_id'       => 'required|string',
            'appointment_type' => 'required|string',
            'scheduled_at'     => 'required|date',
            'reason'           => 'nullable|string|max:500',
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
            'No facility is selected for this session, so there is no way to tell which '
            . 'facility this appointment belongs to. Select a facility first.'
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

    public function appointmentsCancel(Request $request, string $id, AppointmentService $svc)
    {
        $request->validate(['reason' => 'required|string|min:5|max:500']);

        try {
            // Same scoping as confirm: cancelling another facility's
            // appointment silently cancels a real patient's real slot.
            $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);
            $svc->cancel($appointment, $request->reason, $this->ctx->actorId());

            $this->ctx->auditPatientAccess(
                actionType:   'staff_appointment_cancelled',
                resourceType: 'Appointment',
                resourceId:   $appointment->id,
                patientId:    $appointment->patient_id,
            );

            return redirect()->route('portals.staff.appointments')
                ->with('success', __('public.staff_portal.appointment_cancelled', [], app()->getLocale()) ?: 'Appointment cancelled.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function appointmentsCheckIn(Request $request, string $id, AppointmentService $svc)
    {
        try {
            // Scoped to this session's facility, like confirm/cancel — a bare
            // findOrFail let any staff user check a patient in at another facility.
            $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);
            $svc->checkIn($appointment, $this->ctx->actorId());

            return redirect()->route('portals.staff.appointments')
                ->with('success', __('public.staff_portal.appointment_checked_in', [], app()->getLocale()) ?: 'Patient checked in.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function appointmentsNoShow(Request $request, string $id)
    {
        $appointment = $this->ctx->scopeToFacility(Appointment::query())->findOrFail($id);
        $appointment->update([
            'status'     => 'no_show',
            'no_show_at' => now(),
        ]);

        return redirect()->route('portals.staff.appointments')
            ->with('success', __('public.staff_portal.appointment_no_show', [], app()->getLocale()) ?: 'Appointment marked as no-show.');
    }

    // ─── Global Search ────────────────────────────────────────────

    public function search(Request $request, GlobalSearchService $svc)
    {
        $query = trim($request->input('q', ''));

        $context = [
            'actor_id'          => $this->ctx->actorId(),
            'facility_id'       => $this->ctx->facilityId(),
            'include_sensitive' => false,
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
