<?php

namespace App\Console\Commands;

use App\Modules\CareMap\Services\CameroonMasterFacilityImporter;
use App\Modules\CareMap\Services\ExternalFacilityImporter;
use Illuminate\Console\Command;

/**
 * Import the Cameroon national facility master list.
 *
 * ## What this imports
 *
 * `resources/data/cameroon_master_facilities.json` — 1,016 facilities across all
 * ten regions, converted from the workbook the project owner supplied. It is
 * institutional data: real buildings, in the public finder, never `demo_seed`.
 * It is also entirely UNVERIFIED (the workbook's own verification columns are
 * blank on all 1,030 of its rows), and its underlying source is Google Maps /
 * Google Places rather than MINSANTE — so nothing this command writes is ever
 * marked verified, and inserted rows carry `license_status = 'unknown'`.
 *
 * ## Running it
 *
 *   php artisan facilities:import-master                       # dry run, whole file
 *   php artisan facilities:import-master --region=Littoral     # dry run, one region
 *   php artisan facilities:import-master --apply               # actually write
 *
 * A dry run is the DEFAULT, not an option you have to remember. The command
 * reports exactly what it would insert, enrich and hold for review — broken down
 * by region and by reason — and writes nothing unless `--apply` is given. This
 * is a national directory 1,863 rows deep; the report is the thing you read
 * before you change it, and a flag you can forget to pass is not a safeguard.
 *
 * Re-running with `--apply` is always safe. Each record is identified by its
 * Google Place ID (`source_ref = 'gplaces:<place_id>'`, a UNIQUE column), so a
 * second run finds every row by that link, computes no changes, and writes
 * nothing — not one `updated_at`.
 *
 * It is the same machinery as `facilities:import-osm`: both drive
 * ExternalFacilityImporter, so the thresholds that decide "same building or
 * different building?" exist once, and uncertain candidates land in the same
 * `facility_import_reviews` queue with the same reasons, reviewable at
 * /admin/care-map/review/imports.
 */
class ImportCameroonMasterFacilities extends Command
{
    protected $signature = 'facilities:import-master
        {--file= : Path to the master dataset JSON. Defaults to resources/data/cameroon_master_facilities.json.}
        {--region= : Import only the records in one region (administrative spelling, e.g. "Extrême-Nord").}
        {--limit=0 : Stop after this many records. 0 = no limit.}
        {--dry-run : Report what would change and write nothing. This is also the default.}
        {--apply : Write. Without this flag the command only reports.}';

    protected $description = 'Import the Cameroon national facility master list, enriching existing rows rather than duplicating them';

    public function handle(CameroonMasterFacilityImporter $importer): int
    {
        $path = (string) ($this->option('file') ?: resource_path('data/cameroon_master_facilities.json'));

        if (! is_file($path)) {
            $this->error(__('facility_master.file_missing', ['path' => $path]));

            return self::FAILURE;
        }

        $dataset = json_decode((string) file_get_contents($path), true);

        if (! is_array($dataset) || ! isset($dataset['facilities']) || ! is_array($dataset['facilities'])) {
            $this->error(__('facility_master.file_invalid', ['path' => $path]));

            return self::FAILURE;
        }

        $records = array_values($dataset['facilities']);

        // Writing requires saying so. Everything else reports and stops.
        $dryRun = ! $this->option('apply') || (bool) $this->option('dry-run');

        $this->components->info(__('facility_master.title'));

        if ($dryRun) {
            $this->components->warn(__('facility_master.dry_run_notice'));
        }

        $this->line('  ' . __('facility_master.loaded', [
            'count'     => count($records),
            'path'      => $path,
            'retrieved' => (string) ($dataset['retrieved'] ?? '—'),
        ]));

        $attribution = (string) ($dataset['attribution'] ?? $dataset['source'] ?? CameroonMasterFacilityImporter::DEFAULT_ATTRIBUTION);

        $this->line('  ' . __('facility_master.attribution', ['attribution' => $attribution]));
        $this->line('  ' . __('facility_master.unverified_notice'));

        $records = $this->applyRegionFilter($records);

        if ($records === false) {
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0 && count($records) > $limit) {
            $records = array_slice($records, 0, $limit);
            $this->line('  ' . __('facility_master.limit_applied', ['count' => $limit]));
        }

        $this->newLine();

        $counts = $importer->import($records, [
            'dry_run'     => $dryRun,
            'attribution' => $attribution,
        ]);

        $this->report($counts, $dryRun);

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string,mixed>> $records
     * @return list<array<string,mixed>>|false
     */
    private function applyRegionFilter(array $records): array|false
    {
        $region = trim((string) ($this->option('region') ?? ''));

        if ($region === '') {
            return $records;
        }

        $fold = static fn (string $value): string
            => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));

