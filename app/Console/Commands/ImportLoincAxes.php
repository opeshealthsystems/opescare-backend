<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the five LOINC axis columns the NLM import could not reach:
 * `specimen`, `scale`, `example_units` — and, only where they are still NULL,
 * `component` and `property`.
 *
 * ── Why this command exists ──────────────────────────────────────────────
 * `opescare:import-loinc` reads the NLM Clinical Table Search Service, which
 * publishes LOINC openly but exposes only LOINC_NUM, LONG_COMMON_NAME,
 * SHORTNAME, COMPONENT, PROPERTY and RELATEDNAMES2. It says so in its own
 * doc-block: SYSTEM, SCALE_TYP and EXAMPLE_UNITS are not on that endpoint, so
 * `specimen`, `scale` and `example_units` came out NULL on all ~19,300 LOINC
 * rows. A lab result screen that cannot say whether 718-7 is measured in blood
 * or in urine is the gap this closes.
 *
 * ── The source ───────────────────────────────────────────────────────────
 * HL7's public FHIR terminology server, https://tx.fhir.org/r4 — no key, no
 * licence click — the same server ImportFrenchTitles already uses:
 *
 *   CodeSystem/$lookup?system=http://loinc.org&code=718-7
 *       &property=SYSTEM&property=SCALE_TYP&property=EXAMPLE_UNITS
 *
 * Each `property=` MUST be asked for by name. A bare $lookup returns none of
 * them — it returns name/code/system/version/display and stops. That is the
 * single fact that makes this command look more verbose than it needs to be.
 *
 * The content is LOINC, © Regenstrief Institute, Inc., obtained through HL7's
 * public terminology server and used subject to the LOINC licence
 * (https://loinc.org/license/). The server reports the release it is serving
 * (2.82 at the time of writing) and that string is written verbatim into
 * `source_version` on every row this command fills, so the provenance of a
 * row's axes is never a guess.
 *
 * ── LOINC Part codes ─────────────────────────────────────────────────────
 * SYSTEM, SCALE_TYP, COMPONENT and PROPERTY come back as `valueCode` LOINC
 * PART codes, not as text: 718-7's SYSTEM is `LP7057-5`, not "Bld". Parts
 * resolve through the same $lookup endpoint, where their `display` is the
 * human axis value ("Bld", "Qn", "Hemoglobin", "MCnc"). EXAMPLE_UNITS is the
 * exception — a plain `valueString` ("g/dL") needing no second hop.
 *
 * A few hundred distinct Part codes cover all ~19,300 terms, so they are
 * cached: in memory for the run, and on disk between runs
 * (storage/app/terminology/loinc-parts.json) so an interrupted or sliced run
 * does not re-fetch them. `--refresh-parts` discards the file.
 *
 * ── There is no bulk path ────────────────────────────────────────────────
 * Probed and rejected, so nobody probes them again: `ValueSet/$expand` ignores
 * `property=` and returns code + display only, and a FHIR batch Bundle POSTed
 * to /r4 returns 404. It is one HTTP request per code. With connection reuse
 * that measured ~0.3 s/code, so a full fill is roughly an hour and a half.
 * The command is therefore built to be interrupted:
 *
 *   • RESUMABLE — it selects only rows where `specimen IS NULL`, so a second
 *     run continues where the first stopped rather than starting over.
 *   • SLICEABLE — `--limit` takes a bite of any size.
 *   • CHUNKED WRITES — each chunk of 200 is its own guarded transaction, so
 *     work already done survives a Ctrl-C.
 *
 * ── What it refuses to do ────────────────────────────────────────────────
 * On HTTP 429 or 5xx it backs off and retries a bounded number of times; on
 * persistent failure it SKIPS that code, leaves its columns NULL, and reports
 * the count. It never writes a placeholder and never infers an axis. A row
 * left NULL is a correct outcome; a guessed specimen is how a serum result
 * gets filed as a urine one. If the server refuses steadily (many consecutive
 * failures) the run stops cleanly rather than hammering a free public host.
 *
 * A 404 is NOT a refusal and does not count towards that circuit breaker. Our
 * codes came from NLM at LOINC 2.78 and this server serves 2.82, so a code NLM
 * listed and 2.82 does not carry is a real, healthy answer — and since rows are
 * walked in code order those arrive in long runs. They are counted and reported
 * separately, and their rows stay NULL.
 *
 * `component` and `property` are DELIBERATELY not overwritten. The NLM import
 * already filled both on all 19,314 rows with LOINC's own COMPONENT and
 * PROPERTY text, and that is the same data the Part display carries. Clobbering
 * good published data with a second rendering of itself buys nothing and risks
 * losing wording NLM got right, so those two columns are written only where
 * they are still NULL — i.e. essentially never, unless a future import leaves
 * a hole.
 *
 * It adds NO rows and deletes none: every write is an UPDATE matched on
 * (system, code) against a row that already exists. It writes NO column but
 * `specimen`, `scale`, `component`, `property`, `example_units`,
 * `source_version` and `updated_at` — and does not merely intend to: a digest
 * over every other column is taken before and after inside each transaction,
 * and the chunk is rolled back, loudly, if it moves.
 *
 * Idempotent: a filled row is not selected again.
 *
 * Run:  php artisan opescare:import-loinc-axes
 *       php artisan opescare:import-loinc-axes --limit=50 --dry-run
 *       php artisan opescare:import-loinc-axes --limit=2000
 */
class ImportLoincAxes extends Command
{
    protected $signature = 'opescare:import-loinc-axes
        {--limit= : Only process this many still-empty rows (for slicing a long run)}
        {--delay=50 : Milliseconds to pause between requests, to be polite to a free public server}
        {--refresh-parts : Discard the cached LOINC Part displays and re-fetch them}
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Fill LOINC specimen/scale/example_units from HL7 tx.fhir.org (LOINC axes NLM does not publish)';

    /** HL7's public FHIR terminology server. No key, no licence click. */
    private const TX_SERVER = 'https://tx.fhir.org/r4';

    private const LOINC_SYSTEM = 'http://loinc.org';

    /** Rows per guarded transaction. Small enough that Ctrl-C loses little. */
    private const CHUNK = 200;

    /** Attempts per code before it is skipped and left NULL. */
    private const MAX_ATTEMPTS = 4;

    /** Consecutive hard failures after which the run stops rather than hammering. */
    private const CONSECUTIVE_FAILURE_LIMIT = 25;

    /**
     * The FHIR property names to ask for, and the dictionary column each fills.
     * Every one must be named in the query string or the server returns none.
     */
    private const PROPERTY_COLUMNS = [
        'SYSTEM' => 'specimen',
        'SCALE_TYP' => 'scale',
        'COMPONENT' => 'component',
        'PROPERTY' => 'property',
        'EXAMPLE_UNITS' => 'example_units',
    ];

    /** Columns written only when the existing value is NULL — see the doc-block. */
    private const FILL_ONLY_IF_NULL = ['component', 'property'];

    /**
     * Every column that must come out of this command byte-identical. Kept as
     * an explicit list rather than "all except the axes" so that a column added
     * to the table later is noticed here deliberately.
     */
    private const IMMUTABLE_COLUMNS = [
        'id', 'system', 'code', 'display', 'display_fr', 'short_name', 'kind',
        'parent_code', 'chapter', 'synonyms', 'is_common', 'common_reason',
        'is_active', 'source', 'imported_at', 'created_at',
    ];

    /** LOINC Part code => display. Loaded from disk, grown in memory, saved back. */
    private array $parts = [];

    private bool $partsChanged = false;

    /** @var resource|\CurlHandle|null One handle for the whole run, so the TLS connection is reused. */
    private $curl = null;

    private int $requests = 0;

    private int $failures = 0;

    /** HTTP status of the most recent request; 0 when the transport itself failed. */
    protected int $lastStatus = 0;

    private ?string $serverVersion = null;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $delayUs = max(0, (int) $this->option('delay')) * 1000;

        $query = DB::table('clinical_code_dictionary')
            ->where('system', 'LOINC')
            ->whereNull('specimen')
            ->orderBy('code');

        $outstanding = (clone $query)->count();

        if ($this->option('limit') !== null) {
            $query->limit(max(0, (int) $this->option('limit')));
        }

        /** @var list<object{code: string, component: ?string, property: ?string}> $rows */
        $rows = $query->get(['code', 'component', 'property'])->all();

        if ($rows === []) {
            $this->info('Every LOINC row already carries its axes — nothing to do.');

            return self::SUCCESS;
        }

        $this->line(sprintf(
            '  %d LOINC rows still lack their axes; this run will attempt %d, from %s',
            $outstanding,
            count($rows),
            self::TX_SERVER,
        ));

        $this->loadParts();

        $bar = $this->output->createProgressBar(count($rows));
        $filled = 0;
        $skipped = 0;
        $notCarried = 0;
        $noSystem = 0;
        $consecutiveFailures = 0;
        $pending = [];
        $stopped = false;
        $startedAt = microtime(true);

        foreach ($rows as $row) {
            $axes = $this->lookupAxes($row->code, $delayUs);

            if ($axes === null) {
                if ($this->lastStatus === 404) {
                    // The server is healthy and simply does not carry this
                    // code at the release it serves. Left NULL, counted, and
                    // NOT held against the server — see request().
                    $notCarried++;
                    $consecutiveFailures = 0;
                    $bar->advance();

                    continue;
                }

                $skipped++;
                $consecutiveFailures++;
                $bar->advance();

                if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_LIMIT) {
                    $stopped = true;
                    break;
                }

                continue;
            }

            $consecutiveFailures = 0;
            $update = [];

            foreach ($axes as $column => $value) {
                // Never clobber the publisher text NLM already stored.
                if (in_array($column, self::FILL_ONLY_IF_NULL, true) && $row->{$column} !== null) {
                    continue;
                }

                $update[$column] = $value;
            }

            if (! array_key_exists('specimen', $axes)) {
                // The server answered, but carries no SYSTEM axis for this
                // concept. Left NULL rather than filled with anything, so a
                // later run asks again politely instead of storing a guess.
                $noSystem++;
            }

            if ($update === []) {
                $bar->advance();
                continue;
            }

            $pending[$row->code] = $update;
            $filled++;

            if (! $dry && count($pending) >= self::CHUNK) {
                if (! $this->writeChunk($pending)) {
                    $bar->finish();
                    $this->newLine();

                    return self::FAILURE;
                }

                $pending = [];
            }

            $bar->advance();
        }

        if (! $dry && $pending !== []) {
            if (! $this->writeChunk($pending)) {
                $bar->finish();
                $this->newLine();

                return self::FAILURE;
            }
        }

        $bar->finish();
        $this->newLine();

        $this->saveParts();
        $this->closeCurl();

        $elapsed = max(0.001, microtime(true) - $startedAt);

        if ($dry) {
            $this->warn('  Dry run — nothing written.');
        }

        $this->line(sprintf(
            '  %d rows filled. Left NULL: %d not carried by LOINC %s on this server, '
            . '%d skipped after %d failed attempts each, %d answered with no SYSTEM axis.',
            $filled,
            $notCarried,
            $this->serverVersion ?? '?',
            $skipped,
            self::MAX_ATTEMPTS,
            $noSystem,
        ));

        $this->line(sprintf(
            '  %d requests in %s (%.2f req/s), %d Part displays cached, LOINC %s.',
            $this->requests,
            $this->humanDuration($elapsed),
            $this->requests / $elapsed,
            count($this->parts),
            $this->serverVersion ?? 'version not reported',
        ));

        if ($stopped) {
            $this->error(sprintf(
                'STOPPED EARLY: %d consecutive lookups failed. tx.fhir.org is refusing or unreachable. '
                . 'Everything written so far is committed; re-run to continue.',
                self::CONSECUTIVE_FAILURE_LIMIT,
            ));

            return self::FAILURE;
        }

        $this->reportCoverage();

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────────────── lookup ────

    /**
     * The axis values for one LOINC code, keyed by dictionary column.
     *
     * Returns null when the server could not be made to answer within
     * MAX_ATTEMPTS — the caller then leaves every column NULL. An empty array
     * means the server answered and the concept simply carries no axes.
     *
     * @return array<string, string>|null
     */
    protected function lookupAxes(string $code, int $delayUs): ?array
    {
        $properties = implode('', array_map(
            static fn (string $p): string => '&property=' . $p,
            array_keys(self::PROPERTY_COLUMNS),
        ));

        $url = self::TX_SERVER . '/CodeSystem/$lookup?system=' . rawurlencode(self::LOINC_SYSTEM)
            . '&code=' . rawurlencode($code) . $properties;

        $decoded = $this->request($url, $delayUs);

        if ($decoded === null) {
            return null;
        }

        $axes = [];

        foreach ($decoded['parameter'] ?? [] as $parameter) {
            $name = $parameter['name'] ?? null;

            if ($name === 'version' && $this->serverVersion === null) {
                $this->serverVersion = trim((string) ($parameter['valueString'] ?? '')) ?: null;
            }

            if ($name !== 'property') {
                continue;
            }

            $propertyCode = null;
            $valueCode = null;
            $valueString = null;

            foreach ($parameter['part'] ?? [] as $part) {
                $partName = $part['name'] ?? '';

                if ($partName === 'code') {
                    $propertyCode = (string) ($part['valueCode'] ?? '');
                } elseif ($partName === 'value') {
                    $valueCode = isset($part['valueCode']) ? (string) $part['valueCode'] : null;
                    $valueString = isset($part['valueString']) ? (string) $part['valueString'] : null;
                }
            }

            $column = self::PROPERTY_COLUMNS[$propertyCode] ?? null;

            if ($column === null) {
                continue;
            }

            // A valueCode is a LOINC Part and needs resolving; a valueString
            // (EXAMPLE_UNITS) is already the text.
            $value = $valueCode !== null
                ? $this->partDisplay($valueCode, $delayUs)
                : trim((string) $valueString);

            if ($value !== null && $value !== '') {
                $axes[$column] = $value;
            }
        }

        return $axes;
    }

    /**
     * The human display for a LOINC Part code, e.g. LP7057-5 => "Bld".
     *
     * Cached, because a few hundred Parts cover every term and re-fetching them
     * per row would roughly triple the runtime. A Part that cannot be resolved
     * returns null and its column is left NULL — the Part code itself is never
     * stored as though it were a specimen name.
     */
    protected function partDisplay(string $partCode, int $delayUs): ?string
    {
        if (array_key_exists($partCode, $this->parts)) {
            return $this->parts[$partCode];
        }

        $decoded = $this->request(
            self::TX_SERVER . '/CodeSystem/$lookup?system=' . rawurlencode(self::LOINC_SYSTEM)
                . '&code=' . rawurlencode($partCode),
            $delayUs,
        );

        if ($decoded === null) {
            return null;
        }

        $display = null;

        foreach ($decoded['parameter'] ?? [] as $parameter) {
            if (($parameter['name'] ?? null) === 'display') {
                $display = trim((string) ($parameter['valueString'] ?? ''));
                break;
            }
        }

        $display = ($display === '' ? null : $display);

        // Cached even when null: an unresolvable Part is unresolvable every
        // time, and re-asking 19,000 times would be rude and pointless.
        $this->parts[$partCode] = $display;
        $this->partsChanged = true;

        return $display;
    }

    // ─────────────────────────────────────────────────────────── write ────

    /**
     * Write one chunk, guarded.
     *
     * UPDATE only, matched on (system, code), so no row can be created and no
     * code added. The digest over every column this command promised not to
     * touch is taken inside the transaction, before and after; if it moves the
     * whole chunk is discarded and somebody is told, loudly.
     *
     * @param  array<string, array<string, string>>  $pending  code => column => value
     */
    protected function writeChunk(array $pending): bool
    {
        DB::beginTransaction();

        try {
            $before = $this->immutableDigest();

            foreach ($pending as $code => $update) {
                DB::table('clinical_code_dictionary')
                    ->where('system', 'LOINC')
                    ->where('code', (string) $code)
                    ->update($this->updatePayload((string) $code, $update));
            }

            $after = $this->immutableDigest();

            if ($after !== $before) {
                DB::rollBack();

                $this->newLine();
                $this->error('ABORTED AND ROLLED BACK: a column this command must not touch changed. '
                    . 'Digest before ' . $before . ', after ' . $after . '. This chunk was discarded.');

                return false;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * The exact column set one row's UPDATE is allowed to carry.
     *
     * A single named seam, so the guard above can be proved rather than
     * asserted: the test overrides this to smuggle `display` into the payload
     * and shows the digest catches it and the chunk is rolled back.
     *
     * @param  array<string, string>  $update
     * @return array<string, mixed>
     */
    protected function updatePayload(string $code, array $update): array
    {
        $update['source_version'] = $this->serverVersion ?? 'tx.fhir.org (version not reported)';
        $update['updated_at'] = now();

        return $update;
    }

    /**
     * A digest over every column this command must not touch, across the whole
     * table, plus the row count — so an inserted or deleted row is caught too.
     */
    protected function immutableDigest(): string
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

    // ─────────────────────────────────────────────────────── part cache ────

    private function partsPath(): string
    {
        return storage_path('app/terminology/loinc-parts.json');
    }

    private function loadParts(): void
    {
        if ($this->option('refresh-parts')) {
            return;
        }

        $path = $this->partsPath();

        if (! is_file($path)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (is_array($decoded)) {
            $this->parts = $decoded;
            $this->line('  ' . count($this->parts) . ' LOINC Part displays reused from ' . $path);
        }
    }

    private function saveParts(): void
    {
        if (! $this->partsChanged) {
            return;
        }

        $path = $this->partsPath();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, json_encode($this->parts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // ────────────────────────────────────────────────────────── report ────

    private function reportCoverage(): void
    {
        /** @var object $row */
        $row = DB::selectOne(
            "SELECT count(*) AS total, count(specimen) AS specimen, count(scale) AS scale,
                    count(example_units) AS units, count(component) AS component, count(property) AS property
               FROM clinical_code_dictionary WHERE system = 'LOINC'"
        );

        $this->newLine();
        $this->table(
            ['LOINC rows', 'specimen', 'scale', 'example_units', 'component', 'property'],
            [[$row->total, $row->specimen, $row->scale, $row->units, $row->component, $row->property]],
        );

        $this->line('  LOINC content © Regenstrief Institute, Inc., via HL7 tx.fhir.org, used under the LOINC licence.');
    }

    private function humanDuration(float $seconds): string
    {
        return $seconds < 60
            ? sprintf('%.1fs', $seconds)
            : sprintf('%dm %ds', (int) ($seconds / 60), (int) fmod($seconds, 60));
    }

    // ──────────────────────────────────────────────────────── transport ────

    /**
     * One FHIR GET, with bounded backoff.
     *
     * 429 and 5xx are retried with a widening pause; anything else — including
     * a 404 for a code this server does not carry — is a real answer and is not
     * retried. Persistent failure returns null, and the caller leaves the row
     * alone. One cURL handle for the whole run, so the TLS connection is reused
     * (measured ~0.3 s/request instead of ~1.0 s).
     *
     * `$lastStatus` is set on every call so the caller can tell the two kinds
     * of null apart: a 404 is the server working correctly and saying it has
     * never heard of the code, and must NOT count towards the "the server is
     * refusing us" circuit breaker. It matters: our LOINC codes came from NLM
     * at release 2.78 and tx.fhir.org serves 2.82, so codes NLM listed that
     * 2.82 does not carry exist — and because rows are walked in code order
     * they arrive in RUNS. A first version of this command treated 25
     * consecutive 404s as an outage and stopped a healthy import dead.
     *
     * @return array<string, mixed>|null
     */
    protected function request(string $url, int $delayUs): ?array
    {
        $this->lastStatus = 0;

        if ($this->curl === null) {
            $this->curl = curl_init();
            curl_setopt_array($this->curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/fhir+json, application/json',
                    // hl7.org hosts answer 405 to a request with no User-Agent.
                    'User-Agent: OpesCare-Finder terminology importer',
                ],
            ]);
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            if ($delayUs > 0) {
                usleep($delayUs);
            }

            curl_setopt($this->curl, CURLOPT_URL, $url);
            $body = curl_exec($this->curl);
            $this->requests++;
            $status = (int) curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $this->lastStatus = $status;

            if ($body !== false && $status === 200) {
                $decoded = json_decode((string) $body, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            $transportFailure = ($body === false);
            $retryable = $transportFailure || $status === 429 || $status >= 500;

            if (! $retryable) {
                // A 404 or a 400 is the server's real answer. Nothing to retry.
                $this->failures++;

                return null;
            }

            // 1s, 3s, 9s — a free public host gets room, not a hammer.
            usleep((int) (1_000_000 * (3 ** ($attempt - 1))));
        }

        $this->failures++;

        return null;
    }

    private function closeCurl(): void
    {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }
}
