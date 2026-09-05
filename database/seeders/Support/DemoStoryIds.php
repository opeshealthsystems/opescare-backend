<?php

namespace Database\Seeders\Support;

use Ramsey\Uuid\Uuid;

/**
 * Stable row ids for the demo patient story, for ANY demo patient.
 *
 * The story seeders were written for one patient (DEMO_DATA_BRIEF.md) and pin
 * every row to a reserved `00000000-0000-0000-0x0x-…` UUID so a re-run is a
 * no-op. Telling the same story for a second demo patient needs ids that are
 * just as stable, unique per patient, and derivable without a lookup table —
 * so for anyone but the canonical patient the reserved id is folded with the
 * patient id into a name-based (v5) UUID. Same input, same id, forever.
 */
final class DemoStoryIds
{
    /** The patient the story was first written for — Demo Patient One. */
    public const CANONICAL_PATIENT = '00000000-0000-0000-0000-300000000001';

    /** A row id owned by the story: reserved id for the canonical patient, derived otherwise. */
    public static function uid(string $patientId, string $reservedId): string
    {
        if ($patientId === self::CANONICAL_PATIENT) {
            return $reservedId;
        }

        return (string) Uuid::uuid5(Uuid::NAMESPACE_OID, $patientId . ':' . $reservedId);
    }

    /**
     * A row id for something that has NO reserved id — a key of the story
     * seeder's own (a thread, a consent, a message). Always derived, for the
     * canonical patient too, so it is always a valid UUID.
     */
    public static function derived(string $patientId, string $key): string
    {
        return (string) Uuid::uuid5(Uuid::NAMESPACE_OID, $patientId . ':' . $key);
    }

    /** The same, for string seed keys (`demo_seed_key` columns). */
    public static function seedKey(string $patientId, string $key): string
    {
        if ($patientId === self::CANONICAL_PATIENT) {
            return $key;
        }

        return $key . ':' . substr($patientId, 0, 8);
    }
}
