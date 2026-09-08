<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the WHO ATC medicine classification into the clinical code
 * dictionary — the vocabulary that says a prescription is a "J01CA04
 * amoxicillin", not a free-text drug name somebody typed.
 *
 * ── Why these two sources, and what was rejected ─────────────────────────
 * WHO's ATC/DDD Index (whocc.no) is browsable but publishes no bulk file and
 * no API; the full ATC index is licensed content and scraping the browse pages
 * would be taking data the publisher chose not to hand out. So it is not used.
 *
 * What IS used — two open, keyed-by-nobody government endpoints:
 *
 *   1. FRENCH TITLES + THE COMPLETE CODE LIST — the French national
 *      terminology server run by the Agence du Numérique en Santé, the same
 *      server the ICD-10 French titles come from:
 *
 *        https://smt.esante.gouv.fr/fhir  —  CodeSystem `terminologie-atc`
 *        ("Classification anatomique, thérapeutique et chimique")
 *
 *      ~7,000 codes, every level, official French titles published by a health
 *      ministry. This is the spine of the import.
 *
 *   2. ENGLISH TITLES FOR LEVELS 1–4 — NLM's RxClass, which republishes the
 *      WHO ATC class names in English:
 *
 *        https://rxnav.nlm.nih.gov/REST/rxclass/allClasses.json?classTypes=ATC1-4
 *
 *      RxClass carries the ~1,300 CLASS names (levels 1–4) and does NOT carry
 *      level-5 substance names, so English stops at level 4. That gap is left
 *      as a gap.
 *
 * REJECTED: `clinicaltables.nlm.nih.gov/api/rxterms` answers 200 and is
 * tempting, but RxTerms is a US RxNorm prescribing list — brand and dose-form
 * strings, not ATC codes. Loading it under an ATC label would mislabel a whole
 * vocabulary, which is worse than not having one.
 *
 * ── What `display` holds, precisely ──────────────────────────────────────
 * `display` is the English title where an open English title exists (levels
 * 1–4, from RxClass) and otherwise the official FRENCH title from ANS. It is
 * never a translation of the other: no title in this table was produced by
 * machine, and a level-5 substance with no published English name keeps its
 * French one rather than an invented English one. `display_fr` always holds
 * the ANS French title, so the French interface is complete and the English
 * one falls back to French on level-5 substances — most of which are INN names
 * that read the same in both languages, but that is an observation, not a
 * licence to translate the rest. The command reports the exact counts.
 *
 * ── Structure is read from the code, not guessed ─────────────────────────
 * ATC code length IS the level, by definition of the classification: 1 = A,
 * 2 = A01, 3 = A01A, 4 = A01AA, 5 = A01AA01. `parent_code` and `chapter` are
 * derived by truncation, which is the published structure rather than an
 * inference about meaning. A code of any other length is reported and skipped.
 *
 * ── Room for a national list beside it ───────────────────────────────────
 * Cameroon's essential medicines list, when MINSANTE publishes one in machine
 * form, arrives as a SECOND `system` in this same table (e.g. 'MINSANTE-eml')
 * from a second importer — one more import, not a rewrite. This command only
 * ever touches rows where `system = 'ATC'`.
 *
 * Run:  php artisan opescare:import-atc
 */
class ImportAtcMedicines extends Command
{
    protected $signature = 'opescare:import-atc
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the WHO ATC medicine classification into the clinical code dictionary';

    private const SERVER = 'https://smt.esante.gouv.fr/fhir';

    private const CODE_SYSTEM = 'https://smt.esante.gouv.fr/terminologie-atc';

    private const RXCLASS = 'https://rxnav.nlm.nih.gov/REST/rxclass/allClasses.json?classTypes=ATC1-4';

    private const PAGE = 1000;

    /**
     * ATC is ~6,500–7,100 codes depending on release. A harvest far below that
     * means the expansion changed shape or paging broke halfway, and half a
     * vocabulary must not be upserted over a whole one.
     */
    private const FLOOR = 5000;

    public function handle(): int
    {
        $version = $this->codeSystemVersion();

        $this->line('  fetching ATC from ' . self::SERVER . ' (version ' . ($version ?? 'unknown') . ')');

        $french = $this->harvestFrench();

        $this->line('  ' . count($french) . ' ATC codes with official French titles');

        if (count($french) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($french) . ' codes harvested, expected at least '
                . self::FLOOR . '. The expansion changed shape or paging failed part-way.');

            return self::FAILURE;
        }

        // English is a bonus, not a precondition: if NLM is unreachable the
        // import still writes correct French rows rather than failing.
        $english = $this->harvestEnglish();

        $this->line('  ' . count($english) . ' ATC class names in English from NLM RxClass (levels 1-4)');

        $rows = [];
        $malformed = [];
        $englishUsed = 0;

