<?php

namespace App\Console\Commands;

use App\Support\Terminology\ClinicalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports orderable imaging studies — the RSNA Radiology Playbook — into the
 * clinical code dictionary.
 *
 * ── Why these are LOINC rows and not a system of their own ───────────────
 * Radiology orders are not a separate vocabulary. The RSNA Radiology Playbook
 * IS part of LOINC: "CT Chest" is LOINC 24627-2, published by Regenstrief in
 * the same release as the lab terms. Filing it under a made-up system like
 * 'RADLEX' or 'IMAGING' would break every join in this platform and would be
 * a lie about where the code came from, so imaging lands under `system =
 * 'LOINC'` alongside the labs. What separates it is `common_reason`, which
 * records the modality that put it there — 'Imaging: CT', 'Imaging: MRI' —
 * so a reviewer can see the basis, not just the verdict.
 *
 * ── The same open source, and the same limits ────────────────────────────
 * This reads the NLM Clinical Table Search Service, exactly as
 * `opescare:import-loinc` does, for the same reason: the full LOINC table on
 * loinc.org is behind a licence click, and a licence acceptance is the
 * owner's to make and not a script's.
 *
 *   https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search
 *
 * NLM publishes LOINC_NUM, LONG_COMMON_NAME, SHORTNAME, COMPONENT, PROPERTY
 * and RELATEDNAMES2 and nothing else. So `scale`, `specimen` and
 * `example_units` stay NULL on every row written here. They are left empty
 * rather than inferred — the anatomic region of a study is not something to
 * guess from its title. `--file` on `opescare:import-loinc` fills those in
 * from the licensed release CSV if the owner ever accepts the licence.
 *
 * ── Overlap with the lab import is expected, and handled ─────────────────
 * A code reached by a modality sweep here may already be in the dictionary
 * from `opescare:import-loinc` — the two searches genuinely overlap. First
 * sighting wins, as it does there: a row that already carries a
 * `common_reason` keeps it, and this import updates only its descriptive
 * columns. Overwriting "LOINC Universal Lab Orders" with "Imaging: CT"
 * because a title happened to match a second sweep would destroy the
 * publisher's own designation, which is the stronger claim.
 *
 * Run:  php artisan opescare:import-imaging
 */
class ImportImagingCodes extends Command
{
    protected $signature = 'opescare:import-imaging
        {--release=2.78 : The LOINC release these Playbook terms belong to}
        {--dry-run : Fetch and report, write nothing}';

    protected $description = 'Import RSNA Radiology Playbook imaging studies (LOINC) into the clinical code dictionary';

    private const API = 'https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search';

    private const FIELDS = 'LOINC_NUM,LONG_COMMON_NAME,SHORTNAME,COMPONENT,PROPERTY,RELATEDNAMES2';

    /**
     * NLM caps maxList at 1000 and there is NO usable paging past it.
     *
     * The endpoint accepts `offset` and looks like it honours it, but supplying
     * it silently resets maxList to the default 7 — so a paged harvest quietly
     * collects a seventh of every page after the first and reports success.
     * Measured on this endpoint, not assumed:
     *
     *     maxList=500 offset=0   -> 500 rows
     *     maxList=500 offset=500 ->   7 rows
     *
     * A term over the ceiling is therefore SUBDIVIDED into narrower AND-ed
     * terms, never paged, and whatever is still short after subdivision is
     * reported rather than swallowed.
     */
    private const PAGE = 1000;

    /**
     * A harvest that comes back this small has failed, whatever the HTTP
     * status said. The Playbook is thousands of terms wide; if a network
     * blip, a DNS hijack or an endpoint change leaves us with a handful of
     * rows, writing them would quietly replace nothing with almost nothing
     * and look like a successful import in the log.
     */
    private const SANITY_FLOOR = 400;

    /**
     * The modality sweeps. Keys become `common_reason`; values are SEARCH
     * TERMS, not codes.
     *
     * Nothing here asserts that a given LOINC number means a given study —
     * LOINC's own index decides every match, every returned code is check-digit
     * verified, and every kept row is confirmed to be a radiology term by
     * `isImaging()` below. The modality list is simply the set of ways a
     * clinician in Cameroon actually orders imaging.
     */
    private const MODALITIES = [
        'CT' => ['CT', 'CTA', 'computed tomography'],
        'MRI' => ['MR', 'MRI', 'MRA', 'magnetic resonance'],
        'Ultrasound' => ['US', 'ultrasound', 'sonography'],
        'X-ray' => ['XR', 'radiography', 'plain film'],
        'Fluoroscopy' => ['RF', 'fluoroscopy', 'fluoroscopic'],
        'Mammography' => ['MG', 'mammography', 'mammogram'],
        'Echocardiography' => ['echocardiography', 'echocardiogram'],
        'Doppler' => ['doppler'],
        'Angiography' => ['angiography', 'angiogram', 'venography'],
        'Nuclear medicine' => ['NM', 'nuclear medicine', 'scintigraphy'],
        'PET' => ['PET', 'positron emission'],
        'Bone densitometry' => ['DXA', 'bone densitometry'],
    ];

