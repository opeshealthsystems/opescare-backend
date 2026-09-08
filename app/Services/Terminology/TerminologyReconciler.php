<?php

namespace App\Services\Terminology;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TerminologyReconciler — reads this deployment's own clinical data back
 * against the published code dictionary and says what does not line up.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THIS CLASS NEVER WRITES. NOT ONCE. NOT EVEN "OBVIOUS" FIXES.
 * ─────────────────────────────────────────────────────────────────────────
 * Every statement it issues is a SELECT. There is no update(), no delete(),
 * no save(), no normalisation pass, and there must never be one. Clinical
 * events on this platform are immutable — a wrong value is amended, voided or
 * marked entered-in-error by a person who is accountable for the amendment,
 * never silently overwritten by a maintenance job. A vocabulary scan that
 * "tidied" `lab_results.unit` while nobody watched would rewrite the meaning
 * of results that have already been reported to patients and clinicians, with
 * no amendment trail and no author. So this class reports, and a human
 * decides. If a fix looks obvious, that is exactly the moment to leave it to
 * the human: `10^9/L` and `x10^9/L` are almost certainly the same unit, and
 * "almost certainly" is not a standard of proof for editing a blood result.
 *
 * NOT LOGGED AS PATIENT ACCESS. The scan reads counts and distinct code
 * values from clinical tables. It never selects a patient id, a name, a Health
 * ID, a date of birth or an individual clinical row, and it cannot tell you
 * anything about any one patient — `GROUP BY unit` over every lab result on
 * the platform is a statement about the vocabulary, not about a person. The
 * patient access log exists so that a patient can ask who looked at *their*
 * record; writing a row into it for a vocabulary census would be a lie about
 * what happened, and burying the genuine accesses under thousands of
 * housekeeping entries would damage the one log that is supposed to answer
 * that question. Contrast TerminologyApplier::radius(), which reads
 * patient-linked rows for one mapping and IS logged.
 *
 * NEVER INVENTS. A column that does not exist in this deployment is reported
 * as absent; a column that exists and is empty is reported as empty; a
 * vocabulary that has not been imported is reported as not loaded. Those are
 * three different facts and the report keeps them apart.
 */
class TerminologyReconciler
{
    /** A target whose table or column this deployment does not have. */
    public const STATE_COLUMN_ABSENT = 'column_absent';

    /** The column exists, but the vocabulary it should be checked against has not been loaded. */
    public const STATE_VOCABULARY_ABSENT = 'vocabulary_absent';

    /** The column exists and holds nothing. Absent is not zero, and neither is zero a gap. */
    public const STATE_EMPTY = 'empty';

    /** Checked. See the counts. */
    public const STATE_CHECKED = 'checked';

    /**
     * Route abbreviations and their written-out forms.
     *
     * THIS LIST IS CURATED BY HAND, not inferred. No string-similarity rule
     * can be allowed to decide that "IM" and "intramuscular" are the same
     * concept — the two share two letters, and a rule loose enough to pair
     * them would also pair things that are not the same at all. These are
     * standard EDQM/HL7 route abbreviations, asserted by the author of this
     * file, and the report says so wherever it uses them.
     *
     * Each inner array is one concept, written in folded form.
     */
    private const ROUTE_SYNONYMS = [
        ['im', 'intramuscular'],
        ['sc', 'sq', 'subcutaneous', 'subcut'],
        ['id', 'intradermal'],
        ['iv', 'intravenous'],
        ['po', 'oral', 'bymouth'],
        ['pr', 'rectal'],
        ['intranasal', 'nasal'],
        ['top', 'topical'],
        ['sl', 'sublingual'],
        ['inh', 'inhalation', 'inhaled'],
        ['td', 'transdermal'],
    ];