        foreach ($french as $code => $frenchTitle) {
            $level = $this->level($code);

            if ($level === null) {
                $malformed[] = $code;
                continue;
            }

            $englishTitle = $english[$code] ?? null;

            if ($englishTitle !== null) {
                $englishUsed++;
            }

            $rows[$code] = [
                'id' => (string) Str::uuid(),
                'system' => 'ATC',
                'code' => $code,
                // English where one is published, French otherwise. Never a
                // translation — see the note at the top of this file.
                'display' => $englishTitle ?? $frenchTitle,
                'display_fr' => $frenchTitle,
                'short_name' => null,
                // Level 1 is an anatomical main group and level 5 the chemical
                // substance — the only level a prescription actually names.
                // 'term' matches how LOINC marks its assignable rows.
                'kind' => match ($level) {
                    1 => 'chapter',
                    5 => 'term',
                    default => 'group',
                },
                'parent_code' => $this->parent($code),
                // The anatomical main group letter: A, B, C, ... V.
                'chapter' => substr($code, 0, 1),
                'component' => null,
                'property' => null,
                'scale' => null,
                'specimen' => null,
                'example_units' => null,
                'synonyms' => null,
                'is_common' => false,
                'common_reason' => null,
                'is_active' => true,
                'source' => 'ANS SMT terminologie-atc (fr) + NLM RxClass (en, levels 1-4)',
                'source_version' => $version,
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($malformed !== []) {
            $this->warn('  skipped ' . count($malformed) . ' code(s) of a length ATC does not define: '
                . implode(', ', array_slice($malformed, 0, 10)));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;
        $bar = $this->output->createProgressBar(count($rows));

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'display_fr', 'kind', 'parent_code', 'chapter', 'is_active',
                    'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("ATC {$version}: {$written} codes in the dictionary, all with official French titles.");
        $this->warn(($written - $englishUsed) . ' row(s) show the FRENCH title in the English interface: '
            . 'no open source publishes English names for ATC level-5 substances, and none were invented.');

        return self::SUCCESS;
    }

    /** ATC level, by the published code lengths. Null when the code fits none. */
    private function level(string $code): ?int
    {
        return match (strlen($code)) {
            1 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            7 => 5,
            default => null,
        };
    }

    private function parent(string $code): ?string
    {
        return match (strlen($code)) {
            3 => substr($code, 0, 1),
            4 => substr($code, 0, 3),
            5 => substr($code, 0, 4),
            7 => substr($code, 0, 5),
            default => null,
        };
    }

    /**
     * Every ATC code with its official French title, keyed by code.
     *
     * @return array<string, string>
     */
    private function harvestFrench(): array
    {
        $offset = 0;
        $total = null;
        $out = [];

        do {
            $url = self::SERVER . '/ValueSet/$expand?' . http_build_query([
                'url' => self::CODE_SYSTEM . '?vs',
                'count' => self::PAGE,
                'offset' => $offset,
            ]);

            $decoded = $this->fetch($url);

            if (! isset($decoded['expansion'])) {
                throw new RuntimeException('Unexpected response from the terminology server at offset ' . $offset);
            }

            $total ??= $decoded['expansion']['total'] ?? null;
            $contains = $decoded['expansion']['contains'] ?? [];

            foreach ($contains as $entry) {
                $code = strtoupper(trim((string) ($entry['code'] ?? '')));
                $display = trim((string) ($entry['display'] ?? ''));

                if ($code !== '' && $display !== '') {
                    $out[$code] = $display;
                }
            }

            $offset += self::PAGE;
        } while ($contains !== [] && ($total === null || $offset < $total));

        return $out;
    }

    /**
     * WHO's English ATC class names for levels 1–4, keyed by code.
     *
     * Returns an empty array rather than throwing if NLM is unreachable: the
     * French spine is the import, English is an enrichment.
     *
     * @return array<string, string>
     */
    private function harvestEnglish(): array
    {
        try {
            $decoded = $this->fetch(self::RXCLASS);
        } catch (RuntimeException $e) {
            $this->warn('  NLM RxClass unreachable (' . $e->getMessage() . ') — importing French titles only.');

            return [];
        }

        $out = [];

        foreach ($decoded['rxclassMinConceptList']['rxclassMinConcept'] ?? [] as $concept) {
            $code = strtoupper(trim((string) ($concept['classId'] ?? '')));
            $name = trim((string) ($concept['className'] ?? ''));

            // RxClass includes a synthetic root class "0" for the whole
            // classification. It is not an ATC code and is not imported.
            if ($code === '' || $code === '0' || $name === '') {
                continue;
            }

            $out[$code] = $name;
        }

        return $out;
    }

    /** The release the terminology server is currently serving, if it says. */
    private function codeSystemVersion(): ?string
    {
        try {
            $decoded = $this->fetch(self::SERVER . '/CodeSystem/terminologie-atc?_elements=url,version,date');
        } catch (RuntimeException) {
            return null;
        }

        $version = trim((string) ($decoded['version'] ?? ''));

        return $version !== '' ? $version : null;
    }

    /** @return array<string, mixed> */
    private function fetch(string $url): array
    {
        $context = stream_context_create(['http' => [
            'timeout' => 90,
            'ignore_errors' => true,
            'header' => "Accept: application/fhir+json, application/json\r\n",
        ]]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new RuntimeException('Could not reach ' . $url);
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Response from ' . $url . ' was not JSON');
        }

        return $decoded;
    }
}
