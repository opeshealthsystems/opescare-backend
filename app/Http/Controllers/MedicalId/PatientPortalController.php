<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Diagnosis;
use App\Models\ImmunizationRecord;
use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use App\Models\LabResult;
use App\Models\MedicalIdAccessEvent;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Prescription;
use App\Services\Identity\QrTokenService;
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
     */
    private function assertWriteAllowed(): void
    {
        $link = request()->attributes->get('guardian_link');
        if ($link && $link->access_level === 'read_only') {
            abort(403, 'Read-only guardian access does not permit this action.');
        }
    }

    /**
     * Dashboard / My Health ID
     */
    public function index(Request $request)
    {
        $patient = $this->resolvePatient();

        $qrToken         = null;
        $staticQrDataUri = null;
        if ($patient) {
            $qrService = new QrTokenService();
            $tokenData = $qrService->generateToken($patient->id);
            $qrToken   = $tokenData['raw_token'];

            try {
                $verifyUrl       = route('verify.qr', ['token' => $qrToken]);
                $staticQrDataUri = $this->buildQrDataUri($verifyUrl);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('QR generation failed', ['error' => $e->getMessage()]);
            }

            // Audit: patient loaded their own health record dashboard
            $this->ctx->auditPatientAccess(
                actionType:   'patient_dashboard_view',
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

        // Activation / onboarding checklist — drives setup completion.
        $onboarding = [];
        if ($patient) {
            $user       = Auth::user();
            $famCount   = $user
                ? \App\Models\FamilyLink::where('guardian_user_id', $user->id)
                    ->whereIn('status', ['active', 'pending_invite'])->count()
                : 0;
            $referCount = \App\Models\ReferralInvite::where('referrer_patient_id', $patient->id)->count();
            $plan       = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class)->currentPlan($patient);
            $isPremium  = $plan && ! $plan->isFree();
            $status     = is_object($patient->identity_status) ? $patient->identity_status->value : $patient->identity_status;

            $onboarding = [
                ['key' => 'health_id', 'done' => true,              'icon' => 'id-card',     'url' => null],
                ['key' => 'verify',    'done' => $status === 'verified', 'icon' => 'badge-check', 'url' => route('portals.patient.profile')],
                ['key' => 'family',    'done' => $famCount > 0,      'icon' => 'users',       'url' => route('portals.patient.family')],
                ['key' => 'refer',     'done' => $referCount > 0,    'icon' => 'gift',        'url' => route('portals.patient.refer')],
                ['key' => 'premium',   'done' => $isPremium,         'icon' => 'sparkles',    'url' => route('portals.patient.subscription')],
            ];
        }

        $upcomingAppointments = $patient
            ? Appointment::where('patient_id', $patient->id)
                ->whereIn('status', ['scheduled', 'confirmed', 'rescheduled'])
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
            'upcomingAppointments'
        ));
    }

    /**
     * Generate Temporary Access QR
     */
    public function generateTemporaryQr(Request $request)
    {
        $patient = $this->resolvePatient();

        if (!$patient) {
            abort(404);
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
            'token_id'   => $tokenData['model']->id,
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
        $patient = $this->resolvePatient();
        abort_if(!$patient, 404);

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

        $pdf = Pdf::loadView('portals.patient.health-id-card-pdf', [
            'patient'    => $patient,
            'qrDataUri'  => $qrDataUri,
            'issuedAt'   => now()->format('d M Y'),
            'issuedYear' => now()->year,
        ])->setPaper([0, 0, 255.12, 153.07]); // 90mm × 54mm in points (CR80 card size)

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
        $this->assertWriteAllowed();
        $patient = $this->resolvePatient();
        abort_if(!$patient, 403);

        $qrRecord = \App\Models\HealthIdQrToken::where('id', $tokenId)
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
            resourceId:   $qrRecord->id,
            patientId:    $patient->id,
        );

        return back()->with('success', __('flash.qr_token_revoked'));
    }

    /**
     * Revoke all active QR tokens for the patient (used when card is lost/stolen).
     */
    public function revokeAllQrTokens(Request $request)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolvePatient();
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
        $this->assertWriteAllowed();
        $patient = $this->resolvePatient();
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
     * Patient Lab Results
     */
    public function labResults(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $labs = $patient
            ? LabResult::where('patient_id', $patient->id)
                ->with('labOrder')
                ->orderByDesc('resulted_at')
                ->paginate(25)
            : collect([]);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_labs_view',
                resourceType: 'LabResult',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.labs', compact('patient', 'labs'));
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
     * Patient Consent Requests
     */
    public function consentRequests(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        $consentRequests = $patient
            ? ConsentRequest::where('patient_id', $patient->id)
                ->with('requestingFacility')
                ->orderByDesc('created_at')
                ->paginate(20)
            : collect([]);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_consent_view',
                resourceType: 'ConsentRequest',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.consent', compact('patient', 'consentRequests'));
    }

    /**
     * Approve a consent request
     */
    public function approveConsent(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $req = ConsentRequest::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        abort_if($req->status !== 'pending', 422, 'Request is not pending.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($req, $patient) {
            $req->update(['status' => 'approved']);

            ConsentGrant::create([
                'patient_id'         => $patient->id,
                'facility_id'        => $req->requesting_facility_id,
                'consent_request_id' => $req->id,
                'authorizing_actor'  => 'patient',
                'scope'              => $req->requested_scope ?? [],
                'status'             => 'active',
                'expires_at'         => now()->addMinutes($req->duration_minutes ?? 1440),
            ]);
        });

        $this->ctx->auditPatientAccess(
            actionType:   'patient_consent_approved',
            resourceType: 'ConsentRequest',
            resourceId:   $req->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.consent')->with('success', __('flash.consent_approved'));
    }

    /**
     * Deny a consent request
     */
    public function denyConsent(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $req = ConsentRequest::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        abort_if($req->status !== 'pending', 422, 'Request is not pending.');

        $req->update(['status' => 'denied']);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_consent_denied',
            resourceType: 'ConsentRequest',
            resourceId:   $req->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.consent')->with('success', __('flash.consent_denied'));
    }

    /**
     * Revoke a previously approved consent grant
     */
    public function revokeConsent(Request $request, string $id)
    {
        $this->assertWriteAllowed();
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $req = ConsentRequest::where('id', $id)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        abort_if($req->status !== 'approved', 422, 'Only approved consents can be revoked.');

        DB::transaction(function () use ($req, $patient) {
            $req->update(['status' => 'revoked']);
            ConsentGrant::where('consent_request_id', $req->id)
                ->where('patient_id', $patient->id)
                ->where('status', 'active')
                ->update(['status' => 'revoked']);
        });

        $this->ctx->auditPatientAccess(
            actionType:   'patient_consent_revoked',
            resourceType: 'ConsentRequest',
            resourceId:   $req->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.consent')->with('success', __('flash.consent_access_revoked'));
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
                ->select(['id', 'title', 'document_type', 'document_number', 'status', 'issued_at', 'expires_at', 'sensitivity_level'])
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

        return view('portals.patient.documents', compact('patient', 'documents'));
    }

    /**
     * Download a released official document PDF
     */
    public function documentDownload(Request $request, string $id)
    {
        $patient = $this->resolveViewingPatient();
        abort_if(!$patient, 403);

        $doc = OfficialDocument::where('id', $id)
            ->where('patient_id', $patient->id)
            ->where('status', 'released')
            ->firstOrFail();

        if (!$doc->pdf_path || !Storage::exists($doc->pdf_path)) {
            return back()->with('warning', __('flash.document_not_available'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'patient_document_downloaded',
            resourceType: 'OfficialDocument',
            resourceId:   $doc->id,
            patientId:    $patient->id,
        );

        $filename = Str::slug($doc->title ?? $doc->document_type) . '-' . ($doc->document_number ?? $doc->id) . '.pdf';

        return Storage::download($doc->pdf_path, $filename, ['Content-Type' => 'application/pdf']);
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

    // ─── Insurance Marketplace ────────────────────────────────────────────────

    /**
     * GET /portals/patient/insurance
     * Shows the patient's current policies + a marketplace banner.
     */
    public function insuranceMarketplace(Request $request)
    {
        $patient = $this->resolveViewingPatient();

        // Active purchasable providers
        $providers = InsuranceProvider::where('status', 'active')
            ->with(['activePlans' => fn ($q) => $q->where('is_purchasable', true)])
            ->get()
            ->filter(fn ($p) => $p->activePlans->isNotEmpty())
            ->values();

        // Patient's existing policies
        $myPolicies = $patient
            ? PatientInsurancePolicy::where('patient_id', $patient->id)
                ->with(['plan:id,name,plan_type,insurance_provider_id', 'plan.provider:id,name'])
                ->orderByDesc('effective_date')
                ->get()
            : collect([]);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'patient_insurance_marketplace_view',
                resourceType: 'InsuranceMarketplace',
                resourceId:   null,
                patientId:    $patient->id,
            );
        }

        return view('portals.patient.insurance.marketplace',
            compact('patient', 'providers', 'myPolicies'));
    }

    /**
     * GET /portals/patient/insurance/plans/{id}
     * Shows full plan detail with enroll button.
     */
    public function insurancePlanDetail(Request $request, string $id)
    {
        $plan = InsurancePlan::with('provider')
            ->where('id', $id)
            ->where('status', 'active')
            ->where('is_purchasable', true)
            ->firstOrFail();

        $patient = $this->resolveViewingPatient();

        $alreadyEnrolled = $patient
            ? PatientInsurancePolicy::where('patient_id', $patient->id)
                ->where('insurance_plan_id', $plan->id)
                ->where('status', 'active')
                ->exists()
            : false;

        return view('portals.patient.insurance.plan_detail',
            compact('patient', 'plan', 'alreadyEnrolled'));
    }

    /**
     * POST /portals/patient/insurance/plans/{id}/purchase
     * Self-enroll via the web portal.
     */
    public function insurancePurchase(Request $request, string $id)
    {
        $plan = InsurancePlan::where('id', $id)
            ->where('status', 'active')
            ->where('is_purchasable', true)
            ->firstOrFail();

        $patient = $this->resolveViewingPatient();
        if (!$patient) {
            abort(403, 'No patient profile linked to this account.');
        }

        // Prevent duplicate active enrollment
        $existing = PatientInsurancePolicy::where('patient_id', $patient->id)
            ->where('insurance_plan_id', $plan->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('warning', __('flash.policy_already_active'));
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:mobile_money,card,bank_transfer',
        ]);

        PatientInsurancePolicy::create([
            'patient_id'              => $patient->id,
            'insurance_plan_id'       => $plan->id,
            'policy_number'           => 'POL-' . strtoupper(Str::random(10)),
            'relationship_to_primary' => 'self',
            'effective_date'          => now()->toDateString(),
            'expiry_date'             => now()->addYear()->toDateString(),
            'status'                  => 'pending',
            'notes'                   => 'Self-enrolled via patient portal. Payment: ' . $validated['payment_method'],
        ]);

        return redirect()
            ->route('portals.patient.insurance')
            ->with('success', __('flash.enrollment_submitted'));
    }

    // ── Subscription self-service ─────────────────────────────────────────────

    /** Current plan, status, renewal date, invoices, and available patient plans. */
    public function subscription(Request $request)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);

        $svc         = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);
        $active      = $svc->activeSubscription($patient);
        $currentPlan = $svc->currentPlan($patient);
        $plans       = \App\Models\SubscriptionPlan::forAudience('patient')
            ->active()->public()->with('planFeatures')->orderBy('sort_order')->get();
        $invoices    = $active
            ? \App\Models\SubscriptionInvoice::where('subscription_id', $active->id)
                ->orderByDesc('invoice_date')->limit(12)->get()
            : collect();

        return view('portals.patient.subscription', compact('patient', 'active', 'currentPlan', 'plans', 'invoices'));
    }

    /** Choose/upgrade a plan. Free activates immediately; paid routes to checkout. */
    public function subscribe(Request $request)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);
        $this->assertWriteAllowed();

        $validated = $request->validate([
            'plan_id'  => 'required|uuid',
            'interval' => 'required|in:monthly,annual',
        ]);

        $plan = \App\Models\SubscriptionPlan::forAudience('patient')->active()->findOrFail($validated['plan_id']);
        $svc  = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);

        if ($plan->isFree()) {
            $svc->startSubscription($patient, $plan, $validated['interval'], [], (string) $patient->id);
            return redirect()->route('portals.patient.subscription')->with('success', __('flash.subscription_updated'));
        }

        // Paid plans go to the Mobile Money checkout (collect phone, then pay).
        return redirect()->route('portals.patient.subscription.checkout', [
            'plan_id'  => $plan->id,
            'interval' => $validated['interval'],
        ]);
    }

    /** GET — Mobile Money checkout: confirm plan + collect the MoMo phone number. */
    public function subscriptionCheckout(Request $request)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);

        $validated = $request->validate([
            'plan_id'  => 'required|uuid',
            'interval' => 'required|in:monthly,annual',
        ]);

        $plan = \App\Models\SubscriptionPlan::forAudience('patient')->active()->findOrFail($validated['plan_id']);
        abort_if($plan->isFree(), 404);

        $svc    = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);
        $amount = $svc->amountFor($plan, $validated['interval']);

        return view('portals.patient.subscription_checkout', [
            'patient'  => $patient,
            'plan'     => $plan,
            'interval' => $validated['interval'],
            'amount'   => $amount,
            'currency' => $plan->currency ?? 'XAF',
        ]);
    }

    /** POST — initiate the MoMo collection and hand off to the pending poller. */
    public function subscriptionCheckoutPay(Request $request)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);
        $this->assertWriteAllowed();

        $validated = $request->validate([
            'plan_id'  => 'required|uuid',
            'interval' => 'required|in:monthly,annual',
            'phone'    => 'required|string|max:20',
        ]);

        $plan = \App\Models\SubscriptionPlan::forAudience('patient')->active()->findOrFail($validated['plan_id']);
        abort_if($plan->isFree(), 404);

        $svc     = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);
        $amount  = $svc->amountFor($plan, $validated['interval']);
        $pending = $svc->beginCheckout($patient, $plan, $validated['interval'], (string) $patient->id);

        $momo = app(\App\Services\Payments\MtnMomoService::class);
        $result = $momo->requestPayment(
            $validated['phone'],
            (float) $amount,
            $plan->currency ?? 'XAF',
            $pending->id,
            'OpesCare ' . $plan->name . ' subscription',
        );

        if (empty($result['success'])) {
            $svc->failCheckout($pending, (string) $patient->id);
            return redirect()->route('portals.patient.subscription')
                ->with('error', __('flash.subscription_payment_failed'));
        }

        $svc->attachPaymentReference($pending, $result['reference_id']);

        $this->ctx->auditPatientAccess(
            actionType:   'patient_subscription_checkout_initiated',
            resourceType: 'OrganizationSubscription',
            resourceId:   $pending->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.patient.subscription.pending', ['subscription' => $pending->id]);
    }

    /** GET — "approve the prompt on your phone" page; polls the status endpoint. */
    public function subscriptionCheckoutPending(Request $request, string $subscription)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);

        $pending = \App\Models\OrganizationSubscription::where('id', $subscription)
            ->where('subscriber_type', 'patient')
            ->where('subscriber_id', $patient->id)
            ->firstOrFail();

        return view('portals.patient.subscription_pending', [
            'patient'      => $patient,
            'subscription' => $pending,
        ]);
    }

    /** GET (JSON) — poll the MoMo transaction; activate on success. */
    public function subscriptionCheckoutStatus(Request $request, string $subscription)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);

        $pending = \App\Models\OrganizationSubscription::where('id', $subscription)
            ->where('subscriber_type', 'patient')
            ->where('subscriber_id', $patient->id)
            ->firstOrFail();

        $svc = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);

        // Already settled by a prior poll or the webhook.
        if ($pending->status === 'active') {
            return response()->json(['status' => 'successful']);
        }
        if (in_array($pending->status, ['payment_failed', 'cancelled'], true)) {
            return response()->json(['status' => 'failed']);
        }
        if (!$pending->payment_reference) {
            return response()->json(['status' => 'failed']);
        }

        $momo   = app(\App\Services\Payments\MtnMomoService::class);
        $status = strtoupper($momo->checkStatus($pending->payment_reference)['status'] ?? 'UNKNOWN');

        if ($status === 'SUCCESSFUL') {
            $svc->confirmPaidCheckout($pending, (string) $patient->id);
            $this->ctx->auditPatientAccess(
                actionType:   'patient_subscription_activated',
                resourceType: 'OrganizationSubscription',
                resourceId:   $pending->id,
                patientId:    $patient->id,
            );
            return response()->json(['status' => 'successful']);
        }

        if ($status === 'FAILED') {
            $svc->failCheckout($pending, (string) $patient->id);
            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => 'pending']);
    }

    /** Cancel — turns off auto-renew; access continues until the period ends. */
    public function cancelSubscription(Request $request)
    {
        $patient = $this->resolvePatient();
        abort_if($patient === null, 403);
        $this->assertWriteAllowed();

        $svc = app(\App\Modules\Subscription\Services\PatientSubscriptionService::class);
        $svc->cancel($patient, (string) $request->input('reason', ''), (string) $patient->id);

        return redirect()->route('portals.patient.subscription')->with('success', __('flash.subscription_cancelled'));
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
            'preferred_theme'       => 'sometimes|in:light,dark,system',
        ]);

        // Unchecked checkboxes are absent from the request — coerce to false.
        $data = [];
        foreach (['push_appointments', 'push_lab_results', 'push_prescriptions', 'push_billing', 'push_consent_requests'] as $field) {
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
