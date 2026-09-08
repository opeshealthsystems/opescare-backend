<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports CVX — the CDC vaccine-administered code set — into the clinical
 * code dictionary.
 *
 * ── Why ──────────────────────────────────────────────────────────────────
 * `immunization_records.vaccine_code` holds local abbreviations: PENTA, IPV,
 * YF, PCV13, Td, COVID-19. There is a `vaccine_system` column beside it that
 * nothing currently backs, because this deployment had no vaccine vocabulary
 * at all. A vaccination that cannot name its product in a published code set
 * cannot be exchanged, cannot be counted the same way twice, and cannot be
 * read by the next facility the patient walks into.
 *
 * As with the other vocabularies, this command writes reference rows ONLY.
 * It does not touch an immunisation record: deciding that a local `PENTA`
 * means one particular CVX product is a clinical judgement, and the mapping —
 * once a human makes it — belongs in `code_system_mappings`, which is the
 * table for exactly that.
 *
 * ── Whose standard this is. Read this before quoting it. ─────────────────
 * CVX is the UNITED STATES CDC's vaccine code set, maintained by the CDC for
 * its Immunization Information Systems. It is NOT a WHO code set and it is
 * NOT a MINSANTE one. It is imported here for one honest reason: it is the
 * only open, complete, coded, freely redistributable vaccine vocabulary that
 * exists — WHO publishes prequalified-product lists as documents, not as a
 * coded terminology, and MINSANTE publishes no code set. Two consequences
 * that must not be papered over:
 *
 *   • Its content is skewed to the US market. Products used in Cameroon's
 *     EPI schedule may be absent, and US-only products are certainly present.
 *     An absent product is a gap to record, never a code to invent.
 *   • Some CVX rows are not vaccines at all (immune globulins, antitoxins).
 *     CDC flags those, and the flag is preserved as `kind = 'nonvaccine'` in
 *     CDC's own wording rather than being silently dropped or reworded.
 *
 * If WHO or MINSANTE publishes a coded vaccine list, it lands as a SECOND
 * `system` in this same table written by a second importer — one more import,
 * not a rewrite. Nothing here assumes it holds the only vaccine vocabulary:
 * every read and write is scoped to `system = 'CVX'`.
 *
 * ── The source and its shape ─────────────────────────────────────────────
 *   https://www2.cdc.gov/vaccines/iis/iisstandards/downloads/cvx.txt
 *
 * A UTF-8-BOM'd, pipe-delimited file, 7 fields, no header row:
 *   code | short description | full vaccine name | note | status | nonvaccine | last updated
 * `status` is one of Active / Inactive / Never Active / Non-US. `code` is
 * right-padded with spaces and its leading zeros are significant (`09` is Td,
 * `9` is not a CVX code) — it is trimmed of whitespace and NOT of zeros.
 *
 * CDC stamps no release version on this file, so `source_version` records the
 * newest per-concept update date it contains, labelled as such. Inventing a
 * version number would be worse than an honest derived one.
 *
 * `display_fr` is NULL on every row. CDC publishes CVX in English only and no
 * vaccine name here is machine-translated; the French interface falls back to
 * the English name (see ClinicalCodeDictionary::getLabelAttribute).
 *
 * Run:  php artisan opescare:import-vaccine-codes
 */
class ImportVaccineCodes extends Command
{
    protected $signature = 'opescare:import-vaccine-codes
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import the CDC CVX vaccine codes into the clinical code dictionary';

    private const SOURCE = 'https://www2.cdc.gov/vaccines/iis/iisstandards/downloads/cvx.txt';

    /** The `system` value every row this importer writes is scoped to. */
    private const SYSTEM = 'CVX';

    /**
     * CDC publishes 290 concepts in the file this was written against. Far
     * below that means the download served an error page or the delimiter
     * changed — not that CDC withdrew its vaccine list — and a partial
     * harvest must not be upserted over a good one.
     */
    private const FLOOR = 200;

    /** CDC's own status word for a code that is currently in use. */
    private const STATUS_ACTIVE = 'Active';

