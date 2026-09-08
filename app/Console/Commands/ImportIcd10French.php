<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fills in the French titles on the ICD-10 codes already in the dictionary.
 *
 * ── Why this is a second command and a second source ─────────────────────
 * WHO's own CDN publishes the ClaML release in ENGLISH ONLY — there is no
 * icd102019fr.xml.zip, and asking for one returns a 404. So the English
 * import could not fill `display_fr`, and translating 12,599 diagnosis
 * titles by machine was never an option: a guessed diagnosis title is a
 * wrong one, and this platform is bilingual by regulation, not by decoration.
 *
 * The titles here come from the French national terminology server run by
 * the Agence du Numérique en Santé:
 *
 *   https://smt.esante.gouv.fr/fhir  —  CodeSystem CIM_10_FR_PMSI
 *
 * It is a public FHIR terminology server, no key, and it answers
 * ValueSet/$expand with honest paging. These are France's official French
 * ICD-10 titles, published by a health ministry, not a translation.
 *
 * ── Only codes we already hold are touched ───────────────────────────────
 * CIM-10 FR à usage PMSI is France's NATIONAL variant: 19,075 codes against
 * WHO's 12,599, the difference being French extensions that exist for French
 * hospital funding. Those extensions are no more the right vocabulary for
 * Cameroon than ICD-10-CM was, so this command adds NO codes. It only writes
 * `display_fr` onto rows the WHO import already created, and reports how many
 * French-only codes it declined to add.
 *
 * Run:  php artisan opescare:import-icd10-fr
 */
class ImportIcd10French extends Command
{
    protected $signature = 'opescare:import-icd10-fr
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Add official French titles to the ICD-10 codes in the clinical code dictionary';

    private const SERVER = 'https://smt.esante.gouv.fr/fhir';

    private const CODE_SYSTEM = 'https://smt.esante.gouv.fr/terminologie-cim-10';

    private const PAGE = 1000;

    public function handle(): int
    {
        $held = DB::table('clinical_code_dictionary')
            ->where('system', 'ICD-10')
            ->pluck('code')
            ->flip();

        if ($held->isEmpty()) {
            $this->error('No ICD-10 codes in the dictionary. Run opescare:import-icd10 first.');

            return self::FAILURE;
        }

        $this->line('  ' . $held->count() . ' ICD-10 codes held, asking ' . self::SERVER . ' for their French titles');

        $offset = 0;
        $seen = 0;
        $matched = [];
        $frenchOnly = 0;
        $total = null;

        do {
            $page = $this->expand($offset);

            if ($page === null) {
                break;
            }

            $total ??= $page['total'] ?? null;
            $contains = $page['contains'];

            foreach ($contains as $entry) {
                $code = trim((string) ($entry['code'] ?? ''));
                $display = trim((string) ($entry['display'] ?? ''));
                $seen++;

                if ($code === '' || $display === '') {
                    continue;
                }

                if (! $held->has($code)) {
                    // A French national extension. Not added — see the note
                    // at the top of this file.
                    $frenchOnly++;
                    continue;
                }

                $matched[$code] = $display;
            }

            $offset += self::PAGE;
        } while ($contains !== [] && ($total === null || $offset < $total));

        $this->line(sprintf(
            '  %d codes offered, %d matched a code we hold, %d French-only extensions declined',
            $seen,
            count($matched),
            $frenchOnly,
        ));

        // The two classifications overlap heavily; a handful of misses is
        // normal, a near-total miss means the code formats stopped agreeing.
        if (count($matched) < 5000) {
            $this->error('Refusing to write: only ' . count($matched) . ' codes matched, expected thousands. '
                . 'The two code formats look like they have stopped agreeing.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = 0;
        $bar = $this->output->createProgressBar(count($matched));

        // Written one statement per chunk with a CASE, rather than 12,000
        // single updates over a remote database connection.
        foreach (array_chunk($matched, 500, true) as $chunk) {
            $case = 'CASE code';
            $bindings = [];

            foreach ($chunk as $code => $display) {
                $case .= ' WHEN ? THEN ?';
                $bindings[] = $code;
                $bindings[] = $display;
            }

            $case .= ' END';

            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            DB::update(
                "UPDATE clinical_code_dictionary
                    SET display_fr = {$case}, updated_at = now()
                  WHERE system = 'ICD-10' AND code IN ({$placeholders})",
                array_merge($bindings, array_keys($chunk)),
            );

            $written += count($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $missing = DB::table('clinical_code_dictionary')
            ->where('system', 'ICD-10')
            ->whereNull('display_fr')
            ->count();

        $this->info("French titles written for {$written} ICD-10 codes.");

        if ($missing > 0) {
            // Named, not hidden. A code with no French title falls back to the
            // English one on screen, and somebody should know which.
            $this->warn("{$missing} code(s) still have no French title — CIM-10 FR does not carry them. "
                . 'They will show their English title in the French interface.');
        }

        return self::SUCCESS;
    }

    /** One page of the implicit value set. Returns null when the page fails. */
    private function expand(int $offset): ?array
    {
        $url = self::SERVER . '/ValueSet/$expand?' . http_build_query([
            'url' => self::CODE_SYSTEM . '?vs',
            'count' => self::PAGE,
            'offset' => $offset,
        ]);

        $context = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $this->warn("  request failed at offset {$offset}");

            return null;
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded) || ! isset($decoded['expansion'])) {
            throw new RuntimeException('Unexpected response from the terminology server at offset ' . $offset);
        }

        return [
            'total' => $decoded['expansion']['total'] ?? null,
            'contains' => $decoded['expansion']['contains'] ?? [],
        ];
    }
}
