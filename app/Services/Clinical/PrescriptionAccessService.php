<?php

namespace App\Services\Clinical;

use App\Models\Prescription;
use App\Models\PrescriptionAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Mint and verify the token behind a presentable prescription.
 *
 * The shape is deliberately the same as QrTokenService's, because the Health ID
 * QR already proved it out and a second, subtly different scheme is how the two
 * halves of a scan drift apart: the raw token is `rx_<lookupId>_<secret>`, only
 * the secret's hash is stored, and verification is an O(1) lookup on lookup_id
 * followed by a constant-time hash check.
 *
 * What a holder of the token may see is decided in the verify controller, not
 * here. This class only answers "is this token good, and for which prescription".
 */
class PrescriptionAccessService
{
    /** A printed script stays useful for a while; it is not a 15-minute code. */
    public const DEFAULT_TTL_DAYS = 30;

    /**
     * Issue a token for this prescription.
     *
     * @return array{raw_token: string, verify_url: string, model: PrescriptionAccessToken}
     */
    public function issue(Prescription $prescription, ?string $actorId = null, ?int $ttlDays = null): array
    {
        $lookupId = Str::random(16);
        $secret   = Str::random(32);
        $raw      = "rx_{$lookupId}_{$secret}";

        $model = PrescriptionAccessToken::create([
            'prescription_id' => $prescription->id,
            'lookup_id'       => $lookupId,
            'token_hash'      => Hash::make($secret),
            'status'          => 'active',
            'expires_at'      => Carbon::now()->addDays($ttlDays ?? self::DEFAULT_TTL_DAYS),
            'created_by'      => $actorId,
        ]);

        return [
            'raw_token'  => $raw,
            'verify_url' => route('verify.rx', ['token' => $raw]),
            'model'      => $model,
        ];
    }

    /**
     * The active token for a prescription, minting one if there is none.
     *
     * Reused rather than re-minted so a script that has already been printed and
     * handed to a patient keeps working — a fresh token every time the screen
     * opened would silently invalidate the paper in their hand.
     */
    public function currentOrIssue(Prescription $prescription, ?string $actorId = null): array
    {
        $existing = PrescriptionAccessToken::where('prescription_id', $prescription->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest('created_at')
            ->first();

        // The secret is unrecoverable by design, so an existing row can only be
        // re-presented as a URL if the caller already has the raw token. It does
        // not, so a QR is always drawn from a freshly issued token; the old one
        // is left active so previously printed copies still verify.
        return $this->issue($prescription, $actorId);
    }

    /**
     * Verify a raw token. Returns null for malformed, unknown, revoked or
     * expired tokens — the caller must not distinguish these to a scanner.
     */
    public function verify(string $rawToken): ?PrescriptionAccessToken
    {
        $parts = explode('_', $rawToken, 3);

        if (count($parts) !== 3 || $parts[0] !== 'rx') {
            return null;
        }

        [, $lookupId, $secret] = $parts;

        $token = PrescriptionAccessToken::where('lookup_id', $lookupId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();

        if (! $token || ! Hash::check($secret, $token->token_hash)) {
            return null;
        }

        return $token;
    }

    /** Record that someone opened it. The patient is entitled to see this. */
    public function recordUse(PrescriptionAccessToken $token): void
    {
        $token->forceFill([
            'last_used_at' => Carbon::now(),
            'use_count'    => $token->use_count + 1,
        ])->save();
    }

    /** The patient (or the issuing facility) withdraws a presented script. */
    public function revoke(PrescriptionAccessToken $token): void
    {
        $token->forceFill([
            'status'     => 'revoked',
            'revoked_at' => Carbon::now(),
        ])->save();
    }

    /** Revoke every live token for a prescription — "stop sharing this". */
    public function revokeAllFor(Prescription $prescription): int
    {
        return PrescriptionAccessToken::where('prescription_id', $prescription->id)
            ->where('status', 'active')
            ->update([
                'status'     => 'revoked',
                'revoked_at' => Carbon::now(),
            ]);
    }
}
