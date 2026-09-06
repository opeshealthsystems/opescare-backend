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
            'push_messages'          => 'sometimes|boolean',
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
     * `push_token`, when sent, must be an Expo push token —
     * `ExponentPushToken[…]` or `ExpoPushToken[…]`. It used to be
     * `required|string` and nothing more, which is exactly how
     * `push_device_tokens` filled up with DEVICE FINGERPRINTS: the mobile
     * client sent its own random device id as the push token and the endpoint
     * stored it without a murmur. A fingerprint is not a credential Expo will
     * ever accept, so every row holding one is a device that can never be
     * reached — a silent, permanent delivery failure that looks like a
     * successful registration on both ends.
     *
     * The check is `ExpoPushService::isExpoPushToken()`, the same predicate the
     * sender uses to decide what it may put on the wire, so the gate at the
     * door and the gate at the exit can never drift apart. Do not inline a
     * second copy of the pattern here.
     *
     * A registration that OMITS `push_token` (fingerprint only) is allowed: it
     * is the in-app path — a handset that identifies itself but holds no push
     * credential (web, or a build with no EAS project). The row is stored with
     * an empty token, answers `deliverable: false`, and is never sent to; the
     * sender skips it as `skippedNoToken`. Sending the key at all — even empty
     * or blank — means "here is my credential", and it must be one (422).
     *
     * Hygiene, in order:
     *   1. A token belongs to one account. Any OTHER patient holding this
     *      token is revoked — the phone was signed out and signed in again as
     *      someone else, or changed hands.
     *   2. The same handset (patient + fingerprint + platform) re-registering
     *      updates its row — `registered_at` moves, nothing is duplicated.
     *   3. The same TOKEN re-registering under a new fingerprint (a reinstall
     *      regenerated the fingerprint; Expo kept the token) updates the row
     *      that already holds the token instead of leaving two live rows for
     *      one phone. Any further duplicates are revoked.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $rules = [
            'device_fingerprint' => 'required|string|max:128',
            'platform'           => 'required|in:ios,android,web',
        ];

        // Key absent → fingerprint-only. Key present (blank included: the
        // framework trims '   ' to null before this runs) → must be a token.
        if ($request->exists('push_token')) {
            $rules['push_token'] = [
                'required',
                'string',
                'max:255',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ExpoPushService::isExpoPushToken(is_string($value) ? $value : null)) {
                        $fail(__('api.push_token_invalid'));
                    }
                },
            ];
        }

        $validated = $request->validate($rules);

        $patientId   = $this->resolvePatientId($request);
        $fingerprint = $validated['device_fingerprint'];
        $platform    = $validated['platform'];
        $pushToken   = trim((string) ($validated['push_token'] ?? ''));

        $token = \Illuminate\Support\Facades\DB::transaction(function () use ($patientId, $fingerprint, $platform, $pushToken): PushDeviceToken {
            $stamp = [
                'push_token'    => $pushToken,
                'is_active'     => true,
                'revoked_at'    => null,
                'registered_at' => now(),
            ];

            if ($pushToken !== '') {
                // 1. The token moved to this account: nobody else keeps it.
                PushDeviceToken::query()
                    ->where('push_token', $pushToken)
                    ->where('patient_id', '!=', $patientId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'revoked_at' => now()]);
            }

            // 2. This handset's own row.
            $byDevice = PushDeviceToken::query()
                ->where('patient_id', $patientId)
                ->where('device_fingerprint', $fingerprint)
                ->where('platform', $platform)
                ->first();

            // 3. This token already on file for this account under another handset id.
            $byToken = $pushToken === ''
                ? collect()
                : PushDeviceToken::query()
                    ->where('patient_id', $patientId)
                    ->where('push_token', $pushToken)
                    ->when($byDevice !== null, fn ($q) => $q->whereKeyNot($byDevice->getKey()))
                    ->orderByDesc('registered_at')
                    ->get();

            if ($byDevice !== null) {
                $byDevice->fill($stamp)->save();
                $row = $byDevice;
            } elseif ($byToken->isNotEmpty()) {
                /** @var PushDeviceToken $row */
                $row = $byToken->shift();
                $row->fill($stamp + ['device_fingerprint' => $fingerprint, 'platform' => $platform])->save();
            } else {
                $row = PushDeviceToken::create($stamp + [
                    'patient_id'         => $patientId,
                    'device_fingerprint' => $fingerprint,
                    'platform'           => $platform,
                ]);
            }

            foreach ($byToken as $duplicate) {
                if ($duplicate->is_active) {
                    $duplicate->revoke();
                }
            }

            return $row;
        });

        return response()->json([
            'status'      => 'registered',
            'token_id'    => $token->id,
            'platform'    => $token->platform,
            'deliverable' => $token->isDeliverable(),
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

    /**
     * Revoke this account's push tokens — on sign-out.
     *
     * DELETE /api/mobile/push-tokens
     *
     * The single-row DELETE above needs the `token_id` the app stored at
     * registration, which a reinstall or a cleared keychain loses; sign-out
     * must still be able to say "stop pushing to this phone". With
     * `device_fingerprint` in the body only that handset's rows (any platform)
     * are revoked — the patient's other phone keeps ringing; without it every
     * active row of the account goes ("sign out everywhere").
     *
     * Idempotent: revoking nothing is still 200, with `revoked: 0`.
     */
    public function revokeAllPushTokens(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => 'sometimes|nullable|string|max:128',
        ]);

        $patientId   = $this->resolvePatientId($request);
        $fingerprint = trim((string) ($validated['device_fingerprint'] ?? ''));

        $revoked = PushDeviceToken::query()
            ->where('patient_id', $patientId)
            ->where('is_active', true)
            ->when($fingerprint !== '', fn ($q) => $q->where('device_fingerprint', $fingerprint))
            ->update(['is_active' => false, 'revoked_at' => now()]);

        return response()->json([
            'status'  => 'revoked',
            'revoked' => $revoked,
            'scope'   => $fingerprint !== '' ? 'device' : 'account',
        ]);
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
            'push_messages'           => $s->push_messages ?? true,
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
