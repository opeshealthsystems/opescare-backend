<?php

namespace App\Services\Terminology;

use App\Models\AuditEvent;
use App\Models\CodeSystemMapping;
use App\Models\LabResult;
use App\Models\LabTestAvailability;
use App\Support\Terminology\ClinicalCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Puts an APPROVED clinical code mapping onto the records it describes.
 *
 * ── Why this is a separate, explicit act ──────────────────────────────────
 * `CodeSystemMapping::approve()` says "this code is the right code". It does
 * NOT say "write it onto 34 patients' results". Approval is a statement about
 * a mapping; application is a write against clinical rows, and conflating the
 * two would mean a single click in a review queue silently re-labelled a
 * patient's laboratory record. So approval and application are two buttons,
 * and this class refuses to run for anything that is not already approved.
 *
 * ── What it may and may not touch ────────────────────────────────────────
 * Applying a code ADDS a code. It never writes `value`, `unit`,
 * `reference_range`, `flag`, `notes` or `resulted_at`. `LabResult` guards its
 * own identity columns and exposes `loinc_code`/`loinc_display` as amendable,
 * and this class additionally asserts, immediately before every save, that the
 * dirty set is a subset of {loinc_code, loinc_display} — if anything else is
 * dirty the write is abandoned rather than committed. `LabResult::amend()` is
 * deliberately NOT used: it restamps `resulted_at` and appends to `notes`,
 * which would move the result signature the results inbox fingerprints and
 * re-raise every clinician acknowledgement. Labelling a result is not
 * amending it.
 *
 * ── Never a silent overwrite ─────────────────────────────────────────────
 * A target row that already carries a DIFFERENT code is skipped and reported
 * as a conflict. It is only re-coded when the operator explicitly confirms a
 * re-code, and a re-code records the code it replaced.
 *
 * ── The unit is part of the mapping ──────────────────────────────────────
 * A `lab_result` mapping matches on `parameter_name` AND `unit`. Creatinine in
 * umol/L (14682-9) and creatinine in mg/dL (2160-0) are different LOINC codes;
 * matching on the analyte name alone is exactly how a molar result acquires a
 * mass-concentration code and starts lying to every system that trusts it.
 * Rows that match the name but not the unit are reported as skipped, never
 * silently dropped from the count.
 *
 * ── Traceable and reversible ─────────────────────────────────────────────
 * Every applied code writes an `audit_events` row carrying the mapping id, the
 * actor, the target table and id, and `before_state`/`after_state`. A code on
 * a result therefore always answers "which mapping, approved by whom, applied
 * by whom, and what was there before" — and the before state is enough to put
 * the row back.
 */
class TerminologyApplier
{
    /** Per-row outcomes. Every candidate row lands in exactly one of these. */
    public const WRITE          = 'write';
    public const RECODE         = 'recode';
    public const ALREADY        = 'already';
    public const CONFLICT       = 'conflict';
    public const UNIT_MISMATCH  = 'unit_mismatch';

    /** Audit action types, so the log screen and the tests name the same thing. */
    public const ACTION_APPLY   = 'terminology.code_applied';
    public const ACTION_RUN     = 'terminology.apply_run';
    public const ACTION_PREVIEW = 'terminology.blast_radius_read';

    /**
     * Resource types this applier knows a target column for.
     *
     * `condition` is absent on purpose. ICD-10 for a diagnosis is already
     * carried on `diagnoses.code` — there is no separate standard-code column
     * to fill, so there is nothing here to write. Pretending otherwise would
     * mean inventing a destination.
     */
    public const APPLICABLE_TYPES = ['lab_result', 'lab_order'];

    // ── Refusals ─────────────────────────────────────────────────────────