    /**
     * SI prefix letters, which are the reason a one-character difference
     * between two units is usually not a spelling variant at all.
     *
     * `mmol/L` and `umol/L` are one substitution apart and differ by a factor
     * of a thousand; `g/dL` and `mg/dL` are one insertion apart and differ by a
     * factor of a thousand. Both were reported as probable duplicates by the
     * near-string rule until this guard existed, and both are exactly the kind
     * of "obvious fix" that would destroy a result if anyone acted on it. So
     * when the single edit between two values is one of these letters, the pair
     * is dropped rather than shown. That loses a genuine typo now and then;
     * a miss is the failure mode this tool is allowed to have.
     */
    private const SI_PREFIX_LETTERS = ['d', 'c', 'm', 'u', 'n', 'p', 'f', 'k', 'h', 'z', 'y', 'e', 't'];

    /**
     * Words too common in a clinical vocabulary to identify anything.
     *
     * Not a curated blocklist — the cutoff is computed per vocabulary from how
     * many entries a token actually matches (see displayMatches). This constant
     * is only the floor below which a token is never considered too common.
     */
    private const TOKEN_MATCH_FLOOR = 10;

    /** Memoised folded dictionary indexes, keyed by system and kinds. */
    private array $foldedIndex = [];

    /**
     * The declarative map: which clinical column is supposed to carry which
     * vocabulary.
     *
     * `systems` is an ORDERED LIST OF ACCEPTABLE IDENTIFIERS, not one name.
     * UCUM, CVX and the route vocabularies are being imported separately and
     * nobody has yet settled on what the `system` string will read as; the
     * first identifier that actually has usable rows wins, so this map keeps
     * working whichever spelling lands, and honestly reports "not loaded"
     * until one of them does.
     *
     * `kinds` are the dictionary kinds a value may legitimately resolve to.
     * Resolving to a chapter or block heading is not resolving: nobody can
     * assign "Chapter II - Neoplasms" to a medicine.
     *
     * `display` names columns holding the human label already on the clinical
     * row. They are read ONLY to suggest near misses for a code that did not
     * resolve.
     */
    private function map(): array
    {
        return [
            [
                'key' => 'medicines.atc_code',
                'table' => 'medicines',
                'column' => 'atc_code',
                'systems' => ['ATC'],
                'kinds' => ['term'],
                'display' => ['generic_name', 'name'],
            ],
            [
                'key' => 'diagnoses.code',
                'table' => 'diagnoses',
                'column' => 'code',
                'systems' => ['ICD-10'],
                'kinds' => ['category'],
                'display' => ['display_name'],
            ],
            [
                // The dictionary does carry rows tagged SNOMED-CT, but they are
                // the specialty concepts imported for the provider directory,
                // not the clinical-finding subset a diagnosis code belongs to.
                // Declaring the kinds this column needs is what makes the
                // report say "that vocabulary is not loaded" instead of "every
                // one of your SNOMED codes is wrong".
                'key' => 'diagnoses.snomed_code',
                'table' => 'diagnoses',
                'column' => 'snomed_code',
                'systems' => ['SNOMED-CT'],
                'kinds' => ['finding', 'term', 'category'],
                'display' => ['display_name'],
            ],
            [
                'key' => 'lab_results.loinc_code',
                'table' => 'lab_results',
                'column' => 'loinc_code',
                'systems' => ['LOINC'],
                'kinds' => ['term'],
                'display' => ['parameter_name'],
            ],
            [
                'key' => 'lab_results.unit',
                'table' => 'lab_results',
                'column' => 'unit',
                'systems' => ['UCUM'],
                'kinds' => ['unit', 'term'],
                'display' => ['parameter_name'],
                // Typed by a technician into a free-text box, so two spellings
                // of one unit are a real possibility here in a way they never
                // are in a code column.
                'free_text' => true,
            ],
            [
                'key' => 'immunization_records.vaccine_code',
                'table' => 'immunization_records',
                'column' => 'vaccine_code',
                'systems' => ['CVX', 'HL7-vaccine', 'WHO-EPI'],
                'kinds' => ['vaccine', 'term'],
                'display' => ['vaccine_name'],
            ],
            [
                'key' => 'immunization_records.route',
                'table' => 'immunization_records',
                'column' => 'route',
                'systems' => ['EDQM-route', 'HL7-route', 'route'],
                'kinds' => ['route', 'term'],
                'synonyms' => self::ROUTE_SYNONYMS,
                'free_text' => true,
            ],
            [
                'key' => 'immunization_records.dose_unit',
                'table' => 'immunization_records',
                'column' => 'dose_unit',
                'systems' => ['UCUM'],
                'kinds' => ['unit', 'term'],
                'free_text' => true,
            ],
            [
                'key' => 'prescription_items.route',
                'table' => 'prescription_items',
                'column' => 'route',
                'systems' => ['EDQM-route', 'HL7-route', 'route'],
                'kinds' => ['route', 'term'],
                'synonyms' => self::ROUTE_SYNONYMS,
                'free_text' => true,
            ],
            [
                'key' => 'prescription_items.drug_code',
                'table' => 'prescription_items',
                'column' => 'drug_code',
                'systems' => ['ATC'],
                'kinds' => ['term'],
                'display' => ['drug_name'],
            ],
            [
                // Not dead code: allergy intolerance is a FHIR domain this
                // platform does not yet store, and the report saying so out
                // loud is more useful than the row's silent absence.
                'key' => 'allergies.code',
                'table' => 'allergies',
                'column' => 'code',
                'systems' => ['SNOMED-CT'],
                'kinds' => ['finding', 'term'],
            ],
        ];
    }

