<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\HealthIdQrToken;
use App\Models\HealthIdVerificationEvent;
use App\Models\Patient;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Identity\QrTokenService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerifyController extends Controller
{
    public function __construct(private QrTokenService $qrService) {}

    /**
     * Show the Health ID manual lookup form.
     */
    public function healthId()
    {
        return view('verify.health_id', [
            'result' => null,
            'error'  => null,
        ]);
    }

    /**
     * Handle Health ID lookup POST.
     */
    public function healthIdLookup(Request $request)
    {
        $request->validate([
            'health_id' => 'required|string|max:64',
            'purpose'   => 'required|string|max:60',
        ]);

        // `where('is_demo', false)` under a scope that shows only is_demo = true
        // meant this page could never verify anyone on a demo host — and on a
        // production host the scope already partitions, so the clause was
        // redundant there. resolveHealthId() follows a merge, so a card issued
        // before one still verifies.
        $submitted = trim((string) $request->health_id);
        $patient   = Patient::resolveHealthId($submitted);

        // Who is asking. Only signed-in staff with a resolved facility posting
        // see full demographics; everyone else gets the masked card.
        $staffFacilityId = $this->staffFacilityId($request);

        // EVERY lookup is recorded — found or not, anonymous or signed in —
        // so the Health ID audit trail shows who tried which identifier.
        $this->auditLookup($request, $submitted, $patient, $staffFacilityId);

        if (!$patient) {
            return view('verify.health_id', [
                'result' => null,
                'error'  => __('verify.error_not_found', [], app()->getLocale())
                    ?: 'No verified Health ID found for the provided identifier. Please check the ID and try again.',
            ]);
        }

        $full = $staffFacilityId !== null;

        return view('verify.health_id', [
            // The view reads an OBJECT ($result->name …).
            'result' => (object) [
                'health_id'  => $patient->health_id,
                'name'       => $full
                    ? trim($patient->first_name . ' ' . $patient->last_name)
                    : self::maskedName($patient->first_name, $patient->last_name),
                'dob'        => $full ? $patient->date_of_birth?->format('Y-m-d') : null,
                'birth_year' => $patient->date_of_birth?->format('Y'),
                'sex'        => $patient->sex,
                'masked'     => ! $full,
            ],
            'error' => null,
        ]);
    }

    /**
     * "Amara D." — first name plus last initial. Never the full surname to an
     * anonymous caller.
     */
    public static function maskedName(?string $first, ?string $last): string
    {
        $first = trim((string) $first);
        $last  = trim((string) $last);
        $initial = $last !== '' ? mb_strtoupper(mb_substr($last, 0, 1)) . '.' : '';

        return trim($first . ' ' . $initial) ?: '—';
    }

    /**
     * The facility a signed-in caller is acting at, when they genuinely hold a
     * posting there; null for anonymous callers, patients, and platform-tier
     * accounts (who are not facility staff). Facility identity comes from the
     * session context only, never from the request body.
     */
    private function staffFacilityId(Request $request): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // A patient or guardian account is never "facility staff", even if a
        // primary_facility_id happens to be set on it.
        $roleName = $user->role?->name;
        if (!$roleName || in_array($roleName, ['patient', 'guardian', 'caregiver'], true)) {
            return null;
        }

        try {
            $allowed = app(\App\Services\Portal\FacilityMembershipService::class)->facilityIdsFor($user);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($allowed) || $allowed === []) {
            return null;
        }

        $active = $request->hasSession() ? $request->session()->get('active_facility_id') : null;
        if (is_string($active) && in_array($active, $allowed, true)) {
            return $active;
        }

        return count($allowed) === 1 ? $allowed[0] : null;
    }

    /**
     * One medical_id_access_events row per lookup, through the shared
     * HealthIdAuditLogger. Never fatal to the page.
     */
    private function auditLookup(Request $request, string $submitted, ?Patient $patient, ?string $facilityId): void
    {
        $user = Auth::user();

        try {
            app(\App\Services\Identity\HealthIdAuditLogger::class)->record(
                request:    $request,
                eventType:  \App\Enums\AuditEventType::VerifyHealthId,
                result:     $patient ? 'success' : 'not_found',
                healthId:   mb_substr($submitted, 0, 64),
                patientId:  $patient?->id,
                actorId:    $user ? (string) $user->id : null,
                actorType:  $user ? ($facilityId ? 'staff' : 'user') : 'public',
                facilityId: $facilityId,
                purpose:    (string) $request->input('purpose'),
            );
        } catch (\Throwable $e) {
            Log::warning('Health ID lookup audit failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle QR token verification (scanned link) — the provider's side of the
     * OPESCARE Secure Health ID.
     *
     * Four answers, never a bare error string: verified · expired · revoked ·
     * invalid. A successful scan is RECORDED as a HealthIdVerificationEvent —
     * that row is the reference printed on the page — and, when the scanner is
     * signed in, in the patient-visible access log as well. `access_logs.actor_id`
     * is a NOT NULL FK onto users, so an anonymous scan cannot be written there;
     * the page says exactly that instead of claiming an audit it did not make.
     *
     * The raw token is the bearer secret. It used to be echoed onto the page;
     * it is not passed to the view any more.
     */
    public function qr(string $token)
    {
        $qrToken = $this->qrService->verifyToken($token);
        $patient = $qrToken?->patient;

        if (!$qrToken || !$patient) {
            return view('verify.qr', [
                'state'       => $qrToken ? 'invalid' : $this->failureState($token),
                'result'      => null,
                'reference'   => null,
                'verified_at' => now(),
                'expires_at'  => null,
            ]);
        }

        $event = HealthIdVerificationEvent::create([
            'patient_id'              => $patient->id,
            'verification_type'       => 'qr_scan',
            'verified_by_user_id'     => Auth::id(),
            'verified_by_facility_id' => null,
            'verification_status'     => 'verified',
            'evidence_reference'      => $qrToken->uuid,
        ]);

        $this->auditSignedInScan($patient, $qrToken);

        return view('verify.qr', [
            'state'       => 'verified',
            // The view reads this as an OBJECT ($result->name, ->health_id, …).
            'result'      => (object) [
                'name'       => trim($patient->first_name . ' ' . $patient->last_name),
                'health_id'  => $patient->health_id,
                'dob'        => $patient->date_of_birth?->format('Y-m-d'),
                'sex'        => $patient->sex,
                'blood_type' => $patient->blood_group,
            ],
            'reference'   => strtoupper(substr(str_replace('-', '', (string) $event->uuid), 0, 10)),
            'verified_at' => now(),
            'expires_at'  => $qrToken->expires_at ? Carbon::parse($qrToken->expires_at) : null,
        ]);
    }

    /**
     * Why a token was refused, read from the row it names — without trusting
     * the secret. A wrong secret on a live row is still just "invalid".
     */
    private function failureState(string $rawToken): string
    {
        return $this->qrService->failureState($rawToken);
    }

    /**
     * The patient-visible audit entry, when there is a signed-in actor to
     * attribute it to. Never fatal: an audit failure must not take down the
     * page a clinician is standing in front of.
     */
    private function auditSignedInScan(Patient $patient, HealthIdQrToken $qrToken): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $user->id,
                actorType: 'user',
                organizationId: null,
                facilityId: null,
                purpose: 'identity_verification',
                dataCategory: 'demographics',
                resourceType: 'HealthIdQrToken',
                resourceId: (string) $qrToken->uuid,
                accessType: 'read',
            );
        } catch (\Throwable $e) {
            Log::warning('Health ID QR scan audit failed', [
                'token'  => $qrToken->uuid,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
