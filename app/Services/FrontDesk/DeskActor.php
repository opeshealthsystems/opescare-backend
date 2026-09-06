<?php

namespace App\Services\FrontDesk;

use Illuminate\Http\Request;

/**
 * Who is acting on the appointment book, and for which facility.
 *
 * Built ONLY from the attributes AuthenticateMobileProvider stamps on the
 * request from the bearer token (`provider_user_id`, `provider_facility_id`).
 * Nothing here is ever read from the body, the query string, a header or a
 * session — a caller who could name their own facility could read and edit
 * every appointment book in the country.
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
}
