<?php

namespace App\Console\Commands;

use App\Support\Terminology\ClinicalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

/**
 * Imports the WHO ICD-10 classification from WHO's own ClaML release.
 *
 * ── Why WHO ICD-10 and not ICD-10-CM ─────────────────────────────────────
 * There are two things called "ICD-10" and they are not the same list.
 * ICD-10-CM is the United States clinical modification: ~95,000 codes, with
 * laterality and encounter-episode extensions that exist for US billing.
 * Cameroon, like every MINSANTE-regulated system and every other WHO member
 * reporting to the Global Health Observatory, uses the WHO base
 * classification: ~11,500 classes, no CM extensions.
 *
 * Loading ICD-10-CM here would look like a bigger, better dataset and would
 * be the wrong vocabulary — codes no ministry form accepts and a mortality
 * report that cannot be filed. The larger file is the wrong answer.
 *
 * ── Source ───────────────────────────────────────────────────────────────
 *   https://icdcdn.who.int/icd10/claml/icd102019en.xml.zip
 *
 * WHO publishes this openly: no licence key, no registration. 2019 is the
 * final ICD-10 release before ICD-11, so it is the version to hold.
 *
 * ── What is imported ─────────────────────────────────────────────────────
 * All three kinds, because they are not interchangeable:
 *   chapter   I..XXII        a heading. NOT diagnosable.
 *   block     A00-A09        a range heading. NOT diagnosable.
 *   category  A00, A00.0     the actual code a clinician assigns.
 *
 * Keeping the kind is what stops a form offering "Chapter I: Certain
 * infectious and parasitic diseases" as somebody's diagnosis.
 *
 * Inclusion terms are folded into `synonyms`: "Classical cholera" is how a
 * clinician will search for A00.0, and the preferred label does not contain
 * those words.
 *
 * Run:  php artisan opescare:import-icd10
 */