    /**
     * Body regions, swept on their own so a region that is ordered constantly
     * here is reached whichever modality names it.
     *
     * These are region sweeps, so the modality is unknown at collection time
     * and `common_reason` says exactly that: 'Imaging: <region>'. A region row
     * is not claimed to be a CT or an X-ray, because the search cannot know.
     */
    private const REGIONS = [
        'chest', 'abdomen', 'pelvis', 'head', 'brain', 'spine', 'neck',
        'extremity', 'obstetric', 'breast', 'heart', 'kidney', 'liver',
    ];

    /**
     * Words that split an over-ceiling term along the Playbook's own axes.
     *
     * NLM ANDs the words in `terms`, so "CT abdomen" is a strict subset of
     * "CT". Slicing by region, contrast and view reaches results the 1,000-row
     * ceiling hides, without guessing anything about the extra codes.
     */
    private const SLICES = [
        'chest', 'abdomen', 'pelvis', 'head', 'brain', 'spine', 'neck',
        'shoulder', 'knee', 'hip', 'ankle', 'wrist', 'foot', 'hand',
        'contrast', 'without contrast', 'guidance', 'biopsy',
        'left', 'right', 'bilateral', 'views', 'limited', 'portable',
    ];

    /**
     * Modality prefixes the Playbook uses at the head of a LONG_COMMON_NAME.
     *
     * Used only as a second gate on "is this actually a radiology term" — it
     * never supplies a value, only decides whether a published row is kept.
     */
    private const IMAGING_PREFIXES = [
        'CT', 'CTA', 'MR', 'MRA', 'MRI', 'US', 'XR', 'RF', 'NM', 'PET',
        'MG', 'DXA', 'Fluoroscopy', 'Ultrasound', 'Radiography', 'Mammography',
        'Echocardiography', 'Angiography', 'Scintigraphy', 'Portable',
    ];

    /** Search terms that matched more than one request can return: term => missing count. */
    private array $shortfalls = [];

    public function handle(): int
    {
        $release = (string) $this->option('release');
        $dry = (bool) $this->option('dry-run');

        // code => ['row' => [...], 'reason' => string]
        $collected = [];

        foreach (self::MODALITIES as $modality => $terms) {
            $before = count($collected);

            foreach ($terms as $term) {
                $this->harvestDeep($term, 'Imaging: ' . $modality, $collected);
            }

            $this->line(sprintf('  %-22s +%d', $modality, count($collected) - $before));
        }

        foreach (self::REGIONS as $region) {
            $before = count($collected);

            // Region sweeps are ANDed with "imaging" so the request itself is
            // narrowed to radiology rather than returning every lab test that
            // mentions the word "liver".
            $this->harvestDeep('imaging ' . $region, 'Imaging: ' . $region, $collected);

            $this->line(sprintf('  %-22s +%d', $region, count($collected) - $before));
        }

        $this->newLine();
        $this->line('  ' . count($collected) . ' distinct imaging LOINC terms collected');

        if ($this->shortfalls !== []) {
            $this->newLine();
            $this->warn('These search terms matched more LOINC codes than one request can return. '
                . 'The dictionary is short by up to this many imaging terms in each area — narrow the '
                . 'search term, or load the licensed LOINC release CSV with opescare:import-loinc --file:');

            arsort($this->shortfalls);

            foreach (array_slice($this->shortfalls, 0, 12, true) as $term => $missing) {
                $this->line(sprintf('    %-34s ~%d not fetched', $term, $missing));
            }
        }

        if (count($collected) < self::SANITY_FLOOR) {
            $this->error(sprintf(
                'Refusing to write: %d imaging terms is implausibly few (floor %d). The Radiology '
                . 'Playbook is thousands of terms wide, so this is a failed harvest, not a small one. '
                . 'Nothing has been changed.',
                count($collected),
                self::SANITY_FLOOR,
            ));

            return self::FAILURE;
        }

        if ($dry) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        return $this->write($collected, $release);
    }

