<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports WHO ICHI — the International Classification of Health Interventions
 * — into the clinical code dictionary. This is the procedures vocabulary.
 *
 * ── The gap ──────────────────────────────────────────────────────────────
 * Every other axis of this dictionary is backed by a publisher: diagnoses by
 * WHO ICD-10, labs and imaging by LOINC, medicines by WHO ATC, routes by
 * EDQM. Interventions — what was actually DONE to the patient — had nothing,
 * so a procedure could only ever be free text. This closes that.
 *
 * ── What was rejected, and why ───────────────────────────────────────────
 * Four candidates were probed before this one. All four failed, and the
 * reasoning matters more than the verdict:
 *
 * 1. NLM `clinicaltables.nlm.nih.gov/api/procedures` — 284 rows keyed on
 *    opaque MedlinePlus ids (`X100`, `4780`) that no other system resolves.
 *    Only ~11% carry anything code-shaped, and that field MIXES ICD-9-CM
 *    procedure codes (`68.41`) with ICD-9-CM DIAGNOSIS codes (`V45.51`) — two
 *    different classifications in one column, both retired in 2015. It is a
 *    questionnaire picklist, not a vocabulary. `ImagingProcedureImportTest`
 *    holds a standing assertion that no importer for it exists.
 *
 * 2. CPT — AMA-licensed and paid. Never imported, never labelled.
 *
 * 3. CCAM (`Classification Commune des Actes Médicaux`, v84.00, 38,235
 *    concepts) IS openly available on the French national terminology server
 *    this deployment already uses, complete and in French. It was rejected on
 *    purpose. CCAM is France's REIMBURSEMENT nomenclature — its properties
 *    are `facturation`, `modificateur`, `acteActivitePhase`, `tarif` — and
 *    its content is shaped by what the French social-security system pays
 *    for. That is the same reasoning that made `ImportIcd10French` refuse to
 *    add CIM-10 FR's French-only extensions: a national funding artefact is
 *    not an international clinical claim, and 38,235 French billing acts
 *    offered to a Cameroonian surgeon would be selected from and then
 *    exchanged with partners who would read them as clinical statements.
 *    Should MINSANTE ever adopt a national acts nomenclature, THAT is the
 *    French-language procedure list for this platform — not France's.
 *
 * 4. ICD-10-PCS (US CMS, public domain, ~78,000 codes) — a US inpatient
 *    billing classification with a 7-character combinatorial grammar built
 *    around US hospital reimbursement. Public domain is a licence, not a
 *    justification. An inappropriate 78,000-row US billing classification is
 *    worse than an empty gap.
 *
 * ── Why ICHI ─────────────────────────────────────────────────────────────
 * ICHI is WHO's own classification, a member of the WHO Family of
 * International Classifications alongside ICD and ICF, and it exists
 * precisely for countries that have no national procedure classification of
 * their own — which is Cameroon's situation. It is not a billing nomenclature
 * and carries no tariff. Its scope is broader than surgery: it covers
 * interventions on body systems, on activities and participation, on the
 * environment, and on health-related behaviours, which fits a primary-care
 * and public-health platform far better than an operating-theatre code set.
 *
 * ── Where the data comes from, honestly ──────────────────────────────────
 * WHO's ICD API at `id.who.int` returns 401 without a client id, so it is not
 * a path this importer can take. WHO's own ICHI browser, however, answers
 * without any credential at all:
 *
 *   https://icd.who.int/dev11/l-ichi/en/JsonGetRootConcepts?useHtml=false
 *   https://icd.who.int/dev11/l-ichi/en/JsonGetChildrenConcepts?...&ConceptId=...
 *
 * Both return 200 and real JSON anonymously. That is WHO's publication, read
 * through WHO's own front end.
 *
 * BE CLEAR ABOUT WHAT THIS IS NOT: `dev11` is WHO's maintenance platform, not
 * a dated release download. The page states only "Last Update: Sep 07" — there
 * is no release identifier to record. So `source_version` is written as the
 * harvest date, not as a version number this importer would have had to
 * invent. ICHI is still maturing; WHO's own browser text says the ICD-11
 * statistical core is derived from the Foundation and "ICF and ICHI will
 * follow". A re-run replaces the rows and re-dates them.
 *
 * ── English only, deliberately ───────────────────────────────────────────
 * `/l-ichi/fr/...` answers 200 but every label comes back as
 * "… [No translation available]"; `/l-ichi/es/...` returns literal `null`.
 * WHO has not published a French ICHI. So `display_fr` is left NULL on every
 * row. Machine-translating an intervention title would produce a French
 * surgical term nobody published, in a country where French is regulatory —
 * absent beats wrong. Francophone users see the English title, exactly as
 * they already do for the 84 ICD-10 U-chapter codes CIM-10 FR does not carry.
 *
 * ── Kind, and why it is not `term` or `category` ─────────────────────────
 * `ClinicalCodeDictionary::assignable()` offers `kind IN ('category','term')`.
 * Those are diagnoses (ICD-10 categories) and lab terms. An intervention is
 * neither, and an appendicectomy must never be selectable where a diagnosis
 * belongs, so every row here is written with `kind = 'procedure'` and is
 * invisible to `assignable()` by construction. `ProcedureClassificationTest`
 * pins that.
 *
 * ICHI's intermediate groupings ("Interventions on brain") are `block`s in
 * WHO's tree and carry NO code at all — there is nothing to key a row on, so
 * they are counted and reported, not stored under a fabricated code. Only
 * coded `category` entries become rows. `parent_code` therefore points at the
 * nearest CODED ancestor — and on the 2026-09-08 harvest that was NULL on all
 * 8,923 rows, because in ICHI every ancestor of an intervention is a codeless
 * block. The column is filled from the walk rather than left unwired so that
 * a future ICHI release which does code its groupings is picked up; it is not
 * back-filled with a guess in the meantime.
 *
 * The second root chapter, "ICHI Extension Code", is skipped. Extension codes
 * modify an intervention; they are not interventions, and filing them beside
 * one under `kind = 'procedure'` would make the table lie — the same scoping
 * decision `ImportRoutesOfAdministration` made when it took EDQM's `ROA`
 * class and left the dose forms alone.
 *
 * ── Why this fetches in parallel ─────────────────────────────────────────
 * The browser exposes children one parent at a time; there is no bulk export
 * behind it. Measured, a single request to this host costs ~0.85s, and the
 * walk visits well over a thousand internal nodes — sequentially that is
 * twenty minutes of mostly waiting. Requests are issued in small concurrent
 * batches instead. The batch size is deliberately modest: this is WHO's
 * public browser and the polite ceiling matters more than the minute saved.
 *
 * Run:  php artisan opescare:import-procedures --dry-run
 */
