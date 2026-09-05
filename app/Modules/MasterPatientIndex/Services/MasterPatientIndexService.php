<?php

namespace App\Modules\MasterPatientIndex\Services;

use App\Enums\IdentityStatus;
use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Models\PatientMerge;
use App\Modules\MasterPatientIndex\Exceptions\IdentifierHeldByAnotherPatient;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * MasterPatientIndexService — one person, one record.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT CHANGED, AND WHY
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The first version compared every patient at ONE facility against every
 * other at that facility — O(n²), one "does this pair exist" query per pair,
 * scanning the OLDEST 500 by creation date, inside the demo partition. It
 * could not see across facilities, which is the reason a Master Patient Index
 * exists; on production it would have scanned zero patients, because none
 * carry a facility_id; and once a facility passed 500 patients no newer
 * registration would ever be scanned again. `confirmMatch()` then wrote the
 * word "merged" on a row and moved nothing.
 *
 * Detection is now BLOCKED rather than exhaustive: for each patient in the
 * focus set, the register is asked for the handful of records that share a
 * blocking key — the same phone hash, the same national ID, the same
 * normalised name, or the same external identifier — and only those pairs
 * are scored. It looks across every facility, ignores the demo partition
 * (the question is about the register, not about what this request may
 * browse), scans NEWEST first, and takes a `since` so a nightly run
 * considers only what arrived since the last one — paired against everyone.
 *
 * Scoring gained NEGATIVE evidence. Two records with the same common name and
 * birth year but different sexes are not the same person, and the old model
 * could not say so: agreement added points and disagreement added nothing.
 * Now a mismatch on sex, DOB, phone or national ID subtracts, and the
 * reasons list carries both, so the reviewer sees "name matched, phones
 * differ" rather than a bare number.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT DOES NOT CHANGE: NOTHING MERGES ITSELF
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The platform contract says no probabilistic auto-merge, and this service
 * keeps to it absolutely: every merge is a person on the review desk choosing
 * a survivor and giving a reason. A deterministic match (shared national ID,
 * shared identifier) scores 100 and is labelled as such, and STILL waits for
 * that person. The cost of a wrong merge is one patient's history filed under
 * another patient's name; no throughput target is worth it.
 */
final class MasterPatientIndexService
{
    /** Below this a pair is not worth a reviewer's time. */
    public const MATCH_THRESHOLD = 60.0;

    /** At or above: the pair shares an identifier that belongs to one person. */
    public const TIER_DETERMINISTIC = 90.0;

    /** At or above: strong demographic agreement, no contradiction. */
    public const TIER_PROBABLE = 75.0;

    // Agreement.
    private const W_NAME          = 40.0;
    private const W_PHONE         = 30.0;
    private const W_DOB           = 20.0;
    private const W_DOB_ESTIMATED = 10.0;   // either side's DOB is a guess
    private const W_SEX           = 10.0;
    private const W_NATIONAL_ID   = 50.0;
    private const W_IDENTIFIER    = 50.0;

    // Contradiction. A field both records fill in differently.
    private const P_PHONE_DIFFERS       = -15.0;
    private const P_DOB_DIFFERS         = -25.0;
    private const P_SEX_DIFFERS         = -30.0;
    private const P_NATIONAL_ID_DIFFERS = -40.0;

    private const SCAN_COLUMNS = [
        'id', 'health_id', 'first_name', 'last_name', 'phone_number_hash',
        'date_of_birth', 'is_dob_estimated', 'sex', 'national_id_number',
        'identity_status', 'facility_id', 'created_at',
    ];

    public function __construct(private readonly PatientMergeService $merges)
    {
    }

