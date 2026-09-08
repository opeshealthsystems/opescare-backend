<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fills `display_fr` on the dictionary vocabularies that were still
 * English-only, using ONLY French text a real terminology publisher published.
 *
 * ── Why this command exists ──────────────────────────────────────────────
 * The dictionary is bilingual by regulation, not by decoration. ICD-10, ATC
 * and the EDQM routes already carry publisher French (see ImportIcd10French
 * and ImportRoutesOfAdministration). Four vocabularies did not: LOINC,
 * HL7 service-type, UCUM, CVX — plus the SNOMED CT specialty subset. A
 * French-speaking clinician reads English on every one of them.
 *
 * The one rule that governs everything below: **no clinical term is ever
 * machine-translated or invented.** A guessed lab-test title is a wrong lab-
 * test title, and this table is exchanged with partner systems, so a wrong
 * title travels. Where no publisher offers French, `display_fr` stays NULL
 * and the interface falls back to the English title. That fallback is correct
 * behaviour, not a bug, and a row left in English is a fine outcome.
 *
 * ── What was probed, and what each source actually returned ──────────────
 *
 * FILLED — LOINC, from tx.fhir.org (HL7's public FHIR terminology server),
 *   LOINC 2.82, French linguistic variant. Regenstrief publishes LOINC's
 *   French variant (the fr-FR translation contributed by ANS / CHU de Rouen)
 *   as part of the release, and tx.fhir.org serves it for free without a key.
 *   `2965-2` comes back as "Densité [Densité relative] Urine ; Numérique".
 *   The French national server does NOT carry LOINC — a `CodeSystem?url=
 *   http://loinc.org` search there returns total 0, and none of its 358
 *   CodeSystems is LOINC — so tx.fhir.org is the source, not SMT.
 *
 *   THE FALLBACK TRAP, and how it is closed. $expand with
 *   `displayLanguage=fr` silently returns the ENGLISH display for any concept
 *   the French variant does not cover — `33720-4` comes back as "Blood bank
 *   consult" under `fr`. Taking that at face value would stamp English text
 *   into a column that claims to be French. And the designations are no help:
 *   the server tags its English designations `en-US` and returns the French
 *   only as the top-level `display`, with no `fr` designation to key on.
 *
 *   So every batch is expanded TWICE — once `displayLanguage=fr`, once
 *   `displayLanguage=en-US` — and a title is accepted only when the two
 *   differ. Comparing against the same server and the same LOINC version, not
 *   against our stored English (which came from NLM at LOINC 2.78 and can
 *   differ by wording alone), is what makes that test airtight.
 *
 * FILLED — SNOMED CT specialties, from the French national terminology server
 *   run by the Agence du Numérique en Santé, https://smt.esante.gouv.fr/fhir,
 *   SNOMED CT French edition module 11000315107 (version 20260621). Its
 *   `CodeSystem/$lookup` returns real French designations for the practice
 *   codes we hold — 394577000 is "anesthésie", 421661004 is "banques de sang
 *   et médecine transfusionnelle (spécialité)". Only a designation whose
 *   language is `fr` (or an `fr-x-sctlang-…` dialect) AND whose use is
 *   `preferredForLanguage` is taken; the module's top-level `display` is
 *   ignored because it falls back to English exactly as LOINC's does.
 *
 * NO FRENCH EXISTS — UCUM. Probed three ways, all negative:
 *   • UCUM's own release, ucum-essence.xml 2.2 (2024-06-17), the very file
 *     the English names were imported from: it contains ZERO `xml:lang`
 *     attributes and no non-English `<name>` anywhere. UCUM publishes one
 *     name per unit, in English.
 *   • SMT does not carry UCUM (`CodeSystem?url=http://unitsofmeasure.org`
 *     returns total 0).
 *   • tx.fhir.org's UCUM `$lookup` with `displayLanguage=fr` returns the code
 *     itself as the display ("mg") and only `en` designations.
 *   UCUM units are symbols, not prose, so this is unsurprising rather than a
 *   gap — but it is recorded here so nobody probes it a fourth time.
 *
 * NO FRENCH EXISTS — HL7 service-type. SMT does host this CodeSystem
 *   (ServiceType 1.1.0) but hosts HL7's text verbatim: its 596 concepts carry
 *   `code`, `display` and `definition` and not one `designation`, in any
 *   language. tx.fhir.org returns the identical English string for `105`
 *   under `displayLanguage=fr` and under `en`. HL7 has never translated this
 *   value set (it originates from an Australian service directory), so there
 *   is no French to import. Translating 596 service names ourselves is
 *   exactly the invention this command refuses.
 *
 * NO FRENCH EXISTS — CVX. CDC's vaccine code set is a US national code set
 *   and CDC publishes it in English only. SMT does not carry it
 *   (`CodeSystem?url=http://hl7.org/fhir/sid/cvx` returns total 0), and
 *   tx.fhir.org under `displayLanguage=fr` returns "DTaP" — the English short
 *   name, not French. France's own vaccine vocabularies (NUVA,
 *   Catalogue_Vaccins) ARE French but use their own codes, and SMT's four
 *   ConceptMaps map NUVA to ATC/CIS/CIP, never to CVX. Bridging CVX to NUVA
 *   by product name would be a clinical mapping invented here, so it was not
 *   attempted.
 *
 * ── What this command will not do ────────────────────────────────────────
 * It adds NO codes: every write is an UPDATE matched on (system, code)
 * against rows that already exist. It writes NO column but `display_fr` and
 * `updated_at` — and does not merely intend to: a digest over every other
 * column is taken before and after inside one transaction, and the whole
 * write is rolled back if the digest moves. It also refuses to write at all
 * when a source returns implausibly little, so a source that changes shape
 * fails loudly rather than quietly filling nothing.
 *
 * Idempotent: re-running rewrites the same French over itself.
 *
 * Run:  php artisan opescare:import-french-titles
 *       php artisan opescare:import-french-titles --dry-run
 *       php artisan opescare:import-french-titles --only=loinc
 */