class ImportProcedureClassification extends Command
{
    protected $signature = 'opescare:import-procedures
        {--dry-run : Walk the classification and report, write nothing}
        {--concurrency=8 : Requests in flight against WHO at once}';

    protected $description = 'Import the WHO ICHI health interventions classification into the clinical code dictionary';

    private const BROWSER = 'https://icd.who.int/dev11/l-ichi/en';

    /** The `system` value every row this importer writes is scoped to. */
    private const SYSTEM = 'ICHI';

    private const SOURCE = 'WHO ICHI (icd.who.int/dev11/l-ichi)';

    /**
     * The root chapter to walk. WHO's ICHI linearization has two roots and
     * only this one holds interventions — see the note on extension codes.
     */
    private const CATEGORY_ROOT_LABEL = 'ICHI Category';

    /**
     * The block INSIDE that chapter that holds interventions.
     *
     * "ICHI Category" has a second child, "Health Intervention Axes", and it
     * is not a list of interventions — it is the three value lists ICHI is
     * BUILT from: Target, Action, Means. Their members are single tokens
     * ("ZZY Other specified target"), and a walk that starts at the chapter
     * sweeps 1,619 of them up alongside real interventions. They are axis
     * values, not things done to a patient, so the walk starts one level
     * lower. Same reasoning as the extension codes.
     */
    private const INTERVENTIONS_BLOCK_LABEL = 'Health interventions';

