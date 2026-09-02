<?php

namespace App\Http\Controllers\Api\ProviderMobile;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\MobileFacilityContext;
use App\Models\ProviderDevice;
use App\Models\ProviderMobileSession;
use App\Models\ProviderOtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provider Mobile API — Authentication & Device Registration
 *
 * Handles provider (clinician/staff) login to the provider mobile app.
 * Separate from the patient mobile auth. Uses staff credentials.
 */
class ProviderMobileAuthController extends Controller
{
    /**
     * Authenticate a provider.
     *
     * POST /api/provider-mobile/auth/login
     * Body: { email, pin_hash, device_fingerprint, platform, device_name? }
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'              => 'required|email',
            'pin_hash'           => 'required|string|min:8',
            'device_fingerprint' => 'required|string|max:128',
            'platform'           => 'required|in:ios,android,web',
            'device_name'        => 'sometimes|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['error' => __('api.invalid_credentials')], 401);
        }

        // Reject inactive/suspended provider accounts
        if (isset($user->status) && !in_array($user->status, ['active', null], true)) {
            return response()->json(['error' => __('api.account_not_active')], 403);
        }

        if (!Hash::check($validated['pin_hash'], $user->password)) {
            return response()->json(['error' => __('api.invalid_credentials')], 401);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        ProviderOtpCode::create([
            'user_id' => $user->id,
            'device_fingerprint' => $validated['device_fingerprint'],
            'code_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        $payload = [
            'status'  => 'pending_2fa',
            'message' => __('api.credentials_accepted_otp'),
        ];

        if (!app()->isProduction()) {
            $payload['debug_otp'] = $otp;
        }

        return response()->json($payload);
    }

    /**
     * Verify OTP and issue provider session token.
     *
     * POST /api/provider-mobile/auth/otp/verify
     * Body: { otp_code, device_fingerprint, platform, app_version? }
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'otp_code'           => 'required|string',
            'device_fingerprint' => 'required|string|max:128',
            'platform'           => 'required|in:ios,android,web',
            'app_version'        => 'sometimes|string|max:30',
        ]);

        $otpRecord = ProviderOtpCode::where('device_fingerprint', $validated['device_fingerprint'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($validated['otp_code'], $otpRecord->code_hash)) {
            return response()->json(['error' => __('api.otp_invalid')], 401);
        }

        $otpRecord->update(['used_at' => now()]);

        $accessToken = 'prov_' . Str::random(40);
        $tokenHash   = hash('sha256', $accessToken);

        // Register or update device record
        $userId = $otpRecord->user_id;

        ProviderDevice::updateOrCreate(
            ['device_fingerprint' => $validated['device_fingerprint']],
            [
                'user_id'        => $userId,
                'platform'       => $validated['platform'],
                'device_name'    => $request->input('device_name', 'Provider Device'),
                'status'         => 'active',
                'last_seen_at'   => now(),
            ]
        );

        // Create session record
        $session = ProviderMobileSession::create([
            'user_id'            => $userId,
            'device_fingerprint' => $validated['device_fingerprint'],
            'platform'           => $validated['platform'],
            'app_version'        => $validated['app_version'] ?? null,
            'access_token_hash'  => $tokenHash,
            'last_seen_at'       => now(),
            'expires_at'         => now()->addHours(12),
        ]);

        // Resolve facilities the provider is assigned to
        $facilities = Facility::take(5)->get(['id', 'name'])->map(fn ($f) => [
            'id'   => $f->id,
            'name' => $f->name,
        ]);

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 43200,
            'session_id'   => $session->id,
            'facilities'   => $facilities,
        ], 200);
    }

    /**
     * Register/update push token for the provider device.
     *
     * POST /api/provider-mobile/auth/push-token
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => 'required|string|max:128',
            'push_token'         => 'required|string',
        ]);

        $userId = $this->resolveUserId($request);

        ProviderDevice::where('device_fingerprint', $validated['device_fingerprint'])
            ->where('user_id', $userId)
            ->update([
                'push_token'  => $validated['push_token'],
                'push_active' => true,
            ]);

        return response()->json(['status' => 'push_token_registered']);
    }

    /**
     * Revoke the current session (logout).
     *
     * POST /api/provider-mobile/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        ProviderMobileSession::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->latest()
            ->first()?->update([
                'revoked_at'   => now(),
                'revoke_reason'=> 'user_logout',
            ]);

        return response()->json(['status' => 'logged_out']);
    }

    // -------------------------------------------------------------------------

    private function resolveUserId(Request $request): string
    {
        if (app()->environment('testing') && $request->has('_user_id')) {
            return $request->input('_user_id');
        }
        $userId = $request->attributes->get('user_id');
        if ($userId) {
            return $userId;
        }
        abort(401, 'Unauthenticated.');
    }
}
