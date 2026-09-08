<?php

namespace App\Services\Terminology;

use App\Models\CodeSystemMapping;
use App\Support\Terminology\ClinicalCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TerminologyProposer — turns one line of the gap report into a PENDING
 * proposal in `code_system_mappings`, and nothing else.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THIS CLASS NEVER WRITES TO A CLINICAL RECORD. NOT ONE COLUMN.
 * ─────────────────────────────────────────────────────────────────────────
 * The only table it inserts into is `code_system_mappings`, plus the audit
 * trail. It does not touch `medicines`, `diagnoses`, `lab_results`,
 * `immunization_records` or `prescription_items` — it only SELECTs from them,
 * through the reconciler, to check that the finding being proposed is real.
 * The reason is the one written on TerminologyReconciler: a clinical value is
 * amended by an accountable person, never rewritten by a maintenance action,
 * and "the gap report suggested it" is not accountability.
 *
 * ── Where a proposal goes from here ──────────────────────────────────────
 * Nowhere, until a person moves it. A proposal is created `pending`, is
 * therefore inert, and travels the desk that already exists: a reviewer
 * approves or rejects it on /admin/terminology, and only an APPROVED mapping
 * may then be applied by TerminologyApplier — which writes only the code
 * columns it already guards and asserts the dirty set before every save. This
 * class adds a proposal step in front of that queue. It does not add a fix.
 *
 * ── Why it re-derives the finding instead of trusting the form ───────────
 * The posted fields are a target key, a local value and a suggested code. All
 * three are re-checked against a freshly-run reconciliation: the value must
 * still be an unresolved value of that target, and the code must still be one
 * of the suggestions that target produced for it. A form field is not evidence.
 * Without this a POST could mint a pending mapping from any two strings, and a
 * pending mapping is one approval away from being written onto records.
 *
 * ── Machine, and labelled as machine ────────────────────────────────────
 * A proposal made here records that a rule suggested it and which rule
 * (`display` — a dictionary title matched the label already on the record;
 * `code_shape` — a neighbouring code under a shared prefix). It is stored with
 * `mapping_confidence = 'approximate'`, never 'exact' and never 'manual', and
 * the note says in words that no human has judged it yet. The approver has to
 * be able to see that they are the first person in the chain, not the second.
 */
class TerminologyProposer
{
    /** Audit action for a proposal raised from the gap report. */
    public const ACTION_PROPOSE = 'terminology.proposed_from_gaps';

    /** Everything created here is created pending. There is no other value. */
    public const STATUS = 'pending';

    /**
     * A machine suggestion is approximate by definition. It is never 'exact'
     * and never 'manual': both would claim a certainty nothing here has.
     */
    public const CONFIDENCE = 'approximate';

    /**
     * Gap-report targets that have a `CodeSystemMapping` shape, and the
     * resource type a proposal from each one carries.
     *
     * A target is here only when "this local value means that standard code"
     * is a true sentence about it. Everything NOT here is explained by
     * notProposableReason() rather than left silently button-less — a control
     * absent for a reason the reader cannot see is indistinguishable from a
     * control somebody forgot.
     */
    private const PROPOSABLE = [
        'medicines.atc_code'                => 'medication',
        'prescription_items.drug_code'      => 'medication',
        'diagnoses.code'                    => 'condition',
        'diagnoses.snomed_code'             => 'condition',
        'lab_results.loinc_code'            => 'lab_result',
        'immunization_records.vaccine_code' => 'immunization',
        'allergies.code'                    => 'allergy_intolerance',
    ];

