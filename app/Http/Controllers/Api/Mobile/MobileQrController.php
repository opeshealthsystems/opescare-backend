<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Identity\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileQrController extends Controller
{
    /**
     * Generate a short-lived (15-minute) QR token for temporary record access.
     * POST /api/mobile/qr/temporary
     */
    public function generateTemporary(Request $request, QrTokenService $qrTokens): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');
        $patient   = Patient::find($patientId);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // Mint through the SAME service the web verifier reads. The previous
        // implementation minted its own `qr_<random>` into patient_access_tokens
        // and pointed at /verify-health-id?token=, which is not a route this app
        // serves — so a scanned code 404'd, and even on the right URL the
        // verifier could not read it: it splits `qrx_<lookupId>_<secret>` and
        // looks the token up in health_id_qr_tokens by lookup_id. The QR was
        // unverifiable by construction.
        $issued = $qrTokens->generateToken($patient->id, 'temporary_access_qr', 15);
        $rawToken  = $issued['raw_token'];
        $verifyUrl = $issued['secure_url'];          // route('verify.qr'), a real route
        $expiresAt = $issued['model']->expires_at;

        return response()->json([
            // The QR now carries the verification URL itself, so any phone
            // camera opens the verifier. The identity fields stay in the
            // response for the app's own on-screen card, but they are no longer
            // what the code encodes — base64 JSON is unreadable to a scanner.
            'qr_payload'  => $verifyUrl,
            'verify_url'  => $verifyUrl,
            'raw_token'   => $rawToken,
            'expires_at'  => $expiresAt->toIso8601String(),
            'expires_in'  => 900,
            'patient'     => [
                'health_id'  => $patient->health_id,
                'first_name' => $patient->first_name,
                'last_initial' => substr((string) $patient->last_name, 0, 1),
            ],
        ]);
    }
}
