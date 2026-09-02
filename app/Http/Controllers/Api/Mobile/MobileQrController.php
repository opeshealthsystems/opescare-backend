<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PatientAccessToken;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MobileQrController extends Controller
{
    /**
     * Generate a short-lived (15-minute) QR token for temporary record access.
     * POST /api/mobile/qr/temporary
     */
    public function generateTemporary(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');
        $patient   = Patient::find($patientId);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // Create a short-lived token scoped for QR access
        $rawToken = 'qr_' . Str::random(32);
        $expiresAt = Carbon::now()->addMinutes(15);

        // Store it as a PatientAccessToken with a special prefix
        PatientAccessToken::create([
            'patient_id'   => $patientId,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => $expiresAt,
        ]);

        // The QR payload encodes the verification URL
        $verifyUrl = config('app.url') . '/verify-health-id?token=' . $rawToken;

        return response()->json([
            'qr_payload'  => base64_encode(json_encode([
                'hid'   => $patient->health_id,
                'fn'    => $patient->first_name,
                'ln'    => substr($patient->last_name, 0, 1),
                'token' => $rawToken,
                'exp'   => $expiresAt->toIso8601String(),
            ])),
            'verify_url'  => $verifyUrl,
            'raw_token'   => $rawToken,
            'expires_at'  => $expiresAt->toIso8601String(),
            'expires_in'  => 900, // seconds
        ]);
    }
}