    /**
     * Why this mapping may not be applied, or null if it may.
     *
     * Returns a translation key rather than a sentence: the refusal is shown
     * to a human in their own language, and the reason must be nameable.
     */
    public function refusalReason(CodeSystemMapping $mapping): ?string
    {
        if ($mapping->status !== 'approved') {
            return 'terminology.refuse_not_approved';
        }

        if (! in_array($mapping->resource_type, self::APPLICABLE_TYPES, true)) {
            return 'terminology.refuse_no_target';
        }

        // The only column these two tables carry is a LOINC one. An ICD-10 or
        // ATC code has no business being written into `loinc_code`, whatever
        // the mapping says.
        if ($this->system($mapping) !== 'loinc') {
            return 'terminology.refuse_wrong_system';
        }

        // Re-validated HERE, not merely at proposal time. A code can be edited
        // after it was proposed, a seeder can be changed, a row can be written
        // by a job. The check that matters is the one taken immediately before
        // the write.
        if (! $this->codeIsWellFormed($mapping)) {
            return 'terminology.refuse_malformed_code';
        }

        return null;
    }

    public function canApply(CodeSystemMapping $mapping): bool
    {
        return $this->refusalReason($mapping) === null;
    }

    /** Normalised system name — the seeder writes 'LOINC', the model reads 'loinc'. */
    public function system(CodeSystemMapping $mapping): string
    {
        return strtolower(str_replace(['-', ' ', '_'], '', (string) $mapping->standard_system));
    }

    /**
     * Is the standard code well formed for its own system?
     *
     * LOINC carries a real mod-10 check digit and so can be proved malformed.
     * ICD-10 and SNOMED can only be shape-checked, and ClinicalCode says so
     * itself — which is why an ICD-10 mapping leans harder on its approver.
     */
    public function codeIsWellFormed(CodeSystemMapping $mapping): bool
    {
        $code = (string) $mapping->standard_code;

        return match ($this->system($mapping)) {
            'loinc'  => ClinicalCode::isLoinc($code),
            'icd10'  => ClinicalCode::isIcd10($code),
            'snomed' => ClinicalCode::looksLikeSnomed($code),
            default  => false,
        };
    }

    // ── Blast radius ─────────────────────────────────────────────────────

    /**
     * Every row this mapping would touch, each with the decision it would get.
     *
     * Read-only. Nothing here writes, so the queue can show a blast radius on
     * a mapping that is still pending — knowing how many records a decision
     * reaches is part of making the decision.
     *
     * @return Collection<int, array{table:string,id:string,patient_id:?string,label:string,unit:?string,current_code:?string,decision:string}>
     */
    public function candidates(CodeSystemMapping $mapping): Collection
    {
        return match ($mapping->resource_type) {
            'lab_result' => $this->labResultCandidates($mapping),
            'lab_order'  => $this->labOrderCandidates($mapping),
            default      => collect(),
        };
    }

    /** Counts per decision, for the "what would this do" summary. */
    public function radius(CodeSystemMapping $mapping): array
    {
        $rows = $this->candidates($mapping);

        return [
            'total'         => $rows->count(),
            self::WRITE     => $rows->where('decision', self::WRITE)->count(),
            self::ALREADY   => $rows->where('decision', self::ALREADY)->count(),
            self::CONFLICT  => $rows->where('decision', self::CONFLICT)->count(),
            self::UNIT_MISMATCH => $rows->where('decision', self::UNIT_MISMATCH)->count(),
        ];
    }

    /**
     * Result analytes: matched on the analyte name AND its unit.
     *
     * The name query is done in SQL; the unit comparison is done in PHP on
     * purpose, so that a row matching the name but carrying a different unit
     * comes back as a VISIBLE `unit_mismatch` instead of vanishing from the
     * result set. A skip nobody can see is indistinguishable from a row that
     * never existed.
     */
    private function labResultCandidates(CodeSystemMapping $mapping): Collection
    {
        $wantUnit = $this->normaliseUnit($mapping->local_unit);

        return LabResult::query()
            ->where('parameter_name', $mapping->local_code)
            ->orderBy('id')
            ->get(['id', 'patient_id', 'parameter_name', 'unit', 'loinc_code'])
            ->map(function (LabResult $row) use ($mapping, $wantUnit) {
                $decision = $this->normaliseUnit($row->unit) === $wantUnit
                    ? $this->decide($row->loinc_code, $mapping->standard_code)
                    : self::UNIT_MISMATCH;

                return [
                    'table'        => 'lab_results',
                    'id'           => (string) $row->id,
                    'patient_id'   => $row->patient_id ? (string) $row->patient_id : null,
                    'label'        => (string) $row->parameter_name,
                    'unit'         => $row->unit,
                    'current_code' => $row->loinc_code,
                    'decision'     => $decision,
                ];
            })
            ->values();
    }