        $filtered = array_values(array_filter(
            $records,
            static fn (array $r): bool => $fold((string) ($r['region'] ?? '')) === $fold($region)
        ));

        if ($filtered === []) {
            $known = array_values(array_unique(array_filter(array_map(
                static fn (array $r): string => (string) ($r['region'] ?? ''),
                $records
            ))));
            sort($known);

            $this->error(__('facility_master.unknown_region', [
                'region'  => $region,
                'regions' => implode(', ', $known),
            ]));

            return false;
        }

        $this->line('  ' . __('facility_master.region_filter', [
            'count'  => count($filtered),
            'total'  => count($records),
            'region' => $region,
        ]));

        return $filtered;
    }

    /**
     * @param  array<string,mixed> $counts
     */
    private function report(array $counts, bool $dryRun): void
    {
        $this->components->twoColumnDetail('<fg=gray>' . __('facility_master.records_considered') . '</>', (string) $counts['elements']);
        $this->components->twoColumnDetail(__('facility_master.inserted'), '<fg=green>' . $counts['inserted'] . '</>');
        $this->components->twoColumnDetail(__('facility_master.updated'), '<fg=green>' . $counts['updated'] . '</>');
        $this->components->twoColumnDetail(__('facility_master.unchanged'), (string) $counts['matched_unchanged']);
        $this->components->twoColumnDetail(__('facility_master.protected'), (string) $counts['skipped_protected']);
        $this->components->twoColumnDetail(__('facility_master.review'), '<fg=yellow>' . $counts['review'] . '</>');
        $this->components->twoColumnDetail(__('facility_master.unmapped'), (string) $counts['skipped_unmapped_type']);
        $this->components->twoColumnDetail(__('facility_master.no_coords'), (string) $counts['skipped_no_coords']);

        if ($counts['review_by_reason'] !== []) {
            $this->newLine();
            $this->line('  <options=bold>' . __('facility_master.review_heading') . '</>');

            arsort($counts['review_by_reason']);

            foreach ($counts['review_by_reason'] as $reason => $n) {
                $this->components->twoColumnDetail('  ' . $reason, (string) $n);
            }
        }

        if ($counts['fields_written'] !== []) {
            $this->newLine();
            $this->line('  <options=bold>' . __('facility_master.fields_heading') . '</>');

            arsort($counts['fields_written']);

            foreach ($counts['fields_written'] as $field => $n) {
                $this->components->twoColumnDetail('  ' . $field, (string) $n);
            }
        }

        $this->reportByRegion($counts);
        $this->newLine();

        if ($dryRun) {
            $this->components->info(__('facility_master.dry_run_complete'));

            return;
        }

        $this->components->info(__('facility_master.applied'));

        if ($counts['review'] > 0) {
            $this->components->info(__('facility_master.review_pending', ['count' => $counts['review']]));
        }
    }

    /**
     * @param  array<string,mixed> $counts
     */
    private function reportByRegion(array $counts): void
    {
        if (($counts['by_region'] ?? []) === []) {
            return;
        }

        $rows = [];

        foreach ($counts['by_region'] as $region => $outcomes) {
            $rows[] = [
                $region === ExternalFacilityImporter::REGION_UNKNOWN
                    ? __('facility_master.region_unknown')
                    : $region,
                (string) ($outcomes['inserted'] ?? 0),
                (string) ($outcomes['updated'] ?? 0),
                (string) ($outcomes['review'] ?? 0),
                (string) ($outcomes['matched_unchanged'] ?? 0),
                (string) ($outcomes['skipped_protected'] ?? 0),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));

        $this->newLine();
        $this->line('  <options=bold>' . __('facility_master.region_heading') . '</>');

        $this->table([
            __('facility_master.column_region'),
            __('facility_master.column_inserted'),
            __('facility_master.column_updated'),
            __('facility_master.column_review'),
            __('facility_master.column_unchanged'),
            __('facility_master.column_protected'),
        ], $rows);
    }
}
