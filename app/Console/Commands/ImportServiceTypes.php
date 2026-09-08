<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the HL7 service-type codes — "what service does this department
 * actually deliver" — into the clinical code dictionary.
 *
 * ── Why this vocabulary, and what it is NOT ──────────────────────────────
 * A facility profile, a referral and an appointment all need to say which
 * SERVICE is involved, and until now this deployment had no published list to
 * say it in. FHIR R4 ships one, and every FHIR-speaking system on the other
 * end of an exchange already understands it:
 *
 *   https://hl7.org/fhir/R4/codesystem-service-type.json
 *   http://terminology.hl7.org/CodeSystem/service-type   (version 4.0.1)
 *
 * Two honest limitations, stated because they change how much weight the
 * codes can carry:
 *
 *   1. HL7 marks this CodeSystem `content: "example"`. It is a real, stable,
 *      published list used by FHIR implementations and it is fine to exchange
 *      — but HL7 does not hold it out as a normative national service
 *      taxonomy, and this importer does not either.
 *   2. Its origin is the Australian service directory work, so it is skewed
 *      to services an Australian community-health directory lists. Some codes
 *      will never be used in Cameroon. Absent use is harmless; an invented
 *      code would not be, so nothing here is added, dropped or re-worded.
 *
 * The `definition` HL7 publishes is kept in `synonyms` so a search for the
 * longer wording finds the code — it is publisher text, not commentary.
 *
 * ── Room for a MINSANTE list beside it, not instead of it ────────────────
 * If MINSANTE publishes an official Cameroonian department/service list, it
 * arrives as a SECOND `system` in this same table (e.g. 'MINSANTE-service')
 * written by a second importer — one more import, not a rewrite. Nothing in
 * this command assumes it holds the only service vocabulary: it scopes every
 * read and write to `system = 'HL7-service-type'`, and a crosswalk between the
 * two lists belongs in `code_system_mappings`, which is what that table is for.
 *
 * `display_fr` is left NULL on every row. HL7 publishes this CodeSystem in
 * English only, and machine-translating a service name would put a guess in
 * front of a clinician in a bilingual country. The French interface falls back
 * to the English title (see ClinicalCodeDictionary::getLabelAttribute) until
 * somebody supplies real French text.
 *
 * Run:  php artisan opescare:import-service-types
 */
class ImportServiceTypes extends Command
{
    protected $signature = 'opescare:import-service-types
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the HL7 service-type codes (hospital departments / services) into the clinical code dictionary';

    private const SOURCE = 'https://hl7.org/fhir/R4/codesystem-service-type.json';

    /** The `system` value every row this importer writes is scoped to. */
    private const SYSTEM = 'HL7-service-type';

    /**
     * The published list holds 596 concepts. A harvest that comes back with a
     * fraction of that means HL7 changed the resource shape, not that the
     * vocabulary shrank — and a partial harvest must not be upserted over a
     * good one. Deliberately well below 596 so a genuine editorial trim does
     * not fail the import, but far above anything a parse failure returns.
     */
    private const FLOOR = 400;

    public function handle(): int
    {
        $this->line('  fetching ' . self::SOURCE);

        $resource = $this->fetch(self::SOURCE);

        if (($resource['resourceType'] ?? null) !== 'CodeSystem') {
            $this->error('That URL did not return a FHIR CodeSystem. Refusing to guess at its shape.');

            return self::FAILURE;
        }

        $version = (string) ($resource['version'] ?? 'unknown');
        $rows = [];

        // Flattened defensively: R4 publishes this list flat today, but a
        // CodeSystem may nest `concept` at any depth and silently dropping
        // children would lose codes without saying so.
        foreach ($this->flatten($resource['concept'] ?? []) as [$concept, $parent]) {
            $code = trim((string) ($concept['code'] ?? ''));
            $display = trim((string) ($concept['display'] ?? ''));

            if ($code === '' || $display === '') {
                continue;
            }

            $definition = trim((string) ($concept['definition'] ?? ''));

            $rows[$code] = [
                'id' => (string) Str::uuid(),
                'system' => self::SYSTEM,
                'code' => $code,
                'display' => $display,
                // English-only source. See the note at the top of this file.
                'display_fr' => null,
                'short_name' => null,
                'kind' => 'service',
                'parent_code' => $parent,
                'chapter' => null,
                'component' => null,
                'property' => null,
                'scale' => null,
                'specimen' => null,
                'example_units' => null,
                // The publisher's own definition, kept searchable. Null when
                // it merely repeats the display, which it usually does.
                'synonyms' => ($definition !== '' && strcasecmp($definition, $display) !== 0)
                    ? $definition
                    : null,
                'is_common' => false,
                'common_reason' => null,
                'is_active' => true,
                'source' => 'HL7 FHIR R4 CodeSystem service-type',
                'source_version' => $version,
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->line('  ' . count($rows) . ' service-type concepts parsed (version ' . $version . ')');

        if (count($rows) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($rows) . ' concepts parsed, expected at least '
                . self::FLOOR . '. The published resource has changed shape.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            // Idempotent on (system, code): re-running replaces the published
            // text and provenance and leaves the row's id alone.
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'kind', 'parent_code', 'synonyms', 'is_active',
                    'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
        }

        $this->info("HL7 service-type {$version}: {$written} service codes in the dictionary.");
        $this->warn('display_fr is NULL on every row — HL7 publishes this list in English only, and no '
            . 'title here is machine-translated. The French interface shows the English title until real '
            . 'French text (or a MINSANTE list) is imported alongside it.');

        return self::SUCCESS;
    }

    /**
     * Every concept in the tree, paired with its parent code.
     *
     * @param  array<int, array<string, mixed>>  $concepts
     * @return list<array{0: array<string, mixed>, 1: string|null}>
     */
    private function flatten(array $concepts, ?string $parent = null): array
    {
        $out = [];

        foreach ($concepts as $concept) {
            if (! is_array($concept)) {
                continue;
            }

            $out[] = [$concept, $parent];

            if (isset($concept['concept']) && is_array($concept['concept'])) {
                $out = array_merge(
                    $out,
                    $this->flatten($concept['concept'], trim((string) ($concept['code'] ?? '')) ?: null),
                );
            }
        }

        return $out;
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
