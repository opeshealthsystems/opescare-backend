<?php

namespace App\Modules\MasterPatientIndex\Services;

use App\Modules\Connect\Services\WebhookEventPublisher;
use App\Enums\AuditEventType;
use App\Enums\IdentityStatus;
use App\Models\AuditEvent;
use App\Models\HealthIdAlias;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientMerge;
use App\Models\PatientMergeAlias;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
 * Six tables carry a UNIQUE constraint that includes `patient_id`. When both
 * records hold one, moving the retired row would violate it. Rather than
 * delete one or guess which is right, the survivor keeps its own and the
 * retired row stays where it is, listed under `kept` in the manifest.
 *
 * Survivorship for demographics follows the same rule: a field on the survivor
 * is filled from the retired record only when the survivor's is NULL. A
 * non-null value is never overwritten, even by a "better" one — that is a
 * judgement, and judgements are made on the desk, not in here.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT ELSE A MERGE HAS TO TIDY
 * ─────────────────────────────────────────────────────────────────────────
 *
 * - Other PENDING candidates naming the retired patient are no longer a
 *   question about that patient; they are marked `superseded`. The survivor's
 *   `updated_at` moves, so the next scan re-evaluates its pairs from scratch.
 * - A family link can become self-referential: the retired patient's login
 *   was guardian of the survivor, and now both are the survivor. Those rows
 *   are removed, and their contents kept in the manifest for unmerge.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * UNMERGE IS EXACT, AND REFUSES WHEN IT CANNOT BE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The manifest names every row moved, so unmerge puts back those rows and
 * only those. Two situations make that impossible and are refused rather
 * than done badly: the survivor was itself merged into a third record since
 * (undo that one first — merges unwind last-in-first-out), or a party has
 * been purged by account deletion (there is nothing to restore into).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS BYPASSES THE MODELS' IMMUTABILITY GUARDS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Prescription, LabOrder and LabResult refuse to change `patient_id` through
 * Eloquent, and rightly. This is the one sanctioned exception — correcting
 * WHICH identity a record was filed under, with the previous value recorded
 * per row, under an operator's name, reversibly. It is done with
 * query-builder updates for that reason, and for that reason only.
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

        $merge = DB::transaction(function () use ($survivor, $retired, $actorId, $reason, $candidate) {
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

            // 2. A login that guarded the other record now guards itself.
            $manifest = array_merge($manifest, $this->removeSelfFamilyLinks($survivor->getKey()));

            // 3. Fill the survivor's blanks — never its values.
            $survivorship = $this->applySurvivorship($survivor, $retired);

            // 4. The retired Health ID keeps resolving. Both alias tables are
            //    written: `health_id_aliases` is what Patient::resolveHealthId()
            //    reads, `patient_merge_aliases` is the merge-history record its
            //    own model expects (vocabulary: 'forward').
            HealthIdAlias::create([
                'patient_id'  => $survivor->getKey(),
                'alias_type'  => 'merged_health_id',
                'alias_value' => $retired->health_id,
                'status'      => 'active',
            ]);

            PatientMergeAlias::recordMerge(
                retiredHealthId:    $retired->health_id,
                canonicalPatientId: (string) $survivor->getKey(),
                retiredPatientId:   (string) $retired->getKey(),
                mergedByUserId:     $actorId,
                reason:             $reason,
            );

            // 5. Retire, don't delete.
            $previousStatus = $this->statusValue($retired);
            $retired->forceFill([
                'identity_status'        => IdentityStatus::Merged,
                'merged_into_patient_id' => $survivor->getKey(),
                'merged_at'              => now(),
            ])->save();

            // 6. The decision, if it came from the review queue.
            if ($candidate) {
                $candidate->forceFill([
                    'status'              => 'merged',
                    'reviewed_by'         => $actorId,
                    'reviewed_at'         => now(),
                    'survivor_patient_id' => $survivor->getKey(),
                    'review_reason'       => $reason,
                ])->save();
            }

            // 7. The record that makes this undoable — with both Health IDs as
            //    text, so it still reads after a purge removes either row.
            $merge = PatientMerge::create([
                'candidate_id'                     => $candidate?->getKey(),
                'survivor_patient_id'              => $survivor->getKey(),
                'retired_patient_id'               => $retired->getKey(),
                'survivor_health_id'               => $survivor->health_id,
                'retired_health_id'                => $retired->health_id,
                'merged_by'                        => $actorId,
                'reason'                           => $reason,
                'manifest'                         => $manifest,
                'survivorship'                     => $survivorship,
                'retired_previous_identity_status' => $previousStatus,
                'status'                           => PatientMerge::STATUS_MERGED,
            ]);

            // 8. Every other open question about the retired patient is moot.
            $this->supersedeCandidates($retired, $survivor, $candidate, $merge);

            $this->audit(
                AuditEventType::HealthIdMerged->value,
                $actorId,
                $survivor,
                $reason,
                before: ['retired_health_id' => $retired->health_id, 'retired_patient_id' => $retired->getKey()],
                after:  ['merge_id' => $merge->getKey(), 'rows_moved' => $merge->movedRowCount(), 'rows_kept' => $merge->keptRowCount(), 'fields_filled' => array_keys($survivorship)],
            );

            Log::info(AuditEventType::HealthIdMerged->value, [
                'merge_id'   => $merge->getKey(),
                'survivor'   => $survivor->health_id,
                'retired'    => $retired->health_id,
                'rows_moved' => $merge->movedRowCount(),
                'rows_kept'  => $merge->keptRowCount(),
                'merged_by'  => $actorId,
            ]);

            return $merge;
        });

        // Partner webhook (patient.merged), after commit; never able to fail the merge.
        $this->notifyPartners($merge, merged: true);

        return $merge;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Unmerge
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Put back exactly what the merge moved. Rows the survivor acquired
     * afterwards are not in the manifest and are not touched.
     *
     * @throws RuntimeException MPI_MERGE_NOT_ACTIVE | MPI_UNMERGE_PARTY_PURGED | MPI_UNMERGE_OUT_OF_ORDER
     */
    public function unmerge(PatientMerge $merge, ?string $actorId, ?string $reason = null): PatientMerge
    {
        if (! $merge->isMerged()) {
            throw new RuntimeException('MPI_MERGE_NOT_ACTIVE: status is ' . $merge->status);
        }

        // Account deletion may have purged either row since; the keys are
        // SET NULL for exactly that reason. There is nothing to restore into.
        if ($merge->survivor_patient_id === null || $merge->retired_patient_id === null) {
            throw new RuntimeException('MPI_UNMERGE_PARTY_PURGED');
        }

        $merge = DB::transaction(function () use ($merge, $actorId, $reason) {
            $survivor = $this->lockedPatient($merge->survivor_patient_id);
            $retired  = $this->lockedPatient($merge->retired_patient_id);

            // If the survivor has since been merged into a third record, the
            // rows this merge moved have moved AGAIN and are no longer where
            // the manifest says. Merges unwind last-in-first-out.
            if ($survivor->isMerged()) {
                throw new RuntimeException('MPI_UNMERGE_OUT_OF_ORDER');
            }

            // 1. Rows go home. Only the ids we moved, and only if they are
            //    still on the survivor.
            foreach ($merge->manifest as $entry) {
                if (($entry['column'] ?? '') === 'self_link') {
                    foreach ($entry['deleted'] ?? [] as $row) {
                        DB::table($entry['table'])->insertOrIgnore($row);
                    }
                    continue;
                }

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

            // 3. Neither alias may resolve any more. The resolver's table keeps
            //    the row as history with an inactive status; the merge-history
            //    table has no status and its own resolver, so its row goes —
            //    `patient_merges` (status unmerged) is the durable record.
            HealthIdAlias::where('patient_id', $survivor->getKey())
                ->where('alias_type', 'merged_health_id')
                ->where('alias_value', $retired->health_id)
                ->update(['status' => 'inactive']);

            PatientMergeAlias::where('alias_health_id', $retired->health_id)
                ->where('canonical_patient_id', $survivor->getKey())
                ->delete();

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
                    'updated_at'          => now(),
                ]);
            }

            $merge->forceFill([
                'status'         => PatientMerge::STATUS_UNMERGED,
                'unmerged_by'    => $actorId,
                'unmerged_at'    => now(),
                'unmerge_reason' => $reason,
            ])->save();

            $this->audit(
                AuditEventType::HealthIdUnmerged->value,
                $actorId,
                $survivor,
                $reason,
                before: ['merge_id' => $merge->getKey()],
                after:  ['restored_health_id' => $retired->health_id, 'rows_restored' => $merge->movedRowCount()],
            );

            Log::info(AuditEventType::HealthIdUnmerged->value, [
                'merge_id'    => $merge->getKey(),
                'survivor'    => $survivor->health_id,
                'restored'    => $retired->health_id,
                'unmerged_by' => $actorId,
            ]);

            return $merge;
        });

        // Partner webhook (patient.unmerged), after commit.
        $this->notifyPartners($merge, merged: false);

        return $merge;
    }

    /**
     * patient.merged / patient.unmerged to the facilities holding an active
     * consent grant from either party (WebhookEventPublisher). Best effort:
     * a lookup or delivery failure never undoes the merge.
     */
    private function notifyPartners(PatientMerge $merge, bool $merged): void
    {
        rescue(function () use ($merge, $merged) {
            $ids = array_values(array_filter([(string) $merge->survivor_patient_id, (string) $merge->retired_patient_id]));
            $healthIds = Patient::withoutGlobalScope('isolate_demo')->whereIn('id', $ids)->pluck('health_id', 'id');
            $survivor = (string) ($healthIds[(string) $merge->survivor_patient_id] ?? '');
            $retired  = (string) ($healthIds[(string) $merge->retired_patient_id] ?? '');
            if ($survivor === '' || $retired === '') {
                return;
            }

            $merged
                ? WebhookEventPublisher::patientMerged((string) $merge->getKey(), $retired, $survivor, $ids)
                : WebhookEventPublisher::patientUnmerged((string) $merge->getKey(), $retired, $survivor, $ids);
        }, null, report: false);
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

    /** @return array<string, true> "table.column" pairs inside a UNIQUE constraint */
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
                // A one-per-patient table. Row by row inside a savepoint, so a
                // collision keeps THAT row back and lets the rest through.
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
     * After re-pointing, a family link may say "the survivor's own login
     * guards the survivor". Remove those, keeping their contents so unmerge
     * can put them back verbatim.
     *
     * @return list<array{table: string, column: string, moved: list<never>, kept: list<never>, deleted: list<array<string, mixed>>}>
     */
    private function removeSelfFamilyLinks(string $survivorId): array
    {
        if (! Schema::hasTable('family_links')) {
            return [];
        }

        $rows = DB::table('family_links as fl')
            ->join('users as u', 'u.id', '=', 'fl.guardian_user_id')
            ->where('fl.dependent_patient_id', $survivorId)
            ->where('u.patient_id', $survivorId)
            ->select('fl.*')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        if ($rows === []) {
            return [];
        }

        DB::table('family_links')->whereIn('id', array_column($rows, 'id'))->delete();

        return [['table' => 'family_links', 'column' => 'self_link', 'moved' => [], 'kept' => [], 'deleted' => $rows]];
    }

    /**
     * Every other pending pair the retired patient appears in is no longer a
     * question about that patient. The survivor's updated_at moved, so the
     * nightly scan re-pairs it against the same records fresh.
     */
    private function supersedeCandidates(Patient $retired, Patient $survivor, ?MpiCandidate $current, PatientMerge $merge): void
    {
        MpiCandidate::query()
            ->where('status', 'pending_review')
            ->when($current, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->where(fn ($q) => $q->where('source_patient_id', $retired->getKey())->orWhere('target_patient_id', $retired->getKey()))
            ->update([
                'status'        => 'superseded',
                'reviewed_by'   => $merge->merged_by,
                'reviewed_at'   => now(),
                'review_reason' => "Superseded: {$retired->health_id} was merged into {$survivor->health_id} (merge {$merge->getKey()}).",
                'updated_at'    => now(),
            ]);
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
        $role = $actorId
            ? DB::table('users')->leftJoin('roles', 'roles.id', '=', 'users.role_id')->where('users.id', $actorId)->value('roles.name')
            : null;

        // audit_events.is_demo is NOT NULL and AuditEvent does not carry the
        // IsDemoRecord trait, so it is set here from the same switch the
        // patients themselves are stamped with.
        (new AuditEvent())->forceFill([
            'id'                 => (string) Str::uuid(),
            'actor_id'           => $actorId,
            'actor_role'         => $role ?? 'system',
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
