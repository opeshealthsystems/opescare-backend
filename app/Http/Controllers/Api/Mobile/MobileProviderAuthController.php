<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ProviderAccessToken;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — sessions for clinicians.
 *
 * The counterpart to MobileAuthController, which speaks only for patients. A
 * patient token speaks for one person's own record; a provider token acts on
 * OTHER people's records inside one facility, so it lives in its own table
 * (provider_access_tokens) behind its own middleware (`auth.provider`).
 *
 * Two rules shape this file:
 *
 *  1. The facility a clinician writes for is resolved HERE, from their staff
 *     record, and stamped onto the token. It is never read from the request
 *     body — a caller who could name their own facility could prescribe into
 *     any facility in the country.
 *
 *  2. A valid password belonging to a non-clinician is refused with the SAME
 *     401 and the SAME message as a wrong password. Saying "your credentials
 *     are correct but you are not a clinician" confirms the account exists,
 *     which is exactly what an attacker enumerating emails is looking for.
 */
class MobileProviderAuthController extends Controller
{
    /** Matches the patient token's lifetime, so both halves of the app expire alike. */
    private const TOKEN_TTL_DAYS = 30;

    /**
     * Email + password → a 30-day provider bearer token.
     *
     * POST /api/mobile/auth/provider/login
     * Body: { email, password, device_name? }
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => 'required|email|max:180',
            'password'    => 'required|string|min:4',
            'device_name' => 'nullable|string|max:120',
        ]);

        // Verify against the same credentials the web portal uses. Auth is only
        // a password checker here — the mobile API carries its own tokens and
        // must not leave a session cookie behind, so the login is dropped
        // immediately afterwards (identical to MobileAuthController).
        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json(['message' => __('api.provider_invalid_credentials')], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        Auth::logout();

        // Deliberately the same status and the same message as a bad password.
        // Do not add a distinguishing message here.
        if (! $user || ! $user->isProfessional()) {
            return response()->json(['message' => __('api.provider_invalid_credentials')], 401);
        }

        $facilityId = $this->resolveFacilityId($user);

        if (! $facilityId) {
            // Minting a token with no facility would produce a session that can
            // authenticate but can never write — a silent dead end at the
            // bedside. Fail loudly at login instead.
            return response()->json(['message' => __('api.provider_no_facility')], 409);
        }

        $rawToken = $this->mintToken($user, $facilityId, $validated['device_name'] ?? null);

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => self::TOKEN_TTL_DAYS * 86400,
            'provider'     => $this->providerPayload($user, $facilityId),
        ], 200);
    }

    /**
     * Revoke the presented session. The row is kept and stamped `revoked_at`
     * rather than deleted, so the audit trail still shows the session existed.
     *
     * POST /api/mobile/auth/provider/logout   (auth.provider)
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('provider_token');

        if ($token instanceof ProviderAccessToken && $token->revoked_at === null) {
            $token->forceFill(['revoked_at' => Carbon::now()])->save();
        }

        return response()->json(['message' => __('api.provider_logged_out')], 200);
    }

    /**
     * The signed-in clinician — lets the app restore a session on cold start
     * without asking for the password again.
     *
     * GET /api/mobile/auth/provider/me   (auth.provider)
     */
    public function me(Request $request): JsonResponse
    {
        $userId     = $request->attributes->get('provider_user_id');
        $facilityId = $request->attributes->get('provider_facility_id');

        $user = User::with('role')->find($userId);

        if (! $user) {
            return response()->json(['message' => __('api.provider_invalid_credentials')], 401);
        }

        return response()->json([
            'provider' => $this->providerPayload($user, $facilityId),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility this clinician writes for, resolved from their own staff
     * record only. An active staff posting wins over an inactive one, and
     * `users.primary_facility_id` is the fallback for clinicians who have a
     * portal account but no staff_profiles row yet.
     *
     * Never accepts a facility from the request. See the class docblock.
     */
    private function resolveFacilityId(User $user): ?string
    {
        $fromStaffProfile = StaffProfile::where('user_id', $user->id)
            ->whereNotNull('facility_id')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->value('facility_id');

        return $fromStaffProfile ?: ($user->primary_facility_id ?: null);
    }

    /**
     * Mint the bearer and store only its hashes — the same split the patient
     * token uses: an indexed SHA-256 for O(1) lookup, plus a slow hash the
     * middleware verifies in constant time. The raw token is returned once and
     * never stored.
     */
    private function mintToken(User $user, string $facilityId, ?string $deviceName): string
    {
        $rawToken = 'prv_' . Str::random(40);

        ProviderAccessToken::create([
            'user_id'           => $user->id,
            'token_lookup_hash' => hash('sha256', $rawToken),
            'token_hash'        => Hash::make($rawToken),
            'token_prefix'      => substr($rawToken, 0, 12),
            'facility_id'       => $facilityId,
            'expires_at'        => Carbon::now()->addDays(self::TOKEN_TTL_DAYS),
            'device_name'       => $deviceName,
        ]);

        return $rawToken;
    }

    /**
     * The identity block the app renders in its header and stores for session
     * restore. Same shape from login and from /me so the app has one parser.
     *
     * @return array<string,mixed>
     */
    private function providerPayload(User $user, ?string $facilityId): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'role'          => $user->role?->name,
            'facility_id'   => $facilityId,
            'facility_name' => $facilityId ? Facility::whereKey($facilityId)->value('name') : null,
        ];
    }
}
