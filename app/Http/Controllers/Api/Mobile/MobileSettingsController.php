<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppSetting;
use App\Models\PushDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — App Settings & Push Tokens
 */
class MobileSettingsController extends Controller
{
    /**
     * Get the patient's app settings.
     *
     * GET /api/mobile/settings
     */
    public function show(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $settings  = MobileAppSetting::forPatient($patientId);

        return response()->json(['data' => $this->formatSettings($settings)]);
    }

    /**
     * Update notification preferences and language/theme.
     *
     * PATCH /api/mobile/settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_appointments'      => 'sometimes|boolean',
            'push_lab_results'       => 'sometimes|boolean',
            'push_prescriptions'     => 'sometimes|boolean',
            'push_billing'           => 'sometimes|boolean',
            'push_consent_requests'  => 'sometimes|boolean',
            'preferred_language'     => 'sometimes|string|max:10',
            'preferred_theme'        => 'sometimes|in:light,dark,system',
            'biometric_login_enabled'=> 'sometimes|boolean',
        ]);

        $patientId = $this->resolvePatientId($request);
        $settings  = MobileAppSetting::forPatient($patientId);
        $settings->update($validated);

        return response()->json(['data' => $this->formatSettings($settings->fresh())]);
    }

    /**
     * Register or update a push device token.
     *
     * POST /api/mobile/push-tokens
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => 'required|string|max:128',
            'platform'           => 'required|in:ios,android,web',
            'push_token'         => 'required|string',
        ]);

        $patientId = $this->resolvePatientId($request);

        // Upsert: update existing or create new
        $token = PushDeviceToken::updateOrCreate(
            [
                'patient_id'         => $patientId,
                'device_fingerprint' => $validated['device_fingerprint'],
                'platform'           => $validated['platform'],
            ],
            [
                'push_token'    => $validated['push_token'],
                'is_active'     => true,
                'revoked_at'    => null,
                'registered_at' => now(),
            ]
        );

        return response()->json([
            'status'   => 'registered',
            'token_id' => $token->id,
            'platform' => $token->platform,
        ], 201);
    }

    /**
     * Revoke a push token (e.g. on logout or device change).
     *
     * DELETE /api/mobile/push-tokens/{id}
     */
    public function revokePushToken(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $token = PushDeviceToken::where('id', $id)
            ->where('patient_id', $patientId)
            ->firstOrFail();

        $token->revoke();

        return response()->json(['status' => 'revoked']);
    }

    // -------------------------------------------------------------------------

    private function formatSettings(MobileAppSetting $s): array
    {
        return [
            'push_appointments'       => $s->push_appointments,
            'push_lab_results'        => $s->push_lab_results,
            'push_prescriptions'      => $s->push_prescriptions,
            'push_billing'            => $s->push_billing,
            'push_consent_requests'   => $s->push_consent_requests,
            'preferred_language'      => $s->preferred_language,
            'preferred_theme'         => $s->preferred_theme,
            'biometric_login_enabled' => $s->biometric_login_enabled,
        ];
    }

    private function resolvePatientId(Request $request): string
    {
        return $request->attributes->get('patient_id') ?? '';
    }
}
