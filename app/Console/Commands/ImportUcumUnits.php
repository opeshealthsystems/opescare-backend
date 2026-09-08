<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

/**
 * Imports UCUM — the Unified Code for Units of Measure — into the clinical
 * code dictionary, so that a unit of measure is finally a CODE and not a
 * free-typed string.
 *
 * ── Why ──────────────────────────────────────────────────────────────────
 * `lab_results.unit` holds what whoever entered the result happened to type.
 * Today it holds BOTH `10^9/L` (6 platelet rows) and `x10^9/L` (1 platelet
 * row) for the same quantity, alongside g/dL, mg/dL, mmol/L, ng/mL, U/L and
 * umol/L. Two strings for one unit means nothing can compare, trend or
 * convert them, and no amount of string-matching fixes that honestly — what
 * is missing is a published vocabulary to compare AGAINST. UCUM is that
 * vocabulary: it is what FHIR R4 mandates for Quantity.code, so it is also
 * what every system on the other end of an exchange expects.
 *
 * ── This importer does NOT touch a single lab result ──────────────────────
 * It writes reference rows and nothing else. Deciding that a particular
 * `x10^9/L` string meant `10*9/L` is a clinical-record edit, and a script
 * does not get to make those: the reconciliation report surfaces the
 * mismatches and a human decides. All this command does is make the decision
 * possible.
 *
 * ── The source, and where it actually lives ──────────────────────────────
 * ucum-essence.xml is the machine-readable UCUM release. The commonly cited
 * https://ucum.org/ucum-essence.xml now 301-redirects to the HTML spec page
 * (it returns a web page, not XML — a parser pointed at it fails in a
 * confusing way), so this reads the file from the UCUM organisation's own
 * repository, which is where the release is published today:
 *
 *   https://raw.githubusercontent.com/ucum-org/ucum/main/ucum-essence.xml
 *
 * ── What is and is not in here — read before trusting a lookup ───────────
 *  1. UCUM publishes ATOMIC units (`g`, `L`, `mol`, `10*`) plus PREFIXES
 *     (`m`, `u`, `k`). Everything a lab actually writes — `mg/dL`, `mmol/L`,
 *     `10*9/L` — is an EXPRESSION composed from those under UCUM's grammar.
 *     Such expressions are not rows in the release and are not invented here:
 *     the canonical UCUM for the platelet unit is the expression `10*9/L`,
 *     built from the atomic unit `10*` ("the number ten for arbitrary
 *     powers") and `L` (liter), both of which this import does provide.
 *     Validating a composed expression needs a UCUM grammar parser; this
 *     table is the vocabulary such a parser would resolve against, not the
 *     parser.
 *  2. The PREFIXES are deliberately NOT imported. A UCUM prefix code
 *     collides with a unit code — `m` is both "milli" and "metre" — and the
 *     table is unique on (system, code), so importing both would have one
 *     silently overwrite the other. A prefix is not a unit; when the platform
 *     needs them they belong in their own system, not smuggled into this one.
 *  3. `display_fr` is NULL on every row. UCUM publishes names in English
 *     only and a machine-translated unit name is a guess put in front of a
 *     clinician. The French interface falls back to the English name (see
 *     ClinicalCodeDictionary::getLabelAttribute).
 *
 * Column use, stated so nobody has to reverse-engineer it: `property` holds
 * UCUM's own property (`mass`, `volume`, `number`); `chapter` holds UCUM's
 * `class` attribute (`si`, `iso1000`, `clinical`, …), which is the only
 * grouping axis the release exposes; `short_name` holds the printable symbol.
 * `parent_code` stays NULL — a unit's defining unit is a conversion, not a
 * hierarchy, and writing it into a parent column would read as one.
 *
 * Run:  php artisan opescare:import-ucum-units
 */
class ImportUcumUnits extends Command
{
    protected $signature = 'opescare:import-ucum-units
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the UCUM units of measure into the clinical code dictionary';

    private const SOURCE = 'https://raw.githubusercontent.com/ucum-org/ucum/main/ucum-essence.xml';

    /** The `system` value every row this importer writes is scoped to. */
    private const SYSTEM = 'UCUM';

    /**
     * UCUM 2.2 publishes 7 base units and 305 derived units — 312 concepts.
     * A harvest well below that means the file changed shape or a redirect
     * served HTML, not that UCUM shrank, and a partial harvest must never be
     * upserted over a good one. Low enough that a genuine editorial change
     * does not fail the import, far above anything a parse failure returns.
     */
    private const FLOOR = 250;