    /**
     * The declared targets themselves, read-only.
     *
     * Exposed so that a caller checking whether a suggested code is assignable
     * can read the SAME `kinds` this report checked against, instead of keeping
     * a second copy of them somewhere that will drift. Still a pure read: it
     * returns the map, it does not let anyone change it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function targets(): array
    {
        return $this->map();
    }

    /** The whole report: one finding per declared target, in map order. */
    public function report(): array
    {
        return array_map(fn (array $target) => $this->inspect($target), $this->map());
    }

    /** Standing totals of the dictionary itself, so an empty report can be read correctly. */
    public function vocabularies(): array
    {
        return DB::table('clinical_code_dictionary')
            ->selectRaw('system, count(*) as total')
            ->selectRaw('count(*) filter (where is_active) as active')
            ->groupBy('system')
            ->orderBy('system')
            ->get()
            ->map(fn ($r) => ['system' => $r->system, 'total' => (int) $r->total, 'active' => (int) $r->active])
            ->all();
    }

    // ── One target ───────────────────────────────────────────────────────

    private function inspect(array $target): array
    {
        $finding = [
            'key' => $target['key'],
            'table' => $target['table'],
            'column' => $target['column'],
            'systems' => $target['systems'],
            'system' => null,
            'state' => null,
            'rows' => 0,
            'filled' => 0,
            'distinct' => 0,
            'resolved_rows' => 0,
            'resolved_values' => 0,
            'values' => [],
            'unresolved' => [],
            'duplicates' => [],
            'whitespace' => [],
        ];

        if (! Schema::hasTable($target['table']) || ! Schema::hasColumn($target['table'], $target['column'])) {
            $finding['state'] = self::STATE_COLUMN_ABSENT;

            return $finding;
        }

        $table = $target['table'];
        $column = $target['column'];

        $finding['rows'] = (int) DB::table($table)->count();

        // Distinct values with their row counts. No patient column is selected
        // and none is needed — this is a census of the vocabulary in use.
        $values = DB::table($table)
            ->select($column . ' as value')
            ->selectRaw('count(*) as row_count')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->groupBy($column)
            ->orderByDesc('row_count')
            ->orderBy($column)
            ->get()
            ->map(fn ($r) => ['value' => (string) $r->value, 'rows' => (int) $r->row_count])
            ->all();

        $finding['filled'] = array_sum(array_column($values, 'rows'));
        $finding['distinct'] = count($values);
        $finding['values'] = $values;

        // Values that differ from themselves trimmed. Worth its own line: a
        // code with a trailing space is invisible on screen and never joins.
        foreach ($values as $v) {
            if (trim($v['value']) !== $v['value']) {
                $finding['whitespace'][] = $v;
            }
        }

        $finding['duplicates'] = $this->duplicateClusters(
            $values,
            $target['synonyms'] ?? [],
            (bool) ($target['free_text'] ?? false)
        );

        if ($values === []) {
            $finding['state'] = self::STATE_EMPTY;

            return $finding;
        }

        $system = $this->resolveSystem($target);

        if ($system === null) {
            $finding['state'] = self::STATE_VOCABULARY_ABSENT;

            return $finding;
        }

        $finding['system'] = $system;
        $finding['state'] = self::STATE_CHECKED;

        $codes = array_column($values, 'value');

        $assignable = array_flip(
            DB::table('clinical_code_dictionary')
                ->where('system', $system)
                ->whereIn('kind', $target['kinds'])
                ->where('is_active', true)
                ->whereIn('code', $codes)
                ->pluck('code')
                ->all()
        );

        // Codes that exist in the vocabulary but only as a heading, a group or
        // a retired row. "Present" and "assignable" are different answers.
        $present = array_flip(
            DB::table('clinical_code_dictionary')
                ->where('system', $system)
                ->whereIn('code', $codes)
                ->pluck('code')
                ->all()
        );

        foreach ($values as $v) {
            if (isset($assignable[$v['value']])) {
                $finding['resolved_values']++;
                $finding['resolved_rows'] += $v['rows'];

                continue;
            }

            $finding['unresolved'][] = [
                'value' => $v['value'],
                'rows' => $v['rows'],
                'heading_only' => isset($present[$v['value']]),
                'suggestions' => $this->suggest($system, $target, $v['value']),
            ];
        }

        return $finding;
    }