    /**
     * Why a target cannot become a proposal — a translation key, so the screen
     * says it in the reader's own language.
     *
     * `lab_results.unit` and `immunization_records.dose_unit` are the entries
     * that matter. `10^9/L` against `x10^9/L` is a UNIT SPELLING problem:
     * there is no local-code-means-standard-code sentence to write down,
     * because both strings are attempts at the same UCUM expression rather
     * than a local vocabulary needing a mapping — and a UCUM expression is
     * composed from a grammar, not looked up as a dictionary row. A
     * `CodeSystemMapping` here would map a typo to a typo. Correcting a unit
     * on a reported blood result is an amendment to that result, made by the
     * laboratory that issued it.
     *
     * The route columns are the same shape of problem: `IM` and
     * `intramuscular` are one concept spelled two ways in a free-text field,
     * asserted as synonyms by a curated list in the reconciler, and the remedy
     * is to stop two spellings being written — not to map one string to the
     * other.
     */
    private const NOT_PROPOSABLE = [
        'lab_results.unit'               => 'terminology.propose_why_unit',
        'immunization_records.dose_unit' => 'terminology.propose_why_unit',
        'immunization_records.route'     => 'terminology.propose_why_route',
        'prescription_items.route'       => 'terminology.propose_why_route',
    ];

    public function __construct(
        private readonly TerminologyReconciler $reconciler,
        private readonly TerminologyApplier $applier,
    ) {
    }

    // ── What may and may not be proposed ─────────────────────────────────

    public function isProposable(string $key): bool
    {
        return isset(self::PROPOSABLE[$key]);
    }

    /** Translation key naming why this target has no proposal to offer, or null. */
    public function notProposableReason(string $key): ?string
    {
        if ($this->isProposable($key)) {
            return null;
        }

        return self::NOT_PROPOSABLE[$key] ?? 'terminology.propose_why_unknown';
    }

    public function resourceTypeFor(string $key): ?string
    {
        return self::PROPOSABLE[$key] ?? null;
    }

    /**
     * An already-live proposal for this local value in this system, or null.
     *
     * Pending AND approved both count. Re-proposing a value that is already
     * approved would put two contradictory mappings in front of the applier.
     * Rejected and deprecated do not count: a rejection is a decision about one
     * candidate code, and a better candidate may legitimately be offered later.
     */
    public function existing(string $localCode, string $system): ?CodeSystemMapping
    {
        return CodeSystemMapping::query()
            ->where('local_code', $localCode)
            ->whereRaw("lower(replace(standard_system, '-', '')) = ?", [
                strtolower(str_replace('-', '', $system)),
            ])
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('facility_id')
            ->first();
    }

    // ── Propose ──────────────────────────────────────────────────────────

    /**
     * Create one pending proposal from a gap-report line.
     *
     * @return array{mapping:?CodeSystemMapping,refused:?string}
     *         `refused` is a translation key. Every refusal is nameable: a
     *         clinical desk must never let "we declined, because" and "nothing
     *         happened" look the same.
     */
    public function propose(
        string $key,
        string $value,
        string $code,
        ?string $actorId,
        ?string $actorRole,
        ?string $facilityId,
        ?string $ip,
    ): array {
        if (! $this->isProposable($key)) {
            return ['mapping' => null, 'refused' => $this->notProposableReason($key)];
        }

        // The finding, re-derived. Not read from the form.
        $finding = collect($this->reconciler->report())->firstWhere('key', $key);

        if ($finding === null || $finding['state'] !== TerminologyReconciler::STATE_CHECKED) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_no_finding'];
        }

        $unresolved = collect($finding['unresolved'])->firstWhere('value', $value);

