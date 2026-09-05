<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ProviderAccessToken;
use App\Models\User;
use App\Services\Portal\FacilityMembershipService;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Provider API — the facility a clinician is acting as.
 *
 * ── THE GAP THIS CLOSES ───────────────────────────────────────────────────
 * A doctor posted at two facilities used to get whichever one sorted first out
 * of `staff_profiles`, stamped onto a 30-day token, with no indication and no
 * way to change it. Every chart they read, every prescription they wrote and
 * every appointment they saw was scoped to a facility nobody chose. This
 * controller is the choice: list the postings, move between them.
 *
 * ── THE ONE PLACE A FACILITY MAY COME FROM A REQUEST BODY ─────────────────
 * The platform rule is absolute everywhere else: `facility_id` comes from the
 * auth middleware (`$request->attributes`) and never from a header, body,
 * query or session, because a caller who can name their own facility can read
 * and write any facility in the country.
 *
 * `switch()` is the single deliberate exception, and it is only safe because
 * the body does not DECIDE anything — it NOMINATES. The id is then checked,
 * server-side, against the clinician's real postings
 * (FacilityMembershipService: active `facility_role_assignments`, active
 * `staff_profiles`, `users.primary_facility_id`). An id outside that set is
 * refused with 403 and nothing changes. A caller cannot widen their own reach
 * by naming a facility, only move within a reach that already exists.
 *
 * Note what is NOT trusted here either: the CURRENT facility still comes from
 * the token via `$request->attributes`, never from the body. The body carries
 * exactly one field.
 *
 * ── SWITCHING MINTS A NEW SESSION ─────────────────────────────────────────
 * `provider_access_tokens` says a clinician who moves facility "gets a new
 * session rather than a token that silently follows them". So the presented
 * token is revoked and a new one is minted for the destination — see
 * ProviderSessionService::rotateToFacility() for why that order and what it
 * buys. The old bearer is dead the moment the switch is decided; a client that
 * loses the response must sign in again rather than re-use it.
 *
 * Every switch is written to `audit_events` as `facility_context_switch`
 * (actor, from, to, when, channel), whether it came from the phone or the web
 * portal.
 */
class MobileProviderFacilityController extends Controller
{
    public function __construct(
        private readonly FacilityMembershipService $memberships,
        private readonly ProviderSessionService $sessions,
    ) {
    }

    /**
     * The facilities this clinician may act at.
     *
     * GET /api/mobile/provider/facilities   (auth.provider)
     *
     * 200 { current_facility_id, facilities: [ { id, name, type, status, role,
     *       role_label, is_current } ] }
     *
     * The list is the clinician's real postings and nothing else — it is never
     * the facility directory, so there is no facility here they could name that
     * `switch()` would then refuse.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->actingClinician($request);

        if (! $user) {
            return response()->json(['message' => __('api.provider_invalid_credentials')], 401);
        }

        $currentFacilityId = $request->attributes->get('provider_facility_id');

        return response()->json([
            'current_facility_id' => $currentFacilityId,
            'facilities'          => $this->memberships->facilitiesFor($user, $currentFacilityId),
        ], 200);
    }

    /**
     * Act as another facility.
     *
     * POST /api/mobile/provider/facility   (auth.provider)
     * Body: { facility_id }
     *
     * 200 changed  { changed: true,  access_token, token_type, expires_in, provider }
     * 200 no-op    { changed: false, provider }            — already there; the
     *              caller KEEPS its current token, and none is returned. Check
     *              `changed` (or the presence of `access_token`) before storing.
     * 403          the clinician holds no active posting at that facility
     * 409          the facility is suspended
     * 422          malformed / missing facility_id
     */
    public function switch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'facility_id' => 'required|uuid',
        ]);

        $user  = $this->actingClinician($request);
        $token = $request->attributes->get('provider_token');

        if (! $user || ! $token instanceof ProviderAccessToken) {
            return response()->json(['message' => __('api.provider_invalid_credentials')], 401);
        }

        $targetId = (string) $validated['facility_id'];
        $fromId   = $request->attributes->get('provider_facility_id');

        // THE membership check. Everything above this line is caller-supplied;
        // nothing below it trusts the caller for anything but the id it just
        // proved. A facility the clinician does not work at stops here.
        if (! $this->memberships->mayActAt($user, $targetId)) {
            return response()->json([
                'message'    => __('facility_switch.api.not_a_member'),
                'error_code' => 'FACILITY_NOT_ASSIGNED',
            ], 403);
        }

        $facility = Facility::withoutGlobalScope('isolate_demo')->find($targetId);

        if (! $facility) {
            // Membership said yes but the row is gone — a withdrawn facility.
            // Treated as "not yours" rather than 404, which would confirm the
            // id exists somewhere to a caller probing uuids.
            return response()->json([
                'message'    => __('facility_switch.api.not_a_member'),
                'error_code' => 'FACILITY_NOT_ASSIGNED',
            ], 403);
        }

        if ($facility->status === 'suspended') {
            return response()->json([
                'message'    => __('facility_switch.api.facility_suspended'),
                'error_code' => 'FACILITY_SUSPENDED',
            ], 409);
        }

        // Already acting as this facility: nothing to audit and no reason to
        // burn a working session. The current token stays valid and is not
        // reissued, so a double tap cannot sign anyone out.
        if ($fromId !== null && (string) $fromId === $targetId) {
            return response()->json([
                'changed'  => false,
                'provider' => $this->sessions->payload($user, $targetId, $this->facilityCount($user)),
            ], 200);
        }

        $rawToken = $this->sessions->rotateToFacility($token, $user, $targetId);

        $this->memberships->auditSwitch(
            user: $user,
            fromFacilityId: $fromId !== null ? (string) $fromId : null,
            toFacilityId: $targetId,
            channel: 'mobile_provider',
            ipAddress: $request->ip(),
            deviceName: $token->device_name,
        );

        return response()->json([
            'changed'      => true,
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => ProviderSessionService::TOKEN_TTL_DAYS * 86400,
            'provider'     => $this->sessions->payload($user, $targetId, $this->facilityCount($user)),
        ], 200);
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    /** The clinician behind the bearer — from the middleware's attributes only. */
    private function actingClinician(Request $request): ?User
    {
        $userId = $request->attributes->get('provider_user_id');

        return $userId ? User::with('role')->find($userId) : null;
    }

    /** How many facilities this clinician may act at; null for a platform-tier account. */
    private function facilityCount(User $user): ?int
    {
        $ids = $this->memberships->facilityIdsFor($user);

        return $ids === null ? null : count($ids);
    }
}
