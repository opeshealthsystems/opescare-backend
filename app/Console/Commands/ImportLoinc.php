<?php

namespace App\Console\Commands;

use App\Support\Terminology\ClinicalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports LOINC terms into the clinical code dictionary.
 *
 * ── Where this gets LOINC from, and why not from loinc.org ───────────────
 * The full LOINC table on loinc.org sits behind a free account and a licence
 * click. A licence acceptance is the owner's to make, not a script's, so this
 * does not try to get past it. Instead it reads the NLM Clinical Table Search
 * Service, a US National Library of Medicine endpoint that publishes LOINC
 * openly with no key:
 *
 *   https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search
 *
 * The trade is real and worth stating: NLM exposes LOINC_NUM,
 * LONG_COMMON_NAME, SHORTNAME, COMPONENT, PROPERTY and RELATEDNAMES2, and
 * NOT the SYSTEM, CLASS, EXAMPLE_UNITS or STATUS columns. So `specimen`,
 * `example_units` and `scale` stay NULL on every row this importer writes.
 * They are left null rather than inferred: a guessed specimen on a lab code
 * is how a serum result gets filed as a urine one.
 *
 * If the owner downloads the licensed LOINC CSV, `--file` reads it instead
 * and those columns fill in.
 *
 * ── "Codes commonly used in Africa" ──────────────────────────────────────
 * Nobody publishes that list, and inventing one would be an opinion wearing
 * a dataset's clothes. What does exist, and is used here:
 *
 *   1. LOINC's OWN Universal Lab Orders value set (~1,500 terms) — the
 *      publisher's answer to "which orders actually recur everywhere".
 *      Identified by the UniversalLabOrders marker LOINC ships in
 *      RELATEDNAMES2, so this is LOINC's judgement, not mine.
 *
 *   2. The disease areas that dominate the burden across sub-Saharan Africa
 *      and drive the WHO AFRO / IDSR notifiable list: malaria, HIV, TB,
 *      hepatitis B and C, sickle cell, meningitis, cholera, typhoid,
 *      schistosomiasis, measles, yellow fever, syphilis, plus antenatal,
 *      newborn and nutrition testing.
 *
 * Every flagged row records WHICH of these put it there in `common_reason`,
 * so a reviewer can accept or reject the basis rather than the verdict.
 *
 * Run:  php artisan opescare:import-loinc
 */
class ImportLoinc extends Command
{
    protected $signature = 'opescare:import-loinc
        {--file= : A licensed LOINC CSV (Loinc.csv) to read instead of the NLM API}
        {--release=2.78 : The LOINC release being imported}
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import LOINC terms into the clinical code dictionary';

    private const API = 'https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search';

    private const FIELDS = 'LOINC_NUM,LONG_COMMON_NAME,SHORTNAME,COMPONENT,PROPERTY,RELATEDNAMES2';

    /**
     * NLM caps maxList at 1000, and there is NO usable paging past that.
     *
     * The endpoint accepts an `offset` parameter and appears to honour it —
     * but supplying it silently resets maxList to the default 7, so a paged
     * harvest quietly collects a seventh of each page after the first and
     * reports success. Measured, not assumed:
     *
     *     maxList=500 offset=0   -> 500 rows
     *     maxList=500 offset=500 ->   7 rows
     *
     * So each search term is capped at 1000 results, and a term that matches
     * more than that must be SUBDIVIDED into narrower terms rather than paged.
     * `harvest()` reports any term that hits the ceiling, so the shortfall is
     * visible instead of silent.
     */
    private const PAGE = 1000;

    /**
     * The Universal Lab Orders set is ~1,500 terms and one request returns
     * 1,000, so it is fetched in specimen-narrowed slices rather than paged.
     * The slices overlap; `harvest()` keeps the first sighting of a code.
     */
    private const UNIVERSAL_SLICES = [
        'UniversalLabOrders',
        'UniversalLabOrders serum',
        'UniversalLabOrders blood',
        'UniversalLabOrders urine',
        'UniversalLabOrders plasma',
    ];

    /** Search terms that matched more than one request could return: term => missing count. */
    private array $shortfalls = [];

    /**
     * The disease areas above, as the search terms that reach them.
     *
     * These are search terms, NOT codes. Nothing here asserts that a
     * particular LOINC number means a particular thing — LOINC's own index
     * decides what matches, and every code that comes back is check-digit
     * verified before it is stored.
     */
    private const BURDEN = [
        'malaria' => ['malaria', 'plasmodium', 'falciparum'],
        'hiv' => ['HIV', 'CD4', 'viral load HIV'],
        'tuberculosis' => ['tuberculosis', 'mycobacterium', 'GeneXpert', 'acid fast'],
        'hepatitis' => ['hepatitis B', 'hepatitis C', 'HBsAg'],
        'sickle cell' => ['sickle cell', 'haemoglobin electrophoresis', 'hemoglobin S'],
        'meningitis' => ['meningitis', 'neisseria meningitidis', 'cerebrospinal fluid culture'],
        'cholera and enteric' => ['cholera', 'vibrio', 'salmonella typhi', 'stool culture'],
        'parasitology' => ['schistosoma', 'helminth', 'ova and parasites', 'trypanosoma'],
        'vaccine preventable' => ['measles', 'yellow fever', 'poliovirus', 'rubella'],
        'syphilis and sti' => ['syphilis', 'treponema', 'chlamydia', 'gonorrhoeae'],
        'antenatal and newborn' => ['pregnancy test', 'rhesus', 'ABO', 'newborn screening', 'bilirubin'],
        'nutrition' => ['ferritin', 'vitamin A', 'zinc', 'albumin'],
        'core chemistry' => ['glucose', 'creatinine', 'urea', 'sodium', 'potassium'],
        'core haematology' => ['haemoglobin', 'leukocytes', 'platelets', 'CBC panel'],
    ];

