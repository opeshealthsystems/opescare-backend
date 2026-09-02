<?php

namespace App\Modules\Auth\Services;

/**
 * RFC 6238 TOTP for staff/admin two-factor auth (GAP-009 scaffold).
 *
 * Pure-PHP implementation (no external dependency) compatible with Google
 * Authenticator / Microsoft Authenticator / Authy. Parameters come from
 * config/mfa.php. This service is standalone and side-effect-free — it does NOT
 * touch the login flow. Enrollment + challenge wiring is a separate, browser-
 * testable task.
 *
 * Correctness is pinned to the RFC 6238 test vector in the test suite.
 */
class TwoFactorService
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private int $digits;
    private int $period;
    private int $window;
    private string $algo;

    public function __construct()
    {
        $cfg          = config('mfa.totp');
        $this->digits = (int) ($cfg['digits'] ?? 6);
        $this->period = (int) ($cfg['period'] ?? 30);
        $this->window = (int) ($cfg['window'] ?? 1);
        $this->algo   = (string) ($cfg['algorithm'] ?? 'sha1');
    }

    /** Generate a new Base32 TOTP secret (default 160-bit). */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /** Build the otpauth:// provisioning URI for a QR code at enrollment. */
    public function provisioningUri(string $secret, string $accountName, ?string $issuer = null): string
    {
        $issuer = $issuer ?: (string) config('mfa.issuer', 'OpesCare');
        $label  = rawurlencode($issuer) . ':' . rawurlencode($accountName);

        return 'otpauth://totp/' . $label . '?' . http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => strtoupper($this->algo),
            'digits'    => $this->digits,
            'period'    => $this->period,
        ]);
    }

    /** Compute the TOTP code for a secret at a given Unix time (default now). */
    public function codeAt(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = (int) floor($timestamp / $this->period);

        return $this->hotp($secret, $counter);
    }

    /**
     * Verify a user-supplied code against the secret, allowing ±window periods
     * of clock drift. Uses constant-time comparison.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if ($code === '' || ! ctype_digit($code)) {
            return false;
        }

        $timestamp ??= time();
        $counter = (int) floor($timestamp / $this->period);

        for ($i = -$this->window; $i <= $this->window; $i++) {
            if (hash_equals($this->hotp($secret, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** Generate single-use recovery codes (e.g. "ABCD-EFGH"). */
    public function generateRecoveryCodes(?int $count = null): array
    {
        $count = $count ?? (int) config('mfa.recovery_code_count', 8);
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->randomGroup(4) . '-' . $this->randomGroup(4);
        }

        return $codes;
    }

    // ── Internals ─────────────────────────────────────────────────

    /** RFC 4226 HMAC-based one-time password for a counter. */
    private function hotp(string $base32Secret, int $counter): string
    {
        $key    = $this->base32Decode($base32Secret);
        $binCtr = pack('N*', 0) . pack('N*', $counter); // 8-byte big-endian counter
        $hash   = hash_hmac($this->algo, $binCtr, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** $this->digits);

        return str_pad((string) $otp, $this->digits, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::BASE32[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    private function randomGroup(int $len): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}
