<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppSetting;
use App\Models\PushDeviceToken;
use App\Services\ExpoPushService;
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
     *
     * `push_token` must be an Expo push token — `ExponentPushToken[…]` or
     * `ExpoPushToken[…]`. It used to be `required|string` and nothing more,
     * which is exactly how `push_device_tokens` filled up with DEVICE
     * FINGERPRINTS: the mobile client sent its own random device id as the
     * push token and the endpoint stored it without a murmur. A fingerprint is
     * not a credential Expo will ever accept, so every row holding one is a
     * device that can never be reached — a silent, permanent delivery failure
     * that looks like a successful registration on both ends.
     *
     * The check is `ExpoPushService::isExpoPushToken()`, the same predicate the
     * sender uses to decide what it may put on the wire, so the gate at the
     * door and the gate at the exit can never drift apart. Do not inline a
     * second copy of the pattern here.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => 'required|string|max:128',
            'platform'           => 'required|in:ios,android,web',
            'push_token'         => [
                'required',
                'string',
                'max:255',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ExpoPushService::isExpoPushToken(is_string($value) ? $value : null)) {
                        $fail(__('api.push_token_invalid'));
                    }
                },
            ],
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