    /** Orderable tests: matched on `lab_test_availability.local_test_code`. */
    private function labOrderCandidates(CodeSystemMapping $mapping): Collection
    {
        return LabTestAvailability::query()
            ->where('local_test_code', $mapping->local_code)
            ->orderBy('id')
            ->get(['id', 'test_name', 'local_test_code', 'loinc_code'])
            ->map(fn (LabTestAvailability $row) => [
                'table'        => 'lab_test_availability',
                'id'           => (string) $row->id,
                'patient_id'   => null,
                'label'        => (string) $row->test_name,
                'unit'         => null,
                'current_code' => $row->loinc_code,
                'decision'     => $this->decide($row->loinc_code, $mapping->standard_code),
            ])
            ->values();
    }

    private function decide(?string $current, string $proposed): string
    {
        if ($current === null || trim($current) === '') {
            return self::WRITE;
        }

        return trim($current) === trim($proposed) ? self::ALREADY : self::CONFLICT;
    }

    /**
     * Units compare after trimming and case folding only.
     *
     * Deliberately NOT a unit converter: umol/L and mg/dL are not the same
     * unit spelled differently, they are different measurements with different
     * codes. Anything cleverer here would start silently equating them.
     */
    private function normaliseUnit(?string $unit): string
    {
        return Str::lower(trim((string) $unit));
    }

    // ── Apply ────────────────────────────────────────────────────────────

    /**
     * Write the mapping's code onto every row that should get it.
     *
     * @param  bool  $confirmRecode  Operator has explicitly accepted overwriting
     *                               rows that already carry a DIFFERENT code.
     * @return array{applied:int,recoded:int,skipped:array<string,int>,rows:array,refused:?string,run_id:?string}
     */
    public function apply(
        CodeSystemMapping $mapping,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        bool $confirmRecode = false,
        ?string $ip = null,
    ): array {
        $refusal = $this->refusalReason($mapping);

        if ($refusal !== null) {
            return [
                'applied' => 0, 'recoded' => 0,
                'skipped' => [], 'rows' => [],
                'refused' => $refusal, 'run_id' => null,
            ];
        }

        $runId = (string) Str::uuid();
        $rows  = $this->candidates($mapping);

        $applied = 0;
        $recoded = 0;
        $skipped = [self::ALREADY => 0, self::CONFLICT => 0, self::UNIT_MISMATCH => 0];
        $written = [];

        DB::transaction(function () use (
            $mapping, $rows, $confirmRecode, $runId, $actorId, $actorRole, $facilityId, $ip,
            &$applied, &$recoded, &$skipped, &$written
        ) {
            foreach ($rows as $row) {
                $decision = $row['decision'];

                if ($decision === self::CONFLICT && $confirmRecode) {
                    $decision = self::RECODE;
                }

                if ($decision !== self::WRITE && $decision !== self::RECODE) {
                    $skipped[$row['decision']] = ($skipped[$row['decision']] ?? 0) + 1;
                    continue;
                }

                $before = $this->writeCode($mapping, $row);

                $decision === self::RECODE ? $recoded++ : $applied++;

                $written[] = $row + ['decision' => $decision, 'previous_code' => $before];

                // Per-row audit: this is BOTH the access log for a patient-linked
                // write and the record a reversal would read.
                $this->log(self::ACTION_APPLY, (string) $mapping->id, $actorId, $actorRole, $facilityId, $ip, [
                    'run_id'     => $runId,
                    'patient_id' => $row['patient_id'],
                    'before'     => ['table' => $row['table'], 'id' => $row['id'], 'loinc_code' => $before],
                    'after'      => [
                        'table'         => $row['table'],
                        'id'            => $row['id'],
                        'loinc_code'    => $mapping->standard_code,
                        'loinc_display' => $mapping->standard_display,
                        'decision'      => $decision,
                    ],
                ]);
            }
        });

        // One summary row for the run, so the log screen can show "who applied
        // this mapping, when, and what it did" without folding 34 rows together.
        $this->log(self::ACTION_RUN, (string) $mapping->id, $actorId, $actorRole, $facilityId, $ip, [
            'run_id' => $runId,
            'after'  => [
                'run_id'         => $runId,
                'mapping_id'     => (string) $mapping->id,
                'standard_code'  => $mapping->standard_code,
                'applied'        => $applied,
                'recoded'        => $recoded,
                'skipped'        => $skipped,
                'confirm_recode' => $confirmRecode,
                'rows'           => $written,
            ],
        ]);

        return [
            'applied' => $applied,
            'recoded' => $recoded,
            'skipped' => $skipped,
            'rows'    => $written,
            'refused' => null,
            'run_id'  => $runId,
        ];
    }