class ImportFrenchTitles extends Command
{
    protected $signature = 'opescare:import-french-titles
        {--dry-run : Fetch and report, write nothing}
        {--only= : Restrict to one vocabulary: loinc or snomed}';

    protected $description = 'Fill display_fr from publisher French for the vocabularies that were English-only';

    /** HL7's public FHIR terminology server — serves the LOINC French variant. */
    private const TX_SERVER = 'https://tx.fhir.org/r4';

    /** The French national terminology server (Agence du Numérique en Santé). */
    private const SMT_SERVER = 'https://smt.esante.gouv.fr/fhir';

    private const LOINC_SYSTEM = 'http://loinc.org';

    private const SNOMED_SYSTEM = 'http://snomed.info/sct';

    /** SNOMED CT French edition module, as published by ANS. */
    private const SNOMED_FR_VERSION = 'http://snomed.info/sct/11000315107/version/20260621';

    /** Codes per $expand request. Two requests per batch (fr and en-US). */
    private const LOINC_BATCH = 400;

    /**
     * Floors below which a harvest is treated as a broken source rather than
     * a thin one. The French LOINC variant covered ~7,900 of our 19,314 rows
     * and the French SNOMED module all 115 when this was written; a collapse
     * to a fraction of that means the server changed shape, and a broken
     * source must fail loudly, not silently write nothing.
     */
    private const FLOORS = ['LOINC' => 3000, 'SNOMED-CT' => 80];

    /**
     * Every column that must come out of this command byte-identical. Kept as
     * an explicit list rather than "all except display_fr" so that a column
     * added to the table later is noticed here deliberately.
     */
    private const IMMUTABLE_COLUMNS = [
        'id', 'system', 'code', 'display', 'short_name', 'kind', 'parent_code',
        'chapter', 'component', 'property', 'scale', 'specimen', 'example_units',
        'synonyms', 'is_common', 'common_reason', 'is_active', 'source',
        'source_version', 'imported_at', 'created_at',
    ];

