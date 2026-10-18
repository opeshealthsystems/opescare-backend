<?php

namespace App\Services\Auth;

use App\Models\HealthIdQrToken;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Sign-in by Health ID, and the OPESCARD QR that fills it in.
 *
 * A Health ID is an IDENTIFIER here, never a credential: it only names which
 * account the password is checked against. The QR on the card is the same —
 * it fills the identifier box and nothing else; the password is always asked.
 *
 * Both lookups run under the caller's demo-isolation scope (Patient and User
 * both use IsDemoRecord), which is exactly the partition email sign-in sees,
 * so a Health ID never opens an account that its email would not.
 */
class HealthIdLogin
{
    /** QR token types printed on a physical / downloadable identity card. */
    private const CARD_TOKEN_TYPES = ['card_qr', 'static_identity_qr'];

    /**
     * Canonical CM-HID-XXXX-XXXX-XXXX from whatever was typed: case, spaces
     * and dashes are forgiven, the shape is not. Null when it is not a Health ID.
     */
    public static function normalise(string $raw): ?string
    {
        $compact = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        if (! preg_match('/^([A-Z]{2})HID([A-Z0-9]{4})([A-Z0-9]{4})([A-Z0-9]{4})$/', $compact, $m)) {
            return null;
        }

        return "{$m[1]}-HID-{$m[2]}-{$m[3]}-{$m[4]}";
    }

    /**
     * The account a Health ID signs into — following MPI merges, so the card
     * in the patient's pocket still works after their records were merged.
     * Null unless exactly one account is linked (never guess between two).
     */
    public function userForHealthId(string $healthId): ?User
    {
        $patient = Patient::resolveHealthId($healthId);

        if ($patient === null) {
            return null;
        }

        $users = User::where('patient_id', $patient->id)->limit(2)->get();

        return $users->count() === 1 ? $users->first() : null;
    }

    /**
     * The Health ID a scanned OPESCARD QR belongs to, or null.
     *
     * The card QR encodes a /verify/qr/qrx_<lookup>_<secret> URL — an opaque
     * bearer token, not the Health ID — so it cannot be decoded in the browser.
     * The server checks it (active, unexpired, card type, secret hash) and
     * returns only the Health ID, which is printed on the same card anyway.
     * Read-only: unlike QrTokenService::verifyToken() it does not touch
     * last_used_at, so an anonymous scan leaves no write behind.
     */
    public function healthIdFromCardPayload(string $payload): ?string
    {
        if (! preg_match('#(?:^|/verify/qr/)(qrx_([A-Za-z0-9]{1,64})_([A-Za-z0-9]{1,128}))(?:[/?\#].*)?$#', trim($payload), $m)) {
            return null;
        }

        [, , $lookupId, $secret] = $m;

        $token = HealthIdQrToken::where('lookup_id', $lookupId)
            ->where('status', 'active')
            ->whereIn('token_type', self::CARD_TOKEN_TYPES)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($token === null || ! Hash::check($secret, $token->token_hash)) {
            return null;
        }

        return Patient::resolveId((string) $token->patient_id)?->health_id;
    }
}