    public function handle(): int
    {
        $release = (string) $this->option('release');
        $dry = (bool) $this->option('dry-run');

        // code => ['row' => [...], 'reason' => string|null]
        $collected = [];

        $file = $this->option('file');

        if ($file !== null) {
            return $this->importCsv((string) $file, $release, $dry);
        }

        foreach (self::UNIVERSAL_SLICES as $slice) {
            $this->harvest($slice, 'LOINC Universal Lab Orders', $collected);
        }

        $this->line(sprintf('  %-24s +%d', 'universal lab orders', count($collected)));

        foreach (self::BURDEN as $area => $terms) {
            $before = count($collected);

            foreach ($terms as $term) {
                $this->harvest($term, 'Disease burden: ' . $area, $collected);
            }

            $this->line(sprintf('  %-24s +%d', $area, count($collected) - $before));
        }

        $this->newLine();
        $this->line('  ' . count($collected) . ' distinct LOINC terms collected');

        if ($this->shortfalls !== []) {
            $this->newLine();
            $this->warn('These search terms matched more LOINC codes than one request can return. '
                . 'The dictionary is short by up to this many terms in each area — narrow the search '
                . 'term, or load the licensed release CSV with --file:');

            arsort($this->shortfalls);

            foreach (array_slice($this->shortfalls, 0, 12, true) as $term => $missing) {
                $this->line(sprintf('    %-34s ~%d not fetched', $term, $missing));
            }
        }

        if ($collected === []) {
            $this->error('Nothing collected — the NLM endpoint returned no usable rows.');

            return self::FAILURE;
        }

        if ($dry) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($collected as $entry) {
            $row = $entry['row'];
            $row['is_common'] = true;
            $row['common_reason'] = $entry['reason'];
            $row['source_version'] = $release;
            $rows[] = $row;
        }

        $written = 0;
        $bar = $this->output->createProgressBar(count($rows));

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'short_name', 'component', 'property', 'synonyms',
                    'is_common', 'common_reason', 'is_active', 'source', 'source_version',
                    'imported_at', 'updated_at'],
            );
            $written += count($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("LOINC {$release}: {$written} terms in the dictionary, all flagged common.");
        $this->warn('specimen, units and scale are NULL: the open NLM endpoint does not publish those '
            . 'columns. They are left empty rather than inferred — a guessed specimen on a lab code is '
            . 'how a serum result gets filed as a urine one. Load the licensed LOINC CSV with --file to fill them.');

        return self::SUCCESS;
    }

    /**
     * Import the licensed LOINC release CSV (Loinc.csv from a LoincTable zip).
     *
     * This is the complete table — every term, and crucially the columns the
     * open NLM endpoint does not publish: SYSTEM (the specimen), SCALE_TYP,
     * EXAMPLE_UCUM_UNITS and STATUS. Rows arrive here by column NAME, not
     * position, because LOINC has reordered that file between releases.
     *
     * Codes already in the dictionary keep the `is_common` flag and the reason
     * the API import gave them; the CSV fills in the missing detail and adds
     * the rest of the table as ordinary, unflagged reference rows.
     */
    private function importCsv(string $path, string $release, bool $dry): int
    {
        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            $this->error('Empty CSV.');
            fclose($handle);

            return self::FAILURE;
        }

        $at = array_flip(array_map(static fn ($h) => strtoupper(trim((string) $h)), $header));

        foreach (['LOINC_NUM', 'LONG_COMMON_NAME'] as $required) {
            if (! isset($at[$required])) {
                $this->error("Column {$required} is missing — this does not look like a LOINC release CSV.");
                fclose($handle);

                return self::FAILURE;
            }
        }

        $get = static function (array $r, string $col) use ($at): ?string {
            if (! isset($at[$col])) {
                return null;
            }

            $v = trim((string) ($r[$at[$col]] ?? ''));

            return $v === '' ? null : $v;
        };

        $batch = [];
        $written = 0;
        $rejected = 0;

        while (($r = fgetcsv($handle)) !== false) {
            $code = (string) ($r[$at['LOINC_NUM']] ?? '');

            if (! ClinicalCode::isLoinc(trim($code))) {
                $rejected++;
                continue;
            }

            $display = $get($r, 'LONG_COMMON_NAME') ?? $get($r, 'SHORTNAME');

            if ($display === null) {
                $rejected++;
                continue;
            }

            $batch[] = [
                'id' => (string) Str::uuid(),
                'system' => 'LOINC',
                'code' => trim($code),
                'display' => $display,
                'display_fr' => null,
                'short_name' => $get($r, 'SHORTNAME'),
                'kind' => 'term',
                'parent_code' => null,
                'chapter' => $get($r, 'CLASS'),
                'component' => $get($r, 'COMPONENT'),
                'property' => $get($r, 'PROPERTY'),
                'scale' => $get($r, 'SCALE_TYP'),
                // LOINC's SYSTEM axis IS the specimen — Ser/Plas, Urine, Bld.
                'specimen' => $get($r, 'SYSTEM'),
                'example_units' => $get($r, 'EXAMPLE_UCUM_UNITS') ?? $get($r, 'EXAMPLE_UNITS'),
                'synonyms' => $get($r, 'RELATEDNAMES2'),
                'is_common' => false,
                'common_reason' => null,
                'is_active' => strtoupper((string) $get($r, 'STATUS')) !== 'DEPRECATED',
                'source' => 'LOINC release CSV',
                'source_version' => $release,
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                $written += $this->writeCsvBatch($batch, $dry);
                $batch = [];
            }
        }

        fclose($handle);

        if ($batch !== []) {
            $written += $this->writeCsvBatch($batch, $dry);
        }

        $this->newLine();
        $this->info(($dry ? 'Would import ' : 'Imported ') . "{$written} LOINC terms from the release CSV.");

        if ($rejected > 0) {
            $this->warn("{$rejected} row(s) rejected: failed the LOINC check digit or carried no display name.");
        }

        return self::SUCCESS;
    }

    /**
     * Write one CSV batch.
     *
     * `is_common` and `common_reason` are deliberately absent from the update
     * list: the CSV knows the full table but nothing about which subset this
     * deployment cares about, so it must not clear a flag the API import set.
     */
    private function writeCsvBatch(array $batch, bool $dry): int
    {
        if ($dry) {
            return count($batch);
        }

        DB::table('clinical_code_dictionary')->upsert(
            $batch,
            ['system', 'code'],
            ['display', 'short_name', 'chapter', 'component', 'property', 'scale', 'specimen',
                'example_units', 'synonyms', 'is_active', 'source', 'source_version',
                'imported_at', 'updated_at'],
        );

        return count($batch);
    }

    /**
     * Page one search term into the collection.
     *
     * First reason wins: a code reached by Universal Lab Orders keeps that as
     * its reason even if a later disease sweep also matches it, because the
     * publisher's own designation is the stronger claim.
     */
    private function harvest(string $term, string $reason, array &$collected): void
    {
        $payload = $this->request($term);

        if ($payload === null) {
            return;
        }

        [$total, , , $rows] = $payload + [0, [], null, []];

        if (! is_array($rows) || $rows === []) {
            return;
        }

        // Say so when a term matches more than one request can return. The
        // missing terms are not lost data anybody can see from the row count,
        // and an unreported ceiling is how a partial dictionary passes for a
        // complete one.
        if (is_int($total) && $total > count($rows)) {
            $this->shortfalls[$term] = $total - count($rows);
        }

        foreach ($rows as $r) {
                [$code, $long, $short, $component, $property, $related] = array_pad((array) $r, 6, '');

                $code = trim((string) $code);

                // A LOINC number that fails its own check digit is not a LOINC
                // number, whoever served it. This is the same guard that caught
                // 70563-7 being offered for a malaria rapid test.
                if (! ClinicalCode::isLoinc($code)) {
                    continue;
                }

                if (isset($collected[$code])) {
                    continue;
                }

                $display = trim((string) $long) ?: trim((string) $short);

                if ($display === '') {
                    continue;
                }

                $collected[$code] = [
                    'reason' => $reason,
                    'row' => [
                        'id' => (string) Str::uuid(),
                        'system' => 'LOINC',
                        'code' => $code,
                        'display' => $display,
                        'display_fr' => null,
                        'short_name' => trim((string) $short) ?: null,
                        'kind' => 'term',
                        'parent_code' => null,
                        'chapter' => null,
                        'component' => trim((string) $component) ?: null,
                        'property' => trim((string) $property) ?: null,
                        'scale' => null,
                        'specimen' => null,
                        'example_units' => null,
                        'synonyms' => trim((string) $related) ?: null,
                        'is_common' => true,
                        'common_reason' => $reason,
                        'is_active' => true,
                        'source' => 'NLM Clinical Table Search Service (LOINC)',
                        'source_version' => null,
                        'imported_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
        }
    }

    /** One request to NLM. A failure is reported and skipped, never faked. */
    private function request(string $term): ?array
    {
        $url = self::API . '?' . http_build_query([
            'terms' => $term,
            'maxList' => self::PAGE,
            'df' => self::FIELDS,
        ]);

        $context = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $this->warn("  request failed: {$term}");

            return null;
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            // NLM answers a bad offset with plain text, not JSON. That is a
            // stop condition for this term, not an error worth shouting about.
            return null;
        }

        return $decoded;
    }
}