    /**
     * Write the harvest, protecting reasons that were set before us.
     *
     * The dictionary already holds thousands of LOINC lab rows and the two
     * searches overlap. A code that already carries a `common_reason` is
     * updated for its descriptive columns ONLY: `is_common` and
     * `common_reason` are left off that batch's update list entirely, so the
     * earlier, more specific designation survives. Same rule as the CSV path
     * in ImportLoinc, for the same reason.
     */
    private function write(array $collected, string $release): int
    {
        $claimed = DB::table('clinical_code_dictionary')
            ->where('system', 'LOINC')
            ->whereNotNull('common_reason')
            ->pluck('code')
            ->flip();

        $fresh = [];
        $keepReason = [];

        foreach ($collected as $code => $entry) {
            $row = $entry['row'];
            $row['source_version'] = $release;

            if ($claimed->has($code)) {
                $keepReason[] = $row;
            } else {
                $fresh[] = $row;
            }
        }

        $descriptive = ['display', 'short_name', 'component', 'property', 'synonyms',
            'is_active', 'source', 'source_version', 'imported_at', 'updated_at'];

        $bar = $this->output->createProgressBar(count($fresh) + count($keepReason));

        foreach (array_chunk($fresh, 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                array_merge($descriptive, ['is_common', 'common_reason']),
            );
            $bar->advance(count($chunk));
        }

        foreach (array_chunk($keepReason, 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert($chunk, ['system', 'code'], $descriptive);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'RSNA Radiology Playbook (LOINC %s): %d imaging terms written — %d flagged with a modality '
            . 'reason, %d already carried an earlier reason and kept it.',
            $release,
            count($fresh) + count($keepReason),
            count($fresh),
            count($keepReason),
        ));

        $this->warn('scale, specimen and example_units are NULL on these rows: the open NLM endpoint does '
            . 'not publish those columns. They are left empty rather than inferred.');

        return self::SUCCESS;
    }

    /**
     * Harvest a term, and subdivide it if it hit the ceiling.
     *
     * A term over the 1,000-row ceiling is re-issued narrowed by each slice
     * word. This does not guarantee completeness — a slice can hit the ceiling
     * too — so whatever is still short afterwards is reported, because an
     * unreported ceiling is how a partial dictionary passes for a complete one.
     */
    private function harvestDeep(string $term, string $reason, array &$collected): void
    {
        $this->harvest($term, $reason, $collected);

        if (! isset($this->shortfalls[$term])) {
            return;
        }

        // The parent's shortfall is superseded by whatever the slices leave
        // behind; drop it so the report shows the remaining gap.
        unset($this->shortfalls[$term]);

        foreach (self::SLICES as $slice) {
            $this->harvest($term . ' ' . $slice, $reason, $collected);
        }
    }

    /**
     * Fold one search term into the collection.
     *
     * First reason wins: the modality sweeps run before the region sweeps, so
     * a study reached as "CT" keeps 'Imaging: CT' even though the "imaging
     * chest" sweep also matches it. The modality is the more specific claim.
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

        if (is_int($total) && $total > count($rows)) {
            $this->shortfalls[$term] = $total - count($rows);
        }

        foreach ($rows as $r) {
            [$code, $long, $short, $component, $property, $related] = array_pad((array) $r, 6, '');

            $code = trim((string) $code);

            // A LOINC number that fails its own check digit is not a LOINC
            // number, whoever served it. Same guard as the lab import — it is
            // what caught 70563-7 being offered for a malaria rapid test.
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

            // A text search for "US" or "PET" also matches lab terms. Keeping
            // those and calling them imaging would put a serum assay on a
            // radiology order list, so a row that cannot be shown to be a
            // radiology term is dropped, not relabelled.
            if (! $this->isImaging($display, (string) $related)) {
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
                    // Not published by this endpoint. Left NULL, never guessed.
                    'scale' => null,
                    'specimen' => null,
                    'example_units' => null,
                    'synonyms' => trim((string) $related) ?: null,
                    'is_common' => true,
                    'common_reason' => $reason,
                    // LOINC marks a withdrawn term by prefixing its long name
                    // with "Deprecated". The row still has to resolve for the
                    // studies already filed against it, so it is stored
                    // inactive rather than dropped.
                    'is_active' => ! Str::startsWith($display, 'Deprecated'),
                    'source' => 'NLM Clinical Table Search Service (LOINC / RSNA Radiology Playbook)',
                    'source_version' => null,
                    'imported_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
        }
    }

    /**
     * Is this published row actually a radiology term?
     *
     * Two gates, both reading only what LOINC itself published:
     *
     *  1. RELATEDNAMES2 on a Playbook term carries LOINC's own class words —
     *     "Rad", "RADIOLOGY", "Imaging". That is the publisher's designation
     *     leaking into an exposed column, the same trick the lab import uses
     *     to find the UniversalLabOrders set.
     *
     *  2. Failing that, a LONG_COMMON_NAME that opens with a Playbook modality
     *     token ("CT Chest", "MR Brain", "XR Wrist").
     *
     * This filter can only REJECT. It never supplies a value, so a term it
     * wrongly drops is an absent code — which is the safe direction.
     */
    private function isImaging(string $display, string $related): bool
    {
        if (preg_match('/\b(rad|radiology|imaging)\b/i', $related)) {
            return true;
        }

        $name = preg_replace('/^Deprecated\s+/i', '', $display);
        $first = strtok($name, ' ');

        return $first !== false && in_array($first, self::IMAGING_PREFIXES, true);
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
            return null;
        }

        return $decoded;
    }
}