        if ($unresolved === null) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_no_finding'];
        }

        $suggestion = collect($unresolved['suggestions'] ?? [])->firstWhere('code', $code);

        if ($suggestion === null) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_not_suggested'];
        }

        $system = (string) $finding['system'];

        // Well-formedness, checked twice over.
        //
        // ClinicalCode proves a LOINC code malformed by arithmetic and can
        // shape-check ICD-10 and SNOMED. It knows nothing about ATC or CVX, so
        // for those — and as a second gate for the ones it does know — the code
        // must exist in the dictionary as an ACTIVE row of the very system and
        // kind this target may assign. Proposing a non-assignable row is
        // proposing a chapter heading.
        if (! $this->codeIsWellFormed($system, $code)) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_malformed'];
        }

        if (! $this->codeIsAssignable($system, $key, $code)) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_not_in_dictionary'];
        }

        if ($this->existing($value, $system) !== null) {
            return ['mapping' => null, 'refused' => 'terminology.propose_refuse_duplicate'];
        }

        $mapping = DB::transaction(fn () => CodeSystemMapping::create([
            'local_code'         => $value,
            'local_name'         => null,
            'local_unit'         => null,
            'resource_type'      => $this->resourceTypeFor($key),
            'standard_system'    => $system,
            'standard_code'      => $code,
            'standard_display'   => $suggestion['display'] ?? null,
            'mapping_confidence' => self::CONFIDENCE,
            'confidence_score'   => null,
            // Hard-coded, not a parameter. There is no path through this class
            // that can create anything other than a pending proposal.
            'status'             => self::STATUS,
            'approved_by'        => null,
            'approved_at'        => null,
            'notes'              => $this->note($key, (string) ($suggestion['basis'] ?? ''), (int) ($unresolved['rows'] ?? 0)),
            'facility_id'        => null,
            'created_by'         => $actorId,
        ]));

        // The same audit shape the applier writes, so the desk's log screen
        // reads as one story: proposed, approved, applied.
        $this->applier->log(
            self::ACTION_PROPOSE,
            (string) $mapping->id,
            $actorId,
            $actorRole,
            $facilityId,
            $ip,
            [
                'after' => [
                    'origin'            => 'terminology_gap_report',
                    'target'            => $key,
                    'local_code'        => $value,
                    'standard_system'   => $system,
                    'standard_code'     => $code,
                    'suggestion_basis'  => $suggestion['basis'] ?? null,
                    'affected_rows'     => (int) ($unresolved['rows'] ?? 0),
                    'status'            => self::STATUS,
                    'machine_suggested' => true,
                ],
            ],
        );

        return ['mapping' => $mapping, 'refused' => null];
    }

    /**
     * The note carried on the proposal.
     *
     * Written for the approver, with one job: make it impossible to read this
     * row as somebody's clinical judgement. It names the report it came from,
     * the rule that produced it, and how many records the value sits on.
     */
    private function note(string $key, string $basis, int $rows): string
    {
        return Str::limit(implode(' ', [
            __('terminology.propose_note_origin', ['target' => $key]),
            __('terminology.propose_note_basis_' . ($basis === 'display' ? 'display' : 'code_shape')),
            __('terminology.propose_note_rows', ['rows' => $rows]),
            __('terminology.propose_note_unjudged'),
        ]), 480, '');
    }

    /** Malformed by the system's own rules, wherever those rules can be checked. */
    private function codeIsWellFormed(string $system, string $code): bool
    {
        return match (strtolower(str_replace(['-', ' ', '_'], '', $system))) {
            'loinc' => ClinicalCode::isLoinc($code),
            'icd10' => ClinicalCode::isIcd10($code),
            'snomedct', 'snomed' => ClinicalCode::looksLikeSnomed($code),
            // ATC, CVX and the rest carry no check digit, and ClinicalCode
            // declines to pretend otherwise. They are gated by the dictionary
            // check below instead — a stronger test than any shape regex: the
            // code has to actually exist in the published vocabulary.
            default => trim($code) !== '',
        };
    }

    /** The code exists in the dictionary, active, and of a kind this target may assign. */
    private function codeIsAssignable(string $system, string $key, string $code): bool
    {
        $target = collect($this->reconciler->targets())->firstWhere('key', $key);

        $query = DB::table('clinical_code_dictionary')
            ->where('system', $system)
            ->where('code', $code)
            ->where('is_active', true);

        if ($target !== null && ! empty($target['kinds'])) {
            $query->whereIn('kind', $target['kinds']);
        }

        return $query->exists();
    }
}
