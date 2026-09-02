<?php

namespace App\Services\Identity;

use Illuminate\Support\Str;
use App\Models\HealthIdQrToken;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class QrTokenService
{
    /**
     * Token format: qrx_{lookupId}_{secret}
     *
     * lookupId — 12 random alphanumeric chars stored plaintext (indexed, O(1) lookup).
     * secret   — 32 random alphanumeric chars; only its Hash::make() is stored.
     *
     * This prevents full table scans during verification: we find the row by
     * lookup_id (unique index), then verify the secret half with Hash::check().
     *
     * @return array{raw_token: string, secure_url: string, model: HealthIdQrToken}
     */
    public function generateToken(
        string  $patientId,
        string  $type = 'static_identity_qr',
        ?int    $expiresInMinutes = null
    ): array {
        $lookupId  = Str::random(12);
        $secret    = Str::random(32);
        $rawToken  = "qrx_{$lookupId}_{$secret}";

        $qrModel = HealthIdQrToken::create([
            'patient_id' => $patientId,
            'lookup_id'  => $lookupId,
            'token_hash' => Hash::make($secret),
            'token_type' => $type,
            'status'     => 'active',
            'expires_at' => $expiresInMinutes ? Carbon::now()->addMinutes($expiresInMinutes) : null,
        ]);

        return [
            'raw_token'  => $rawToken,
            'secure_url' => route('verify.qr', ['token' => $rawToken]),
            'model'      => $qrModel,
        ];
    }

    /**
     * Verify a raw QR token.
     *
     * Splits the token, looks up by lookup_id (O(1)), then verifies the secret
     * half with Hash::check(). Returns null for expired, revoked, or invalid tokens.
     */
    public function verifyToken(string $rawToken): ?HealthIdQrToken
    {
        $parts = explode('_', $rawToken, 3); // ['qrx', lookupId, secret]

        if (count($parts) !== 3 || $parts[0] !== 'qrx') {
            return null;
        }

        [, $lookupId, $secret] = $parts;

        $token = HealthIdQrToken::where('lookup_id', $lookupId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$token || !Hash::check($secret, $token->token_hash)) {
            return null;
        }

        $token->update(['last_used_at' => now()]);

        return $token;
    }

    /**
     * Revoke a QR token so it can no longer be verified.
     */
    public function revokeToken(HealthIdQrToken $token): void
    {
        $token->update([
            'status'     => 'revoked',
            'revoked_at' => now(),
        ]);
    }
}
