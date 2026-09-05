<?php

namespace App\Modules\MasterPatientIndex\Services;

use App\Enums\AuditEventType;
use App\Enums\IdentityStatus;
use App\Models\AuditEvent;
use App\Models\HealthIdAlias;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientMerge;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * PatientMergeService — two Health IDs become one record, reversibly.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT A MERGE IS HERE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Every row that referenced the retired patient now references the survivor.
 * The retired row is NOT deleted: it is marked `merged`, pointed at its
 * survivor, and its Health ID becomes an alias that still resolves — a
 * patient who scans the card they were issued last year must still open their
 * record. Nothing about a clinical event changes except which identity it is
 * attached to; the prescription is the same prescription.
 *
 * Both sides of the fork previously had a "merge" that wrote an alias row and
 * flipped a status. The prescriptions stayed on the loser. This one moves them,
 * and writes down exactly what it moved so it can be undone.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE FOREIGN KEYS ARE DISCOVERED, NOT LISTED
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `patients.id` parents 101 foreign-key columns today. A hand-written list
 * would be wrong the first time a migration added a table, and wrong silently
 * — the new table's rows would simply stay behind. The list is read from
 * `information_schema` at run time, so a merge is complete by construction.
 *
 * Four tables are excluded on purpose: they record identity DECISIONS rather
 * than clinical ownership, and re-pointing them would rewrite the history
 * this service exists to keep (`patient_merges`, `mpi_candidates`,
 * `identity_merge_cases`, `patient_merge_aliases`), plus the retirement
 * pointer itself on `patients`.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ONE-PER-PATIENT ROWS ARE KEPT, NOT CLOBBERED
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Six tables carry a UNIQUE constraint that includes `patient_id`
 * (`mobile_app_settings`, `patient_identity_profiles`, `wallets`, …). When
 * both records hold one, moving the retired row would violate it. Rather than
 * delete one or guess which is right, the survivor keeps its own and the
 * retired row stays where it is, listed under `kept` in the manifest. The
 * operator can see it; nothing was lost.
 *
 * Survivorship for demographics follows the same rule: a field on the survivor
 * is filled from the retired record only when the survivor's is NULL. A
 * non-null value is never overwritten, even by a "better" one — that is a
 * judgement, and judgements are made on the desk, not in here.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS BYPASSES THE MODELS' IMMUTABILITY GUARDS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Prescription, LabOrder and LabResult refuse to change `patient_id` through
 * Eloquent, and rightly: a clinical event's subject must not drift. This is
 * the one sanctioned exception — correcting WHICH identity a record was filed
 * under, with the previous value recorded per row in the manifest, under an
 * operator's name, reversibly. It is done with query-builder updates for that
 * reason, and for that reason only.
 */
final class PatientMergeService
{
    /** Identity-history tables — never re-pointed. */
    private const HISTORY_TABLES = [
        'patient_merges',
        'mpi_candidates',
        'identity_merge_cases',
        'patient_merge_aliases',
    ];

    /** The retirement pointer, not an ownership reference. */
    private const EXCLUDED_COLUMNS = [
        'patients.merged_into_patient_id',
    ];

    /**
     * Nullable demographics the survivor may inherit when its own is NULL.
     * Encrypted fields go through the model's mutators, which re-encrypt.
     *
     * @var list<string>
     */
    private const SURVIVORSHIP_FIELDS = [
        'middle_name',
        'date_of_birth',
        'sex',
        'phone_number',
        'email',
        'address',
        'blood_group',
        'national_id_number',
        'national_id_type',
        'cnamgs_id',
        'emergency_contact',
        'country_code',
    ];

    /** @var list<array{table: string, column: string}>|null */
    private ?array $foreignKeys = null;

    /** @var array<string, true>|null  "table.column" that sits in a UNIQUE constraint */
    private ?array $uniqueColumns = null;

    // ══════════════════════════════════════════════════════════════════════
    // Merge
    // ══════════════════════════════════════════════════════════════════════