    /**
     * An ICHI stem code: three letters for the target, then two-character
     * groups for the action and the means — AAN.AD.AA.
     *
     * The dot is REQUIRED. Matching bare `[A-Z]{3}` would read the "HIV" of
     * an untitled "HIV testing" as a code, which is exactly the class of
     * silent fabrication this dictionary must not contain. A `category` whose
     * label does not match is counted and skipped, never guessed at.
     */
    private const CODE_PATTERN = '/^([A-Z]{3}(?:\.[A-Z0-9]{2}){1,3})\s+(\S.*)$/';

    /**
     * Refuse to write below this many interventions.
     *
     * ICHI publishes several thousand. A harvest that comes back with dozens
     * means the browser changed shape or started refusing the walk part-way,
     * and half a classification written over a whole one is worse than a
     * failed command.
     */
    private const SANITY_FLOOR = 3000;

    /** code => row, first sighting wins (ICHI allows multiple parents). */
    private array $rows = [];

    private int $blocksWithoutCode = 0;

    private int $categoriesWithoutParsableCode = 0;

    private array $unparsable = [];

    public function handle(): int
    {
        $roots = $this->children(null);

        if ($roots === []) {
            $this->error('WHO returned no root concepts. ' . self::BROWSER . ' is unreachable or has changed shape.');

            return self::FAILURE;
        }

        $this->line('  roots offered by WHO: ' . implode(', ', array_map(
            fn (array $r) => trim($r['label']),
            $roots,
        )));

        $start = null;

        foreach ($roots as $root) {
            if (str_contains($root['label'], self::CATEGORY_ROOT_LABEL)) {
                $start = $root;
            }
        }

        if ($start === null) {
            $this->error('The "' . self::CATEGORY_ROOT_LABEL . '" root is gone. Refusing to guess which root holds interventions.');

            return self::FAILURE;
        }

        $interventions = null;

        foreach ($this->children($start['id']) as $child) {
            if (trim($child['label']) === self::INTERVENTIONS_BLOCK_LABEL) {
                $interventions = $child;
            }
        }

        if ($interventions === null) {
            $this->error('The "' . self::INTERVENTIONS_BLOCK_LABEL . '" block is gone. '
                . 'Refusing to walk the whole chapter, which would sweep in the Target/Action/Means axis values.');

            return self::FAILURE;
        }

        $this->line('  walking "' . trim($interventions['label']) . '" — extension codes and the axis value lists are skipped on purpose');
        $this->newLine();

        $this->walk([['id' => $interventions['id'], 'parent_code' => null]]);

        $this->newLine(2);
        $this->line(sprintf(
            '  %s interventions harvested, %s codeless group headings passed over, %s coded-category labels unparsable',
            number_format(count($this->rows)),
            number_format($this->blocksWithoutCode),
            number_format($this->categoriesWithoutParsableCode),
        ));

        // Named, not swallowed, and GROUPED BY LEADING TOKEN — a flat sample
        // of ten cannot tell you whether the skipped rows are WHO's own
        // placeholders or a real code format this importer stopped reading.
        // Measured on the 2026-09-08 harvest, every one of the 742 is a
        // placeholder WHO has not assigned a code to: `??.ZY.ZY` / `??.ZZ.ZZ`
        // (the target wildcard) and `_LDINCOMPLETE` (WHO's own marker for a
        // category still being drafted). None is importable. If a token that
        // looks like a real code ever shows up in this tally, the pattern —
        // not the data — is what needs changing.
        $tally = array_count_values(array_map(
            fn (string $label) => Str::before($label, ' '),
            $this->unparsable,
        ));
        arsort($tally);

        foreach (array_slice($tally, 0, 10, true) as $token => $count) {
            $this->warn(sprintf('    skipped %6s x  leading token "%s"', number_format($count), $token));
        }

        // A skipped label whose first token still LOOKS like a stem code means
        // the pattern above has gone stale and real interventions are being
        // dropped silently. That is the one failure this importer could not
        // otherwise see, so it is fatal rather than a warning.
        $codeShaped = array_filter(
            array_keys($tally),
            fn (string $token) => (bool) preg_match('/^[A-Z]{3}\./', $token),
        );

        if ($codeShaped !== []) {
            $this->error('Refusing to write: these skipped labels start with something code-shaped, so the '
                . 'code pattern has stopped matching WHO\'s format — ' . implode(', ', array_slice($codeShaped, 0, 5)));

            return self::FAILURE;
        }

        if (count($this->rows) < self::SANITY_FLOOR) {
            $this->error(sprintf(
                'Refusing to write: %s interventions is below the floor of %s. The walk did not complete.',
                number_format(count($this->rows)),
                number_format(self::SANITY_FLOOR),
            ));

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run — nothing written. Sample:');

            foreach (array_slice($this->rows, 0, 5) as $row) {
                $this->line(sprintf('    %-16s %s', $row['code'], $row['display']));
            }

            return self::SUCCESS;
        }

        return $this->write();
    }