    // ══════════════════════════════════════════════════════════════════════
    // 1. Search — "does this person already exist?"
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Patients that could be the person described.
     *
     * Three independent paths, any one of which is enough: an external
     * identifier (exact), a phone number (keyed hash, exact), or name + date
     * of birth through Patient::findExistingIdentity() — the same normalising,
     * scope-blind check every patient-creation path uses, so this and those
     * cannot drift apart.
     *
     * @param  array{identifiers?: list<array{type: string, value: string}>, first_name?: string, last_name?: string, date_of_birth?: string, phone_number?: string}  $data
     */
    public function searchCandidates(array $data): Collection
    {
        $found = collect();

        foreach ($data['identifiers'] ?? [] as $identifier) {
            if (empty($identifier['type']) || empty($identifier['value'])) {
                continue;
            }

            $ids = PatientIdentifier::where('identifier_type', $identifier['type'])
                ->where('identifier_value', $identifier['value'])
                ->pluck('patient_id');

            $found = $found->merge($this->register()->whereIn('id', $ids)->get());
        }

        if (! empty($data['phone_number'])) {
            $hit = Patient::findExistingIdentity(null, null, null, $data['phone_number']);
            if ($hit) {
                $found->push($hit);
            }
        }

        if (! empty($data['first_name']) && ! empty($data['last_name']) && ! empty($data['date_of_birth'])) {
            $hit = Patient::findExistingIdentity($data['first_name'], $data['last_name'], $data['date_of_birth']);
            if ($hit) {
                $found->push($hit);
            }
        }

        return $found->unique(fn (Patient $p) => (string) $p->getKey())->values();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. Detection
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Find likely duplicates and queue them for review.
     *
     * @param  string|null           $facilityId  narrow the FOCUS set to one facility (mates come from anywhere)
     * @param  int                   $limit       most patients to focus on in one run
     * @param  CarbonInterface|null  $since       focus only on patients created or updated since
     * @param  bool                  $dryRun      score everything, write nothing
     * @return array{scanned: int, pairs_scored: int, created: int, already_known: int, below_threshold: int}
     */
    public function detectDuplicates(
        ?string $facilityId = null,
        int $limit = 500,
        ?CarbonInterface $since = null,
        bool $dryRun = false,
    ): array {
        $focus = $this->register()
            ->select(self::SCAN_COLUMNS)
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->when($since, fn ($q) => $q->where(fn ($w) => $w->where('created_at', '>=', $since)->orWhere('updated_at', '>=', $since)))
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get();

        $report = ['scanned' => $focus->count(), 'pairs_scored' => 0, 'created' => 0, 'already_known' => 0, 'below_threshold' => 0];

        if ($focus->isEmpty()) {
            return $report;
        }

        // Every pair the queue already holds that touches the focus set — ONE
        // query, not one per pair.
        $focusIds = $focus->pluck('id')->map(fn ($id) => (string) $id)->all();
        $known = MpiCandidate::query()
            ->where(fn ($q) => $q->whereIn('source_patient_id', $focusIds)->orWhereIn('target_patient_id', $focusIds))
            ->get(['source_patient_id', 'target_patient_id'])
            ->mapWithKeys(fn (MpiCandidate $c) => [$c->source_patient_id . '|' . $c->target_patient_id => true])
            ->all();

        $seenThisRun = [];

        foreach ($focus as $patient) {
            foreach ($this->blockMates($patient) as $mate) {
                [$a, $b] = $this->canonical($patient, $mate);
                $key = $a->getKey() . '|' . $b->getKey();

                if (isset($seenThisRun[$key])) {
                    continue;
                }
                $seenThisRun[$key] = true;

                if (isset($known[$key])) {
                    $report['already_known']++;
                    continue;
                }

                [$score, $reasons] = $this->scoreMatch($a, $b);
                $report['pairs_scored']++;

                if ($score < self::MATCH_THRESHOLD) {
                    $report['below_threshold']++;
                    continue;
                }

                if (! $dryRun) {
                    $this->ensureCandidate($a, $b, $score, $reasons, 'scan');
                }
                $report['created']++;
            }
        }

        Log::info('MasterPatientIndex: detection complete', $report + ['facility_id' => $facilityId, 'since' => $since?->toIso8601String(), 'dry_run' => $dryRun]);

        return $report;
    }

    /**
     * Records that share at least one blocking key with $patient, from the
     * whole register. Merged records are never mates: their rows already
     * belong to someone.
     *
     * @return Collection<int, Patient>
     */
    private function blockMates(Patient $patient): Collection
    {
        $base = fn () => $this->register()
            ->select(self::SCAN_COLUMNS)
            ->whereKeyNot($patient->getKey());

        $mates = collect();

        if ($patient->phone_number_hash) {
            $mates = $mates->merge($base()->where('phone_number_hash', $patient->phone_number_hash)->get());
        }

        if ($patient->national_id_number) {
            $mates = $mates->merge($base()->where('national_id_number', $patient->national_id_number)->get());
        }

        $mates = $mates->merge(
            $base()
                ->whereRaw('lower(btrim(first_name)) = ?', [mb_strtolower(trim((string) $patient->first_name))])
                ->whereRaw('lower(btrim(last_name)) = ?', [mb_strtolower(trim((string) $patient->last_name))])
                ->get()
        );

        $own = PatientIdentifier::where('patient_id', $patient->getKey())->get(['identifier_type', 'identifier_value']);
        if ($own->isNotEmpty()) {
            $shared = PatientIdentifier::where('patient_id', '!=', $patient->getKey())
                ->where(function ($q) use ($own) {
                    foreach ($own as $i) {
                        $q->orWhere(fn ($w) => $w->where('identifier_type', $i->identifier_type)->where('identifier_value', $i->identifier_value));
                    }
                })
                ->pluck('patient_id');

            if ($shared->isNotEmpty()) {
                $mates = $mates->merge($base()->whereIn('id', $shared)->get());
            }
        }

        return $mates->unique(fn (Patient $p) => (string) $p->getKey())->values();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 3. Scoring
    // ══════════════════════════════════════════════════════════════════════

    /**
     * How likely two records are one person, and why.
     *
     * @return array{0: float, 1: list<string>}  [score 0–100, reasons]
     */
    public function scoreMatch(Patient $a, Patient $b): array
    {
        $score   = 0.0;
        $reasons = [];

        // Name — accent-folded, case- and whitespace-insensitive.
        if ($this->norm($a->first_name) === $this->norm($b->first_name)
            && $this->norm($a->last_name) === $this->norm($b->last_name)
            && $this->norm($a->last_name) !== '') {
            $score    += self::W_NAME;
            $reasons[] = 'name_match';
        }

        // Phone — keyed hash; compared, never decrypted.
        if ($a->phone_number_hash && $b->phone_number_hash) {
            if ($a->phone_number_hash === $b->phone_number_hash) {
                $score    += self::W_PHONE;
                $reasons[] = 'phone_match';
            } else {
                $score    += self::P_PHONE_DIFFERS;
                $reasons[] = 'phone_differs';
            }
        }

        // Date of birth — decrypted per pair; a guessed DOB is worth less.
        $dobA = $this->dob($a);
        $dobB = $this->dob($b);
        if ($dobA !== null && $dobB !== null) {
            if ($dobA === $dobB) {
                $estimated = (bool) $a->is_dob_estimated || (bool) $b->is_dob_estimated;
                $score    += $estimated ? self::W_DOB_ESTIMATED : self::W_DOB;
                $reasons[] = $estimated ? 'dob_match_estimated' : 'dob_match';
            } else {
                $score    += self::P_DOB_DIFFERS;
                $reasons[] = 'dob_differs';
            }
        }

        // Sex — weak agreement, strong disagreement.
        $sexA = $this->enumValue($a->sex);
        $sexB = $this->enumValue($b->sex);
        if ($sexA !== null && $sexB !== null) {
            if ($sexA === $sexB) {
                $score    += self::W_SEX;
                $reasons[] = 'sex_match';
            } else {
                $score    += self::P_SEX_DIFFERS;
                $reasons[] = 'sex_differs';
            }
        }

        // National ID — deterministic either way.
        if ($a->national_id_number && $b->national_id_number) {
            if ($this->norm($a->national_id_number) === $this->norm($b->national_id_number)) {
                $score    += self::W_NATIONAL_ID;
                $reasons[] = 'national_id_match';
            } else {
                $score    += self::P_NATIONAL_ID_DIFFERS;
                $reasons[] = 'national_id_differs';
            }
        }

        // A shared external identifier (insurance number, facility MRN, …).
        foreach ($this->sharedIdentifierTypes($a, $b) as $type) {
            $score    += self::W_IDENTIFIER;
            $reasons[] = 'identifier_match:' . $type;
        }

        return [max(0.0, min(100.0, round($score, 2))), $reasons];
    }

    /** Which review tier a score falls in. */
    public static function tierFor(float $score): string
    {
        return match (true) {
            $score >= self::TIER_DETERMINISTIC => 'deterministic',
            $score >= self::TIER_PROBABLE      => 'probable',
            default                            => 'possible',
        };
    }

    // ══════════════════════════════════════════════════════════════════════
    // 4. Decisions
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The reviewer says these are one person, and which record survives.
     *
     * This is the only path to a merge from the queue, and the name is finally
     * true: confirming a match merges the records — every referencing row is
     * moved, an alias is written, the retired record is retired — and it can
     * be undone.
     */
    public function confirmMatch(MpiCandidate $candidate, ?string $actorId, string $survivorId, ?string $reason = null): PatientMerge
    {
        if (! $candidate->isPending()) {
            throw new RuntimeException('MPI_CANDIDATE_NOT_PENDING: status is ' . $candidate->status);
        }

        $pair = [(string) $candidate->source_patient_id, (string) $candidate->target_patient_id];
        if (! in_array($survivorId, $pair, true)) {
            throw new InvalidArgumentException('MPI_SURVIVOR_NOT_IN_PAIR');
        }

        $retiredId = $survivorId === $pair[0] ? $pair[1] : $pair[0];

        $survivor = $this->register()->findOrFail($survivorId);
        $retired  = $this->register()->findOrFail($retiredId);

        return $this->merges->merge($survivor, $retired, $actorId, $reason, $candidate);
    }

    /** The reviewer says these are two people. */
    public function rejectMatch(MpiCandidate $candidate, string $actorId, ?string $reason = null): MpiCandidate
    {
        if (! $candidate->isPending()) {
            throw new RuntimeException('MPI_CANDIDATE_NOT_PENDING: status is ' . $candidate->status);
        }

        $candidate->forceFill([
            'status'        => 'rejected',
            'reviewed_by'   => $actorId,
            'reviewed_at'   => now(),
            'review_reason' => $reason,
        ])->save();

        Log::info('MasterPatientIndex: match rejected', ['candidate_id' => $candidate->getKey(), 'reviewed_by' => $actorId]);

        return $candidate->fresh();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 5. Identifiers
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Attach an external identifier to a patient.
     *
     * If the same (type, value, issuer) is already on a DIFFERENT patient,
     * that is the strongest duplicate signal there is, and it used to surface
     * as a raw unique-constraint violation. It now becomes a deterministic
     * candidate on the review queue, and the exception carries it.
     *
     * @param  array{type: string, value: string, issuer?: string|null, facility_id?: string|null}  $identifier
     * @throws IdentifierHeldByAnotherPatient
     */
    public function linkIdentifier(string $patientId, array $identifier): PatientIdentifier
    {
        if (empty($identifier['type']) || empty($identifier['value'])) {
            throw new RuntimeException('MPI_LINK_IDENTIFIER: type and value are required');
        }

        $holder = PatientIdentifier::where('identifier_type', $identifier['type'])
            ->where('identifier_value', $identifier['value'])
            ->where('issuer', $identifier['issuer'] ?? null)
            ->where('patient_id', '!=', $patientId)
            ->first();

        if ($holder) {
            $a = $this->register()->findOrFail($patientId);
            $b = $this->register()->findOrFail($holder->patient_id);
            [$a, $b] = $this->canonical($a, $b);

            $candidate = $this->ensureCandidate($a, $b, 100.0, ['identifier_collision:' . $identifier['type']], 'identifier_collision');

            throw new IdentifierHeldByAnotherPatient($candidate, $identifier['type']);
        }

        $linked = PatientIdentifier::updateOrCreate(
            [
                'patient_id'       => $patientId,
                'identifier_type'  => $identifier['type'],
                'identifier_value' => $identifier['value'],
            ],
            [
                'issuer'      => $identifier['issuer'] ?? null,
                'facility_id' => $identifier['facility_id'] ?? null,
            ]
        );

        Log::info('MasterPatientIndex: identifier linked', ['patient_id' => $patientId, 'identifier_type' => $identifier['type']]);

        return $linked;
    }

    // ══════════════════════════════════════════════════════════════════════
    // 6. The queue
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @param  array{status?: string, tier?: string, min_score?: float}  $filters
     */
    public function listCandidates(array $filters = [], int $perPage = 20)
    {
        $scopeBlind = fn ($q) => $q->withoutGlobalScope('isolate_demo');

        $query = MpiCandidate::with([
            'sourcePatient' => $scopeBlind,
            'targetPatient' => $scopeBlind,
            'survivor'      => $scopeBlind,
            'reviewer:id,name',
            'merge',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tier'])) {
            $query->where('match_score', '>=', match ($filters['tier']) {
                'deterministic' => self::TIER_DETERMINISTIC,
                'probable'      => self::TIER_PROBABLE,
                default         => self::MATCH_THRESHOLD,
            });
            if ($filters['tier'] === 'probable') {
                $query->where('match_score', '<', self::TIER_DETERMINISTIC);
            } elseif ($filters['tier'] === 'possible') {
                $query->where('match_score', '<', self::TIER_PROBABLE);
            }
        }

        if (! empty($filters['min_score'])) {
            $query->where('match_score', '>=', (float) $filters['min_score']);
        }

        return $query->orderByDesc('match_score')->orderBy('created_at')->paginate($perPage);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Plumbing
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The whole register: every facility, both sides of the demo partition,
     * nobody who has already been merged away.
     */
    private function register()
    {
        return Patient::withoutGlobalScope('isolate_demo')
            ->where('identity_status', '!=', IdentityStatus::Merged->value);
    }

    /**
     * A pair is stored once, smaller id first, so (a,b) and (b,a) are the
     * same row — and the unique index can say so.
     *
     * @return array{0: Patient, 1: Patient}
     */
    private function canonical(Patient $x, Patient $y): array
    {
        return strcmp((string) $x->getKey(), (string) $y->getKey()) < 0 ? [$x, $y] : [$y, $x];
    }

    private function ensureCandidate(Patient $a, Patient $b, float $score, array $reasons, string $detectedBy): MpiCandidate
    {
        return MpiCandidate::firstOrCreate(
            ['source_patient_id' => $a->getKey(), 'target_patient_id' => $b->getKey()],
            ['match_score' => $score, 'match_reasons' => $reasons, 'status' => 'pending_review', 'detected_by' => $detectedBy],
        );
    }

    /** @return list<string> identifier types both patients carry with the same value */
    private function sharedIdentifierTypes(Patient $a, Patient $b): array
    {
        return DB::table('patient_identifiers as x')
            ->join('patient_identifiers as y', function ($j) {
                $j->on('x.identifier_type', '=', 'y.identifier_type')
                  ->on('x.identifier_value', '=', 'y.identifier_value');
            })
            ->where('x.patient_id', $a->getKey())
            ->where('y.patient_id', $b->getKey())
            ->distinct()
            ->pluck('x.identifier_type')
            ->all();
    }

    private function norm(?string $s): string
    {
        return Str::ascii(mb_strtolower(trim((string) $s)));
    }

    private function dob(Patient $p): ?string
    {
        try {
            return $p->date_of_birth?->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function enumValue(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return $v instanceof \BackedEnum ? (string) $v->value : (string) $v;
    }
}