    public function handle(): int
    {
        $this->line('  fetching ' . self::SOURCE);

        $body = $this->fetch(self::SOURCE);

        $rows = [];
        $latestUpdate = null;
        $malformed = 0;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $fields = explode('|', $line);

            if (count($fields) < 7) {
                // Not parsed on a guess. A short line means the file changed
                // shape, and the count is reported below so a shape change is
                // visible rather than silently halving the import.
                $malformed++;
                continue;
            }

            // Whitespace only. The leading zeros ARE the code.
            $code = trim($fields[0]);
            $shortDescription = trim($fields[1]);
            $fullName = trim($fields[2]);
            $note = trim($fields[3]);
            $status = trim($fields[4]);
            $nonVaccine = strtolower(trim($fields[5])) === 'true';
            $updated = trim($fields[6]);

            if ($code === '' || $fullName === '') {
                $malformed++;
                continue;
            }

            if ($updated !== '' && ($latestUpdate === null || $updated > $latestUpdate)) {
                // CDC writes YYYY/MM/DD, which sorts correctly as a string.
                $latestUpdate = $updated;
            }

            $rows[$code] = [
                'code' => $code,
                'display' => $fullName,
                'display_fr' => null,
                'short_name' => $shortDescription !== '' ? Str::limit($shortDescription, 200, '') : null,
                // Neither value is 'term': a vaccine product is not a
                // diagnosis and assignable() must never offer one. CDC's own
                // Nonvaccine flag decides which of the two applies.
                'kind' => $nonVaccine ? 'nonvaccine' : 'vaccine',
                // CDC's status word verbatim — 'Active', 'Inactive',
                // 'Never Active', 'Non-US'. is_active alone would flatten
                // "withdrawn" and "never marketed in the US" into one thing,
                // and for a Cameroonian deployment 'Non-US' is the single
                // most informative of the four.
                'property' => Str::limit($status, 40, ''),
                // CDC's note, kept searchable. It is publisher text.
                'synonyms' => $note !== '' ? $note : null,
                'is_active' => $status === self::STATUS_ACTIVE,
            ];
        }

        $version = $latestUpdate !== null
            ? 'CDC file, newest entry ' . $latestUpdate
            : 'unversioned';

        $this->line('  ' . count($rows) . ' CVX concepts parsed (' . $version . ')'
            . ($malformed > 0 ? ', ' . $malformed . ' unparseable lines skipped' : ''));

        if (count($rows) < self::FLOOR) {
            $this->error('Refusing to write: only ' . count($rows) . ' concepts parsed, expected at least '
                . self::FLOOR . '. The published file has changed shape.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            $chunk = array_map(fn (array $row): array => array_merge($row, [
                'id' => (string) Str::uuid(),
                'system' => self::SYSTEM,
                'parent_code' => null,
                'chapter' => null,
                'component' => null,
                'scale' => null,
                'specimen' => null,
                'example_units' => null,
                'is_common' => false,
                'common_reason' => null,
                'source' => 'CDC CVX (US) vaccine code set',
                'source_version' => Str::limit($version, 40, ''),
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]), $chunk);

            // Idempotent on (system, code).
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'short_name', 'kind', 'property', 'synonyms', 'is_active',
                    'source', 'source_version', 'imported_at', 'updated_at'],
            );

            $written += count($chunk);
        }

        $this->info("CVX ({$version}): {$written} vaccine codes in the dictionary.");
        $this->warn('CVX is the US CDC vaccine code set — not a WHO or MINSANTE standard. Products used in '
            . "Cameroon's EPI schedule may simply be absent, and an absent product is a gap to record, never "
            . 'a code to invent. No immunisation record was read or rewritten by this command.');

        return self::SUCCESS;
    }

    private function fetch(string $url): string
    {
        $context = stream_context_create(['http' => [
            'timeout' => 60,
            'ignore_errors' => true,
            'header' => "Accept: text/plain\r\n"
                . "User-Agent: OpesCare-Finder terminology importer\r\n",
        ]]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new RuntimeException('Could not reach ' . $url);
        }

        // The file is served with a UTF-8 BOM, which would otherwise glue
        // itself to the first code and create a phantom concept.
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body) ?? $body;

        if (str_contains(substr($body, 0, 200), '<html')) {
            throw new RuntimeException($url . ' returned an HTML page, not the CVX file.');
        }

        return $body;
    }
}
