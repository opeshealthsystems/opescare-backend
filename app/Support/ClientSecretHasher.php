<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * One place that knows how an integration client's secret is hashed and how it
 * is verified.
 *
 * WHY THIS EXISTS
 * ---------------
 * The column is called `client_secret_argon` and the migration that added it
 * cites ISO 27001 A.10.1 — a key-derivation function with a work factor. What
 * actually went into it was bcrypt. Every writer used `Hash::make()`, which
 * uses the DEFAULT hash driver, and this application publishes no
 * config/hashing.php, so the default is bcrypt. The column name has been a
 * claim rather than a fact since June.
 *
 * Storing a real Argon2id hash is not a one-word change, because it breaks the
 * readers. Laravel's BcryptHasher defaults `verify` to TRUE, so
 * `Hash::check($secret, '$argon2id$...')` does not return false — it THROWS
 * ("This password does not use the Bcrypt algorithm"), which in
 * AuthController and VerifyIntegrationClient would surface as a 500 on every
 * token request. The same is true in reverse for the Argon driver.
 *
 * So verification dispatches on the hash prefix, which is exactly what the
 * prefix is for, and both formats keep working side by side: existing bcrypt
 * rows authenticate unchanged, new rows are genuinely Argon2id, and neither
 * verifier has to care which it is holding.
 */
class ClientSecretHasher
{
    /** Hash a client secret. Argon2id, named explicitly rather than defaulted. */
    public static function make(string $secret): string
    {
        return Hash::driver('argon2id')->make($secret);
    }

    /** Is this an Argon2 hash (as opposed to a legacy bcrypt one)? */
    public static function isArgon(string $hash): bool
    {
        return str_starts_with($hash, '$argon2');
    }

    /**
     * Verify a secret against a stored hash of either format.
     *
     * Never throws for a well-formed hash of the other algorithm — that is the
     * whole point — and returns false for anything unrecognised rather than
     * letting an exception become a 500 on the token endpoint.
     */
    public static function check(string $secret, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return false;
        }

        try {
            return self::isArgon($hash)
                ? Hash::driver('argon2id')->check($secret, $hash)
                : Hash::driver('bcrypt')->check($secret, $hash);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Does this hash want rewriting?
     *
     * An Argon2id hash NEVER does. Asking the bcrypt driver would get "yes"
     * for every Argon2id hash on the platform, and the caller would dutifully
     * rewrite it as bcrypt — silently downgrading the strongest secrets we
     * hold, one successful authentication at a time.
     */
    public static function needsRehash(string $hash): bool
    {
        return ! self::isArgon($hash);
    }
}
