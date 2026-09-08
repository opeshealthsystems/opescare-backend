<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the EDQM Standard Terms routes of administration — bilingual,
 * EN + FR — into the clinical code dictionary.
 *
 * ── Why ──────────────────────────────────────────────────────────────────
 * `immunization_records.route` is free text and it shows: the register holds
 * `IM` (15 rows) and `intramuscular` (10 rows) for the SAME route, plus `SC`,
 * `oral` and `intradermal`. Nothing can group, count or report on a route
 * while it is two strings, and there was no published list here to compare
 * against. This command supplies the list. It does NOT rewrite a single
 * immunisation record — reconciling `IM` to a code is a clinical-record edit
 * and belongs to a human working from the reconciliation report.
 *
 * ── The source HL7 names, and why it could not be used ───────────────────
 * https://hl7.org/fhir/R4/valueset-route-codes.json was checked first. It
 * returns 200 and it is a real ValueSet — but it enumerates NOTHING. Its
 * whole content is a `compose` with one intensional include:
 *
 *   system http://snomed.info/sct, filter concept is-a 284009009
 *
 * There is no `expansion`, so there are no codes in it to import; expanding
 * it needs a SNOMED CT server, and SNOMED CT is licensed. HL7's own narrative
 * says as much — it lists SNOMED CT, ISO 11239, NCIt and EDQM as possible
 * sources and calls SNOMED "the example terminology". So that URL was
 * rejected, not worked around.
 *
 * ── What is imported instead ─────────────────────────────────────────────
 * EDQM Standard Terms, from the French national terminology server run by
 * the Agence du Numérique en Santé — public, no key, the same server this
 * deployment already uses for French ICD-10 titles:
 *
 *   https://smt.esante.gouv.fr/fhir
 *   CodeSystem https://smt.esante.gouv.fr/terminologie-standardterms
 *
 * EDQM Standard Terms is the European Pharmacopoeia's published list, it is
 * what ISO 11239 / IDMP implementations use for route of administration, and
 * — the reason it beats every other candidate here — it is genuinely
 * MULTILINGUAL. The French title is EDQM's own ("Voie intramusculaire"), and
 * the English one is EDQM's own ("Intramuscular use"). Nothing on these rows
 * is machine-translated, in a country where both languages are regulatory.
 *
 * ── Scoped to routes, deliberately ───────────────────────────────────────
 * The CodeSystem carries 1,305 concepts across eighteen EDQM classes — dose
 * forms, containers, closures, devices. Only the 108 in class `ROA` (Route of
 * Administration) are imported: that is the vocabulary the gap is about, and
 * importing dose forms under a "route" heading would make the table lie.
 * EDQM's separate `MAP` class (Method of Administration — "Intramusculaire"
 * without the "Voie") is left out for the same reason; if the platform ever
 * needs methods they arrive as their own kind, not folded in with routes.
 *
 * A concept EDQM marks `Rejected` is still imported, with is_active = false.
 * A withdrawn code must still resolve, because a record may already point at
 * it — see the note on the table's migration.
 *
 * Run:  php artisan opescare:import-routes-of-administration
 */
class ImportRoutesOfAdministration extends Command
{
    protected $signature = 'opescare:import-routes-of-administration
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the EDQM routes of administration (EN + FR) into the clinical code dictionary';

    private const SERVER = 'https://smt.esante.gouv.fr/fhir';

    private const CODE_SYSTEM = 'https://smt.esante.gouv.fr/terminologie-standardterms';

    /** The `system` value every row this importer writes is scoped to. */
    private const SYSTEM = 'EDQM-route';

    /** The EDQM class that means "route of administration". */
    private const ROUTE_CLASS = 'ROA';

    /**
     * EDQM publishes 108 routes in the release this was written against. A
     * harvest far below that means the server changed the resource shape or
     * the class code, not that the pharmacopoeia dropped routes — and a
     * partial harvest must not be upserted over a good one.
     */
    private const FLOOR = 80;