    /**
     * The only write in this class.
     *
     * Returns whatever code was there before. Asserts the dirty set before
     * saving: if anything other than the two code columns has moved, the save
     * is abandoned. A labelling pass that can alter a value is not a labelling
     * pass.
     */
    private function writeCode(CodeSystemMapping $mapping, array $row): ?string
    {
        if ($row['table'] === 'lab_results') {
            $model = LabResult::query()->findOrFail($row['id']);
            $before = $model->loinc_code;

            $model->loinc_code = $mapping->standard_code;
            $model->loinc_display = $mapping->standard_display;

            $this->assertOnlyCodeColumnsMoved($model->getDirty());
            $model->save();

            return $before;
        }

        $model = LabTestAvailability::query()->findOrFail($row['id']);
        $before = $model->loinc_code;

        $model->loinc_code = $mapping->standard_code;

        // `lab_test_availability` has no display column. Write the display only
        // where the column exists rather than inventing one.
        if (Schema::hasColumn('lab_test_availability', 'loinc_display')) {
            $model->loinc_display = $mapping->standard_display;
        }

        $this->assertOnlyCodeColumnsMoved($model->getDirty());
        $model->save();

        return $before;
    }

    /** @param array<string, mixed> $dirty */
    private function assertOnlyCodeColumnsMoved(array $dirty): void
    {
        $illegal = array_diff(array_keys($dirty), ['loinc_code', 'loinc_display']);

        if ($illegal !== []) {
            throw new \LogicException(
                'Applying a terminology code may only set loinc_code and loinc_display; '
                . 'refusing a write that also changes: ' . implode(', ', $illegal) . '.'
            );
        }
    }

    // ── Audit ────────────────────────────────────────────────────────────

    /**
     * Record a read or a write against patient-linked rows.
     *
     * `facility_id` is whatever the caller was handed by the auth middleware —
     * this class never looks at a request, so there is nowhere for a header or
     * a query string to get in.
     */
    public function log(
        string $action,
        ?string $mappingId,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
        array $payload = [],
    ): void {
        AuditEvent::create([
            'actor_id'      => $actorId,
            'actor_role'    => $actorRole,
            'facility_id'   => $facilityId,
            'patient_id'    => $payload['patient_id'] ?? null,
            'action_type'   => $action,
            'resource_type' => 'code_system_mapping',
            'resource_id'   => $mappingId,
            'source_system' => 'terminology_desk',
            'ip_address'    => $ip,
            'reason'        => $payload['reason'] ?? null,
            'before_state'  => $payload['before'] ?? null,
            'after_state'   => $payload['after'] ?? null,
            'created_at'    => now(),
        ]);
    }
}