    public function handle(): int
    {
        $this->line('  fetching ' . self::SOURCE);

        $xml = $this->fetch(self::SOURCE);

        $version = trim((string) ($xml['version'] ?? ''));
        $revisionDate = trim((string) ($xml['revision-date'] ?? ''));

        if ($version === '') {
            $this->error('That file carried no UCUM version attribute. Refusing to guess at its shape.');

            return self::FAILURE;
        }

        $sourceVersion = $revisionDate !== '' ? $version . ' (' . $revisionDate . ')' : $version;

        $rows = [];

        // Base units and derived units are the same kind of thing to a
        // consumer — a code you can write in a result — and differ only in
        // how UCUM defines them, so both are imported under one kind.
        foreach (['base-unit', 'unit'] as $element) {
            foreach ($xml->{$element} as $unit) {
                $row = $this->row($unit, $sourceVersion);

                if ($row !== null) {
                    $rows[$row['code']] = $row;
                }
            }
        }

        $this->line('  ' . count($rows) . ' UCUM units parsed (release ' . $sourceVersion . ')');

        if (count($rows) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($rows) . ' units parsed, expected at least '
                . self::FLOOR . '. The published release has changed shape.');

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
                ['display', 'short_name', 'kind', 'chapter', 'property', 'synonyms',
                    'is_active', 'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
        }

        $this->info("UCUM {$sourceVersion}: {$written} units of measure in the dictionary.");
        $this->warn('Atomic units only. Composed expressions (mg/dL, 10*9/L) are built from these under the '
            . 'UCUM grammar and are NOT rows here; prefixes are not imported because a prefix code collides '
            . 'with a unit code. No lab result was read or rewritten by this command.');

        return self::SUCCESS;
    }

    /**
     * One dictionary row from one UCUM <unit> / <base-unit> element.
     *
     * @return array<string, mixed>|null
     */
    private function row(SimpleXMLElement $unit, string $sourceVersion): ?array
    {
        // `Code` is the case-SENSITIVE code, which is the one written in FHIR
        // and in a lab result. `CODE` is UCUM's case-insensitive variant and
        // is deliberately ignored — carrying both would put two rows in the
        // table for one unit.
        $code = trim((string) ($unit['Code'] ?? ''));

        // UCUM may carry several <name> elements for one unit (e.g. the
        // degree signs). The first is the display; the rest stay searchable
        // as synonyms rather than being dropped.
        $names = [];

        foreach ($unit->name as $name) {
            $name = trim((string) $name);

            if ($name !== '') {
                $names[] = $name;
            }
        }

        if ($code === '' || $names === []) {
            return null;
        }

        $printSymbol = trim((string) ($unit->printSymbol ?? ''));
        $property = trim((string) ($unit->property ?? ''));
        $class = trim((string) ($unit['class'] ?? ''));

        return [
            'id' => (string) Str::uuid(),
            'system' => self::SYSTEM,
            'code' => $code,
            'display' => $names[0],
            // English-only source. See the note at the top of this file.
            'display_fr' => null,
            'short_name' => $printSymbol !== '' ? Str::limit($printSymbol, 200, '') : null,
            // Not 'term': a unit is not something a clinician diagnoses, and
            // ClinicalCodeDictionary::assignable() must never offer one.
            'kind' => 'unit',
            'parent_code' => null,
            'chapter' => $class !== '' ? Str::limit($class, 20, '') : null,
            'component' => null,
            'property' => $property !== '' ? Str::limit($property, 40, '') : null,
            'scale' => null,
            'specimen' => null,
            'example_units' => null,
            'synonyms' => count($names) > 1 ? implode('; ', array_slice($names, 1)) : null,
            'is_common' => false,
            'common_reason' => null,
            // UCUM does not retire units in this file; everything published is
            // current, and nothing here infers otherwise.
            'is_active' => true,
            'source' => 'UCUM ucum-essence.xml',
            'source_version' => Str::limit($sourceVersion, 40, ''),
            'imported_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function fetch(string $url): SimpleXMLElement
    {
        $context = stream_context_create(['http' => [
            'timeout' => 60,
            'ignore_errors' => true,
            // Named honestly rather than impersonating a browser — several
            // terminology hosts reject a request that carries no User-Agent.
            'header' => "Accept: application/xml, text/xml\r\n"
                . "User-Agent: OpesCare-Finder terminology importer\r\n",
        ]]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new RuntimeException('Could not reach ' . $url);
        }

        // A redirect to the HTML spec page is the failure mode this source
        // actually has, so it is named rather than left to surface as a
        // baffling XML parse error.
        if (! str_contains(substr($body, 0, 200), '<?xml')) {
            throw new RuntimeException($url . ' did not return XML — it looks like an HTML page. '
                . 'The UCUM release has moved again.');
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException('Could not parse the UCUM release at ' . $url);
        }

        return $xml;
    }
}