    public function merge(
        Patient $survivor,
        Patient $retired,
        ?string $actorId,
        ?string $reason = null,
        ?MpiCandidate $candidate = null,
    ): PatientMerge {
        if ((string) $survivor->getKey() === (string) $retired->getKey()) {
            throw new InvalidArgumentException('MPI_MERGE_SAME_PATIENT');
        }

        return DB::transaction(function () use ($survivor, $retired, $actorId, $reason, $candidate) {
            // Re-read both under a row lock, scope-blind: the caller may hold a
            // stale copy, and two operators merging the same pair at once must
            // serialise here rather than both succeed.
            $survivor = $this->lockedPatient($survivor->getKey());
            $retired  = $this->lockedPatient($retired->getKey());

            if ($survivor->isMerged()) {
                throw new RuntimeException('MPI_SURVIVOR_ALREADY_MERGED');
            }
            if ($retired->isMerged()) {
                throw new RuntimeException('MPI_PATIENT_ALREADY_MERGED');
            }

            // 1. Every row the retired record owns now belongs to the survivor.
            $manifest = $this->repoint($retired->getKey(), $survivor->getKey());

            // 2. Fill the survivor's blanks — never its values.
            $survivorship = $this->applySurvivorship($survivor, $retired);

            // 3. The retired Health ID keeps resolving.
            HealthIdAlias::create([
                'patient_id'  => $survivor->getKey(),
                'alias_type'  => 'merged_health_id',
                'alias_value' => $retired->health_id,
                'status'      => 'active',
            ]);

            DB::table('patient_merge_aliases')->insert([
                'id'                   => (string) Str::uuid(),
                'alias_health_id'      => $retired->health_id,
                'canonical_patient_id' => $survivor->getKey(),
                'retired_patient_id'   => $retired->getKey(),
                'merged_by_user_id'    => $actorId,
                'merge_reason'         => $reason,
                'merge_direction'      => 'into_canonical',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 4. Retire, don't delete.
            $previousStatus = $this->statusValue($retired);
            $retired->forceFill([
                'identity_status'        => IdentityStatus::Merged,
                'merged_into_patient_id' => $survivor->getKey(),
                'merged_at'              => now(),
            ])->save();

            // 5. The decision, if it came from the review queue.
            if ($candidate) {
                $candidate->forceFill([
                    'status'              => 'merged',
                    'reviewed_by'         => $actorId,
                    'reviewed_at'         => now(),
                    'survivor_patient_id' => $survivor->getKey(),
                    'review_reason'       => $reason,
                ])->save();
            }

            // 6. The record that makes this undoable.
            $merge = PatientMerge::create([
                'candidate_id'                     => $candidate?->getKey(),
                'survivor_patient_id'              => $survivor->getKey(),
                'retired_patient_id'               => $retired->getKey(),
                'merged_by'                        => $actorId,
                'reason'                           => $reason,
                'manifest'                         => $manifest,
                'survivorship'                     => $survivorship,
                'retired_previous_identity_status' => $previousStatus,
                'status'                           => PatientMerge::STATUS_MERGED,
            ]);

            $this->audit(
                AuditEventType::HealthIdMerged->value,
                $actorId,
                $survivor,
                $reason,
                before: ['retired_health_id' => $retired->health_id, 'retired_patient_id' => $retired->getKey()],
                after:  ['merge_id' => $merge->getKey(), 'rows_moved' => $merge->movedRowCount(), 'rows_kept' => $merge->keptRowCount(), 'fields_filled' => array_keys($survivorship)],
            );

            Log::info(AuditEventType::HealthIdMerged->value, [
                'merge_id'    => $merge->getKey(),
                'survivor'    => $survivor->health_id,
                'retired'     => $retired->health_id,
                'rows_moved'  => $merge->movedRowCount(),
                'rows_kept'   => $merge->keptRowCount(),
                'merged_by'   => $actorId,
            ]);

            return $merge;
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // Unmerge
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Put back exactly what the merge moved. Rows the survivor acquired
     * afterwards are not in the manifest and are not touched.
     */
    public function unmerge(PatientMerge $merge, ?string $actorId, ?string $reason = null): PatientMerge
    {
        if (! $merge->isMerged()) {
            throw new RuntimeException('MPI_MERGE_NOT_ACTIVE: status is ' . $merge->status);
        }

        return DB::transaction(function () use ($merge, $actorId, $reason) {
            $survivor = $this->lockedPatient($merge->survivor_patient_id);
            $retired  = $this->lockedPatient($merge->retired_patient_id);

            // 1. Rows go home. Only the ids we moved, and only if they are
            //    still on the survivor (an operator may have moved one since).
            foreach ($merge->manifest as $entry) {
                foreach (array_chunk($entry['moved'] ?? [], 500) as $ids) {
                    DB::table($entry['table'])
                        ->whereIn('id', $ids)
                        ->where($entry['column'], $survivor->getKey())
                        ->update([$entry['column'] => $retired->getKey()]);
                }
            }

            // 2. Fields the survivor only had because of the merge go back to NULL.
            foreach (array_keys($merge->survivorship ?? []) as $field) {
                $survivor->{$field} = null;
            }
            $survivor->save();

            // 3. The alias stops resolving — kept as history, not deleted.
            HealthIdAlias::where('patient_id', $survivor->getKey())
                ->where('alias_type', 'merged_health_id')
                ->where('alias_value', $retired->health_id)
                ->update(['status' => 'inactive']);

            // 4. The retired record is a patient again.
            $retired->forceFill([
                'identity_status'        => IdentityStatus::from($merge->retired_previous_identity_status),
                'merged_into_patient_id' => null,
                'merged_at'              => null,
            ])->save();

            // 5. The candidate returns to the queue: a human said the merge
            //    was wrong, and a human should now say what the pair is.
            if ($merge->candidate_id) {
                MpiCandidate::whereKey($merge->candidate_id)->update([
                    'status'              => 'pending_review',
                    'survivor_patient_id' => null,
                    'review_reason'       => 'Unmerged: ' . ($reason ?? '(no reason given)'),
                ]);
            }

            $merge->forceFill([
                'status'         => PatientMerge::STATUS_UNMERGED,
                'unmerged_by'    => $actorId,
                'unmerged_at'    => now(),
                'unmerge_reason' => $reason,
            ])->save();

            $this->audit(
                'health_id_unmerged',
                $actorId,
                $survivor,
                $reason,
                before: ['merge_id' => $merge->getKey()],
                after:  ['restored_health_id' => $retired->health_id, 'rows_restored' => $merge->movedRowCount()],
            );

            Log::info('health_id_unmerged', [
                'merge_id'    => $merge->getKey(),
                'survivor'    => $survivor->health_id,
                'restored'    => $retired->health_id,
                'unmerged_by' => $actorId,
            ]);

            return $merge;
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // The parts that touch the schema
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Every column in the database that references patients.id, excluding
     * identity history and the retirement pointer. Read once per process.
     *
     * @return list<array{table: string, column: string}>
     */
    public function foreignKeysToPatients(): array
    {
        if ($this->foreignKeys !== null) {
            return $this->foreignKeys;
        }

        $rows = DB::select("
            SELECT DISTINCT kcu.table_name, kcu.column_name
              FROM information_schema.table_constraints tc
              JOIN information_schema.key_column_usage kcu
                ON kcu.constraint_name = tc.constraint_name
               AND kcu.table_schema    = tc.table_schema
              JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
               AND ccu.table_schema    = tc.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema    = current_schema()
               AND ccu.table_name     = 'patients'
               AND ccu.column_name    = 'id'
             ORDER BY kcu.table_name, kcu.column_name");

        $keys = [];
        foreach ($rows as $r) {
            if (in_array($r->table_name, self::HISTORY_TABLES, true)) {
                continue;
            }
            if (in_array($r->table_name . '.' . $r->column_name, self::EXCLUDED_COLUMNS, true)) {
                continue;
            }
            $keys[] = ['table' => $r->table_name, 'column' => $r->column_name];
        }

        return $this->foreignKeys = $keys;
    }

    /**
     * "table.column" pairs where the FK column is part of a UNIQUE constraint,
     * i.e. where moving a row may collide with one the survivor already has.
     *
     * @return array<string, true>
     */
    private function uniqueColumns(): array
    {
        if ($this->uniqueColumns !== null) {
            return $this->uniqueColumns;
        }

        $rows = DB::select("
            SELECT DISTINCT kcu.table_name, kcu.column_name
              FROM information_schema.table_constraints tc
              JOIN information_schema.key_column_usage kcu
                ON kcu.constraint_name = tc.constraint_name
               AND kcu.table_schema    = tc.table_schema
             WHERE tc.constraint_type = 'UNIQUE'
               AND tc.table_schema    = current_schema()");

        $set = [];
        foreach ($rows as $r) {
            $set[$r->table_name . '.' . $r->column_name] = true;
        }

        return $this->uniqueColumns = $set;
    }

    /**
     * Move every referencing row from $from to $to, recording each id.
     *
     * @return list<array{table: string, column: string, moved: list<string>, kept: list<string>}>
     */
    private function repoint(string $from, string $to): array
    {
        $manifest = [];
        $unique   = $this->uniqueColumns();

        foreach ($this->foreignKeysToPatients() as ['table' => $table, 'column' => $column]) {
            $ids = DB::table($table)->where($column, $from)->pluck('id')->map(fn ($id) => (string) $id)->all();

            if ($ids === []) {
                continue;
            }

            $moved = [];
            $kept  = [];

            if (isset($unique[$table . '.' . $column])) {
                // A one-per-patient table. Move row by row inside a savepoint
                // so a collision keeps THAT row back and lets the rest through,
                // instead of failing the whole merge or clobbering the survivor's.
                foreach ($ids as $id) {
                    try {
                        DB::transaction(function () use ($table, $column, $id, $to) {
                            DB::table($table)->where('id', $id)->update([$column => $to]);
                        });
                        $moved[] = $id;
                    } catch (UniqueConstraintViolationException) {
                        $kept[] = $id;
                    } catch (QueryException $e) {
                        if (($e->errorInfo[0] ?? null) === '23505') {
                            $kept[] = $id;
                        } else {
                            throw $e;
                        }
                    }
                }
            } else {
                DB::table($table)->where($column, $from)->update([$column => $to]);
                $moved = $ids;
            }

            $manifest[] = ['table' => $table, 'column' => $column, 'moved' => $moved, 'kept' => $kept];
        }

        return $manifest;
    }

    /**
     * Fill NULL demographics on the survivor from the retired record.
     *
     * @return array<string, true>  the fields that were filled
     */
    private function applySurvivorship(Patient $survivor, Patient $retired): array
    {
        $filled = [];

        foreach (self::SURVIVORSHIP_FIELDS as $field) {
            $current = $survivor->{$field};
            $donor   = $retired->{$field};

            if ($this->isBlank($current) && ! $this->isBlank($donor)) {
                // Carbon (date_of_birth) goes back through the mutator as a
                // date string; everything else is a scalar or array.
                $survivor->{$field} = $donor instanceof \DateTimeInterface ? $donor->format('Y-m-d') : $donor;
                $filled[$field] = true;
            }
        }

        if ($filled !== []) {
            $survivor->save();
        }

        return $filled;
    }

    private function isBlank(mixed $v): bool
    {
        return $v === null || $v === '' || $v === [];
    }

    private function lockedPatient(string $id): Patient
    {
        return Patient::withoutGlobalScope('isolate_demo')->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function statusValue(Patient $p): string
    {
        $s = $p->identity_status;

        return $s instanceof IdentityStatus ? $s->value : (string) $s;
    }

    private function audit(string $action, ?string $actorId, Patient $subject, ?string $reason, array $before, array $after): void
    {
        // audit_events.is_demo is NOT NULL and AuditEvent does not carry the
        // IsDemoRecord trait, so it is set here, from the same switch the
        // patients themselves are stamped with.
        (new AuditEvent())->forceFill([
            'id'                 => (string) Str::uuid(),
            'actor_id'           => $actorId,
            'actor_role'         => 'platform_admin',
            'patient_id'         => $subject->getKey(),
            'action_type'        => $action,
            'resource_type'      => 'patient',
            'resource_id'        => $subject->getKey(),
            'emergency_override' => false,
            'source_system'      => 'mpi',
            'reason'             => $reason,
            'before_state'       => $before,
            'after_state'        => $after,
            'is_demo'            => (bool) config('demo.enabled', false),
            'created_at'         => now(),
        ])->save();
    }
}
