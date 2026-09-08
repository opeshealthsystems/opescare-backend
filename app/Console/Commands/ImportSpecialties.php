<?php

namespace App\Console\Commands;

use App\Support\Terminology\ClinicalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the clinical SPECIALTY codes — cardiology, obstetrics, paediatric
 * surgery — into the clinical code dictionary.
 *
 * ── Where they come from ─────────────────────────────────────────────────
 * FHIR R4's practice-setting value set, which HL7 assembled for the C-CDA
 * "practice setting" element and which every FHIR PractitionerRole /
 * HealthcareService example in the wild uses:
 *
 *   https://hl7.org/fhir/R4/valueset-c80-practice-codes.json
 *   http://hl7.org/fhir/ValueSet/c80-practice-codes   (version 4.0.1)
 *
 * ── These are SNOMED CT concepts, and are labelled as such ───────────────
 * The value set does not mint codes of its own: it ENUMERATES concepts from
 * `http://snomed.info/sct`, so 394579002 is a SNOMED concept id and storing it
 * under any other system name would be a lie about what the number is. Rows
 * are therefore written with `system = 'SNOMED-CT'`, and `source` records that
 * this particular subset arrived via the HL7 value set rather than from a
 * SNOMED release — those are different provenances and only one of them is
 * true here.
 *
 * `ClinicalCode::looksLikeSnomed()` is used as a SHAPE assertion only. It
 * checks that a code is 6–18 digits; it does NOT compute the Verhoeff check
 * digit and says so in its own docblock, so a code passing it is "plausibly a
 * SNOMED id", never "verified". Nothing here claims verification. Codes are
 * copied byte-for-byte from HL7 and any that fail even the shape test are
 * reported and skipped rather than repaired.
 *
 * SNOMED CT is licensed content. What is imported here is the small named
 * subset HL7 publishes openly inside an R4 value set (117 concepts, English
 * display text) — not a SNOMED distribution, which this deployment has no
 * licence to redistribute and does not fetch.
 *
 * ── Room for a MINSANTE list beside it ───────────────────────────────────
 * If MINSANTE publishes Cameroon's official specialty list, it lands as a
 * SECOND `system` in this same table (e.g. 'MINSANTE-specialty') from a second
 * importer: an import, not a rewrite. This command only ever touches rows
 * where `system = 'SNOMED-CT'` AND `kind = 'specialty'`, so a national list
 * and this one coexist, and the crosswalk between them belongs in
 * `code_system_mappings`.
 *
 * `display_fr` is left NULL. HL7 publishes these displays in English only.
 * A machine-translated specialty name is a guess put in front of a clinician,
 * and this platform is bilingual by regulation — so the French interface
 * falls back to the English title until real French text exists.
 *
 * Run:  php artisan opescare:import-specialties
 */
class ImportSpecialties extends Command
{
    protected $signature = 'opescare:import-specialties
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the HL7 c80 practice-setting (clinical specialty) SNOMED codes into the clinical code dictionary';

    private const SOURCE = 'https://hl7.org/fhir/R4/valueset-c80-practice-codes.json';

    private const SNOMED_SYSTEM = 'http://snomed.info/sct';

    /** What these rows are called in the dictionary. */
    private const SYSTEM = 'SNOMED-CT';

    private const KIND = 'specialty';

    /**
     * The value set enumerates 117 concepts. A harvest well below that means
     * the resource changed shape — which must fail loudly rather than upsert
     * a fragment over a complete list.
     */
    private const FLOOR = 90;

    public function handle(): int
    {
        $this->line('  fetching ' . self::SOURCE);

        $resource = $this->fetch(self::SOURCE);

        if (($resource['resourceType'] ?? null) !== 'ValueSet') {
            $this->error('That URL did not return a FHIR ValueSet. Refusing to guess at its shape.');

            return self::FAILURE;
        }

        $version = (string) ($resource['version'] ?? 'unknown');
        $rows = [];
        $rejected = [];
        $foreign = 0;

        foreach ($resource['compose']['include'] ?? [] as $include) {
            // Only the SNOMED includes. If HL7 ever mixes another code system
            // into this value set, those concepts are NOT silently written
            // under a SNOMED label — they are counted and left out.
            if (($include['system'] ?? null) !== self::SNOMED_SYSTEM) {
                $foreign += count($include['concept'] ?? []);
                continue;
            }

            foreach ($include['concept'] ?? [] as $concept) {
                $code = trim((string) ($concept['code'] ?? ''));
                $display = trim((string) ($concept['display'] ?? ''));

                if ($code === '' || $display === '') {
                    continue;
                }

                // Shape only — see the note at the top of this file.
                if (! ClinicalCode::looksLikeSnomed($code)) {
                    $rejected[] = $code;
                    continue;
                }

                $rows[$code] = [
                    'id' => (string) Str::uuid(),
                    'system' => self::SYSTEM,
                    'code' => $code,
                    'display' => $display,
                    'display_fr' => null,
                    'short_name' => null,
                    'kind' => self::KIND,
                    'parent_code' => null,
                    'chapter' => null,
                    'component' => null,
                    'property' => null,
                    'scale' => null,
                    'specimen' => null,
                    'example_units' => null,
                    'synonyms' => null,
                    'is_common' => false,
                    'common_reason' => null,
                    'is_active' => true,
                    'source' => 'HL7 FHIR R4 ValueSet c80-practice-codes (SNOMED CT subset)',
                    'source_version' => $version,
                    'imported_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $this->line('  ' . count($rows) . ' specialty concepts parsed (value set version ' . $version . ')');

        if ($foreign > 0) {
            $this->warn("  {$foreign} concept(s) from a non-SNOMED system in this value set were NOT imported.");
        }

        if ($rejected !== []) {
            $this->warn('  skipped ' . count($rejected) . ' code(s) that do not look like SNOMED ids: '
                . implode(', ', array_slice($rejected, 0, 10)));
        }

        if (count($rows) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($rows) . ' concepts parsed, expected at least '
                . self::FLOOR . '. The published value set has changed shape.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'kind', 'is_active', 'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
        }

        $this->info("Clinical specialties: {$written} SNOMED CT concepts in the dictionary "
            . "(system=SNOMED-CT, kind=specialty).");
        $this->warn('Codes are copied verbatim from HL7 and only shape-checked — no Verhoeff check digit '
            . 'was computed, so nothing here is "verified SNOMED". display_fr is NULL: the source is '
            . 'English-only and no title is machine-translated.');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function fetch(string $url): array
    {
        $context = stream_context_create(['http' => [
            'timeout' => 60,
            'ignore_errors' => true,
            // hl7.org sits behind a WAF that rejects a request carrying no
            // User-Agent outright (405, human-verification page). This names
            // the caller honestly rather than impersonating a browser.
            'header' => "Accept: application/fhir+json, application/json\r\n"
                . "User-Agent: OpesCare-Finder terminology importer\r\n",
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
