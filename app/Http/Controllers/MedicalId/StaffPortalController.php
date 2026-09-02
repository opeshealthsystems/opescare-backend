<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\ReferralCase;
use App\Models\SupportTicket;
use App\Models\VaccinationSchedule;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Billing\Services\PaymentService;
use App\Modules\Immunization\Services\ImmunizationService;
use App\Modules\Queue\Services\QueueService;
use App\Modules\Referral\Services\ReferralService;
use App\Modules\Search\Services\GlobalSearchService;
use App\Modules\Support\Services\SupportService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $patient = Patient::where('health_id', $healthId)->first();
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
        $query = Appointment::query()->orderByDesc('scheduled_at');

        if ($request->filled('facility_id')) {
            $query->where('facility_id', $request->facility_id);
        } else {
            $this->ctx->scopeToFacility($query);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', 'like', '%'.$request->patient_id.'%');
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
        $facilities = Facility::orderBy('name')->limit(20)->get();
        $patients   = Patient::whereNotNull('health_id')->orderBy('created_at', 'desc')->limit(20)->get();

        return view('portals.staff.appointments_create', compact('facilities', 'patients'));
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

        abort_unless(
            $facilityId !== null && $facilityId !== '',
            409,
            'No facility is selected for this session, so there is no way to tell which '
            . 'facility this appointment belongs to. Select a facility first.'
        );

        try {
            $appointment = Appointment::create([
                'patient_id'            => $request->patient_id,
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
            $appointment = Appointment::findOrFail($id);
            $svc->checkIn($appointment, $this->ctx->actorId());

            return redirect()->route('portals.staff.appointments')
                ->with('success', __('public.staff_portal.appointment_checked_in', [], app()->getLocale()) ?: 'Patient checked in.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function appointmentsNoShow(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update([
            'status'     => 'no_show',
            'no_show_at' => now(),
        ]);

        return redirect()->route('portals.staff.appointments')
            ->with('success', __('public.staff_portal.appointment_no_show', [], app()->getLocale()) ?: 'Appointment marked as no-show.');
    }

    // ─── Queue ────────────────────────────────────────────────────

    public function queue(Request $request)
    {
        $query = QueueTicket::query()
            ->orderBy('priority_level')
            ->orderBy('checked_in_at');

        if ($request->filled('facility_id')) {
            $query->where('facility_id', $request->facility_id);
        } else {
            $this->ctx->scopeToFacility($query);
        }

        if ($request->filled('queue_name')) {
            $query->where('current_queue', $request->queue_name);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['waiting', 'called', 'service_started']);
        }

        $entries = $query->limit(100)->get();

        return view('portals.staff.queue', compact('entries'));
    }

    public function queueDisplay(Request $request)
    {
        $facilityId = $request->query('facility_id') ?? $this->ctx->facilityId();

        $tickets = QueueTicket::whereIn('status', ['waiting', 'called', 'service_started'])
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->orderBy('priority_level')
            ->orderBy('checked_in_at')
            ->limit(20)
            ->get();

        return view('portals.staff.queue_display', compact('tickets'));
    }

    public function queueCheckIn(Request $request, QueueService $svc)
    {
        $request->validate([
            'patient_id'        => 'required|string',
            'destination_queue' => 'required|string',
        ]);

        $facilityId = $this->ctx->facilityId() ?? $request->input('facility_id');

        if (!$facilityId) {
            return redirect()->back()->with('error', __('flash.no_facility_context_select'));
        }

        try {
            $svc->checkInWalkIn([
                'patient_id'        => $request->patient_id,
                'facility_id'       => $facilityId,
                'destination_queue' => $request->destination_queue,
                'visit_type'        => 'outpatient',
                'actor_id'          => $this->ctx->actorId(),
                'check_in_type'     => 'walk_in',
            ]);

            return redirect()->route('portals.staff.queue')
                ->with('success', __('public.staff_portal.queue_checked_in', [], app()->getLocale()) ?: 'Patient added to queue.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function queueCall(Request $request, string $id, QueueService $svc)
    {
        try {
            $ticket = QueueTicket::findOrFail($id);
            $ticket->update([
                'status'    => 'called',
                'called_at' => now(),
            ]);

            return redirect()->route('portals.staff.queue')
                ->with('success', __('public.staff_portal.queue_called', [], app()->getLocale()) ?: 'Patient called.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function queueStart(Request $request, string $id, QueueService $svc)
    {
        try {
            $ticket = QueueTicket::findOrFail($id);
            $svc->startService($ticket, $this->ctx->actorId());

            return redirect()->route('portals.staff.queue')
                ->with('success', __('public.staff_portal.queue_service_started', [], app()->getLocale()) ?: 'Service started.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function queueComplete(Request $request, string $id, QueueService $svc)
    {
        $request->validate(['reason' => 'nullable|string|max:300']);

        try {
            $ticket = QueueTicket::findOrFail($id);
            $svc->complete($ticket, $request->reason ?: 'Completed by staff.', $this->ctx->actorId());

            return redirect()->route('portals.staff.queue')
                ->with('success', __('public.staff_portal.queue_completed', [], app()->getLocale()) ?: 'Queue ticket completed.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ─── Billing ─────────────────────────────────────────────────

    public function billing(Request $request)
    {
        $query = Invoice::query()->orderByDesc('issued_at');

        if (!$request->filled('facility_id')) {
            $this->ctx->scopeToFacility($query);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', 'like', '%'.$request->patient_id.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->limit(100)->get();

        return view('portals.staff.billing', compact('invoices'));
    }

    public function billingCreate(Request $request)
    {
        $patients = Patient::whereNotNull('health_id')->orderBy('created_at', 'desc')->limit(20)->get();

        return view('portals.staff.billing_create', compact('patients'));
    }

    public function billingStore(Request $request, BillingService $svc)
    {
        $request->validate([
            'patient_id'              => 'required|string',
            'items'                   => 'required|array|min:1',
            'items.*.description'     => 'required|string',
            'items.*.quantity'        => 'required|numeric|min:1',
            'items.*.unit_price'      => 'required|numeric|min:0',
        ]);

        $facilityId = $this->ctx->facilityId() ?? $request->input('facility_id');

        if (!$facilityId) {
            return redirect()->back()->withInput()->with('error', __('flash.no_facility_context'));
        }

        try {
            $invoice = $svc->createInvoice([
                'patient_id'  => $request->patient_id,
                'facility_id' => $facilityId,
                'items'       => $request->items,
                'actor_id'    => $this->ctx->actorId(),
            ]);

            return redirect()->route('portals.staff.billing')
                ->with('success', (__('public.staff_portal.invoice_created', [], app()->getLocale()) ?: 'Invoice created:').' '.$invoice->invoice_number);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function billingPay(Request $request, string $id, PaymentService $svc)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($id);
            $svc->recordPayment($invoice, [
                'amount'           => $request->amount,
                'payment_method'   => $request->payment_method,
                'actor_id'         => $this->ctx->actorId(),
                'reference_number' => $request->reference_number,
            ]);

            return redirect()->route('portals.staff.billing')
                ->with('success', __('public.staff_portal.payment_recorded', [], app()->getLocale()) ?: 'Payment recorded successfully.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ─── Support ─────────────────────────────────────────────────

    public function support(Request $request)
    {
        $query = SupportTicket::withCount('messages')->orderByDesc('created_at');

        $this->ctx->scopeToFacility($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->limit(100)->get();

        return view('portals.staff.support', compact('tickets'));
    }

    public function supportStore(Request $request, SupportService $svc)
    {
        $request->validate([
            'subject'     => 'required|string|max:200',
            'category'    => 'required|string',
            'priority'    => 'required|in:normal,high,urgent,critical',
            'description' => 'required|string|min:10|max:2000',
        ]);

        try {
            $svc->createTicket([
                'requester_type' => 'staff',
                'requester_id'   => $this->ctx->actorId(),
                'facility_id'    => $this->ctx->facilityId(),
                'category'       => $request->category,
                'priority'       => $request->priority,
                'subject'        => $request->subject,
                'description'    => $request->description,
            ], $this->ctx->actorId());

            return redirect()->route('portals.staff.support')
                ->with('success', __('public.staff_portal.ticket_created', [], app()->getLocale()) ?: 'Support ticket created.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function supportReply(Request $request, string $id, SupportService $svc)
    {
        $request->validate(['body' => 'required|string|min:2|max:2000']);

        try {
            $ticket = SupportTicket::findOrFail($id);
            $svc->addMessage($ticket, [
                'sender_type' => 'staff',
                'sender_id'   => $this->ctx->actorId(),
                'body'        => $request->body,
                'internal'    => false,
            ], $this->ctx->actorId());

            return redirect()->route('portals.staff.support')
                ->with('success', __('public.staff_portal.ticket_reply_sent', [], app()->getLocale()) ?: 'Reply sent.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function supportClose(Request $request, string $id, SupportService $svc)
    {
        try {
            $ticket = SupportTicket::findOrFail($id);
            $svc->resolveTicket($ticket, $this->ctx->actorId(), $request->resolution_note);

            return redirect()->route('portals.staff.support')
                ->with('success', __('public.staff_portal.ticket_closed', [], app()->getLocale()) ?: 'Ticket resolved.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function supportEscalate(Request $request, string $id, SupportService $svc)
    {
        $request->validate([
            'escalation_level' => 'required|in:l1,l2,l3,management',
            'reason'           => 'nullable|string|max:500',
        ]);

        try {
            $ticket = SupportTicket::findOrFail($id);
            $svc->escalateTicket($ticket, $request->escalation_level, $this->ctx->actorId(), $request->reason);

            return redirect()->route('portals.staff.support')
                ->with('success', __('public.staff_portal.ticket_escalated', [], app()->getLocale()) ?: 'Ticket escalated.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function supportAssign(Request $request, string $id, SupportService $svc)
    {
        $request->validate(['assigned_to' => 'required|string|max:100']);

        try {
            $ticket = SupportTicket::findOrFail($id);
            $svc->assignTicket($ticket, $request->assigned_to, $this->ctx->actorId());

            return redirect()->route('portals.staff.support')
                ->with('success', __('public.staff_portal.ticket_assigned', [], app()->getLocale()) ?: 'Ticket assigned.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ─── Referrals ────────────────────────────────────────────────

    public function referrals(Request $request)
    {
        $facilityId = $this->ctx->facilityId();

        $referrals = ReferralCase::query()
            ->when($facilityId, fn ($q) => $q->where(function ($w) use ($facilityId) {
                $w->where('referring_facility_id', $facilityId)
                  ->orWhere('receiving_facility_id', $facilityId);
            }))
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->input('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('urgency', $request->input('priority')))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('portals.staff.referrals.index', compact('referrals'));
    }

    public function referralsCreate(Request $request)
    {
        return view('portals.staff.referrals.create', [
            'facilityId' => $this->ctx->facilityId(),
        ]);
    }

    public function referralsStore(Request $request, ReferralService $service)
    {
        $validated = $request->validate([
            'patient_id'            => 'required|string',
            'urgency'               => 'required|in:routine,urgent,emergency',
            'referring_facility_id' => 'required|string',
            'receiving_facility_id' => 'nullable|string',
            'receiving_specialty'   => 'nullable|string|max:120',
            'reason'                => 'required|string|min:10',
            'clinical_summary'      => 'nullable|string',
        ]);
        $validated['created_by_id'] = $this->ctx->actorId();

        try {
            $referral = $service->create($validated);

            return redirect()->route('portals.staff.referrals.show', $referral->id)
                ->with('success', __('flash.referral_draft_created'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function referralsShow(Request $request, $id)
    {
        $referral = ReferralCase::with(['patient', 'referringFacility', 'receivingFacility'])
            ->findOrFail($id);

        return view('portals.staff.referrals.show', compact('referral'));
    }

    public function referralsSend(Request $request, $id, ReferralService $service)
    {
        $referral = ReferralCase::findOrFail($id);

        try {
            $service->send($referral, $this->ctx->actorId());

            return redirect()->route('portals.staff.referrals.show', $id)->with('success', __('flash.referral_sent'));
        } catch (Throwable $e) {
            return redirect()->route('portals.staff.referrals.show', $id)->with('error', $e->getMessage());
        }
    }

    public function referralsAccept(Request $request, $id, ReferralService $service)
    {
        $referral = ReferralCase::findOrFail($id);

        try {
            $service->accept($referral, (string) $this->ctx->actorId());

            return redirect()->route('portals.staff.referrals.show', $id)->with('success', __('flash.referral_accepted'));
        } catch (Throwable $e) {
            return redirect()->route('portals.staff.referrals.show', $id)->with('error', $e->getMessage());
        }
    }

    public function referralsReject(Request $request, $id, ReferralService $service)
    {
        $referral = ReferralCase::findOrFail($id);
        $reason   = $request->input('reason') ?: __('flash.referral_rejected');

        try {
            $service->reject($referral, $reason);

            return redirect()->route('portals.staff.referrals.show', $id)->with('success', __('flash.referral_rejected'));
        } catch (Throwable $e) {
            return redirect()->route('portals.staff.referrals.show', $id)->with('error', $e->getMessage());
        }
    }

    public function referralsComplete(Request $request, $id, ReferralService $service)
    {
        $referral = ReferralCase::findOrFail($id);

        try {
            $service->complete($referral, $request->input('feedback'));

            return redirect()->route('portals.staff.referrals.show', $id)->with('success', __('flash.referral_completed'));
        } catch (Throwable $e) {
            return redirect()->route('portals.staff.referrals.show', $id)->with('error', $e->getMessage());
        }
    }

    public function referralsCancel(Request $request, $id, ReferralService $service)
    {
        $referral = ReferralCase::findOrFail($id);
        $reason   = $request->input('reason') ?: __('flash.referral_cancelled');

        try {
            $service->cancel($referral, $reason);

            return redirect()->route('portals.staff.referrals')->with('success', __('flash.referral_cancelled'));
        } catch (Throwable $e) {
            return redirect()->route('portals.staff.referrals.show', $id)->with('error', $e->getMessage());
        }
    }

    // ─── Immunizations ────────────────────────────────────────────

    public function immunizations(Request $request)
    {
        $facilityId     = $this->ctx->facilityId();
        $patientId      = trim((string) $request->input('patient_id', ''));
        $facilityFilter = trim((string) $request->input('facility_id', '')) ?: $facilityId;

        $records = ImmunizationRecord::query()
            ->where('status', '!=', 'entered_in_error')
            ->when($facilityFilter, fn ($q) => $q->where('facility_id', $facilityFilter))
            ->when($patientId !== '', fn ($q) => $q->where('patient_id', $patientId))
            ->orderByDesc('administered_at')
            ->limit(100)
            ->get();

        $schedule = VaccinationSchedule::query()
            ->whereIn('status', ['due', 'overdue'])
            ->when(
                $patientId !== '',
                fn ($q) => $q->where('patient_id', $patientId),
                fn ($q) => $q->whereIn('patient_id', $records->pluck('patient_id')->unique()->all())
            )
            ->orderBy('due_date')
            ->limit(100)
            ->get();

        return view('portals.staff.immunizations.index', compact('records', 'schedule'));
    }

    public function immunizationsRecord(Request $request)
    {
        return view('portals.staff.immunizations.record', [
            'facilityId' => $this->ctx->facilityId(),
        ]);
    }

    public function immunizationsStore(Request $request, ImmunizationService $service)
    {
        $validated = $request->validate([
            'patient_id'      => 'required|string',
            'facility_id'     => 'required|string',
            'vaccine_code'    => 'required|string',
            'vaccine_name'    => 'required|string',
            'administered_at' => 'required|date',
            'status'          => 'required|in:completed,not_done',
            'dose_number'     => 'nullable|integer|min:1',
            'lot_number'      => 'nullable|string|max:100',
            'route'           => 'nullable|string|max:50',
            'site'            => 'nullable|string|max:50',
            'not_done_reason' => 'nullable|string|max:255',
        ]);
        $validated['administered_by_id'] = $this->ctx->actorId();

        try {
            $service->record($validated);

            return redirect()->route('portals.staff.immunizations')->with('success', __('flash.immunization_saved'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
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
