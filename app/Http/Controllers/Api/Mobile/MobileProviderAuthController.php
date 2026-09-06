<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProviderAccessToken;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Portal\FacilityMembershipService;
use App\Services\Portal\ProviderSessionService;
use App\Support\StaffCapabilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
 *     The ONE deliberate exception on this whole surface is
 *     POST /api/mobile/provider/facility (MobileProviderFacilityController),
 *     which takes a facility id from the body precisely so a clinician can
 *     change it — and refuses any id the caller does not hold an active
 *     posting at, checked server-side against FacilityMembershipService.
 *     Nothing else on the provider surface reads a facility from a request.
 *
 *  2. A valid password belonging to a non-clinician is refused with the SAME
 *     401 and the SAME message as a wrong password. Saying "your credentials
 *     are correct but you are not a clinician" confirms the account exists,
 *     which is exactly what an attacker enumerating emails is looking for.
 *
 *  3. A professional whose role holds NO capability at all (insurance, NGO,
 *     API-partner and other non-clinical staff — config/staff_capabilities.php)
 *     is refused a session with a 403 that says so. Unlike rule 2 this is not
 *     an enumeration risk: it is only reachable with the right password, and
 *     every `capability:` route would refuse the token anyway, so the honest
 *     answer is to say it once, at the door, instead of minting a dead session.
 *
 * Token minting, rotation and the `provider` payload all live in
 * ProviderSessionService so that login and the facility switch produce
 * byte-identical sessions; this controller only decides WHO and WHERE.
 */
class MobileProviderAuthController extends Controller
{
    public function __construct(
        private readonly ProviderSessionService $sessions,
        private readonly FacilityMembershipService $memberships,
    ) {
    }

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

        // Rule 3 (class docblock): a role with no capability at all has nothing
        // on this surface — refuse the session rather than mint one that every
        // clinician route will turn away. Same shape as the middleware refusal
        // so the app can recognise it.
        if (StaffCapabilities::forRole($user->role?->name) === []) {
            return response()->json([
                'message'    => __('api.provider_role_not_clinical'),
                'error_code' => 'role_forbidden',
            ], 403);
        }

        $facilityId = $this->resolveFacilityId($user);

        if (! $facilityId) {
            // Minting a token with no facility would produce a session that can
            // authenticate but can never write — a silent dead end at the
            // bedside. Fail loudly at login instead.
            return response()->json(['message' => __('api.provider_no_facility')], 409);
        }

        $rawToken = $this->sessions->mint($user, $facilityId, $validated['device_name'] ?? null);

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => ProviderSessionService::TOKEN_TTL_DAYS * 86400,
            'provider'     => $this->sessions->payload($user, $facilityId, $this->facilityCount($user)),
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

        if ($token instanceof ProviderAccessToken) {
            $this->sessions->revoke($token);
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
            'provider' => $this->sessions->payload($user, $facilityId, $this->facilityCount($user)),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /**
     * The facility a fresh session STARTS at.
     *
     * This used to be `StaffProfile::where(...)->value('facility_id')` — one
     * column off whichever row Postgres returned first. For a clinician posted
     * at one facility that is right by accident; for a clinician posted at two
     * it handed out an arbitrary facility, stamped it on a 30-day token, said
     * nothing, and offered no way to change it. That is the bug this feature
     * exists to close.
     *
     * It is now DETERMINISTIC and shares its definition of "a posting" with the
     * web portal: FacilityMembershipService prefers `users.primary_facility_id`
     * when that is a real posting, and otherwise takes the alphabetically first
     * facility the clinician is actually posted at. Whichever it picks, the
     * session is no longer stuck with it — GET /api/mobile/provider/facilities
     * lists the rest and POST /api/mobile/provider/facility moves to one.
     *
     * Still never accepts a facility from the request. See the class docblock.
     */
    private function resolveFacilityId(User $user): ?string
    {
        $resolved = $this->memberships->defaultFacilityIdFor($user);

        if ($resolved) {
            return $resolved;
        }

        // Legacy last resort, kept so this change cannot lock anybody out who
        // could sign in yesterday: a clinician whose ONLY record is a
        // staff_profiles row that is not marked active (older seeded staff, and
        // records imported before `status` was populated) still gets a session.
        // The switch endpoint does NOT honour this fallback — moving to another
        // facility requires a genuine active posting.
        return StaffProfile::where('user_id', $user->getKey())
            ->whereNotNull('facility_id')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->value('facility_id') ?: null;
    }

    /**
     * How many facilities this clinician may act at — the number the app uses
     * to decide whether to offer a "Switch facility" row at all. Null for a
     * platform-tier account, which holds no postings.
     */
    private function facilityCount(User $user): ?int
    {
        $ids = $this->memberships->facilityIdsFor($user);

        return $ids === null ? null : count($ids);
    }
}