    public function handle(): int
    {
        $this->line('  asking ' . self::SERVER . ' for ' . self::CODE_SYSTEM);

        // Two requests, and it is not an oversight: the server answers
        // `_elements=concept` with the concepts and NOTHING else (no version,
        // no content flag), and answers any wider `_elements` list with the
        // metadata and NO concepts. So the metadata is asked for first — it
        // is what says whether importing at all is safe — and the concepts
        // second.
        $metadata = $this->fetchCodeSystem(null);

        if (($metadata['content'] ?? null) !== 'complete') {
            $this->error('That CodeSystem did not come back complete (content = '
                . ($metadata['content'] ?? 'null') . '). Refusing to import a partial vocabulary.');

            return self::FAILURE;
        }

        $version = (string) ($metadata['version'] ?? 'unknown');
        $concepts = $this->fetchCodeSystem('concept')['concept'] ?? [];
        $rows = [];
        $skippedClasses = 0;

        foreach ($concepts as $concept) {
            if (! is_array($concept)) {
                continue;
            }

            $properties = $this->properties($concept);

            if (($properties['class'] ?? null) !== self::ROUTE_CLASS) {
                // A dose form, container, device… Not a route. See above.
                $skippedClasses++;
                continue;
            }

            $code = trim((string) ($concept['code'] ?? ''));
            $french = trim((string) ($concept['display'] ?? ''));
            $english = $this->designation($concept, 'en');

            if ($code === '' || ($french === '' && $english === null)) {
                continue;
            }

            // The server publishes this CodeSystem with French as the display
            // and English as a designation. `display` on this table is the
            // published English by convention (every other vocabulary here is
            // English-primary), and the French goes where French goes. If a
            // concept ever arrives without English, the French title is used
            // for both rather than leaving the row unreadable — it is still
            // EDQM's own text, not a translation of ours.
            $rows[$code] = [
                'id' => (string) Str::uuid(),
                'system' => self::SYSTEM,
                'code' => $code,
                'display' => $english ?? $french,
                'display_fr' => $french !== '' ? $french : null,
                'short_name' => null,
                // Not 'term': a route is not a diagnosis, and
                // ClinicalCodeDictionary::assignable() must never offer one.
                'kind' => 'route',
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
                // EDQM's own status word, not an inference of ours.
                'is_active' => ($properties['status'] ?? 'Current') === 'Current',
                'source' => 'EDQM Standard Terms via SMT (ANS)',
                'source_version' => Str::limit($version, 40, ''),
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->line(sprintf(
            '  %d concepts offered, %d routes (class %s) parsed, %d other EDQM classes declined (version %s)',
            count($concepts),
            count($rows),
            self::ROUTE_CLASS,
            $skippedClasses,
            $version,
        ));

        if (count($rows) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($rows) . ' routes parsed, expected at least '
                . self::FLOOR . '. The published CodeSystem has changed shape.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            // Idempotent on (system, code).
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'display_fr', 'kind', 'is_active',
                    'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
        }

        $this->info("EDQM Standard Terms {$version}: {$written} routes of administration in the dictionary, EN and FR.");
        $this->warn('No immunisation record was read or rewritten. Reconciling free-text routes (IM vs '
            . 'intramuscular) to these codes is a human decision, not this command.');

        return self::SUCCESS;
    }

    /**
     * The concept's properties, flattened to code => scalar value.
     *
     * @param  array<string, mixed>  $concept
     * @return array<string, string>
     */
    private function properties(array $concept): array
    {
        $out = [];

        foreach ($concept['property'] ?? [] as $property) {
            if (! is_array($property) || ! isset($property['code'])) {
                continue;
            }

            $value = $property['valueCode'] ?? $property['valueString'] ?? null;

            if (is_scalar($value)) {
                $out[(string) $property['code']] = trim((string) $value);
            }
        }

        return $out;
    }

    /**
     * The concept's designation in one language, or null when the source
     * carries none. Never guessed — an absent language stays absent.
     *
     * @param  array<string, mixed>  $concept
     */
    private function designation(array $concept, string $language): ?string
    {
        foreach ($concept['designation'] ?? [] as $designation) {
            if (! is_array($designation)) {
                continue;
            }

            if (strtolower((string) ($designation['language'] ?? '')) !== $language) {
                continue;
            }

            $value = trim((string) ($designation['value'] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * The CodeSystem, either summarised (metadata) or with `_elements` set
     * to the one element wanted.
     *
     * The search Bundle — rather than $expand on the matching ValueSet —
     * because $expand hands back displays without the `class` property this
     * import must filter routes on.
     *
     * @return array<string, mixed>
     */
    private function fetchCodeSystem(?string $elements): array
    {
        $url = self::SERVER . '/CodeSystem?url=' . rawurlencode(self::CODE_SYSTEM)
            . ($elements !== null ? '&_elements=' . rawurlencode($elements) : '');

        $context = stream_context_create(['http' => [
            'timeout' => 120,
            'ignore_errors' => true,
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

        $resource = $decoded['entry'][0]['resource'] ?? null;

        if (! is_array($resource) || ($resource['resourceType'] ?? null) !== 'CodeSystem') {
            throw new RuntimeException('No CodeSystem came back for ' . self::CODE_SYSTEM);
        }

        return $resource;
    }
}