    /** The first declared identifier that actually has usable rows in the dictionary. */
    private function resolveSystem(array $target): ?string
    {
        foreach ($target['systems'] as $system) {
            $has = DB::table('clinical_code_dictionary')
                ->where('system', $system)
                ->whereIn('kind', $target['kinds'])
                ->where('is_active', true)
                ->exists();

            if ($has) {
                return $system;
            }
        }

        return null;
    }

    // ── "These two strings are probably the same thing" ──────────────────

    /**
     * Fold a value for comparison: lowercased, accents stripped, and every
     * character that is not a letter or a digit removed.
     *
     * So `10^9/L` and `10^9 / l` both fold to `109l`, and `Oral` and `oral`
     * both fold to `oral`.
     */
    public static function fold(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));

        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae',
        ]);

        return (string) preg_replace('/[^a-z0-9]+/u', '', $value);
    }

    /**
     * Group distinct values that are probably one concept written two ways.
     *
     * Deliberately conservative, in three separately-labelled rules, because a
     * false "these are the same" is worse than a miss: a reviewer told that two
     * genuinely different units are duplicates may merge them, and that
     * corrupts data. A miss only leaves the report quieter than it could be.
     *
     *  1. `case_punctuation` — identical once case, accents, spaces and
     *     punctuation are removed. Close to certain: `Oral` and `oral` are the
     *     same word.
     *
     *  2. `near_string` — folded forms one single-character edit apart, and
     *     BOTH at least three characters long. `109l` vs `x109l` is one
     *     insertion, so `10^9/L` and `x10^9/L` surface.
     *
     *     TWO GUARDS, both learned the hard way, both load-bearing:
     *
     *     - FREE-TEXT COLUMNS ONLY (`free_text` on the map entry). Run on a
     *       column of structured codes it is not merely noisy, it is wrong in
     *       principle: a code system is BUILT so that neighbouring codes differ
     *       by one character and mean entirely different things. On this
     *       deployment's 400 ATC codes the rule produced hundreds of pairs —
     *       `N02BE01` paracetamol against `N02BA01` aspirin — every one of them
     *       false, and a reviewer handed that list would learn to ignore the
     *       report. Units and routes are typed by people; codes are not.
     *
     *     - Both folded forms at least three characters. This stops the rule
     *       firing on the short unit and route tokens that differ by one letter
     *       and are not the same thing at all: `mg`/`mL`, `IM`/`IV`, `SC`/`SL`.
     *
     *     Even past both guards it means only "probably": offered to a
     *     reviewer, never acted on.
     *
     *  3. `declared_synonym` — both values are members of one hand-curated
     *     synonym set declared on that column (ROUTE_SYNONYMS). No similarity
     *     measure could find `IM` = `intramuscular`; a person asserted it, and
     *     the report labels the rule so the reader knows which is which.
     */
    private function duplicateClusters(array $values, array $synonymSets, bool $freeText): array
    {
        $clusters = [];

        $folded = [];
        foreach ($values as $v) {
            $f = self::fold($v['value']);
            if ($f === '') {
                continue;
            }
            $folded[] = ['value' => $v['value'], 'rows' => $v['rows'], 'folded' => $f];
        }

        // 1 — identical after folding.
        $byFold = [];
        foreach ($folded as $f) {
            $byFold[$f['folded']][] = $f;
        }
        foreach ($byFold as $group) {
            if (count($group) > 1) {
                $clusters[] = ['rule' => 'case_punctuation', 'members' => $this->members($group)];
            }
        }

        // 2 — one edit apart, both folded forms at least three characters, and
        // only on a column people type free-hand. See the doc block above for
        // why running this over a code column is not a tuning problem but a
        // category error.
        $count = $freeText ? count($folded) : 0;
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $folded[$i];
                $b = $folded[$j];

                if ($a['folded'] === $b['folded']) {
                    continue; // already reported by rule 1
                }
                if (mb_strlen($a['folded']) < 3 || mb_strlen($b['folded']) < 3) {
                    continue;
                }
                if (levenshtein($a['folded'], $b['folded']) !== 1) {
                    continue;
                }
                if ($this->editIsMagnitude($a['folded'], $b['folded'])) {
                    continue;
                }

                $clusters[] = ['rule' => 'near_string', 'members' => $this->members([$a, $b])];
            }
        }

        // 3 — both members of one curated synonym set.
        foreach ($synonymSets as $set) {
            $hits = array_values(array_filter($folded, fn ($f) => in_array($f['folded'], $set, true)));

            if (count($hits) > 1) {
                $clusters[] = ['rule' => 'declared_synonym', 'members' => $this->members($hits)];
            }
        }

        // One pair, one line. `oral` and `Oral` are both the same word after
        // folding AND both members of the curated "by mouth" set, and saying so
        // twice makes the report look like it found two problems.
        $seen = [];
        $unique = [];
        foreach ($clusters as $cluster) {
            $signature = array_column($cluster['members'], 'value');
            sort($signature);
            $signature = implode("\0", $signature);

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;
            $unique[] = $cluster;
        }

        return $unique;
    }

    /**
     * True when the one character separating two otherwise-identical values is
     * an SI prefix letter — meaning the pair is a change of magnitude, not a
     * change of spelling. See SI_PREFIX_LETTERS.
     *
     * Both inputs are already known to be exactly one edit apart.
     */
    private function editIsMagnitude(string $a, string $b): bool
    {
        // Find the first and last positions at which the two disagree; for a
        // single edit the character in question lies between them.
        $prefix = 0;
        $limitA = mb_strlen($a);
        $limitB = mb_strlen($b);
        $limit = min($limitA, $limitB);

        while ($prefix < $limit && mb_substr($a, $prefix, 1) === mb_substr($b, $prefix, 1)) {
            $prefix++;
        }

        $changed = [mb_substr($a, $prefix, 1), mb_substr($b, $prefix, 1)];

        foreach ($changed as $character) {
            if ($character !== '' && in_array($character, self::SI_PREFIX_LETTERS, true)) {
                return true;
            }
        }

        return false;
    }

    private function members(array $group): array
    {
        return array_map(fn ($g) => ['value' => $g['value'], 'rows' => $g['rows']], $group);
    }

    // ── Near misses for a code that did not resolve ──────────────────────

    /**
     * Candidate dictionary codes for an unresolved value. SUGGESTIONS ONLY —
     * nothing here is applied, and the report never presents one as the answer.
     *
     * Two independent routes, because the interesting failure is the one where
     * the code is well-formed and simply no longer where it used to be:
     *
     *  a) `display` — the clinical row already carries a human label, and so
     *     does the dictionary. `L01XX14` on "Tretinoin 10mg Capsule" is a dead
     *     code, but the dictionary's `L01XF01 trétinoïne` folds to
     *     `tretinoine`, which contains the folded `tretinoin` from the
     *     medicine's own generic name. That match is what identifies a WHO
     *     reclassification — ATC moved tretinoin into the new L01XF retinoid
     *     subgroup and did not reuse the old L01XX slot — and no amount of
     *     staring at the code string alone would have found it.
     *
     *  b) `code_shape` — assignable siblings under the longest shared prefix,
     *     ranked by edit distance. Catches typos and truncations, and shows
     *     that the old branch is still populated by other, unrelated children.
     */
    private function suggest(string $system, array $target, string $value): array
    {
        $suggestions = [];

        foreach ($this->displayMatches($system, $target, $value) as $s) {
            $suggestions[$s['code']] = $s;
        }

        foreach ($this->codeShapeMatches($system, $target, $value) as $s) {
            if (! isset($suggestions[$s['code']])) {
                $suggestions[$s['code']] = $s;
            }
        }

        return array_slice(array_values($suggestions), 0, 5);
    }

    /** Dictionary rows whose folded title contains a distinctive word from the clinical row's own label. */
    private function displayMatches(string $system, array $target, string $value): array
    {
        $displayColumns = $target['display'] ?? [];

        if ($displayColumns === []) {
            return [];
        }

        // The labels attached to this ONE code value. No patient column is
        // selected; a drug name or an analyte name is vocabulary, not identity.
        $labels = DB::table($target['table'])
            ->where($target['column'], $value)
            ->limit(25)
            ->get($displayColumns)
            ->flatMap(fn ($row) => array_values((array) $row))
            ->filter(fn ($v) => filled($v))
            ->all();

        // Words of five folded characters or more only. Shorter tokens ("10mg",
        // "oral", "tab") match a large share of any vocabulary and would bury
        // the one suggestion that matters.
        $tokens = [];
        foreach ($labels as $label) {
            foreach (preg_split('/[^\p{L}\p{N}]+/u', (string) $label) as $word) {
                $foldedWord = self::fold((string) $word);
                if (mb_strlen($foldedWord) >= 5 && ! ctype_digit($foldedWord)) {
                    $tokens[$foldedWord] = true;
                }
            }
        }

        if ($tokens === []) {
            return [];
        }

        $index = $this->index($system, $target['kinds']);
        $tokens = array_keys($tokens);

        // How many entries each token matches. A token that matches a large
        // share of the vocabulary identifies nothing: "vaccine" appears in
        // almost every CVX title, and matching on it turned the suggestions for
        // COVID-19 into a list of measles and hepatitis products. The cutoff is
        // computed from the size of this vocabulary rather than fixed, so it
        // travels to a vocabulary of a different size.
        $matches = array_fill_keys($tokens, 0);
        foreach ($index as $entry) {
            foreach ($tokens as $token) {
                if (str_contains($entry['folded'], $token)) {
                    $matches[$token]++;
                }
            }
        }

        $cutoff = max(self::TOKEN_MATCH_FLOOR, (int) ceil(count($index) * 0.02));
        $tokens = array_values(array_filter($tokens, fn ($t) => $matches[$t] > 0 && $matches[$t] <= $cutoff));

        if ($tokens === []) {
            return [];
        }

        $out = [];
        foreach ($index as $entry) {
            $rarest = null;
            foreach ($tokens as $token) {
                if (str_contains($entry['folded'], $token)) {
                    $rarest = $rarest === null ? $matches[$token] : min($rarest, $matches[$token]);
                }
            }

            if ($rarest !== null) {
                $out[] = [
                    'code' => $entry['code'],
                    'display' => $entry['display'],
                    'basis' => 'display',
                    'rarity' => $rarest,
                ];
            }
        }

        // Ranked by how much of the code prefix the candidate still shares with
        // the value on the record, longest first. A title match alone is not
        // enough to be useful: "tretinoin" also matches the dermatological
        // D10AD01 and half a dozen isotretinoin preparations, which are real
        // codes for a different drug in a different branch. Ordering by shared
        // prefix puts L01XF01 — same L01X antineoplastic branch as the dead
        // L01XX14 on the record — at the top, which is what makes the reading
        // "WHO moved this code" rather than "here are some similar words".
        usort($out, function (array $a, array $b) use ($value) {
            return $this->sharedPrefix($value, $b['code']) <=> $this->sharedPrefix($value, $a['code'])
                ?: $a['rarity'] <=> $b['rarity']
                ?: strcmp($a['code'], $b['code']);
        });

        return array_map(
            fn (array $s) => ['code' => $s['code'], 'display' => $s['display'], 'basis' => $s['basis']],
            array_slice($out, 0, 5)
        );
    }

    /** Length of the common leading substring of two codes. */
    private function sharedPrefix(string $a, string $b): int
    {
        $limit = min(mb_strlen($a), mb_strlen($b));

        for ($i = 0; $i < $limit; $i++) {
            if (mb_substr($a, $i, 1) !== mb_substr($b, $i, 1)) {
                return $i;
            }
        }

        return $limit;
    }

    /** Assignable siblings sharing the longest prefix of at least four characters. */
    private function codeShapeMatches(string $system, array $target, string $value): array
    {
        for ($length = min(mb_strlen($value) - 1, 6); $length >= 4; $length--) {
            $prefix = mb_substr($value, 0, $length);
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $prefix);

            $rows = DB::table('clinical_code_dictionary')
                ->where('system', $system)
                ->whereIn('kind', $target['kinds'])
                ->where('is_active', true)
                ->where('code', 'LIKE', $escaped . '%')
                ->orderBy('code')
                ->limit(50)
                ->get(['code', 'display']);

            if ($rows->isEmpty()) {
                continue;
            }

            return $rows
                ->sortBy(fn ($r) => levenshtein($value, $r->code))
                ->take(5)
                ->map(fn ($r) => [
                    'code' => $r->code,
                    'display' => $r->display,
                    'basis' => 'code_shape',
                ])
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * A folded (code, title) index for one system, built once per run.
     *
     * Built in PHP rather than with ILIKE because the ATC titles here are
     * accented French — `trétinoïne` — and `ILIKE '%tretinoin%'` does not match
     * an accented string without the unaccent extension, which this deployment
     * does not require. The largest vocabulary loaded is ICD-10 at roughly
     * twelve thousand code-and-title rows, which is small.
     */
    private function index(string $system, array $kinds): array
    {
        $key = $system . '|' . implode(',', $kinds);

        if (isset($this->foldedIndex[$key])) {
            return $this->foldedIndex[$key];
        }

        $rows = DB::table('clinical_code_dictionary')
            ->where('system', $system)
            ->whereIn('kind', $kinds)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'display', 'display_fr', 'synonyms']);

        $index = [];
        foreach ($rows as $r) {
            $index[] = [
                'code' => $r->code,
                'display' => $r->display,
                'folded' => self::fold((string) $r->display . ' ' . (string) $r->display_fr . ' ' . (string) $r->synonyms),
            ];
        }

        return $this->foldedIndex[$key] = $index;
    }
}