    public function handle(): int
    {
        $only = $this->option('only');
        $harvests = [];

        if ($only === null || $only === 'loinc') {
            $harvests['LOINC'] = $this->harvestLoinc();
        }

        if ($only === null || $only === 'snomed') {
            $harvests['SNOMED-CT'] = $this->harvestSnomed();
        }

        if ($harvests === []) {
            $this->error('--only must be "loinc" or "snomed".');

            return self::FAILURE;
        }

        foreach ($harvests as $system => $titles) {
            $floor = self::FLOORS[$system];

            if (count($titles) < $floor) {
                $this->error("Refusing to write: only " . count($titles) . " French {$system} titles harvested, "
                    . "expected at least {$floor}. The published source has changed shape — this is a source "
                    . 'failure, not an empty vocabulary.');

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run — nothing written.');
            $this->reportCoverage(array_keys($harvests), $harvests);

            return self::SUCCESS;
        }

        $before = $this->immutableDigest();

        DB::beginTransaction();

        try {
            foreach ($harvests as $system => $titles) {
                $this->write($system, $titles);
            }

            $after = $this->immutableDigest();

            if ($after !== $before) {
                // The guard, not a comment claiming to be one. If this command
                // ever touches a column that is not display_fr, the whole write
                // is discarded and somebody is told, loudly.
                DB::rollBack();

                $this->error('ABORTED AND ROLLED BACK: a column other than display_fr changed. '
                    . 'Digest before ' . $before . ', after ' . $after . '. Nothing was written.');

                return self::FAILURE;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $this->reportCoverage(array_keys($harvests), $harvests);

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────── LOINC ────

    /**
     * French LOINC long names for the codes we hold, from tx.fhir.org.
     *
     * @return array<string, string>
     */
    private function harvestLoinc(): array
    {
        $codes = DB::table('clinical_code_dictionary')
            ->where('system', 'LOINC')
            ->orderBy('code')
            ->pluck('code')
            ->all();

        if ($codes === []) {
            $this->warn('  no LOINC codes held — nothing to translate');

            return [];
        }

        $this->line('  LOINC: ' . count($codes) . ' codes held, asking ' . self::TX_SERVER
            . ' for the French linguistic variant');

        $french = [];
        $englishFallbacks = 0;
        $version = null;
        $batches = array_chunk($codes, self::LOINC_BATCH);
        $bar = $this->output->createProgressBar(count($batches));

        foreach ($batches as $batch) {
            // Twice, deliberately — see the fallback trap in the doc-block.
            $fr = $this->expandLoinc($batch, 'fr');
            $en = $this->expandLoinc($batch, 'en-US');
            $version ??= $fr['version'];

            foreach ($fr['displays'] as $code => $display) {
                $englishForCode = $en['displays'][$code] ?? null;

                if ($englishForCode === null || $display === '' || $display === $englishForCode) {
                    // Identical under both languages: the French variant does
                    // not cover this concept and the server handed back its
                    // English. Not French, so not written.
                    $englishFallbacks++;
                    continue;
                }

                $french[$code] = $display;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->line(sprintf(
            '  LOINC %s: %d codes carry a French title, %d fell back to English and were declined',
            $version ?? 'unknown version',
            count($french),
            $englishFallbacks,
        ));

        return $french;
    }

    /**
     * One $expand of an explicit code list in one display language.
     *
     * POST with an inline ValueSet rather than GET, because the list of codes
     * is ours and there is no published ValueSet that is "the LOINC codes this
     * deployment happens to hold".
     *
     * @param  list<string>  $codes
     * @return array{displays: array<string, string>, version: ?string}
     */
    private function expandLoinc(array $codes, string $language): array
    {
        $parameters = [
            'resourceType' => 'Parameters',
            'parameter' => [
                ['name' => 'valueSet', 'resource' => [
                    'resourceType' => 'ValueSet',
                    'compose' => ['include' => [[
                        'system' => self::LOINC_SYSTEM,
                        'concept' => array_map(static fn (string $c): array => ['code' => $c], $codes),
                    ]]],
                ]],
                ['name' => 'displayLanguage', 'valueCode' => $language],
                ['name' => 'count', 'valueInteger' => count($codes) + 10],
            ],
        ];

        $decoded = $this->request(
            self::TX_SERVER . '/ValueSet/$expand',
            json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        if (! isset($decoded['expansion'])) {
            throw new RuntimeException('tx.fhir.org returned no expansion for a batch of '
                . count($codes) . ' LOINC codes (displayLanguage=' . $language . ')');
        }

        $displays = [];

        foreach ($decoded['expansion']['contains'] ?? [] as $entry) {
            $code = trim((string) ($entry['code'] ?? ''));
            $display = trim((string) ($entry['display'] ?? ''));

            if ($code !== '' && $display !== '') {
                $displays[$code] = $display;
            }
        }

        $version = null;

        foreach ($decoded['expansion']['parameter'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) === 'used-codesystem') {
                $version = (string) ($parameter['valueUri'] ?? '');
            }
        }

        return ['displays' => $displays, 'version' => $version];
    }

    // ────────────────────────────────────────────────────────── SNOMED ────

    /**
     * French designations for the SNOMED CT specialty concepts we hold.
     *
     * @return array<string, string>
     */
    private function harvestSnomed(): array
    {
        $codes = DB::table('clinical_code_dictionary')
            ->where('system', 'SNOMED-CT')
            ->orderBy('code')
            ->pluck('code')
            ->all();

        if ($codes === []) {
            $this->warn('  no SNOMED-CT codes held — nothing to translate');

            return [];
        }

        $this->line('  SNOMED-CT: ' . count($codes) . ' concepts held, asking ' . self::SMT_SERVER
            . ' (French edition module 11000315107)');

        $french = [];
        $noFrench = 0;
        $bar = $this->output->createProgressBar(count($codes));

        // One $lookup per concept. 115 concepts, so no batching is needed and
        // $lookup is the only operation that returns designations by language.
        foreach ($codes as $code) {
            $preferred = $this->frenchPreferredTerm($code);

            if ($preferred === null) {
                $noFrench++;
            } else {
                $french[$code] = $preferred;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->line(sprintf(
            '  SNOMED CT FR (20260621): %d concepts carry a French preferred term, %d carry none',
            count($french),
            $noFrench,
        ));

        return $french;
    }

    /**
     * The French preferred term for one SNOMED concept, or null.
     *
     * Only a `designation` whose language is French and whose use is
     * `preferredForLanguage` counts. The response's top-level `display` is
     * NOT used: the module falls back to English for a concept it has not
     * translated, and an English string in display_fr is the failure this
     * command exists to avoid.
     */
    private function frenchPreferredTerm(string $code): ?string
    {
        $url = self::SMT_SERVER . '/CodeSystem/$lookup?' . http_build_query([
            'system' => self::SNOMED_SYSTEM,
            'code' => $code,
            'version' => self::SNOMED_FR_VERSION,
            'property' => 'designation',
        ]);

        $decoded = $this->request($url);

        foreach ($decoded['parameter'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) !== 'designation') {
                continue;
            }

            $language = null;
            $use = null;
            $value = null;

            foreach ($parameter['part'] ?? [] as $part) {
                match ($part['name'] ?? '') {
                    'language' => $language = strtolower((string) ($part['valueCode'] ?? '')),
                    'use' => $use = (string) ($part['valueCoding']['code'] ?? ''),
                    'value' => $value = trim((string) ($part['valueString'] ?? '')),
                    default => null,
                };
            }

            if ($language === null || $value === null || $value === '') {
                continue;
            }

            // 'fr' and the module's own 'fr-x-sctlang-…' dialect tag both count.
            if (! str_starts_with($language, 'fr') || $use !== 'preferredForLanguage') {
                continue;
            }

            return $value;
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────── write ────

    /**
     * Write the French titles for one system.
     *
     * UPDATE only, matched on (system, code), so no row can be created and no
     * code added. One statement per chunk with a CASE, rather than thousands
     * of single updates.
     *
     * @param  array<string, string>  $titles
     */
    private function write(string $system, array $titles): void
    {
        foreach (array_chunk($titles, 500, true) as $chunk) {
            $case = 'CASE code';
            $bindings = [];

            foreach ($chunk as $code => $display) {
                $case .= ' WHEN ? THEN ?';
                $bindings[] = (string) $code;
                $bindings[] = $display;
            }

            $case .= ' END';

            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            DB::update(
                "UPDATE clinical_code_dictionary
                    SET display_fr = {$case}, updated_at = now()
                  WHERE system = ? AND code IN ({$placeholders})",
                array_merge($bindings, [$system], array_map(strval(...), array_keys($chunk))),
            );
        }
    }

    /**
     * A digest over every column this command must not touch, across the whole
     * table. Taken before and after the write; a change means the command did
     * something it promised not to, and the transaction is thrown away.
     */
    private function immutableDigest(): string
    {
        $columns = implode(", '|', ", array_map(
            static fn (string $c): string => "coalesce({$c}::text, '')",
            self::IMMUTABLE_COLUMNS,
        ));

        /** @var object{digest: ?string, rows: int} $row */
        $row = DB::selectOne(
            "SELECT md5(string_agg(line, E'\n' ORDER BY line)) AS digest, count(*) AS rows
               FROM (SELECT concat_ws('|', {$columns}) AS line FROM clinical_code_dictionary) t"
        );

        return ($row->digest ?? 'empty') . ':' . $row->rows;
    }

    // ────────────────────────────────────────────────────────── report ────

    /**
     * Per-vocabulary coverage read back from the database — every system, not
     * only the ones touched, so a regression anywhere is visible.
     *
     * @param  list<string>  $touched
     * @param  array<string, array<string, string>>  $harvests
     */
    private function reportCoverage(array $touched, array $harvests): void
    {
        $publishers = [
            'LOINC' => 'Regenstrief LOINC French linguistic variant via tx.fhir.org (HL7)',
            'SNOMED-CT' => 'SNOMED CT French edition module 11000315107 via SMT (ANS)',
            'ICD-10' => 'CIM-10 FR PMSI via SMT (ANS)',
            'ATC' => 'ATC French via SMT (ANS)',
            'EDQM-route' => 'EDQM Standard Terms via SMT (ANS)',
            'UCUM' => 'none — UCUM publishes English names only',
            'CVX' => 'none — CDC publishes CVX in English only',
            'HL7-service-type' => 'none — HL7 has never translated this value set',
        ];

        $rows = DB::table('clinical_code_dictionary')
            ->selectRaw('system, count(*) AS total, count(display_fr) AS french')
            ->groupBy('system')
            ->orderBy('system')
            ->get();

        $this->newLine();
        $this->table(
            ['vocabulary', 'rows', 'French', 'still English', 'French publisher'],
            $rows->map(static fn ($row): array => [
                $row->system . (in_array($row->system, $touched, true) ? ' *' : ''),
                $row->total,
                $row->french,
                $row->total - $row->french,
                $publishers[$row->system] ?? 'unknown',
            ])->all(),
        );

        $this->line('  * touched by this run. "still English" rows fall back to their English title on '
            . 'screen, which is correct: no publisher offers French for them.');

        foreach ($harvests as $system => $titles) {
            $this->info("  {$system}: " . count($titles) . ' French titles from ' . $publishers[$system]);
        }
    }

    // ──────────────────────────────────────────────────────── transport ────

    /**
     * One FHIR request. POST when a body is given, GET otherwise.
     *
     * @return array<string, mixed>
     */
    private function request(string $url, ?string $body = null): array
    {
        $options = [
            'timeout' => 180,
            'ignore_errors' => true,
            'header' => "Accept: application/fhir+json, application/json\r\n"
                . "User-Agent: OpesCare-Finder terminology importer\r\n"
                . ($body !== null ? "Content-Type: application/fhir+json\r\n" : ''),
        ];

        if ($body !== null) {
            $options['method'] = 'POST';
            $options['content'] = $body;
        }

        $response = @file_get_contents($url, false, stream_context_create(['http' => $options]));

        if ($response === false) {
            throw new RuntimeException('Could not reach ' . $url);
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Response from ' . $url . ' was not JSON');
        }

        return $decoded;
    }
}
