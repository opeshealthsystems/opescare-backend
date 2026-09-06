<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GuardianAccessMiddleware;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\MedicalIdAccessEvent;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Identity\QrTokenService;
use App\Services\Patient\PatientDocumentService;
use App\Services\Portal\PortalContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientPortalController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    private function buildQrDataUri(string $url): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64'    => true,
            'addQuietzone'    => true,
            'quietzoneSize'   => 2,
            'scale'           => 5,
        ]);

        return (new QRCode($options))->render($url);
    }

    /**
     * Resolve the patient record for the authenticated user.
     *
     * Only returns a patient if the authenticated user has a direct patient_id link.
     * Returns null if the user is not linked to any patient.
     */
    private function resolvePatient(): ?Patient
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return $user->patient ?? null;
    }

    /**
     * Returns the dependent patient if guardian context is active, otherwise own patient.
     */
    private function resolveViewingPatient(): ?Patient
    {
        if (request()->attributes->has('viewing_patient')) {
            return request()->attributes->get('viewing_patient');
        }
        return $this->resolvePatient();
    }

    /**
     * Aborts 403 if the guardian is in read_only mode and attempting a write action.
     *
     * Only `full` writes; `read_only` and any legacy value are read-only
     * (GuardianAccessMiddleware::isReadOnly).
     */
    private function assertWriteAllowed(): void
    {
        if ($this->isReadOnlyGuardian()) {
            abort(403, __('family.read_only_refused', ['name' => $this->viewingName()]));
        }
    }

    /** True while a guardian views a dependent through a link that does not grant writes. */
    private function isReadOnlyGuardian(): bool
    {
        return GuardianAccessMiddleware::isReadOnly(request()->attributes->get('guardian_link'));
    }

    /**
     * The polite refusal for actions a read-only guardian may not take on a
     * page they may otherwise use (mint or revoke a QR, print the card):
     * back to where they were with a flash, never a 403 page and never a 500.
     */
    private function refuseReadOnly()
    {
        return back()->with('error', __('family.read_only_refused', ['name' => $this->viewingName()]));
    }

    private function viewingName(): string
    {
        $p = request()->attributes->get('viewing_patient');
        return $p ? trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) : '';
    }

    /**
     * The dashboard and the temporary-QR endpoint are declared outside the
     * `guardian.context` route group (routes/web.php), so they apply the same
     * session → attributes rule themselves. Returns false when the session
     * pointed at a link that is no longer usable (the session is cleared).
     */
    private function bindGuardianContext(Request $request): bool
    {
        return GuardianAccessMiddleware::bind($request);
    }

    /**
     * A guardian-role account (or any user who manages dependents) with no
     * patient record of their own has nothing to show on the patient
     * dashboard — their landing page is the dependents dashboard.
     */
    private function isGuardianWithoutOwnRecord(): bool
    {
        $user = Auth::user();
        if (!$user || $user->patient_id) {
            return false;
        }
        if (($user->role?->name) === 'guardian') {
            return true;
        }
        return \App\Models\FamilyLink::where('guardian_user_id', $user->id)
            ->whereIn('status', ['active', 'pending_invite'])
            ->exists();
    }

    /**
     * Dashboard / My Health ID
     *
     * Honours a guardian switch: while session('guardian_viewing_patient_id')
     * points at an active link, the card, the QR, the allergies, the
     * conditions and the appointments are the DEPENDENT's. A read-only
     * guardian sees the card but no QR is minted for them.
     */
    public function index(Request $request)
    {
        if (!$this->bindGuardianContext($request)) {
            return redirect()->route('portals.patient')
                ->with('error', __('family.switch_no_longer_active'));
        }

        $patient      = $this->resolveViewingPatient();
        $guardianLink = $request->attributes->get('guardian_link');
        $isViewing    = $guardianLink !== null;
        $canWrite     = !$this->isReadOnlyGuardian();

        if (!$patient && $this->isGuardianWithoutOwnRecord()) {
            return redirect()->route('portals.guardian.dashboard');
        }

        $qrToken         = null;
        $staticQrDataUri = null;
        if ($patient && $canWrite) {
            $qrService = new QrTokenService();
            $tokenData = $qrService->generateToken($patient->id);
            $qrToken   = $tokenData['raw_token'];

            try {
                $verifyUrl       = route('verify.qr', ['token' => $qrToken]);
                $staticQrDataUri = $this->buildQrDataUri($verifyUrl);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('QR generation failed', ['error' => $e->getMessage()]);
            }
        }

        if ($patient) {
            // Audit: the record's dashboard was loaded — by the patient, or by
            // a guardian on their behalf (actor = the signed-in guardian).
            $this->ctx->auditPatientAccess(
                actionType:   $isViewing ? 'guardian_dashboard_view' : 'patient_dashboard_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        // Clinical safety summary for dashboard widgets
        $criticalAllergies = $patient
            ? AllergyRecord::where('patient_id', $patient->id)
                ->where('status', 'active')
                ->whereIn('severity', ['severe', 'high', 'life-threatening'])
                ->get(['id', 'substance', 'severity'])
            : collect();

        $activeAllergies = $patient
            ? AllergyRecord::where('patient_id', $patient->id)
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->get(['id', 'substance', 'severity'])
            : collect();

        $activeConditions = $patient
            ? Diagnosis::where('patient_id', $patient->id)
                ->whereIn('status', ['active', 'chronic'])
                ->orderByDesc('created_at')
                ->get(['id', 'display_name', 'code', 'status'])
            : collect();

        // Activation / onboarding checklist — drives setup completion. It is
        // about the signed-in user's OWN account, so it is not shown while a
        // guardian views a dependent.
        $onboarding = [];
        if ($patient && !$isViewing) {
            $user       = Auth::user();
            $famCount   = $user
                ? \App\Models\FamilyLink::where('guardian_user_id', $user->id)
                    ->whereIn('status', ['active', 'pending_invite'])->count()
                : 0;
            $referCount = \App\Models\ReferralInvite::where('referrer_patient_id', $patient->id)->count();
            $status     = is_object($patient->identity_status) ? $patient->identity_status->value : $patient->identity_status;

            $onboarding = [
                ['key' => 'health_id', 'done' => true,              'icon' => 'id-card',     'url' => null],
                ['key' => 'verify',    'done' => $status === 'verified', 'icon' => 'badge-check', 'url' => route('portals.patient.profile')],
                ['key' => 'family',    'done' => $famCount > 0,      'icon' => 'users',       'url' => route('portals.patient.family')],
                ['key' => 'refer',     'done' => $referCount > 0,    'icon' => 'gift',        'url' => route('portals.patient.refer')],
            ];
        }

        $upcomingAppointments = $patient
            ? Appointment::where('patient_id', $patient->id)
                ->whereIn('status', ['requested', 'scheduled', 'confirmed', 'rescheduled']) // 'requested' included deliberately: a just-booked appointment that vanishes from the dashboard reads as a failed booking to the patient
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>=', now())
                ->with(['facility:id,name', 'provider:id,name'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
            : collect();

        return view('portals.patient.index', compact(
            'patient', 'qrToken', 'staticQrDataUri',
            'criticalAllergies', 'activeAllergies', 'activeConditions', 'onboarding',
            'upcomingAppointments', 'guardianLink', 'isViewing', 'canWrite'
        ));
    }

    /**
     * Generate Temporary Access QR
     *
     * Minted for the record currently open — the dependent's while a guardian
     * with full access views them. A read-only guardian gets a 403 JSON
     * message the page shows in place of the QR.
     */
    public function generateTemporaryQr(Request $request)
    {
        if (!$this->bindGuardianContext($request)) {
            return response()->json(['message' => __('family.switch_no_longer_active')], 403);
        }

        $patient = $this->resolveViewingPatient();

        if (!$patient) {
            abort(404);
        }

        if ($this->isReadOnlyGuardian()) {
            return response()->json([
                'message' => __('family.read_only_refused', ['name' => $this->viewingName()]),
            ], 403);
        }

        $qrService = new QrTokenService();
        $tokenData = $qrService->generateToken($patient->id, 'temporary_consent_qr', 60); // 60-minute TTL; secret stored as SHA-256 hash

        $verifyUrl = route('verify.qr', ['token' => $tokenData['raw_token']]);
        try {
            $qrImage = $this->buildQrDataUri($verifyUrl);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Temp QR generation failed', ['error' => $e->getMessage()]);
            $qrImage = null;
        }

        // Audit: temporary QR generated
        $this->ctx->auditPatientAccess(
            actionType:   'temporary_qr_generated',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return response()->json([
            'url'        => $verifyUrl,
            'qr_image'   => $qrImage,
            'expires_in' => 3600,
            'expires_at' => now()->addHour()->toIso8601String(),
            'status'     => 'active',
            // The token's key is its uuid (HealthIdQrToken::$primaryKey); ->id
            // is the unfetched bigint and was always null here, so the revoke
            // link the page built from it could never find the row.
            'token_id'   => $tokenData['model']->getKey(),
        ]);
    }

    /**
     * Download the patient's Health ID card as a print-ready PDF.
     *
     * Generates a fresh static QR token for the card, embeds it as a data URI,
     * then renders a Blade view through DomPDF and streams the result as a PDF
     * download. The token type is 'card_qr' with no expiry (permanent card QR).
     *
     * Every download is audited for MINSANTE compliance.
     */
    public function downloadHealthIdCard(Request $request)
    {
        if (!$this->bindGuardianContext($request)) {
            return redirect()->route('portals.patient')
                ->with('error', __('family.switch_no_longer_active'));
        }

        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 404);

        // The card carries a QR minted for the holder — a write on their
        // record, so a read-only guardian is refused (with a flash, not a 500).
        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }

        // Generate a permanent card QR (no TTL) for printing — distinct from
        // temporary consent QRs so they can be revoked independently.
        $qrService = new QrTokenService();
        $tokenData = $qrService->generateToken($patient->id, 'card_qr', null);

        $qrDataUri = null;
        try {
            $verifyUrl = route('verify.qr', ['token' => $tokenData['raw_token']]);
            $qrDataUri = $this->buildQrDataUri($verifyUrl);
        } catch (\Throwable $e) {
            Log::error('health_id_card_qr_failed', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_health_id_card_downloaded',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        // `true` merges with the package defaults; without it the chroot falls
        // back to vendor/dompdf and the seal image under public/ is refused.
        $pdf = Pdf::loadView('portals.patient.health-id-card-pdf', [
            'patient'    => $patient,
            'qrDataUri'  => $qrDataUri,
            'issuedAt'   => now(),
            'issuedYear' => now()->year,
        ])
            ->setPaper([0, 0, 255.12, 153.07]) // 90mm × 54mm in points (CR80 card size)
            ->setOptions([
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
                'dpi'                     => 96,
            ], true);

        $filename = 'OpesCare-HealthID-' . str_replace('-', '', $patient->health_id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Revoke a specific QR token (patient-initiated).
     *
     * Called when patient shares a QR accidentally or wants to invalidate an
     * old/lost QR token. The token is looked up by its DB id (not the raw token)
     * so the patient only needs to pass the token_id from generateTemporaryQr().
     */
    public function revokeQrToken(Request $request, string $tokenId)
    {
        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        // Accept the uuid the JSON hands out (and a numeric id for old links);
        // a uuid string against the bigint column would be a Postgres type error.
        $qrRecord = \App\Models\HealthIdQrToken::query()
            ->when(ctype_digit($tokenId), fn ($q) => $q->where('id', (int) $tokenId), fn ($q) => $q->where('uuid', $tokenId))
            ->where('patient_id', $patient->id)
            ->where('status', 'active')
            ->first();

        if (!$qrRecord) {
            return back()->with('warning', __('flash.qr_token_not_found'));
        }

        (new QrTokenService())->revokeToken($qrRecord);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_qr_token_revoked',
            resourceType: 'HealthIdQrToken',
            // audit_events.resource_id is a uuid column; the token's key is its uuid (->id is the bigint).
            resourceId:   (string) $qrRecord->getKey(),
            patientId:    $patient->id,
        );

        return back()->with('success', __('flash.qr_token_revoked'));
    }

    /**
     * Revoke all active QR tokens for the patient (used when card is lost/stolen).
     */
    public function revokeAllQrTokens(Request $request)
    {
        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $qrService = new QrTokenService();
        $tokens    = \App\Models\HealthIdQrToken::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->get();

        $count = 0;
        foreach ($tokens as $token) {
            $qrService->revokeToken($token);
            $count++;
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_all_qr_tokens_revoked',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return back()->with('success', __('flash.qr_all_tokens_revoked', ['count' => $count]));
    }

    /**
     * Report a lost or stolen Health ID card.
     *
     * Revokes all active QR tokens so the old card cannot be scanned. The Health ID
     * number itself remains valid — the patient can generate a new QR at any time.
     * This action is audited for MINSANTE compliance.
     */
    public function reportLostCard(Request $request)
    {
        if ($this->isReadOnlyGuardian()) {
            return $this->refuseReadOnly();
        }
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $request->validate([
            'report_reason' => 'required|string|max:500',
        ]);

        // Revoke all active QR tokens linked to this patient
        $qrService = new QrTokenService();
        $tokens    = \App\Models\HealthIdQrToken::where('patient_id', $patient->id)
            ->where('status', 'active')
            ->get();

        foreach ($tokens as $token) {
            $qrService->revokeToken($token);
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_lost_card_reported',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        \Illuminate\Support\Facades\Log::info('patient_lost_card_reported', [
            'patient_id'    => $patient->id,
            'health_id'     => $patient->health_id,
            'report_reason' => $request->input('report_reason'),
            'tokens_revoked'=> $tokens->count(),
            'ip'            => $request->ip(),
        ]);

        return back()->with('success', __('flash.lost_card_reported', ['health_id' => $patient->health_id]));
    }

    /**
     * Patient Appointments
     */
    public function appointments(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $appointments = $patient
            ? Appointment::where('patient_id', $patient->id)
                ->with(['facility:id,name', 'provider:id,name'])
                ->orderByDesc('scheduled_at')
                ->paginate(20)
            : collect([]);

        // Audit: patient viewed their appointments list
        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_appointments_view',
                resourceType: 'Appointment',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.appointments', compact('patient', 'appointments'));
    }

    /**
     * Cancel an appointment (patient-initiated)
     */
    public function cancelAppointment(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $appt = Appointment::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        abort_if(!in_array($appt->status, ['requested', 'scheduled', 'confirmed']), 422, 'This appointment cannot be cancelled.');

        $appt->update([
            'status'               => 'cancelled',
            'cancellation_reason'  => 'Cancelled by patient via portal',
            'cancelled_at'         => now(),
            'cancelled_by_id'      => Auth::id(),
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_appointment_cancelled',
            resourceType: 'Appointment',
            resourceId:   $appt->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.appointments')
            ->with('success', __('flash.patient_appointment_cancelled'));
    }

    /** Appointment types a patient may request. */
    private function appointmentTypes(): array
    {
        return ['consultation', 'follow_up', 'vaccination', 'lab_test', 'antenatal', 'dental', 'general'];
    }

    /** GET — appointment request form (pick facility, type, date/time, reason). */
    public function bookAppointmentForm(Request $request)
    {
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $facilities = \App\Models\Facility::orderBy('name')->limit(300)->get(['id', 'name']);

        return view('portals.patient.appointment_book', [
            'patient'     => $patient,
            'facilities'  => $facilities,
            'types'       => $this->appointmentTypes(),
            'defaultFacility' => $patient->facility_id,
        ]);
    }

    /** POST — create a patient-requested appointment (status: requested). */
    public function bookAppointment(Request $request)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $validated = $request->validate([
            'facility_id'      => 'required|uuid|exists:facilities,id',
            'appointment_type' => 'required|string|in:' . implode(',', $this->appointmentTypes()),
            'scheduled_at'     => 'required|date|after:now',
            'reason'           => 'nullable|string|max:500',
        ]);

        $appt = Appointment::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $validated['facility_id'],
            'appointment_type' => $validated['appointment_type'],
            'scheduled_at'     => $validated['scheduled_at'],
            'status'           => 'requested',
            'booked_by_type'   => 'patient',
            'booked_by_id'     => $patient->id,
            'reason'           => $validated['reason'] ?? null,
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_appointment_requested',
            resourceType: 'Appointment',
            resourceId:   $appt->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.appointments')
            ->with('success', __('flash.patient_appointment_requested'));
    }

    /** GET — reschedule form for a pending/scheduled appointment. */
    public function rescheduleAppointmentForm(Request $request, string $id)
    {
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $appt = Appointment::where('id', $id)->where('patient_id', $patient->id)->firstOrFail();
        abort_if(!in_array($appt->status, ['requested', 'scheduled', 'confirmed']), 422, 'This appointment cannot be rescheduled.');

        return view('portals.patient.appointment_reschedule', compact('patient', 'appt'));
    }

    /** POST — apply a new date/time to an appointment (re-enters 'requested'). */
    public function rescheduleAppointment(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $appt = Appointment::where('id', $id)->where('patient_id', $patient->id)->firstOrFail();
        abort_if(!in_array($appt->status, ['requested', 'scheduled', 'confirmed']), 422, 'This appointment cannot be rescheduled.');

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $appt->update([
            'scheduled_at' => $validated['scheduled_at'],
            'status'       => 'requested',
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_appointment_rescheduled',
            resourceType: 'Appointment',
            resourceId:   $appt->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.appointments')
            ->with('success', __('flash.patient_appointment_rescheduled'));
    }

    /**
     * Patient Prescriptions
     */
    public function prescriptions(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $prescriptions = $patient
            ? Prescription::where('patient_id', $patient->id)
                ->with(['items', 'facility'])
                ->orderByDesc('prescribed_at')
                ->paginate(15)
            : collect([]);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_prescriptions_view',
                resourceType: 'Prescription',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.prescriptions', compact('patient', 'prescriptions'));
    }

    /**
     * Request a prescription refill (patient-initiated)
     */
    public function requestRefill(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $rx = Prescription::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $this->ctx->auditPatientAccess(
            actionType:   'patient_refill_requested',
            resourceType: 'Prescription',
            resourceId:   $rx->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.prescriptions')
            ->with('success', __('flash.refill_submitted'));
    }

    /**
     * Patient Official Documents
     */
    public function documents(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $documents = $patient
            ? OfficialDocument::where('patient_id', $patient->id)
                // pdf_path is a server filesystem path — never expose it to the
                // list view; downloads resolve it in their own scoped query.
                ->select(['id', 'title', 'document_type', 'document_number', 'status', 'issued_at', 'expires_at', 'sensitivity_level', 'payload_json', 'patient_id'])
                ->orderByDesc('issued_at')
                ->paginate(20)
            : collect([]);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_documents_view',
                resourceType: 'OfficialDocument',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        // What the record can produce right now, rendered on request and
        // never stored. This is what a patient can actually take away;
        // the issued-document rows above are a separate, older register.
        $available = $patient ? app(PatientDocumentService::class)->available($patient) : [];

        return view('portals.patient.documents', compact('patient', 'documents', 'available'));
    }

    /**
     * Download a released official document PDF
     */
    /**
     * Hand the patient one of their documents, generated now.
     *
     * Nothing is stored. The sheet is rendered from the record at the moment of
     * the request and streamed straight out, so what the patient takes away is
     * their record as it stands rather than a copy of how it looked whenever
     * some file happened to be written. See PatientDocumentService for why that
     * is the design and not an optimisation.
     *
     * The patient is resolved the way every other page in this portal resolves
     * it — including a guardian who has switched to a dependent — and the
     * service refuses any record that does not belong to them.
     */
    public function renderDocument(Request $request, PatientDocumentService $documents, string $type, ?string $record = null)
    {
        $patient = $this->resolveViewingPatient();
        abort_if(! $patient, 403);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_document_generated',
            resourceType: 'PatientDocument',
            resourceId:   $record ?? $type,
            patientId:    $patient->id,
        );

        return $documents->download($patient, $type, $record, $request->user()?->id);
    }

    /**
     * An already-issued document, rendered from the record it was made from.
     *
     * These rows carry a document number and a verification code but no file —
     * `pdf_path` is a column nothing in this codebase writes. This used to read
     * that path and tell the patient the document was "not available", which
     * was true and useless. It now renders the same record the document was
     * issued from, so the button works.
     */
    public function documentDownload(Request $request, string $id)
    {
        $patient = $this->resolveViewingPatient();
        abort_if(! $patient, 403);

        $doc = OfficialDocument::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        $documents  = app(PatientDocumentService::class);
        $renderable = $documents->renderableFor($doc);

        if ($renderable === null) {
            return back()->with('warning', __('flash.document_not_available'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_document_downloaded',
            resourceType: 'OfficialDocument',
            resourceId:   $doc->id,
            patientId:    $patient->id,
        );

        [$type, $record] = $renderable;

        return $documents->download($patient, $type, $record, $request->user()?->id);
    }

    /**
     * Patient Profile & Privacy Settings
     */
    public function profile(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $allergies = $patient
            ? AllergyRecord::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get(['id', 'substance', 'severity', 'status'])
            : collect();

        $conditions = $patient
            ? Diagnosis::where('patient_id', $patient->id)
                ->whereIn('status', ['active', 'chronic'])
                ->orderByDesc('created_at')
                ->get(['id', 'display_name', 'code', 'status'])
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_profile_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.profile', compact('patient', 'allergies', 'conditions'));
    }

    /**
     * Update Patient Profile & Privacy Settings
     */
    public function updateProfile(Request $request)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $validated = $request->validate([
            'phone_number'                   => 'sometimes|nullable|string|max:30',
            'email'                          => 'sometimes|nullable|email|max:255',
            'address'                        => 'sometimes|nullable|string|max:500',
            'blood_group'                    => 'sometimes|nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'date_of_birth'                  => 'sometimes|nullable|date|before:today',
            'emergency_contact.name'         => 'sometimes|nullable|string|max:100',
            'emergency_contact.phone'        => 'sometimes|nullable|string|max:30',
            'emergency_contact.relationship' => 'sometimes|nullable|string|max:50',
            'privacy_require_consent'        => 'nullable|boolean',
            'privacy_emergency_access'       => 'nullable|boolean',
        ]);

        $updateData = [];

        foreach (['phone_number', 'email', 'address', 'blood_group', 'date_of_birth'] as $field) {
            if (array_key_exists($field, $validated)) {
                $updateData[$field] = $validated[$field];
            }
        }

        if (isset($validated['emergency_contact'])) {
            $updateData['emergency_contact'] = $validated['emergency_contact'];
        }

        $privacyPrefs = $patient->privacy_preferences ?? [];
        if (array_key_exists('privacy_require_consent', $validated)) {
            $privacyPrefs['require_consent_for_full_record'] = (bool) $validated['privacy_require_consent'];
        }
        if (array_key_exists('privacy_emergency_access', $validated)) {
            $privacyPrefs['emergency_access_allowed'] = (bool) $validated['privacy_emergency_access'];
        }
        if ($privacyPrefs !== ($patient->privacy_preferences ?? [])) {
            $updateData['privacy_preferences'] = $privacyPrefs;
        }

        if (!empty($updateData)) {
            $patient->update($updateData);
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_profile_updated',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.profile')->with('success', __('flash.profile_updated'));
    }

    /**
     * Patient Allergies
     */
    public function allergies(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $allergies = $patient
            ? AllergyRecord::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_allergies_view',
                resourceType: 'AllergyRecord',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.allergies', compact('patient', 'allergies'));
    }

    /**
     * Patient Clinical History (Diagnoses / Conditions)
     */
    public function clinicalHistory(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $conditions = $patient
            ? Diagnosis::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_clinical_history_view',
                resourceType: 'Diagnosis',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.clinical', compact('patient', 'conditions'));
    }

    /**
     * Patient Immunization History
     */
    public function immunizations(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $immunizations = $patient
            ? ImmunizationRecord::where('patient_id', $patient->id)
                ->orderByDesc('administered_at')
                ->get()
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_immunizations_view',
                resourceType: 'ImmunizationRecord',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.immunizations', compact('patient', 'immunizations'));
    }

    /**
     * View Access Logs
     */
    public function accessLogs(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $logs = collect([]);
        if ($patient) {
            $logs = MedicalIdAccessEvent::where('patient_id', $patient->id)
                ->orderBy('created_at', 'desc')
                ->paginate(25);

            // Audit: patient viewed their own access log
            $this->ctx->auditPatientAccess(
                actionType:   'patient_access_log_view',
                resourceType: 'MedicalIdAccessEvent',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.logs', compact('patient', 'logs'));
    }

    /**
     * Health Timeline — chronological feed of visits, lab results and prescriptions.
     *
     * Mirrors Api/Mobile/MobilePatientController@getTimeline: aggregates Visit,
     * resulted LabOrder, and Prescription records into a single newest-first list.
     */
    public function timeline(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $events = collect();

        if ($patient) {
            $visits = \App\Models\Visit::where('patient_id', $patient->id)
                ->with('facility:id,name')
                ->latest('created_at')
                ->take(50)
                ->get();
            foreach ($visits as $v) {
                $events->push((object) [
                    'event_type'    => 'visit',
                    'facility_name' => $v->facility?->name,
                    'occurred_at'   => $v->created_at,
                    'summary'       => ucfirst((string) ($v->visit_type ?? 'outpatient')),
                ]);
            }

            $labs = \App\Models\LabOrder::where('patient_id', $patient->id)
                ->where('status', 'resulted')
                ->with('facility:id,name')
                ->latest('resulted_at')
                ->take(50)
                ->get();
            foreach ($labs as $l) {
                $events->push((object) [
                    'event_type'    => 'lab_result',
                    'facility_name' => $l->facility?->name,
                    'occurred_at'   => $l->resulted_at ?? $l->ordered_at,
                    'summary'       => $l->test_name,
                ]);
            }

            $prescriptions = Prescription::where('patient_id', $patient->id)
                ->with(['facility:id,name', 'items'])
                ->latest('prescribed_at')
                ->take(50)
                ->get();
            foreach ($prescriptions as $p) {
                $events->push((object) [
                    'event_type'    => 'prescription',
                    'facility_name' => $p->facility?->name,
                    'occurred_at'   => $p->prescribed_at,
                    'summary'       => $p->items->count(),
                ]);
            }

            $this->ctx->auditPatientAccess(
                actionType:   'patient_timeline_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        $events = $events->sortByDesc('occurred_at')->values();

        return view('portals.patient.timeline', compact('patient', 'events'));
    }

    /**
     * Care Plans — active plans with goals and interventions.
     *
     * Mirrors Api/Mobile/MobileCarePlanController@index via CarePlanService:
     * active CarePlan records for the patient, eager-loading goals + interventions.
     */
    public function carePlans(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $carePlans = collect();
        if ($patient) {
            $carePlans = app(\App\Services\Clinical\CarePlanService::class)
                ->getActivePlansForPatient((string) $patient->id);

            $this->ctx->auditPatientAccess(
                actionType:   'patient_care_plans_view',
                resourceType: 'CarePlan',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.care-plans', compact('patient', 'carePlans'));
    }

    /**
     * Referrals — the patient's referral cases and their status.
     *
     * Mirrors Api/Mobile/MobileReferralController@index: ReferralCase records for
     * the patient with referring/receiving facility names, newest-first.
     */
    public function referrals(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $referrals = $patient
            ? \App\Models\ReferralCase::where('patient_id', $patient->id)
                ->with(['referringFacility:id,name', 'receivingFacility:id,name'])
                ->latest()
                ->get()
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_referrals_view',
                resourceType: 'ReferralCase',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.referrals', compact('patient', 'referrals'));
    }

    /**
     * Surveys — questionnaires assigned to the patient (read-only list).
     *
     * Mirrors Api/Mobile/MobileSurveyController@index but lists all of the
     * patient's surveys (not only 'sent') so completed history is visible too.
     */
    public function surveys(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $surveys = $patient
            ? \App\Models\PatientSurvey::where('patient_id', $patient->id)
                ->with(['facility:id,name', 'responses'])
                ->orderByDesc('sent_at')
                ->get()
            : collect();

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_surveys_view',
                resourceType: 'PatientSurvey',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.surveys', compact('patient', 'surveys'));
    }

    /**
     * Settings / Notifications — read notification & app preferences.
     *
     * Mirrors Api/Mobile/MobileSettingsController: MobileAppSetting::forPatient()
     * (notification toggles, language, theme, biometric). The web form posts to
     * updateSettings() for the boolean notification preferences.
     */
    public function settings(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $settings = $patient
            ? \App\Models\MobileAppSetting::forPatient((string) $patient->id)
            : null;

        return view('portals.patient.settings', compact('patient', 'settings'));
    }

    /**
     * Update notification preferences from the web settings form.
     */
    public function updateSettings(Request $request)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $validated = $request->validate([
            'push_appointments'     => 'sometimes|boolean',
            'push_lab_results'      => 'sometimes|boolean',
            'push_prescriptions'    => 'sometimes|boolean',
            'push_billing'          => 'sometimes|boolean',
            'push_consent_requests' => 'sometimes|boolean',
            'push_messages'         => 'sometimes|boolean',
            'preferred_theme'       => 'sometimes|in:light,dark,system',
        ]);

        // Unchecked checkboxes are absent from the request — coerce to false.
        $data = [];
        foreach (['push_appointments', 'push_lab_results', 'push_prescriptions', 'push_billing', 'push_consent_requests', 'push_messages'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        if (array_key_exists('preferred_theme', $validated)) {
            $data['preferred_theme'] = $validated['preferred_theme'];
        }

        $settings = \App\Models\MobileAppSetting::forPatient((string) $patient->id);
        $settings->update($data);

        return redirect()->route('portals.patient.settings')->with('success', __('flash.settings_updated'));
    }
}
