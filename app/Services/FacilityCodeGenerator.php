<?php

namespace App\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FacilityCodeGenerator
 *
 * Generates human-readable facility codes in the format:
 *
 *   OP-[REGION_CODE]-FID-[XXXX]
 *
 * Examples:
 *   OP-LT-FID-1825   (Littoral)
 *   OP-NW-FID-0410   (North West)
 *   OP-SW-FID-4507   (South West)
 *   OP-CE-FID-4013   (Centre)
 *   OP-OU-FID-3658   (West / Ouest)
 *
 * The 4-digit numeric suffix is zero-padded and randomly generated.
 *
 * ## Uniqueness guarantee — three layers
 *
 * Layer 1 — DB check loop (normal path):
 *   Each candidate is checked against care_facilities.facility_code before
 *   being returned. Up to MAX_RETRIES attempts are made. Handles all cases
 *   where the code space is not exhausted and no concurrent writers exist.
 *
 * Layer 2 — PostgreSQL advisory lock (race-condition guard):
 *   The check+reserve is wrapped in a transaction with a per-candidate
 *   advisory lock so two simultaneous requests cannot both claim the same
 *   code. pg_try_advisory_xact_lock() returns false immediately if another
 *   transaction holds the lock — no blocking, just skip and retry.
 *
 * Layer 3 — DB UNIQUE constraint (hard stop):
 *   care_facilities.facility_code has a database-level UNIQUE index.
 *   Even if layers 1 and 2 are somehow bypassed, the DB will reject the
 *   duplicate insert with UniqueConstraintViolationException, which
 *   CareFacility::boot() catches and retries automatically.
 *
 * NOTE: This code is a display/human-readable identifier only.
 *       The system UUID (care_facilities.id) remains the internal PK used in
 *       all foreign-key relationships. Never replace UUIDs with facility_code.
 */
class FacilityCodeGenerator
{
    private const MAX_RETRIES = 20;

    /**
     * Cameroon regions → 2-letter OpesCare region code.
     * Names stored in care_facilities.region come from facility_registry.region
     * which follows MINSANTE / BUCREP naming conventions (mixed FR/EN).
     */
    public const REGION_MAP = [
        // ── English names ────────────────────────────────────────────────────
        'adamawa'    => 'AD',
        'centre'     => 'CE',
        'center'     => 'CE',
        'east'       => 'ES',
        'far north'  => 'EN',
        'far-north'  => 'EN',
        'farnorth'   => 'EN',
        'littoral'   => 'LT',
        'north'      => 'NO',
        'north west' => 'NW',
        'north-west' => 'NW',
        'northwest'  => 'NW',
        'south'      => 'SU',
        'south west' => 'SW',
        'south-west' => 'SW',
        'southwest'  => 'SW',
        'west'       => 'OU',

        // ── French names (MINSANTE standard) ─────────────────────────────────
        'adamaoua'       => 'AD',
        'centre'         => 'CE',
        'est'            => 'ES',
        'extrême-nord'   => 'EN',
        'extreme-nord'   => 'EN',
        'extreme nord'   => 'EN',
        'littoral'       => 'LT',
        'nord'           => 'NO',
        'nord-ouest'     => 'NW',
        'nord ouest'     => 'NW',
        'sud'            => 'SU',
        'sud-ouest'      => 'SW',
        'sud ouest'      => 'SW',
        'ouest'          => 'OU',

        // ── Abbreviations (already coded) ────────────────────────────────────
        'ad' => 'AD',
        'ce' => 'CE',
        'es' => 'ES',
        'en' => 'EN',
        'lt' => 'LT',
        'no' => 'NO',
        'nw' => 'NW',
        'su' => 'SU',
        'sw' => 'SW',
        'ou' => 'OU',
    ];

    /**
     * Resolve a region string to its 2-letter code.
     * Returns 'XX' for unknown regions so generation does not fail.
     */
    public static function regionCode(string $region): string
    {
        $key = strtolower(trim($region));
        return self::REGION_MAP[$key] ?? 'XX';
    }

    /**
     * Generate a unique facility code for the given region.
     *
     * Uses a PostgreSQL advisory lock inside a transaction to make the
     * availability check + reservation atomic. If the lock is already held
     * by a concurrent request the attempt is skipped and a new candidate is
     * drawn — no blocking, guaranteed progress.
     *
     * @param  string $region  Region name or code (e.g. "Littoral", "LT")
     * @return string          e.g. "OP-LT-FID-1825"
     * @throws \RuntimeException if a unique code cannot be generated after MAX_RETRIES
     */
    public static function generate(string $region): string
    {
        $regionCode = self::regionCode($region);

        for ($i = 0; $i < self::MAX_RETRIES; $i++) {
            $suffix    = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $candidate = "OP-{$regionCode}-FID-{$suffix}";

            // ── Layer 2: advisory-lock atomic check ───────────────────────────
            // Each candidate maps to a deterministic 32-bit lock key.
            // pg_try_advisory_xact_lock() returns TRUE  → we hold the lock for
            // this transaction and can safely check+return the candidate.
            //              returns FALSE → another transaction has this candidate
            //              locked right now; skip and try a new suffix.
            $reserved = DB::transaction(function () use ($candidate) {
                $lockKey = self::advisoryKey($candidate);

                $locked = DB::selectOne(
                    'SELECT pg_try_advisory_xact_lock(?) AS acquired',
                    [$lockKey]
                );

                // Lock not acquired — another request is processing this exact code
                if (! $locked?->acquired) {
                    return false;
                }

                // ── Layer 1: DB existence check (inside the lock) ─────────────
                $exists = DB::table('care_facilities')
                    ->where('facility_code', $candidate)
                    ->exists();

                // Already taken — release lock and skip
                if ($exists) {
                    return false;
                }

                // Code is free and we hold the advisory lock.
                // The calling code will write facility_code = $candidate.
                // The lock is held until the outer transaction commits.
                return true;
            });

            if ($reserved) {
                return $candidate;
            }

            // Either the code was taken or another request held the lock —
            // loop and draw a new random suffix.
        }

        Log::error('FacilityCodeGenerator: exhausted retries', [
            'region'      => $region,
            'region_code' => $regionCode,
            'max_retries' => self::MAX_RETRIES,
        ]);

        throw new \RuntimeException(
            "FacilityCodeGenerator: could not generate a unique code for region [{$region}] "
            . 'after ' . self::MAX_RETRIES . ' attempts. '
            . 'The region code space may be heavily saturated — consider expanding the suffix range.'
        );
    }

    /**
     * Derive a deterministic 32-bit advisory lock key from a candidate code.
     *
     * crc32() can return negative values on 32-bit PHP; abs() normalises it.
     * The key space (2^31 ≈ 2 billion) is large enough that accidental
     * collisions between unrelated candidate strings are negligible.
     */
    private static function advisoryKey(string $candidate): int
    {
        return abs(crc32($candidate));
    }
}
