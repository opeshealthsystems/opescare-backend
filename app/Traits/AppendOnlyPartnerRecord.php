<?php

namespace App\Traits;

/**
 * Clinical immutability for the partner-ingested FHIR tables.
 *
 * A clinical event is never hard-overwritten here. A partner that wants to
 * correct itself POSTs a REPLACEMENT resource carrying `supersedes` — a new
 * row is written, and the row it replaces is left standing with its status
 * moved to 'amended' or 'entered-in-error'.
 *
 * The guard therefore permits exactly one mutation on a stored row: `status`.
 * Everything else — whose it is, which facility asserted it, what the number
 * was, when it was taken — is frozen at write time, and deletion is blocked
 * outright.
 *
 * The guard lives in the model rather than in the ingest service so that no
 * caller can route around it: a console command, a future admin screen, a
 * seeder or a queued job all hit the same wall.
 *
 * NOT AFFECTED (deliberately): the Postgres CASCADE from `patients`. When a
 * retention period ends the patient row is deleted and the database removes
 * these rows without ever constructing a model. That is the one sanctioned
 * erasure and it stays legal.
 */
trait AppendOnlyPartnerRecord
{
    /** The only column a stored partner record may ever change. */
    public const AMENDABLE_ATTRIBUTES = ['status'];

    public static function bootAppendOnlyPartnerRecord(): void
    {
        static::updating(function ($model): void {
            $touched = array_keys($model->getDirty());
            $illegal = array_diff($touched, array_merge(self::AMENDABLE_ATTRIBUTES, ['updated_at']));

            if ($illegal !== []) {
                throw new \RuntimeException(
                    'Partner-ingested clinical records are append-only. Refused an in-place update of ['
                    . implode(', ', $illegal) . '] on ' . static::class . ' ' . $model->getKey()
                    . '. Post a replacement resource that supersedes it instead.'
                );
            }
        });

        static::deleting(function ($model): void {
            throw new \RuntimeException(
                'Partner-ingested clinical records are never deleted. Refused a delete of '
                . static::class . ' ' . $model->getKey()
                . '. Mark it entered-in-error with a superseding resource instead.'
            );
        });
    }
}
