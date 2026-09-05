<?php

namespace App\Services\Portal;

use App\Models\Facility;
use App\Models\ProviderAccessToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ProviderSessionService — the one place a clinician's mobile session is
 * minted, rotated and described.
 *
 * It exists because there are now TWO ways a provider token comes into being:
 * signing in (MobileProviderAuthController::login) and changing facility
 * (MobileProviderFacilityController::switch). Both must produce a token with
 * exactly the same shape, the same lifetime and the same hash split, or one of
 * them becomes the weaker door.
 *
 * ── WHY SWITCHING MINTS A NEW TOKEN ───────────────────────────────────────
 * The `provider_access_tokens` migration states the intent plainly:
 *
 *     "The facility the session is scoped to, captured at login. A clinician
 *      who moves facility gets a new session rather than a token that silently
 *      follows them."
 *
 * `rotateToFacility()` honours that literally. The presented token is REVOKED
 * (stamped `revoked_at`, never deleted — the audit trail keeps the fact that
 * the session existed) and a brand-new token is minted for the destination
 * facility. So:
 *
 *   • a bearer string always means exactly one facility, for its whole life —
 *     a token captured off the wire cannot start reading another facility's
 *     records because its holder tapped "switch" on a phone somewhere;
 *   • the old bearer stops working the instant the switch is decided, which is
 *     what makes "subsequent reads are scoped to the NEW facility" provable
 *     rather than asserted;
 *   • `provider_access_tokens` reads as a session log: one row per facility a
 *     clinician acted at, with the times they started and stopped.
 *
 * The cost is that a client which loses the response loses the session — the
 * old token is already dead. That is the safe direction (fail closed: sign in
 * again) and is called out in the endpoint's own docblock so no client tries
 * to "recover" by re-using the revoked bearer.
 */
class ProviderSessionService
{
    /** Matches the patient token's lifetime, so both halves of the app expire alike. */
    public const TOKEN_TTL_DAYS = 30;

    /**
     * Mint the bearer and store only its hashes — an indexed SHA-256 for O(1)
     * lookup plus a slow hash the middleware verifies in constant time. The raw
     * token is returned once and never stored.
     */
    public function mint(User $user, string $facilityId, ?string $deviceName = null): string
    {
        $rawToken = 'prv_' . Str::random(40);

        ProviderAccessToken::create([
            'user_id'           => $user->getKey(),
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
     * End the presented session and open a new one at another facility.
     *
     * The revoke happens FIRST. If the mint then fails, the clinician is signed
     * out and signs in again — annoying, and strictly better than the other
     * order, which on a failure would leave them holding a live token for the
     * facility they just tried to leave.
     *
     * The new token inherits the old one's `device_name`, so a rotated session
     * is still recognisable as the same phone in the token list.
     */
    public function rotateToFacility(ProviderAccessToken $current, User $user, string $facilityId): string
    {
        if ($current->revoked_at === null) {
            $current->forceFill(['revoked_at' => Carbon::now()])->save();
        }

        return $this->mint($user, $facilityId, $current->device_name);
    }

    /** Revoke one session without opening another. Kept, not deleted. */
    public function revoke(ProviderAccessToken $token): void
    {
        if ($token->revoked_at === null) {
            $token->forceFill(['revoked_at' => Carbon::now()])->save();
        }
    }

    /**
     * The identity block the app renders in its header and stores for session
     * restore. Identical from login, from /me and from a switch, so the app has
     * one parser.
     *
     * `facility_count` is additive and deliberate: it is how the phone knows to
     * show a "Switch facility" row at all, without a second round trip on every
     * launch. Clients that predate it ignore an unknown key.
     *
     * @return array<string,mixed>
     */
    public function payload(User $user, ?string $facilityId, ?int $facilityCount = null): array
    {
        return [
            'id'             => $user->getKey(),
            'name'           => $user->name,
            'role'           => $user->role?->name,
            'facility_id'    => $facilityId,
            'facility_name'  => $facilityId
                ? Facility::withoutGlobalScope('isolate_demo')->whereKey($facilityId)->value('name')
                : null,
            'facility_count' => $facilityCount,
        ];
    }
}