    /**
     * Breadth-first over the tree, a level at a time so each level's requests
     * can go out together.
     *
     * `$frontier` carries each node's nearest coded ancestor down with it,
     * because ICHI's group levels have no code of their own to inherit.
     */
    private function walk(array $frontier): void
    {
        $visited = [];

        while ($frontier !== []) {
            $next = [];
            $responses = $this->childrenOfMany(array_column($frontier, 'id'));

            foreach ($frontier as $node) {
                foreach ($responses[$node['id']] ?? [] as $child) {
                    $label = trim($child['label']);
                    $childCode = null;

                    if ($child['kind'] === 'category' && preg_match(self::CODE_PATTERN, $label, $m)) {
                        $childCode = $m[1];

                        $this->rows[$childCode] ??= [
                            'code' => $childCode,
                            'display' => $m[2],
                            'parent_code' => $node['parent_code'],
                        ];
                    } elseif ($child['kind'] === 'category') {
                        $this->categoriesWithoutParsableCode++;
                        $this->unparsable[] = $label;
                    } else {
                        // A block or chapter. ICHI does not code these, so
                        // there is no row to write — only a level to descend.
                        $this->blocksWithoutCode++;
                    }

                    if ($child['leaf'] || isset($visited[$child['id']])) {
                        continue;
                    }

                    $visited[$child['id']] = true;
                    $next[] = [
                        'id' => $child['id'],
                        'parent_code' => $childCode ?? $node['parent_code'],
                    ];
                }
            }

            $this->line(sprintf(
                '  level done — %s interventions so far, %s nodes to expand next',
                number_format(count($this->rows)),
                number_format(count($next)),
            ));

            $frontier = $next;
        }
    }

