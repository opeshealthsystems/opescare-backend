<?php

namespace App\Services\FrontDesk;

use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;

/**
 * Who is acting on the appointment book, and for which facility.
 *
 * Built ONLY from what middleware resolved:
 *
 *   • on the clinician API, the attributes AuthenticateMobileProvider stamps
 *     on the request from the bearer token (`provider_user_id`,
 *     `provider_facility_id`) — fromRequest();
 *   • on the web desk, the signed-in user and the session's ACTIVE facility,
 *     which RequireFacilityContext resolved from the user's own postings
 *     (PortalContextService::facilityId()) — fromPortal().
 *
 * Nothing here is ever read from the body, the query string or a header —
 * a caller who could name their own facility could read and edit every
 * appointment book in the country.
 */
final class DeskActor
{
    public function __construct(
        public readonly string $userId,
        public readonly string $facilityId,
        public readonly ?string $ip = null,
        /** Written to audit_events.source_system and the history metadata. */
        public readonly string $channel = 'mobile_desk',
    ) {
    }

    /** Null when the token names no facility or no user — callers fail closed on that. */
    public static function fromRequest(Request $request, string $channel = 'mobile_desk'): ?self
    {
        $userId     = $request->attributes->get('provider_user_id');
        $facilityId = $request->attributes->get('provider_facility_id');

        if (! is_string($userId) || $userId === '' || ! is_string($facilityId) || $facilityId === '') {
            return null;
        }

        return new self($userId, $facilityId, $request->ip(), $channel);
    }

    /**
     * The web desk's actor: Auth user + the session's active facility, as
     * PortalContextService resolves them. Null when either is missing — a
     * portal request with no facility context books nothing.
     */
    public static function fromPortal(Request $request, PortalContextService $ctx, string $channel = 'portal'): ?self
    {
        $userId     = $ctx->actorId();
        $facilityId = $ctx->facilityId();

        if (! is_string($userId) || $userId === '' || ! is_string($facilityId) || $facilityId === '') {
            return null;
        }

        return new self($userId, $facilityId, $request->ip(), $channel);
    }
}