class ImportIcd10 extends Command
{
    protected $signature = 'opescare:import-icd10
        {--file= : Path to WHO ClaML XML (icd102019en.xml); downloaded if absent}
        {--url=https://icdcdn.who.int/icd10/claml/icd102019en.xml.zip : Where to download it from}
        {--release=2019 : The WHO release being imported}
        {--dry-run : Parse and report, write nothing}';

    protected $description = 'Import the WHO ICD-10 classification into the clinical code dictionary';

    private const SOURCE = 'WHO ICD-10 ClaML';

    /**
     * A release this size is a known quantity. If the parse suddenly yields a
     * few hundred rows, something upstream changed shape — and writing that
     * over a good dictionary is worse than stopping.
     */
    private const MIN_CATEGORIES = 8000;

    public function handle(): int
    {
        $path = $this->option('file') ?: $this->download((string) $this->option('url'));

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $version = (string) $this->option('release');
        $dry = (bool) $this->option('dry-run');

        $rows = [];
        $counts = ['chapter' => 0, 'block' => 0, 'category' => 0];
        $skipped = 0;

        // group => ['.0' => 'With coma', ...]
        $modifierLabels = [];
        // parent category code => [group, ...]
        $pendingModifiers = [];

        $reader = new XMLReader();

        // A plain path, not a file:// URI — XMLReader treats the URI form as a
        // remote stream and refuses it outright. realpath() also settles the
        // relative path the command is normally invoked with.
        if (! $reader->open((string) realpath($path))) {
            throw new RuntimeException("XMLReader could not open {$path}");
        }

        // Streamed rather than simplexml_load_file: the release is ~9.5 MB of
        // XML across ~11,500 nodes, and building the whole DOM to read it flat
        // is memory this can spend somewhere better.
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            // The shared fourth-character lists. ClaML declares each once —
            // "the following fourth-character subdivisions are for use with
            // categories E10-E14" — and every category that uses it carries a
            // reference rather than its own child elements.
            if ($reader->name === 'ModifierClass') {
                $mod = simplexml_load_string($reader->readOuterXml());

                if ($mod !== false) {
                    $suffix = trim((string) $mod['code']);
                    $group = trim((string) $mod['modifier']);

                    foreach ($mod->Rubric as $rubric) {
                        if ((string) $rubric['kind'] === 'preferred') {
                            $modifierLabels[$group][$suffix] = $this->flatten($rubric->Label);
                            break;
                        }
                    }
                }

                continue;
            }

            if ($reader->name !== 'Class') {
                continue;
            }

            $node = simplexml_load_string($reader->readOuterXml());

            if ($node === false) {
                $skipped++;
                continue;
            }

            $code = trim((string) $node['code']);
            $kind = trim((string) $node['kind']);

            if ($code === '' || ! array_key_exists($kind, $counts)) {
                $skipped++;
                continue;
            }

            // Which shared fourth-character list, if any, subdivides this
            // category. Recorded now and expanded after the pass, because the
            // list itself may be declared either side of the category in the
            // file and streaming gives no way to look ahead.
            foreach ($node->ModifiedBy as $modifiedBy) {
                $ref = trim((string) $modifiedBy['code']);

                if ($ref !== '') {
                    $pendingModifiers[$code][] = $ref;
                }
            }

            $preferred = null;
            $synonyms = [];

            foreach ($node->Rubric as $rubric) {
                $rubricKind = (string) $rubric['kind'];
                $label = $this->flatten($rubric->Label);

                if ($label === '') {
                    continue;
                }

                if ($rubricKind === 'preferred') {
                    $preferred = $label;
                } elseif ($rubricKind === 'inclusion' || $rubricKind === 'preferredLong') {
                    // Inclusion terms are how a clinician actually searches.
                    // "Classical cholera" finds nothing against A00.0's
                    // preferred label, which talks about biovar cholerae.
                    $synonyms[] = $label;
                }
            }

            if ($preferred === null) {
                $skipped++;
                continue;
            }

            // A category must still look like ICD-10. A chapter's code is a
            // Roman numeral and a block's is a range, so neither is checked
            // against a pattern built for diagnosable codes.
            if ($kind === 'category' && ! ClinicalCode::isIcd10($code)) {
                $this->warn("  malformed category code skipped: {$code}");
                $skipped++;
                continue;
            }

            $counts[$kind]++;

            $rows[] = [
                'id' => (string) Str::uuid(),
                'system' => 'ICD-10',
                'code' => $code,
                'display' => $preferred,
                'display_fr' => null,
                'short_name' => null,
                'kind' => $kind,
                'parent_code' => isset($node->SuperClass[0]) ? trim((string) $node->SuperClass[0]['code']) : null,
                'chapter' => null,
                'component' => null,
                'property' => null,
                'scale' => null,
                'specimen' => null,
                'example_units' => null,
                'synonyms' => $synonyms === [] ? null : implode('; ', array_unique($synonyms)),
                'is_common' => false,
                'common_reason' => null,
                'is_active' => true,
                'source' => self::SOURCE,
                'source_version' => $version,
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $reader->close();

        $expanded = $this->expandFourthCharacters($rows, $pendingModifiers, $modifierLabels, $version);
        $counts['category'] += $expanded;

        $this->line("  expanded {$expanded} fourth-character subdivisions (E11.9 and the like)");

        $this->line(sprintf(
            '  parsed  chapters=%d  blocks=%d  categories=%d  skipped=%d',
            $counts['chapter'],
            $counts['block'],
            $counts['category'],
            $skipped,
        ));

        if ($counts['category'] < self::MIN_CATEGORIES) {
            $this->error('Refusing to import: only ' . $counts['category'] . ' categories parsed, expected ~11,000. '
                . 'The source file looks wrong or truncated.');

            return self::FAILURE;
        }

        if ($dry) {
            $this->info('Dry run — nothing written.');

            return self::SUCCESS;
        }

        $written = $this->upsert($rows);
        $this->resolveChapters();

        $this->newLine();
        $this->info("ICD-10 {$version}: {$written} codes in the dictionary.");
        $this->warn('Titles are the published ENGLISH. French titles are a separate import — they are not '
            . 'translated here, because a guessed diagnosis title is a wrong one.');

        return self::SUCCESS;
    }

    /**
     * Build the fourth-character codes that ClaML declares by reference.
     *
     * ── The gap this closes ──────────────────────────────────────────────
     * ICD-10 does not repeat a fourth-character list for every category that
     * shares it. E10 through E14 all take the same ten subdivisions, so the
     * file declares them once as a Modifier and each category points at it.
     * Reading only the Class elements therefore yields E11 but NOT E11.9 —
     * and E11.9, type 2 diabetes without complications, is among the most
     * assigned codes in the world. It was missing from the first import,
     * along with roughly two thousand others.
     *
     * ── These titles are COMPOSED, and the row says so ───────────────────
     * WHO does not publish a single string for E11.9. The title here is the
     * category's title joined to the subdivision's — "Type 2 diabetes
     * mellitus, without complications" — which is exactly how ClaML defines
     * such a code to be read, but it is still assembled rather than quoted.
     * Every one of these rows is therefore stamped with a distinct `source`,
     * so anyone can tell at a glance which titles came verbatim from WHO and
     * which this importer built. That distinction should never be guessable.
     *
     * @param  array  $rows              parsed rows, appended to in place
     * @param  array  $pending           parent code => modifier group refs
     * @param  array  $labels            modifier group => suffix => label
     */
    private function expandFourthCharacters(array &$rows, array $pending, array $labels, string $version): int
    {
        if ($pending === [] || $labels === []) {
            return 0;
        }

        // Codes already present as their own Class element win outright — a
        // published title is always better than a composed one.
        $existing = [];

        foreach ($rows as $row) {
            $existing[$row['code']] = true;
        }

        $parents = [];

        foreach ($rows as $row) {
            if (isset($pending[$row['code']])) {
                $parents[$row['code']] = $row;
            }
        }

        $added = 0;

        foreach ($pending as $parentCode => $groups) {
            if (! isset($parents[$parentCode])) {
                continue;
            }

            $parent = $parents[$parentCode];

            foreach ($groups as $group) {
                foreach ($labels[$group] ?? [] as $suffix => $label) {
                    // The suffix carries its own dot (".9"), so the two
                    // concatenate directly. Adding one here yields "E11..9".
                    $code = $parentCode . $suffix;

                    if (isset($existing[$code]) || $label === '' || ! ClinicalCode::isIcd10($code)) {
                        continue;
                    }

                    $existing[$code] = true;
                    $added++;

                    $rows[] = array_merge($parent, [
                        'id' => (string) Str::uuid(),
                        'code' => $code,
                        'display' => rtrim($parent['display'], '.') . ', ' . lcfirst($label),
                        'kind' => 'category',
                        'parent_code' => $parentCode,
                        'synonyms' => null,
                        'source' => self::SOURCE . ' (fourth character composed)',
                        'source_version' => $version,
                    ]);
                }
            }
        }

        return $added;
    }

    /** Chunked upsert keyed on (system, code), so a re-import replaces a release cleanly. */
    private function upsert(array $rows): int
    {
        $written = 0;
        $bar = $this->output->createProgressBar(count($rows));

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('clinical_code_dictionary')->upsert(
                $chunk,
                ['system', 'code'],
                ['display', 'display_fr', 'short_name', 'kind', 'parent_code', 'synonyms',
                    'is_active', 'source', 'source_version', 'imported_at', 'updated_at'],
            );
            $written += count($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();

        return $written;
    }

    /**
     * Walk every class up to the chapter it belongs to.
     *
     * ClaML gives a node its immediate parent only, so A00.0's chapter is
     * three hops away. A recursive CTE does it in one statement instead of
     * 11,000 round trips.
     */
    private function resolveChapters(): void
    {
        DB::statement(
            // `cur` carries the ANCESTOR'S OWN CODE up the chain. Emitting
            // d.parent_code here instead is the obvious-looking version and is
            // wrong: on the hop that reaches a chapter, the chapter's parent is
            // NULL, so every row is assigned a null chapter and the statement
            // reports success having set nothing.
            "WITH RECURSIVE up AS (
                 SELECT code AS leaf, code AS cur, parent_code, kind
                   FROM clinical_code_dictionary
                  WHERE system = 'ICD-10'
                 UNION ALL
                 SELECT u.leaf, d.code, d.parent_code, d.kind
                   FROM up u
                   JOIN clinical_code_dictionary d
                     ON d.system = 'ICD-10' AND d.code = u.parent_code
             )
             UPDATE clinical_code_dictionary t
                SET chapter = c.root
               FROM (
                    SELECT u.leaf, u.cur AS root
                      FROM up u
                     WHERE u.kind = 'chapter'
               ) c
              WHERE t.system = 'ICD-10' AND t.code = c.leaf"
        );

        // Verified, not assumed. The first version of the statement above ran
        // without error and assigned a null chapter to all 11,539 rows; a
        // successful UPDATE that changes nothing looks identical to a working
        // one unless something counts the result.
        $resolved = DB::table('clinical_code_dictionary')
            ->where('system', 'ICD-10')
            ->whereNotNull('chapter')
            ->count();

        if ($resolved === 0) {
            throw new RuntimeException(
                'Chapter resolution assigned nothing. The hierarchy walk is broken — '
                . 'refusing to leave the dictionary in a half-built state.'
            );
        }

        $this->line("  chapters resolved for {$resolved} codes");
    }

    /** Fetch and unzip the WHO release under storage/app/terminology. */
    private function download(string $url): string
    {
        $dir = storage_path('app/terminology');

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create {$dir}");
        }

        $this->line("  downloading {$url}");

        $body = @file_get_contents($url);

        if ($body === false || strlen($body) < 100_000) {
            throw new RuntimeException("Download failed or returned too little data: {$url}");
        }

        $zip = $dir . '/icd10.xml.zip';
        file_put_contents($zip, $body);

        $archive = new ZipArchive();

        if ($archive->open($zip) !== true) {
            throw new RuntimeException("Not a readable zip: {$zip}");
        }

        $name = $archive->getNameIndex(0);
        $archive->extractTo($dir);
        $archive->close();

        return $dir . '/' . $name;
    }

    /** ClaML labels carry nested markup; the text is what we store. */
    private function flatten(SimpleXMLElement $label): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags((string) $label->asXML())));
    }
}
