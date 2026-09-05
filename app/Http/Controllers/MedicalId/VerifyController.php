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
        $patient = Patient::resolveHealthId((string) $request->health_id);

        if (!$patient) {
            return view('verify.health_id', [
                'result' => null,
                'error'  => __('verify.error_not_found', [], app()->getLocale())
                    ?: 'No verified Health ID found for the provided identifier. Please check the ID and try again.',
            ]);
        }

        return view('verify.health_id', [
            'result' => [
                'health_id'     => $patient->health_id,
                'first_name'    => $patient->first_name,
                'last_name'     => $patient->last_name,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'sex'           => $patient->sex,
            ],
            'error' => null,
        ]);
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