    /** Upsert on (system, code). Re-running replaces a release in place. */
    private function write(): int
    {
        $now = now();
        $version = 'harvested ' . $now->toDateString();
        $written = 0;

        $bar = $this->output->createProgressBar(count($this->rows));

        foreach (array_chunk(array_values($this->rows), 500) as $chunk) {
            $payload = [];

            foreach ($chunk as $row) {
                $payload[] = [
                    'id' => (string) Str::uuid(),
                    'system' => self::SYSTEM,
                    'code' => $row['code'],
                    'display' => $row['display'],
                    // WHO publishes no French ICHI. Left NULL, never guessed.
                    'display_fr' => null,
                    'short_name' => null,
                    // NOT 'category' and NOT 'term' — see the class doc-block.
                    'kind' => 'procedure',
                    'parent_code' => $row['parent_code'],
                    // ICHI's group levels are codeless, so there is no chapter
                    // code to record. NULL rather than an invented one.
                    'chapter' => null,
                    'component' => null,
                    'property' => null,
                    'scale' => null,
                    'specimen' => null,
                    'example_units' => null,
                    'synonyms' => null,
                    // No publisher-defined "common interventions" subset
                    // exists, so nothing here claims membership of one.
                    'is_common' => false,
                    'common_reason' => null,
                    'is_active' => true,
                    'source' => self::SOURCE,
                    'source_version' => $version,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('clinical_code_dictionary')->upsert(
                $payload,
                ['system', 'code'],
                ['display', 'kind', 'parent_code', 'is_active', 'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($payload);
            $bar->advance(count($payload));
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('%s ICHI interventions in the dictionary (%s).', number_format($written), $version));
        $this->line('  display_fr is NULL on every row — WHO has not published a French ICHI.');

        return self::SUCCESS;
    }

    /** The root concepts, or the children of one node. */
    private function children(?string $conceptId): array
    {
        if ($conceptId === null) {
            return $this->decode($this->get(self::BROWSER . '/JsonGetRootConcepts?useHtml=false'));
        }

        return $this->childrenOfMany([$conceptId])[$conceptId] ?? [];
    }

    /**
     * Children of many nodes at once, keyed by concept id.
     *
     * curl_multi rather than file_get_contents purely for the concurrency —
     * see the note on request cost in the class doc-block.
     */
    protected function childrenOfMany(array $conceptIds): array
    {
        $out = [];

        foreach (array_chunk($conceptIds, max(1, (int) $this->option('concurrency'))) as $batch) {
            $multi = curl_multi_init();
            $handles = [];

            foreach ($batch as $id) {
                $ch = curl_init(self::BROWSER . '/JsonGetChildrenConcepts?' . http_build_query([
                    'useHtml' => 'false',
                    'ConceptId' => $id,
                ]));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);
                curl_multi_add_handle($multi, $ch);
                $handles[$id] = $ch;
            }

            do {
                curl_multi_exec($multi, $running);
                curl_multi_select($multi, 1.0);
            } while ($running > 0);

            foreach ($handles as $id => $ch) {
                $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                $body = curl_multi_getcontent($ch);
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);

                // A failed branch is a HOLE in the classification, not a
                // slow row. It must stop the run, because the sanity floor
                // cannot see the difference between "ICHI is small" and
                // "half of ICHI never arrived".
                if ($status !== 200) {
                    throw new RuntimeException("WHO returned HTTP {$status} for {$id}; the walk is incomplete.");
                }

                $out[$id] = $this->decode($body);
            }

            curl_multi_close($multi);
        }

        return $out;
    }

    /** WHO's node list, reduced to the four fields this importer uses. */
    private function decode(string|false|null $body): array
    {
        if (! is_string($body) || $body === '') {
            throw new RuntimeException('Empty response from ' . self::BROWSER);
        }

        $decoded = json_decode($body, true);

        // A leaf legitimately answers `null`. That is no children, not a fault.
        if ($decoded === null) {
            return [];
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Unexpected response shape from ' . self::BROWSER);
        }

        return array_values(array_map(fn (array $n) => [
            'id' => (string) ($n['ID'] ?? ''),
            'label' => html_entity_decode((string) ($n['label'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'kind' => (string) ($n['classKind'] ?? ''),
            'leaf' => (bool) ($n['isLeaf'] ?? false),
        ], $decoded));
    }

    /** A single GET. Anything but a 200 is fatal — see childrenOfMany(). */
    private function get(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status !== 200 || ! is_string($body)) {
            throw new RuntimeException("WHO returned HTTP {$status} for {$url}");
        }

        return $body;
    }
}
