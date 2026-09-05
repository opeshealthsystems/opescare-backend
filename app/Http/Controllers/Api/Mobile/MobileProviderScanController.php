<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ConsentGrant;
use App\Models\HealthIdVerificationEvent;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Identity\QrTokenService;
use App\Support\ProviderPatientSummary;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /mobile/doctor/patients/scan — a clinician scanned a patient's Health
 * ID QR code with the app.
 *
 * The code the patient presents encodes the public verifier URL
 * (/verify/qr/{qrx_…}); the app posts whatever it decoded and this endpoint
 * turns it into the SAME patient summary the exact-Health-ID lookup returns,
 * plus whether this facility already holds an active consent grant. The scan
 * is recorded twice, like a signed-in scan of the web verifier: as a
 * HealthIdVerificationEvent (the reference shown to the clinician) and in the
 * patient's own access log under the scanning facility.
 *
 * What it refuses to do: accept anything that is not a Health ID code (422),
 * or treat an expired / withdrawn / unknown token as a match (404 with a
 * precise error_code so the app can say which). No enumeration: a token names
 * exactly one patient or nobody.
 */
class MobileProviderScanController extends Controller
{
    public function __construct(private readonly QrTokenService $qr)
    {
    }

    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:2048',
        ]);

        $rawToken = self::extractToken($validated['code']);

        if ($rawToken === null) {
            return response()->json([
                'message'    => __('api.qr_code_not_recognised'),
                'error_code' => 'not_a_health_id_code',
            ], 422);
        }

        $qrToken = $this->qr->verifyToken($rawToken);
        $patient = $qrToken?->patient;

        if (! $qrToken || ! $patient) {
            $state = $qrToken ? 'invalid' : $this->qr->failureState($rawToken);

            return response()->json([
                'message'    => __('api.qr_' . $state),
                'error_code' => 'qr_' . $state,
            ], 404);
        }

        $userId     = $request->attributes->get('provider_user_id');
        $facilityId = $request->attributes->get('provider_facility_id');

        $event = HealthIdVerificationEvent::create([
            'patient_id'              => $patient->id,
            'verification_type'       => 'qr_scan',
            'verified_by_user_id'     => $userId,
            'verified_by_facility_id' => $facilityId,
            'verification_status'     => 'verified',
            'evidence_reference'      => $qrToken->uuid,
        ]);

        $this->audit($patient->id, $userId, $facilityId, (string) $qrToken->uuid);

        $granted = ConsentGrant::where('patient_id', $patient->id)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();

        return response()->json([
            'data'            => ProviderPatientSummary::from($patient),
            'consent_granted' => $granted,
            'verification'    => [
                'reference'  => strtoupper(substr(str_replace('-', '', (string) $event->uuid), 0, 10)),
                'token_type' => $qrToken->token_type,
                'expires_at' => $qrToken->expires_at ? Carbon::parse($qrToken->expires_at)->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * The raw token inside whatever the camera decoded: the verifier URL the
     * card and Presentation Mode encode, or a bare `qrx_…` token. Anything
     * else is not a Health ID code.
     */
    public static function extractToken(string $code): ?string
    {
        $code = trim($code);

        if (preg_match('~/verify/qr/(qrx_[A-Za-z0-9]+_[A-Za-z0-9]+)~', $code, $m)) {
            return $m[1];
        }

        if (preg_match('~^qrx_[A-Za-z0-9]+_[A-Za-z0-9]+$~', $code)) {
            return $code;
        }

        return null;
    }

    private function audit(string $patientId, ?string $userId, ?string $facilityId, string $tokenUuid): void
    {
        if (! $userId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $userId,
                actorType: 'user',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'identity_verification',
                dataCategory: 'demographics',
                resourceType: 'HealthIdQrToken',
                resourceId: $tokenUuid,
                accessType: 'read',
            );
        } catch (\Throwable $e) {
            Log::warning('Health ID QR scan (app) audit failed', [
                'token' => $tokenUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
